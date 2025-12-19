<?php
/**
 * Main Entry Point
 * PulTech Social Media Application
 */

require_once __DIR__ . '/includes/functions.php';

startSession();

// Redirect based on authentication status
if (isLoggedIn()) {
  if (isAdmin()) {
    redirect(BASE_URL . '/admin/index.php');
  } else {
    redirect(BASE_URL . '/user/index.php');
  }
} else {
  redirect(BASE_URL . '/auth/login.php');
}
