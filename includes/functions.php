<?php
/**
 * Helper Functions
 * PulTech Social Media Application
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Start session if not started
 */
function startSession()
{
  if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
      'lifetime' => SESSION_LIFETIME,
      'path' => '/',
      'secure' => false, // Set to true in production with HTTPS
      'httponly' => true,
      'samesite' => 'Lax'
    ]);
    session_start();
  }
}

/**
 * Check if user is logged in
 */
function isLoggedIn()
{
  startSession();
  return isset($_SESSION['user_id']);
}

/**
 * Get current user ID
 */
function getCurrentUserId()
{
  startSession();
  return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user data
 */
function getCurrentUser()
{
  if (!isLoggedIn())
    return null;

  static $user = null;
  if ($user === null) {
    $stmt = db()->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([getCurrentUserId()]);
    $user = $stmt->fetch();
  }
  return $user;
}

/**
 * Check if current user is admin
 */
function isAdmin()
{
  $user = getCurrentUser();
  return $user && $user['role'] === 'admin';
}

/**
 * Redirect to URL
 */
function redirect($url)
{
  header("Location: " . $url);
  exit;
}

/**
 * Sanitize input
 */
function sanitize($input)
{
  if (is_array($input)) {
    return array_map('sanitize', $input);
  }
  return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token
 */
function generateCsrfToken()
{
  startSession();
  if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCsrfToken($token)
{
  startSession();
  return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Set flash message
 */
function setFlash($type, $message)
{
  startSession();
  $_SESSION['flash'] = [
    'type' => $type,
    'message' => $message
  ];
}

/**
 * Get and clear flash message
 */
function getFlash()
{
  startSession();
  $flash = $_SESSION['flash'] ?? null;
  unset($_SESSION['flash']);
  return $flash;
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'M d, Y')
{
  return date($format, strtotime($date));
}

/**
 * Time ago format
 */
function timeAgo($datetime)
{
  $now = new DateTime();
  $ago = new DateTime($datetime);
  $diff = $now->diff($ago);

  if ($diff->y > 0)
    return $diff->y . ' tahun lalu';
  if ($diff->m > 0)
    return $diff->m . ' bulan lalu';
  if ($diff->d > 6)
    return floor($diff->d / 7) . ' minggu lalu';
  if ($diff->d > 0)
    return $diff->d . ' hari lalu';
  if ($diff->h > 0)
    return $diff->h . ' jam lalu';
  if ($diff->i > 0)
    return $diff->i . ' menit lalu';
  return 'Baru saja';
}

/**
 * Generate random string
 */
function generateRandomString($length = 32)
{
  return bin2hex(random_bytes($length / 2));
}

/**
 * Upload file
 */
function uploadFile($file, $destination, $allowedTypes, $maxSize)
{
  if ($file['error'] !== UPLOAD_ERR_OK) {
    return ['success' => false, 'message' => 'Upload error'];
  }

  if ($file['size'] > $maxSize) {
    return ['success' => false, 'message' => 'File too large'];
  }

  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mimeType = finfo_file($finfo, $file['tmp_name']);
  finfo_close($finfo);

  if (!in_array($mimeType, $allowedTypes)) {
    return ['success' => false, 'message' => 'File type not allowed'];
  }

  // Create directory if not exists
  if (!is_dir($destination)) {
    mkdir($destination, 0755, true);
  }

  // Generate unique filename
  $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
  $filename = generateRandomString(16) . '_' . time() . '.' . $extension;
  $filepath = $destination . '/' . $filename;

  if (move_uploaded_file($file['tmp_name'], $filepath)) {
    return ['success' => true, 'filename' => $filename];
  }

  return ['success' => false, 'message' => 'Failed to move file'];
}

/**
 * Get video duration using FFmpeg (if available)
 */
function getVideoDuration($filepath)
{
  // Try to get video duration using getID3 or FFmpeg
  // For simplicity, we'll estimate based on file size
  // In production, use FFmpeg: ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 video.mp4

  $output = [];
  $cmd = 'ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 "' . $filepath . '" 2>&1';
  exec($cmd, $output);

  if (!empty($output[0]) && is_numeric($output[0])) {
    return floatval($output[0]);
  }

  // Fallback: estimate 1 second per 500KB for typical social media video
  return filesize($filepath) / (500 * 1024);
}

/**
 * Delete file
 */
function deleteFile($filepath)
{
  if (file_exists($filepath)) {
    return unlink($filepath);
  }
  return false;
}

/**
 * Format number (1000 -> 1K)
 */
function formatNumber($number)
{
  if ($number >= 1000000) {
    return round($number / 1000000, 1) . 'M';
  }
  if ($number >= 1000) {
    return round($number / 1000, 1) . 'K';
  }
  return $number;
}

/**
 * Get user avatar URL
 */
function getAvatarUrl($avatar)
{
  if (empty($avatar) || $avatar === 'default.png') {
    return BASE_URL . '/assets/img/default-avatar.png';
  }
  return AVATAR_URL . '/' . $avatar;
}

/**
 * Validate email
 */
function isValidEmail($email)
{
  return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate password strength
 */
function isStrongPassword($password)
{
  // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
  return strlen($password) >= 8
    && preg_match('/[A-Z]/', $password)
    && preg_match('/[a-z]/', $password)
    && preg_match('/[0-9]/', $password);
}

/**
 * JSON response helper
 */
function jsonResponse($data, $statusCode = 200)
{
  http_response_code($statusCode);
  header('Content-Type: application/json');
  echo json_encode($data);
  exit;
}

/**
 * Check if request is AJAX
 */
function isAjax()
{
  return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Get client IP address
 */
function getClientIP()
{
  if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    return $_SERVER['HTTP_CLIENT_IP'];
  }
  if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    return $_SERVER['HTTP_X_FORWARDED_FOR'];
  }
  return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Log admin action
 */
function logAdminAction($action, $description = '')
{
  if (!isAdmin())
    return false;

  $stmt = db()->prepare("INSERT INTO admin_logs (admin_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
  return $stmt->execute([getCurrentUserId(), $action, $description, getClientIP()]);
}

/**
 * Update site statistics
 */
function updateSiteStats($field, $increment = 1)
{
  $today = date('Y-m-d');

  // Check if today's record exists
  $stmt = db()->prepare("SELECT id FROM site_statistics WHERE date = ?");
  $stmt->execute([$today]);

  if ($stmt->fetch()) {
    $stmt = db()->prepare("UPDATE site_statistics SET $field = $field + ? WHERE date = ?");
    $stmt->execute([$increment, $today]);
  } else {
    $stmt = db()->prepare("INSERT INTO site_statistics (date, $field) VALUES (?, ?)");
    $stmt->execute([$today, $increment]);
  }
}

/**
 * Clean up expired stories
 * Deletes expired stories and their media files
 */
function cleanupExpiredStories()
{
  // Get expired stories with media files
  $stmt = db()->query("SELECT id, media_url FROM stories WHERE expires_at <= NOW()");
  $expiredStories = $stmt->fetchAll();

  $deletedCount = 0;
  foreach ($expiredStories as $story) {
    // Delete media file if exists
    if ($story['media_url']) {
      $filepath = STORY_PATH . '/' . $story['media_url'];
      if (file_exists($filepath)) {
        unlink($filepath);
      }
    }
    $deletedCount++;
  }

  // Delete expired stories from database
  if (!empty($expiredStories)) {
    $ids = array_column($expiredStories, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    // Delete story views first (foreign key)
    $stmt = db()->prepare("DELETE FROM story_views WHERE story_id IN ($placeholders)");
    $stmt->execute($ids);

    // Delete stories
    $stmt = db()->prepare("DELETE FROM stories WHERE id IN ($placeholders)");
    $stmt->execute($ids);
  }

  return $deletedCount;
}

/**
 * Get remaining time until story expires
 */
function getStoryRemainingTime($expiresAt)
{
  $now = new DateTime();
  $expires = new DateTime($expiresAt);

  if ($expires <= $now) {
    return 'Expired';
  }

  $diff = $now->diff($expires);

  if ($diff->h > 0) {
    return $diff->h . ' jam lagi';
  }
  if ($diff->i > 0) {
    return $diff->i . ' menit lagi';
  }
  return 'Sebentar lagi';
}
