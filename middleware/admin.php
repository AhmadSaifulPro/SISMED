<?php
/**
 * Admin Middleware
 * Requires user to be an admin
 */

require_once __DIR__ . '/auth.php';

if (!isAdmin()) {
  if (isAjax()) {
    jsonResponse(['error' => 'Forbidden', 'message' => 'Admin access required'], 403);
  }

  setFlash('error', 'Anda tidak memiliki akses ke halaman ini');
  redirect(BASE_URL . '/user/index.php');
}
