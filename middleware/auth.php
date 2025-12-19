<?php
/**
 * Authentication Middleware
 * Requires user to be logged in
 */

require_once __DIR__ . '/../includes/functions.php';

startSession();

if (!isLoggedIn()) {
  // Store intended URL for redirect after login
  $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];

  if (isAjax()) {
    jsonResponse(['error' => 'Unauthorized', 'message' => 'Please login to continue'], 401);
  }

  setFlash('warning', 'Silakan login terlebih dahulu');
  redirect(BASE_URL . '/auth/login.php');
}

// Check if user is still active
$stmt = db()->prepare("SELECT is_active FROM users WHERE id = ?");
$stmt->execute([getCurrentUserId()]);
$user = $stmt->fetch();

if (!$user || !$user['is_active']) {
  session_destroy();

  if (isAjax()) {
    jsonResponse(['error' => 'Account disabled', 'message' => 'Your account has been disabled'], 403);
  }

  setFlash('error', 'Akun Anda telah dinonaktifkan');
  redirect(BASE_URL . '/auth/login.php');
}
