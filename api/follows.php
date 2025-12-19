<?php
/**
 * Follows API
 * PulTech Social Media Application
 */

require_once __DIR__ . '/../middleware/auth.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$currentUser = getCurrentUser();

if ($method !== 'POST' && $method !== 'GET') {
  jsonResponse(['error' => 'Method not allowed'], 405);
}

if ($method === 'GET') {
  handleGetFollows();
} else {
  handleToggleFollow();
}

function handleGetFollows()
{
  global $currentUser;

  $userId = intval($_GET['user_id'] ?? $currentUser['id']);
  $type = $_GET['type'] ?? 'followers'; // followers or following

  if ($type === 'followers') {
    $stmt = db()->prepare("
            SELECT u.id, u.username, u.avatar, u.full_name, u.bio,
                   (SELECT id FROM follows WHERE follower_id = ? AND following_id = u.id) as is_following
            FROM follows f
            JOIN users u ON f.follower_id = u.id
            WHERE f.following_id = ?
            ORDER BY f.created_at DESC
        ");
  } else {
    $stmt = db()->prepare("
            SELECT u.id, u.username, u.avatar, u.full_name, u.bio,
                   (SELECT id FROM follows WHERE follower_id = ? AND following_id = u.id) as is_following
            FROM follows f
            JOIN users u ON f.following_id = u.id
            WHERE f.follower_id = ?
            ORDER BY f.created_at DESC
        ");
  }

  $stmt->execute([$currentUser['id'], $userId]);
  $users = $stmt->fetchAll();

  foreach ($users as &$user) {
    $user['avatar_url'] = getAvatarUrl($user['avatar']);
    $user['is_following'] = !empty($user['is_following']);
  }

  jsonResponse(['success' => true, 'users' => $users]);
}

function handleToggleFollow()
{
  global $currentUser;

  $input = json_decode(file_get_contents('php://input'), true);
  $userId = intval($input['user_id'] ?? 0);

  if ($userId <= 0) {
    jsonResponse(['success' => false, 'message' => 'ID user tidak valid'], 400);
  }

  if ($userId === $currentUser['id']) {
    jsonResponse(['success' => false, 'message' => 'Tidak bisa mengikuti diri sendiri'], 400);
  }

  // Check if user exists
  $stmt = db()->prepare("SELECT id, username FROM users WHERE id = ? AND is_active = 1");
  $stmt->execute([$userId]);
  $targetUser = $stmt->fetch();

  if (!$targetUser) {
    jsonResponse(['success' => false, 'message' => 'Pengguna tidak ditemukan'], 404);
  }

  // Check if already following
  $stmt = db()->prepare("SELECT id FROM follows WHERE follower_id = ? AND following_id = ?");
  $stmt->execute([$currentUser['id'], $userId]);
  $existingFollow = $stmt->fetch();

  if ($existingFollow) {
    // Unfollow
    $stmt = db()->prepare("DELETE FROM follows WHERE id = ?");
    $stmt->execute([$existingFollow['id']]);
    $following = false;
  } else {
    // Follow
    $stmt = db()->prepare("INSERT INTO follows (follower_id, following_id, created_at) VALUES (?, ?, NOW())");
    $stmt->execute([$currentUser['id'], $userId]);

    // Create notification
    $stmt = db()->prepare("
            INSERT INTO notifications (user_id, from_user_id, type, message, created_at)
            VALUES (?, ?, 'follow', ?, NOW())
        ");
    $stmt->execute([
      $userId,
      $currentUser['id'],
      $currentUser['username'] . ' mulai mengikuti Anda'
    ]);

    $following = true;
  }

  // Get updated counts
  $stmt = db()->prepare("SELECT COUNT(*) FROM follows WHERE following_id = ?");
  $stmt->execute([$userId]);
  $followersCount = $stmt->fetchColumn();

  jsonResponse([
    'success' => true,
    'following' => $following,
    'followers_count' => $followersCount
  ]);
}
