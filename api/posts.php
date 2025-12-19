<?php
/**
 * Posts API
 * PulTech Social Media Application
 */

require_once __DIR__ . '/../middleware/auth.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$currentUser = getCurrentUser();

switch ($method) {
  case 'GET':
    handleGetPosts();
    break;
  case 'POST':
    handleCreatePost();
    break;
  case 'DELETE':
    handleDeletePost();
    break;
  default:
    jsonResponse(['error' => 'Method not allowed'], 405);
}

function handleGetPosts()
{
  global $currentUser;

  $page = intval($_GET['page'] ?? 1);
  $limit = intval($_GET['limit'] ?? POSTS_PER_PAGE);
  $offset = ($page - 1) * $limit;
  $userId = intval($_GET['user_id'] ?? 0);

  $sql = "
        SELECT p.*, u.username, u.avatar, u.full_name,
               (SELECT COUNT(*) FROM likes WHERE likeable_type = 'post' AND likeable_id = p.id) as likes_count,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count,
               (SELECT id FROM likes WHERE likeable_type = 'post' AND likeable_id = p.id AND user_id = ?) as user_liked
        FROM posts p
        JOIN users u ON p.user_id = u.id
    ";

  $params = [$currentUser['id']];

  if ($userId > 0) {
    $sql .= " WHERE p.user_id = ?";
    $params[] = $userId;
  } else {
    $sql .= " WHERE p.user_id = ? OR p.user_id IN (SELECT following_id FROM follows WHERE follower_id = ?) OR p.visibility = 'public'";
    $params[] = $currentUser['id'];
    $params[] = $currentUser['id'];
  }

  $sql .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
  $params[] = $limit;
  $params[] = $offset;

  $stmt = db()->prepare($sql);
  $stmt->execute($params);
  $posts = $stmt->fetchAll();

  // Format posts
  foreach ($posts as &$post) {
    $post['avatar_url'] = getAvatarUrl($post['avatar']);
    $post['media_url'] = $post['media_url'] ? POST_URL . '/' . $post['media_url'] : null;
    $post['time_ago'] = timeAgo($post['created_at']);
    $post['is_liked'] = !empty($post['user_liked']);
  }

  jsonResponse(['success' => true, 'posts' => $posts]);
}

function handleCreatePost()
{
  global $currentUser;

  $content = trim($_POST['content'] ?? '');
  $visibility = $_POST['visibility'] ?? 'public';
  $mediaType = 'text';
  $mediaUrl = null;

  // Validate
  if (empty($content) && empty($_FILES['media']['name'])) {
    jsonResponse(['success' => false, 'message' => 'Konten atau media harus diisi'], 400);
  }

  // Handle file upload
  if (!empty($_FILES['media']['name'])) {
    $file = $_FILES['media'];

    // Determine media type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
      $mediaType = 'image';
      $result = uploadFile($file, POST_PATH, ALLOWED_IMAGE_TYPES, MAX_IMAGE_SIZE);
    } elseif (in_array($mimeType, ALLOWED_VIDEO_TYPES)) {
      $mediaType = 'video';

      // Check video duration
      $tempPath = $file['tmp_name'];
      $duration = getVideoDuration($tempPath);

      if ($duration > MAX_VIDEO_DURATION) {
        jsonResponse(['success' => false, 'message' => 'Video tidak boleh lebih dari 1 menit'], 400);
      }

      $result = uploadFile($file, POST_PATH, ALLOWED_VIDEO_TYPES, MAX_VIDEO_SIZE);
    } else {
      jsonResponse(['success' => false, 'message' => 'Tipe file tidak didukung'], 400);
    }

    if (!$result['success']) {
      jsonResponse(['success' => false, 'message' => $result['message']], 400);
    }

    $mediaUrl = $result['filename'];
  }

  // Insert post
  $stmt = db()->prepare("
        INSERT INTO posts (user_id, content, media_type, media_url, visibility, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");

  if ($stmt->execute([$currentUser['id'], $content, $mediaType, $mediaUrl, $visibility])) {
    $postId = db()->lastInsertId();

    // Update statistics
    updateSiteStats('new_posts');
    updateSiteStats('total_posts');

    jsonResponse(['success' => true, 'post_id' => $postId, 'message' => 'Postingan berhasil dibuat']);
  } else {
    jsonResponse(['success' => false, 'message' => 'Gagal membuat postingan'], 500);
  }
}

function handleDeletePost()
{
  global $currentUser;

  $postId = intval($_GET['id'] ?? 0);

  if ($postId <= 0) {
    jsonResponse(['success' => false, 'message' => 'ID post tidak valid'], 400);
  }

  // Check ownership
  $stmt = db()->prepare("SELECT * FROM posts WHERE id = ?");
  $stmt->execute([$postId]);
  $post = $stmt->fetch();

  if (!$post) {
    jsonResponse(['success' => false, 'message' => 'Postingan tidak ditemukan'], 404);
  }

  if ($post['user_id'] != $currentUser['id'] && !isAdmin()) {
    jsonResponse(['success' => false, 'message' => 'Tidak memiliki izin'], 403);
  }

  // Delete media file
  if ($post['media_url']) {
    deleteFile(POST_PATH . '/' . $post['media_url']);
  }

  // Delete post
  $stmt = db()->prepare("DELETE FROM posts WHERE id = ?");

  if ($stmt->execute([$postId])) {
    jsonResponse(['success' => true, 'message' => 'Postingan berhasil dihapus']);
  } else {
    jsonResponse(['success' => false, 'message' => 'Gagal menghapus postingan'], 500);
  }
}
