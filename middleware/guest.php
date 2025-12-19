<?php
/**
 * Guest Middleware
 * Only allows non-authenticated users
 */

require_once __DIR__ . '/../includes/functions.php';

startSession();

if (isLoggedIn()) {
  // Redirect authenticated users to dashboard
  if (isAdmin()) {
    redirect(BASE_URL . '/admin/index.php');
  } else {
    redirect(BASE_URL . '/user/index.php');
  }
}
