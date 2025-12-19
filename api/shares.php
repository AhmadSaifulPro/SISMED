<?php
/**
 * Shares API
 * PulTech Social Media Application
 */

require_once __DIR__ . '/../middleware/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  jsonResponse(['error' => 'Method not allowed'], 405);
}

$currentUser = getCurrentUser();
$input = json_decode(file_get_contents('php://input'), true);

$postId = intval($input['post_id'] ?? 0);
$shareType = $input['share_type'] ?? 'external'; // internal or external
$sharedToUserId = intval($input['shared_to_user_id'] ?? 0);
$platform = $input['platform'] ?? ''; // whatsapp, facebook, twitter, etc.

if ($postId <= 0) {
  jsonResponse(['success' => false, 'message' => 'ID post tidak valid'], 400);
}

// Check if post exists
$stmt = db()->prepare("SELECT id, user_id FROM posts WHERE id = ?");
$stmt->execute([$postId]);
$post = $stmt->fetch();

if (!$post) {
  jsonResponse(['success' => false, 'message' => 'Postingan tidak ditemukan'], 404);
}

// Record share
$stmt = db()->prepare("
    INSERT INTO shares (user_id, post_id, share_type, shared_to_user_id, platform, created_at)
    VALUES (?, ?, ?, ?, ?, NOW())
");

$stmt->execute([
  $currentUser['id'],
  $postId,
  $shareType,
  $sharedToUserId ?: null,
  $platform ?: null
]);

// Update post shares count
db()->prepare("UPDATE posts SET shares_count = shares_count + 1 WHERE id = ?")->execute([$postId]);

// If internal share, create notification
if ($shareType === 'internal' && $sharedToUserId > 0) {
  $stmt = db()->prepare("
        INSERT INTO notifications (user_id, from_user_id, type, reference_type, reference_id, message, created_at)
        VALUES (?, ?, 'mention', 'post', ?, ?, NOW())
    ");
  $stmt->execute([
    $sharedToUserId,
    $currentUser['id'],
    $postId,
    $currentUser['username'] . ' membagikan postingan dengan Anda'
  ]);
}

// Get updated shares count
$stmt = db()->prepare("SELECT shares_count FROM posts WHERE id = ?");
$stmt->execute([$postId]);
$result = $stmt->fetch();

jsonResponse([
  'success' => true,
  'shares_count' => $result['shares_count'],
  'message' => 'Postingan berhasil dibagikan'
]);
