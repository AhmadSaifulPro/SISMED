<?php
/**
 * Comments API
 * PulTech Social Media Application
 */

require_once __DIR__ . '/../middleware/auth.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$currentUser = getCurrentUser();

switch ($method) {
  case 'GET':
    handleGetComments();
    break;
  case 'POST':
    handleCreateComment();
    break;
  case 'DELETE':
    handleDeleteComment();
    break;
  default:
    jsonResponse(['error' => 'Method not allowed'], 405);
}

function handleGetComments()
{
  global $currentUser;

  $postId = intval($_GET['post_id'] ?? 0);

  if ($postId <= 0) {
    jsonResponse(['success' => false, 'message' => 'ID post tidak valid'], 400);
  }

  // Get top-level comments
  $stmt = db()->prepare("
        SELECT c.*, u.username, u.avatar, u.full_name,
               (SELECT COUNT(*) FROM likes WHERE likeable_type = 'comment' AND likeable_id = c.id) as likes_count,
               (SELECT id FROM likes WHERE likeable_type = 'comment' AND likeable_id = c.id AND user_id = ?) as user_liked
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.post_id = ? AND c.parent_id IS NULL
        ORDER BY c.created_at ASC
        LIMIT 50
    ");
  $stmt->execute([$currentUser['id'], $postId]);
  $comments = $stmt->fetchAll();

  // Get replies for each comment
  foreach ($comments as &$comment) {
    $comment['avatar_url'] = getAvatarUrl($comment['avatar']);
    $comment['time_ago'] = timeAgo($comment['created_at']);
    $comment['is_liked'] = !empty($comment['user_liked']);

    // Get replies
    $stmtReplies = db()->prepare("
            SELECT c.*, u.username, u.avatar, u.full_name,
                   (SELECT COUNT(*) FROM likes WHERE likeable_type = 'comment' AND likeable_id = c.id) as likes_count
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.parent_id = ?
            ORDER BY c.created_at ASC
            LIMIT 10
        ");
    $stmtReplies->execute([$comment['id']]);
    $replies = $stmtReplies->fetchAll();

    foreach ($replies as &$reply) {
      $reply['avatar_url'] = getAvatarUrl($reply['avatar']);
      $reply['time_ago'] = timeAgo($reply['created_at']);
    }

    $comment['replies'] = $replies;
  }

  jsonResponse(['success' => true, 'comments' => $comments]);
}

function handleCreateComment()
{
  global $currentUser;

  $input = json_decode(file_get_contents('php://input'), true);

  $postId = intval($input['post_id'] ?? 0);
  $parentId = !empty($input['parent_id']) ? intval($input['parent_id']) : null;
  $content = trim($input['content'] ?? '');

  if ($postId <= 0) {
    jsonResponse(['success' => false, 'message' => 'ID post tidak valid'], 400);
  }

  if (empty($content)) {
    jsonResponse(['success' => false, 'message' => 'Komentar tidak boleh kosong'], 400);
  }

  // Check if post exists
  $stmt = db()->prepare("SELECT user_id FROM posts WHERE id = ?");
  $stmt->execute([$postId]);
  $post = $stmt->fetch();

  if (!$post) {
    jsonResponse(['success' => false, 'message' => 'Postingan tidak ditemukan'], 404);
  }

  // Insert comment
  $stmt = db()->prepare("
        INSERT INTO comments (user_id, post_id, parent_id, content, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");

  if ($stmt->execute([$currentUser['id'], $postId, $parentId, $content])) {
    $commentId = db()->lastInsertId();

    // Update post comments count
    db()->prepare("UPDATE posts SET comments_count = comments_count + 1 WHERE id = ?")->execute([$postId]);

    // Create notification if commenting on someone else's post
    if ($post['user_id'] != $currentUser['id']) {
      $stmt = db()->prepare("
                INSERT INTO notifications (user_id, from_user_id, type, reference_type, reference_id, message, created_at)
                VALUES (?, ?, 'comment', 'post', ?, ?, NOW())
            ");
      $stmt->execute([
        $post['user_id'],
        $currentUser['id'],
        $postId,
        $currentUser['username'] . ' mengomentari postingan Anda'
      ]);
    }

    jsonResponse(['success' => true, 'comment_id' => $commentId, 'message' => 'Komentar berhasil ditambahkan']);
  } else {
    jsonResponse(['success' => false, 'message' => 'Gagal menambahkan komentar'], 500);
  }
}

function handleDeleteComment()
{
  global $currentUser;

  $commentId = intval($_GET['id'] ?? 0);

  if ($commentId <= 0) {
    jsonResponse(['success' => false, 'message' => 'ID komentar tidak valid'], 400);
  }

  // Check ownership
  $stmt = db()->prepare("SELECT * FROM comments WHERE id = ?");
  $stmt->execute([$commentId]);
  $comment = $stmt->fetch();

  if (!$comment) {
    jsonResponse(['success' => false, 'message' => 'Komentar tidak ditemukan'], 404);
  }

  if ($comment['user_id'] != $currentUser['id'] && !isAdmin()) {
    jsonResponse(['success' => false, 'message' => 'Tidak memiliki izin'], 403);
  }

  // Delete comment and replies
  $stmt = db()->prepare("DELETE FROM comments WHERE id = ? OR parent_id = ?");

  if ($stmt->execute([$commentId, $commentId])) {
    // Update post comments count
    db()->prepare("UPDATE posts SET comments_count = comments_count - 1 WHERE id = ?")->execute([$comment['post_id']]);

    jsonResponse(['success' => true, 'message' => 'Komentar berhasil dihapus']);
  } else {
    jsonResponse(['success' => false, 'message' => 'Gagal menghapus komentar'], 500);
  }
}
