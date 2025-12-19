<?php
/**
 * Reset Password Page
 * PulTech Social Media Application
 */

require_once __DIR__ . '/../middleware/guest.php';

$error = '';
$success = '';
$validToken = false;
$token = $_GET['token'] ?? '';

// Verify token
if (!empty($token)) {
  $stmt = db()->prepare("SELECT id, username FROM users WHERE reset_token = ? AND reset_token_expiry > NOW() AND is_active = 1");
  $stmt->execute([$token]);
  $user = $stmt->fetch();

  if ($user) {
    $validToken = true;
  } else {
    $error = 'Link reset password tidak valid atau sudah kadaluarsa.';
  }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
  $password = $_POST['password'] ?? '';
  $confirmPassword = $_POST['confirm_password'] ?? '';

  if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $error = 'Invalid request. Please try again.';
  } elseif (empty($password)) {
    $error = 'Password harus diisi.';
  } elseif (strlen($password) < 8) {
    $error = 'Password minimal 8 karakter.';
  } elseif (!isStrongPassword($password)) {
    $error = 'Password harus mengandung huruf besar, huruf kecil, dan angka.';
  } elseif ($password !== $confirmPassword) {
    $error = 'Konfirmasi password tidak cocok.';
  } else {
    // Update password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = db()->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?");

    if ($stmt->execute([$hashedPassword, $user['id']])) {
      setFlash('success', 'Password berhasil diubah! Silakan login dengan password baru.');
      redirect(BASE_URL . '/auth/login.php');
    } else {
      $error = 'Terjadi kesalahan. Silakan coba lagi.';
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
  <title>Reset Password - <?= APP_NAME ?></title>
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
        <h1 class="auth-title">Reset Password</h1>
        <p class="auth-subtitle">Buat password baru untuk akun Anda</p>
      </div>

      <?php if ($error): ?>
        <div class="auth-alert error">
          <i class="bi bi-exclamation-circle"></i>
          <span><?= $error ?></span>
        </div>
      <?php endif; ?>

      <?php if ($validToken): ?>
        <form class="auth-form" method="POST" action="">
          <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

          <div class="form-group">
            <label class="form-label">Password Baru</label>
            <div class="input-group">
              <i class="bi bi-lock input-icon"></i>
              <input type="password" name="password" id="password" class="form-control"
                placeholder="Masukkan password baru" required>
              <i class="bi bi-eye toggle-password" onclick="togglePassword('password')"></i>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Konfirmasi Password</label>
            <div class="input-group">
              <i class="bi bi-lock input-icon"></i>
              <input type="password" name="confirm_password" id="confirmPassword" class="form-control"
                placeholder="Ulangi password baru" required>
              <i class="bi bi-eye toggle-password" onclick="togglePassword('confirmPassword')"></i>
            </div>
          </div>

          <button type="submit" class="auth-submit">
            <span>Simpan Password</span>
            <i class="bi bi-check-lg"></i>
          </button>
        </form>
      <?php else: ?>
        <div class="text-center">
          <a href="forgot-password.php" class="auth-submit" style="text-decoration: none; display: inline-flex;">
            <span>Minta Link Baru</span>
            <i class="bi bi-arrow-right"></i>
          </a>
        </div>
      <?php endif; ?>

      <div class="auth-footer" style="margin-top: 24px;">
        <p>Ingat password? <a href="login.php">Kembali ke login</a></p>
      </div>

      <div class="auth-copyright">
        <p><?= COPYRIGHT ?></p>
      </div>
    </div>
  </div>

  <script>
    function togglePassword(inputId) {
      const input = document.getElementById(inputId);
      const icon = input.nextElementSibling;

      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
      } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
      }
    }
  </script>
</body>

</html>