<?php
/**
 * Messages API
 * PulTech Social Media Application
 * Real-time chat using Long Polling
 */

require_once __DIR__ . '/../middleware/auth.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$currentUser = getCurrentUser();

switch ($method) {
  case 'GET':
    $action = $_GET['action'] ?? 'conversations';
    if ($action === 'poll') {
      handlePollMessages();
    } elseif ($action === 'messages') {
      handleGetMessages();
    } else {
      handleGetConversations();
    }
    break;
  case 'POST':
    handleSendMessage();
    break;
  default:
    jsonResponse(['error' => 'Method not allowed'], 405);
}

function handleGetConversations()
{
  global $currentUser;

  $stmt = db()->prepare("
        SELECT 
            c.id as conversation_id,
            CASE 
                WHEN c.user1_id = ? THEN c.user2_id 
                ELSE c.user1_id 
            END as other_user_id,
            u.username, u.avatar, u.full_name,
            m.message as last_message,
            m.created_at as last_message_time,
            (SELECT COUNT(*) FROM messages WHERE 
                ((sender_id = c.user1_id AND receiver_id = c.user2_id) OR (sender_id = c.user2_id AND receiver_id = c.user1_id))
                AND receiver_id = ? AND is_read = 0
            ) as unread_count
        FROM conversations c
        JOIN users u ON (CASE WHEN c.user1_id = ? THEN c.user2_id ELSE c.user1_id END) = u.id
        LEFT JOIN messages m ON c.last_message_id = m.id
        WHERE c.user1_id = ? OR c.user2_id = ?
        ORDER BY c.updated_at DESC
    ");
  $stmt->execute([
    $currentUser['id'],
    $currentUser['id'],
    $currentUser['id'],
    $currentUser['id'],
    $currentUser['id']
  ]);
  $conversations = $stmt->fetchAll();

  foreach ($conversations as &$conv) {
    $conv['avatar_url'] = getAvatarUrl($conv['avatar']);
    $conv['time_ago'] = $conv['last_message_time'] ? timeAgo($conv['last_message_time']) : '';
  }

  jsonResponse(['success' => true, 'conversations' => $conversations]);
}

function handleGetMessages()
{
  global $currentUser;

  $userId = intval($_GET['user_id'] ?? 0);
  $lastId = intval($_GET['last_id'] ?? 0);

  if ($userId <= 0) {
    jsonResponse(['success' => false, 'message' => 'ID user tidak valid'], 400);
  }

  $sql = "
        SELECT m.*, 
               CASE WHEN m.sender_id = ? THEN 'sent' ELSE 'received' END as direction
        FROM messages m
        WHERE ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))
    ";

  $params = [$currentUser['id'], $currentUser['id'], $userId, $userId, $currentUser['id']];

  if ($lastId > 0) {
    $sql .= " AND m.id > ?";
    $params[] = $lastId;
  }

  $sql .= " ORDER BY m.created_at ASC LIMIT 100";

  $stmt = db()->prepare($sql);
  $stmt->execute($params);
  $messages = $stmt->fetchAll();

  foreach ($messages as &$msg) {
    $msg['time'] = date('H:i', strtotime($msg['created_at']));
    $msg['media_url'] = $msg['media_url'] ? MESSAGE_URL . '/' . $msg['media_url'] : null;
  }

  // Mark as read
  $stmt = db()->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
  $stmt->execute([$userId, $currentUser['id']]);

  jsonResponse(['success' => true, 'messages' => $messages]);
}

function handlePollMessages()
{
  global $currentUser;

  $userId = intval($_GET['user_id'] ?? 0);
  $lastId = intval($_GET['last_id'] ?? 0);

  if ($userId <= 0) {
    jsonResponse(['success' => false, 'message' => 'ID user tidak valid'], 400);
  }

  // Immediate check - no waiting loop for faster response
  $stmt = db()->prepare("
    SELECT m.*, 
           CASE WHEN m.sender_id = ? THEN 'sent' ELSE 'received' END as direction
    FROM messages m
    WHERE ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))
    AND m.id > ?
    ORDER BY m.created_at ASC
  ");
  $stmt->execute([$currentUser['id'], $currentUser['id'], $userId, $userId, $currentUser['id'], $lastId]);
  $messages = $stmt->fetchAll();

  if (!empty($messages)) {
    foreach ($messages as &$msg) {
      $msg['time'] = date('H:i', strtotime($msg['created_at']));
      $msg['media_url'] = $msg['media_url'] ? MESSAGE_URL . '/' . $msg['media_url'] : null;
    }

    // Mark as read
    $stmt = db()->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
    $stmt->execute([$userId, $currentUser['id']]);
  }

  jsonResponse(['success' => true, 'messages' => $messages]);
}

function handleSendMessage()
{
  global $currentUser;

  $receiverId = intval($_POST['receiver_id'] ?? 0);
  $message = trim($_POST['message'] ?? '');
  $mediaType = 'text';
  $mediaUrl = null;

  if ($receiverId <= 0) {
    jsonResponse(['success' => false, 'message' => 'ID penerima tidak valid'], 400);
  }

  // Handle file upload
  if (!empty($_FILES['media']['name'])) {
    $file = $_FILES['media'];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
      $mediaType = 'image';
      $result = uploadFile($file, MESSAGE_PATH, ALLOWED_IMAGE_TYPES, MAX_IMAGE_SIZE);
    } elseif (in_array($mimeType, ALLOWED_VIDEO_TYPES)) {
      $mediaType = 'video';
      $result = uploadFile($file, MESSAGE_PATH, ALLOWED_VIDEO_TYPES, MAX_VIDEO_SIZE);
    } else {
      jsonResponse(['success' => false, 'message' => 'Tipe file tidak didukung'], 400);
    }

    if (!$result['success']) {
      jsonResponse(['success' => false, 'message' => $result['message']], 400);
    }

    $mediaUrl = $result['filename'];
  }

  if (empty($message) && empty($mediaUrl)) {
    jsonResponse(['success' => false, 'message' => 'Pesan tidak boleh kosong'], 400);
  }

  // Insert message
  $stmt = db()->prepare("
        INSERT INTO messages (sender_id, receiver_id, message, media_type, media_url, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");

  if ($stmt->execute([$currentUser['id'], $receiverId, $message, $mediaType, $mediaUrl])) {
    $messageId = db()->lastInsertId();

    // Update or create conversation
    $user1 = min($currentUser['id'], $receiverId);
    $user2 = max($currentUser['id'], $receiverId);

    $stmt = db()->prepare("SELECT id FROM conversations WHERE user1_id = ? AND user2_id = ?");
    $stmt->execute([$user1, $user2]);
    $conv = $stmt->fetch();

    if ($conv) {
      $stmt = db()->prepare("UPDATE conversations SET last_message_id = ?, updated_at = NOW() WHERE id = ?");
      $stmt->execute([$messageId, $conv['id']]);
    } else {
      $stmt = db()->prepare("INSERT INTO conversations (user1_id, user2_id, last_message_id, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
      $stmt->execute([$user1, $user2, $messageId]);
    }

    // Create notification
    $stmt = db()->prepare("
            INSERT INTO notifications (user_id, from_user_id, type, message, created_at)
            VALUES (?, ?, 'message', ?, NOW())
        ");
    $stmt->execute([
      $receiverId,
      $currentUser['id'],
      $currentUser['username'] . ' mengirim pesan'
    ]);

    updateSiteStats('total_messages');

    jsonResponse([
      'success' => true,
      'message_id' => $messageId,
      'message' => [
        'id' => $messageId,
        'message' => $message,
        'media_type' => $mediaType,
        'media_url' => $mediaUrl ? MESSAGE_URL . '/' . $mediaUrl : null,
        'direction' => 'sent',
        'time' => date('H:i')
      ]
    ]);
  } else {
    jsonResponse(['success' => false, 'message' => 'Gagal mengirim pesan'], 500);
  }
}
