<?php
/**
 * Users API
 * PulTech Social Media Application
 */

require_once __DIR__ . '/../middleware/auth.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$currentUser = getCurrentUser();

if ($method !== 'GET') {
  jsonResponse(['error' => 'Method not allowed'], 405);
}

$search = $_GET['search'] ?? '';
$limit = intval($_GET['limit'] ?? 20);

if (strlen($search) < 2) {
  jsonResponse(['success' => true, 'users' => []]);
}

$stmt = db()->prepare("
    SELECT id, username, avatar, full_name, bio,
           (SELECT id FROM follows WHERE follower_id = ? AND following_id = users.id) as is_following
    FROM users
    WHERE (username LIKE ? OR full_name LIKE ?)
    AND id != ?
    AND is_active = 1
    ORDER BY 
        CASE WHEN username LIKE ? THEN 0 ELSE 1 END,
        username
    LIMIT ?
");

$searchParam = "%$search%";
$exactParam = "$search%";
$stmt->execute([$currentUser['id'], $searchParam, $searchParam, $currentUser['id'], $exactParam, $limit]);
$users = $stmt->fetchAll();

foreach ($users as &$user) {
  $user['avatar_url'] = getAvatarUrl($user['avatar']);
  $user['is_following'] = !empty($user['is_following']);
}

jsonResponse(['success' => true, 'users' => $users]);
