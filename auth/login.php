<?php
/**
 * Login Page
 * PulTech Social Media Application
 */

require_once __DIR__ . '/../middleware/guest.php';

$error = '';
$success = '';

// Get flash messages
$flash = getFlash();
if ($flash) {
  if ($flash['type'] === 'success') {
    $success = $flash['message'];
  } else {
    $error = $flash['message'];
  }
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = sanitize($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $remember = isset($_POST['remember']);

  // Validate CSRF
  if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $error = 'Invalid request. Please try again.';
  } else if (empty($email) || empty($password)) {
    $error = 'Email dan password harus diisi.';
  } else {
    // Check user credentials
    $stmt = db()->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
      // Login successful
      $_SESSION['user_id'] = $user['id'];

      // Update last login
      $stmt = db()->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
      $stmt->execute([$user['id']]);

      // Update site statistics
      updateSiteStats('page_views');

      // Remember me functionality
      if ($remember) {
        $token = generateRandomString(64);
        setcookie('remember_token', $token, time() + (86400 * 30), '/');
        // In production, store this token in database
      }

      // Redirect to intended URL or dashboard
      $intended = $_SESSION['intended_url'] ?? null;
      unset($_SESSION['intended_url']);

      if ($user['role'] === 'admin') {
        redirect($intended ?? BASE_URL . '/admin/index.php');
      } else {
        redirect($intended ?? BASE_URL . '/user/index.php');
      }
    } else {
      $error = 'Email atau password salah.';
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
  <title>Login - <?= APP_NAME ?></title>
  <meta name="description" content="Login ke akun SISMED Social Anda">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="icon" href="<?= BASE_URL ?>/assets/images/logo.png">
  <link href="<?= BASE_URL ?>/assets/css/auth.css" rel="stylesheet">
</head>

<body>
  <div class="auth-wrapper">
    <!-- Floating Shapes -->
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
        <h1 class="auth-title">Selamat Datang Kembali!</h1>
        <p class="auth-subtitle">Masuk ke akun Anda untuk melanjutkan</p>
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
            <input type="email" name="email" class="form-control" placeholder="nama@email.com"
              value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Password</label>
          <div class="input-group">
            <i class="bi bi-lock input-icon"></i>
            <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password"
              required>
            <i class="bi bi-eye toggle-password" onclick="togglePassword('password')"></i>
          </div>
        </div>

        <div class="form-options">
          <div class="form-check">
            <input type="checkbox" name="remember" id="remember">
            <label for="remember">Ingat saya</label>
          </div>
          <a href="forgot-password.php" class="forgot-link">Lupa password?</a>
        </div>

        <button type="submit" class="auth-submit">
          <span>Masuk</span>
          <i class="bi bi-arrow-right"></i>
        </button>
      </form>

      <div class="auth-footer">
        <p>Belum punya akun? <a href="register.php">Daftar sekarang</a></p>
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