<?php
/**
 * Notifications API
 * PulTech Social Media Application
 */

require_once __DIR__ . '/../middleware/auth.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$currentUser = getCurrentUser();

switch ($method) {
  case 'GET':
    $action = $_GET['action'] ?? 'list';
    if ($action === 'counts') {
      handleGetCounts();
    } else {
      handleGetNotifications();
    }
    break;
  case 'PUT':
    handleMarkAsRead();
    break;
  default:
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// Get unread counts for sidebar polling
function handleGetCounts()
{
  global $currentUser;

  // Unread notifications
  $stmt = db()->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
  $stmt->execute([$currentUser['id']]);
  $unreadNotifications = (int) $stmt->fetchColumn();

  // Unread messages
  $stmt = db()->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
  $stmt->execute([$currentUser['id']]);
  $unreadMessages = (int) $stmt->fetchColumn();

  jsonResponse([
    'success' => true,
    'unread_notifications' => $unreadNotifications,
    'unread_messages' => $unreadMessages
  ]);
}

function handleGetNotifications()
{
  global $currentUser;

  $page = intval($_GET['page'] ?? 1);
  $limit = 20;
  $offset = ($page - 1) * $limit;

  $stmt = db()->prepare("
        SELECT n.*, u.username, u.avatar, u.full_name
        FROM notifications n
        LEFT JOIN users u ON n.from_user_id = u.id
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
        LIMIT ? OFFSET ?
    ");
  $stmt->execute([$currentUser['id'], $limit, $offset]);
  $notifications = $stmt->fetchAll();

  foreach ($notifications as &$notif) {
    $notif['avatar_url'] = getAvatarUrl($notif['avatar']);
    $notif['time_ago'] = timeAgo($notif['created_at']);
  }

  // Get unread count
  $stmt = db()->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
  $stmt->execute([$currentUser['id']]);
  $unreadCount = $stmt->fetchColumn();

  jsonResponse([
    'success' => true,
    'notifications' => $notifications,
    'unread_count' => $unreadCount
  ]);
}

function handleMarkAsRead()
{
  global $currentUser;

  $input = json_decode(file_get_contents('php://input'), true);
  $notifId = intval($input['id'] ?? 0);
  $markAll = $input['mark_all'] ?? false;

  if ($markAll) {
    $stmt = db()->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$currentUser['id']]);
  } elseif ($notifId > 0) {
    $stmt = db()->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$notifId, $currentUser['id']]);
  } else {
    jsonResponse(['success' => false, 'message' => 'Parameter tidak valid'], 400);
  }

  jsonResponse(['success' => true, 'message' => 'Notifikasi ditandai sudah dibaca']);
}
