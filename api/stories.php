<?php
/**
 * Stories API
 * PulTech Social Media Application
 */

// Set error handler to return JSON for all errors
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
  header('Content-Type: application/json');
  echo json_encode([
    'success' => false,
    'message' => 'PHP Error: ' . $errstr,
    'debug' => "$errfile:$errline"
  ]);
  exit;
});

set_exception_handler(function ($e) {
  header('Content-Type: application/json');
  echo json_encode([
    'success' => false,
    'message' => 'Exception: ' . $e->getMessage()
  ]);
  exit;
});

require_once __DIR__ . '/../middleware/auth.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$currentUser = getCurrentUser();

switch ($method) {
  case 'GET':
    handleGetStories();
    break;
  case 'POST':
    handleCreateStory();
    break;
  case 'DELETE':
    handleDeleteStory();
    break;
  default:
    jsonResponse(['error' => 'Method not allowed'], 405);
}

function handleGetStories()
{
  global $currentUser;

  // Clean up expired stories on each request
  cleanupExpiredStories();

  $userId = intval($_GET['user_id'] ?? 0);

  if ($userId > 0) {
    // Get specific user's stories
    $stmt = db()->prepare("
            SELECT s.*, u.username, u.avatar, u.full_name,
                   (SELECT id FROM story_views WHERE story_id = s.id AND viewer_id = ?) as viewed
            FROM stories s
            JOIN users u ON s.user_id = u.id
            WHERE s.user_id = ? AND s.expires_at > NOW()
            ORDER BY s.created_at ASC
        ");
    $stmt->execute([$currentUser['id'], $userId]);
  } else {
    // Get all stories from followed users
    $stmt = db()->prepare("
            SELECT s.*, u.username, u.avatar, u.full_name,
                   (SELECT id FROM story_views WHERE story_id = s.id AND viewer_id = ?) as viewed
            FROM stories s
            JOIN users u ON s.user_id = u.id
            WHERE s.expires_at > NOW() 
            AND (s.user_id = ? OR s.user_id IN (SELECT following_id FROM follows WHERE follower_id = ?))
            ORDER BY s.user_id = ? DESC, s.created_at DESC
        ");
    $stmt->execute([$currentUser['id'], $currentUser['id'], $currentUser['id'], $currentUser['id']]);
  }

  $stories = $stmt->fetchAll();

  foreach ($stories as &$story) {
    $story['avatar_url'] = getAvatarUrl($story['avatar']);
    $story['media_url'] = $story['media_url'] ? STORY_URL . '/' . $story['media_url'] : null;
    $story['time_ago'] = timeAgo($story['created_at']);
    $story['is_viewed'] = !empty($story['viewed']);
    $story['remaining_time'] = getStoryRemainingTime($story['expires_at']);
  }

  jsonResponse(['success' => true, 'stories' => $stories]);
}

function handleCreateStory()
{
  global $currentUser;

  $content = trim($_POST['content'] ?? '');
  $backgroundColor = $_POST['background_color'] ?? '#667eea';
  $mediaType = $_POST['media_type'] ?? 'text';
  $mediaUrl = null;

  // Handle file upload - check both name and size to ensure file is valid
  if (!empty($_FILES['media']['name']) && $_FILES['media']['size'] > 0 && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['media'];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
      $mediaType = 'image';
      $result = uploadFile($file, STORY_PATH, ALLOWED_IMAGE_TYPES, MAX_IMAGE_SIZE);
    } elseif (in_array($mimeType, ALLOWED_VIDEO_TYPES)) {
      $mediaType = 'video';
      $result = uploadFile($file, STORY_PATH, ALLOWED_VIDEO_TYPES, MAX_VIDEO_SIZE);
    } else {
      jsonResponse(['success' => false, 'message' => 'Tipe file tidak didukung'], 400);
    }

    if (!$result['success']) {
      jsonResponse(['success' => false, 'message' => $result['message']], 400);
    }

    $mediaUrl = $result['filename'];
  } elseif (empty($content)) {
    // No media and no content
    jsonResponse(['success' => false, 'message' => 'Konten atau media harus diisi'], 400);
  }

  try {
    // Set expiry time (24 hours)
    $expiresAt = date('Y-m-d H:i:s', strtotime('+' . STORY_EXPIRY_HOURS . ' hours'));

    // Get visibility setting
    $visibility = $_POST['visibility'] ?? 'public';
    if (!in_array($visibility, ['public', 'followers', 'private'])) {
      $visibility = 'public';
    }

    $stmt = db()->prepare("
          INSERT INTO stories (user_id, content, media_type, media_url, background_color, visibility, expires_at, created_at)
          VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
      ");

    if ($stmt->execute([$currentUser['id'], $content, $mediaType, $mediaUrl, $backgroundColor, $visibility, $expiresAt])) {
      $storyId = db()->lastInsertId();

      // Try to update stats, but don't fail if it doesn't work
      try {
        updateSiteStats('total_stories');
      } catch (Exception $e) {
        // Ignore stats error
      }

      jsonResponse(['success' => true, 'story_id' => $storyId, 'message' => 'Story berhasil dibuat']);
    } else {
      jsonResponse(['success' => false, 'message' => 'Gagal membuat story'], 500);
    }
  } catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
  }
}

function handleDeleteStory()
{
  global $currentUser;

  $storyId = intval($_GET['id'] ?? 0);

  if ($storyId <= 0) {
    jsonResponse(['success' => false, 'message' => 'ID story tidak valid'], 400);
  }

  $stmt = db()->prepare("SELECT * FROM stories WHERE id = ?");
  $stmt->execute([$storyId]);
  $story = $stmt->fetch();

  if (!$story) {
    jsonResponse(['success' => false, 'message' => 'Story tidak ditemukan'], 404);
  }

  if ($story['user_id'] != $currentUser['id'] && !isAdmin()) {
    jsonResponse(['success' => false, 'message' => 'Tidak memiliki izin'], 403);
  }

  if ($story['media_url']) {
    deleteFile(STORY_PATH . '/' . $story['media_url']);
  }

  $stmt = db()->prepare("DELETE FROM stories WHERE id = ?");

  if ($stmt->execute([$storyId])) {
    jsonResponse(['success' => true, 'message' => 'Story berhasil dihapus']);
  } else {
    jsonResponse(['success' => false, 'message' => 'Gagal menghapus story'], 500);
  }
}
