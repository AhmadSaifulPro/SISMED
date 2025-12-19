<?php
/**
 * Forgot Password Page
 * PulTech Social Media Application
 */

require_once __DIR__ . '/../middleware/guest.php';
require_once __DIR__ . '/../config/mail.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = sanitize($_POST['email'] ?? '');

  if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $error = 'Invalid request. Please try again.';
  } elseif (empty($email)) {
    $error = 'Email harus diisi.';
  } elseif (!isValidEmail($email)) {
    $error = 'Format email tidak valid.';
  } else {
    // Check if email exists
    $stmt = db()->prepare("SELECT id, username, email FROM users WHERE email = ? AND is_active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
      // Generate reset token
      $token = generateRandomString(64);
      $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

      // Save token to database
      $stmt = db()->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?");
      $stmt->execute([$token, $expiry, $user['id']]);

      // Send email
      $emailSent = sendPasswordResetEmail($user['email'], $token, $user['username']);

      if ($emailSent) {
        $success = 'Link reset password telah dikirim ke email Anda. Silakan cek inbox atau folder spam.';
      } else {
        // Fallback: show link directly if email fails
        $resetLink = BASE_URL . '/auth/reset-password.php?token=' . $token;
        $success = 'Email gagal dikirim. Gunakan link berikut: <a href="' . $resetLink . '">' . $resetLink . '</a>';
      }
    } else {
      // Don't reveal if email exists or not for security
      $success = 'Jika email terdaftar, link reset password akan dikirim.';
    }
  }
}

$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lupa Password - <?= APP_NAME ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="icon" href="<?= BASE_URL ?>/assets/images/logo.png">
  <link href="<?= BASE_URL ?>/assets/css/auth.css" rel="stylesheet">
</head>

<body>
  <div class="auth-wrapper">
    <div class="floating-shapes">
      <div class="shape"></div>
      <div class="shape"></div>
      <div class="shape"></div>
      <div class="shape"></div>
    </div>

    <div class="auth-card">
      <div class="auth-header">
        <div class="auth-logo">
          <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="SISMED" style="height: 60px; width: auto;">
        </div>
        <h1 class="auth-title">Lupa Password?</h1>
        <p class="auth-subtitle">Masukkan email Anda untuk reset password</p>
      </div>

      <?php if ($error): ?>
        <div class="auth-alert error">
          <i class="bi bi-exclamation-circle"></i>
          <span><?= $error ?></span>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="auth-alert success">
          <i class="bi bi-check-circle"></i>
          <span><?= $success ?></span>
        </div>
      <?php endif; ?>

      <form class="auth-form" method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <div class="form-group">
          <label class="form-label">Email</label>
          <div class="input-group">
            <i class="bi bi-envelope input-icon"></i>
            <input type="email" name="email" class="form-control" placeholder="nama@email.com" required>
          </div>
        </div>

        <button type="submit" class="auth-submit">
          <span>Kirim Link Reset</span>
          <i class="bi bi-send"></i>
        </button>
      </form>

      <div class="auth-footer">
        <p>Ingat password? <a href="login.php">Kembali ke login</a></p>
      </div>

      <div class="auth-copyright">
        <p><?= COPYRIGHT ?></p>
      </div>
    </div>
  </div>
</body>

</html>