<?php
/**
 * Logout
 * PulTech Social Media Application
 */

// No output before headers
ob_start();

require_once __DIR__ . '/../includes/functions.php';

startSession();

// Regenerate session ID for security
session_regenerate_id(true);

// Destroy session data
$_SESSION = array();

// Delete session cookie
if (ini_get("session.use_cookies")) {
  $params = session_get_cookie_params();
  setcookie(
    session_name(),
    '',
    time() - 42000,
    $params["path"],
    $params["domain"],
    $params["secure"],
    $params["httponly"]
  );
}

// Destroy session
session_destroy();

// Delete remember me cookie
if (isset($_COOKIE['remember_token'])) {
  setcookie('remember_token', '', time() - 3600, '/');
}

// Clear output buffer and redirect
ob_end_clean();

// Redirect to login
header("Location: " . BASE_URL . "/auth/login.php");
exit;
