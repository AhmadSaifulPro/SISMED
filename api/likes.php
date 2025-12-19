<?php
/**
 * Likes API
 * PulTech Social Media Application
 */

require_once __DIR__ . '/../middleware/auth.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$currentUser = getCurrentUser();

if ($method !== 'POST') {
  jsonResponse(['error' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

$type = $input['type'] ?? '';  // post, comment, story
$id = intval($input['id'] ?? 0);

if (!in_array($type, ['post', 'comment', 'story'])) {
  jsonResponse(['success' => false, 'message' => 'Tipe tidak valid'], 400);
}

if ($id <= 0) {
  jsonResponse(['success' => false, 'message' => 'ID tidak valid'], 400);
}

// Check if already liked
$stmt = db()->prepare("SELECT id FROM likes WHERE user_id = ? AND likeable_type = ? AND likeable_id = ?");
$stmt->execute([$currentUser['id'], $type, $id]);
$existingLike = $stmt->fetch();

if ($existingLike) {
  // Unlike
  $stmt = db()->prepare("DELETE FROM likes WHERE id = ?");
  $stmt->execute([$existingLike['id']]);

  // Update count
  if ($type === 'post') {
    db()->prepare("UPDATE posts SET likes_count = likes_count - 1 WHERE id = ?")->execute([$id]);
  } elseif ($type === 'comment') {
    db()->prepare("UPDATE comments SET likes_count = likes_count - 1 WHERE id = ?")->execute([$id]);
  }

  $liked = false;
} else {
  // Like
  $stmt = db()->prepare("INSERT INTO likes (user_id, likeable_type, likeable_id, created_at) VALUES (?, ?, ?, NOW())");
  $stmt->execute([$currentUser['id'], $type, $id]);

  // Update count
  if ($type === 'post') {
    db()->prepare("UPDATE posts SET likes_count = likes_count + 1 WHERE id = ?")->execute([$id]);

    // Get post owner
    $stmt = db()->prepare("SELECT user_id FROM posts WHERE id = ?");
    $stmt->execute([$id]);
    $post = $stmt->fetch();

    // Create notification
    if ($post && $post['user_id'] != $currentUser['id']) {
      $stmt = db()->prepare("
                INSERT INTO notifications (user_id, from_user_id, type, reference_type, reference_id, message, created_at)
                VALUES (?, ?, 'like', 'post', ?, ?, NOW())
            ");
      $stmt->execute([
        $post['user_id'],
        $currentUser['id'],
        $id,
        $currentUser['username'] . ' menyukai postingan Anda'
      ]);
    }
  } elseif ($type === 'comment') {
    db()->prepare("UPDATE comments SET likes_count = likes_count + 1 WHERE id = ?")->execute([$id]);
  } elseif ($type === 'story') {
    // Get story owner
    $stmt = db()->prepare("SELECT user_id FROM stories WHERE id = ?");
    $stmt->execute([$id]);
    $story = $stmt->fetch();

    if ($story && $story['user_id'] != $currentUser['id']) {
      $stmt = db()->prepare("
                INSERT INTO notifications (user_id, from_user_id, type, reference_type, reference_id, message, created_at)
                VALUES (?, ?, 'like', 'story', ?, ?, NOW())
            ");
      $stmt->execute([
        $story['user_id'],
        $currentUser['id'],
        $id,
        $currentUser['username'] . ' menyukai story Anda'
      ]);
    }
  }

  $liked = true;
}

// Get updated count
$countColumn = 'likes_count';
$table = $type === 'post' ? 'posts' : ($type === 'comment' ? 'comments' : 'stories');

$stmt = db()->prepare("SELECT (SELECT COUNT(*) FROM likes WHERE likeable_type = ? AND likeable_id = ?) as count");
$stmt->execute([$type, $id]);
$result = $stmt->fetch();

jsonResponse([
  'success' => true,
  'liked' => $liked,
  'likes_count' => $result['count']
]);
