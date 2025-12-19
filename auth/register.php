<?php
/**
 * Register Page
 * PulTech Social Media Application
 */

require_once __DIR__ . '/../middleware/guest.php';

$error = '';
$success = '';
$errors = [];

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $fullName = sanitize($_POST['full_name'] ?? '');
  $username = sanitize($_POST['username'] ?? '');
  $email = sanitize($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $confirmPassword = $_POST['confirm_password'] ?? '';

  // Validate CSRF
  if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $error = 'Invalid request. Please try again.';
  } else {
    // Validation
    if (empty($fullName)) {
      $errors['full_name'] = 'Nama lengkap harus diisi.';
    }

    if (empty($username)) {
      $errors['username'] = 'Username harus diisi.';
    } elseif (strlen($username) < 3) {
      $errors['username'] = 'Username minimal 3 karakter.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
      $errors['username'] = 'Username hanya boleh huruf, angka, dan underscore.';
    } else {
      // Check if username exists
      $stmt = db()->prepare("SELECT id FROM users WHERE username = ?");
      $stmt->execute([$username]);
      if ($stmt->fetch()) {
        $errors['username'] = 'Username sudah digunakan.';
      }
    }

    if (empty($email)) {
      $errors['email'] = 'Email harus diisi.';
    } elseif (!isValidEmail($email)) {
      $errors['email'] = 'Format email tidak valid.';
    } else {
      // Check if email exists
      $stmt = db()->prepare("SELECT id FROM users WHERE email = ?");
      $stmt->execute([$email]);
      if ($stmt->fetch()) {
        $errors['email'] = 'Email sudah terdaftar.';
      }
    }

    if (empty($password)) {
      $errors['password'] = 'Password harus diisi.';
    } elseif (strlen($password) < 8) {
      $errors['password'] = 'Password minimal 8 karakter.';
    } elseif (!isStrongPassword($password)) {
      $errors['password'] = 'Password harus mengandung huruf besar, huruf kecil, dan angka.';
    }

    if ($password !== $confirmPassword) {
      $errors['confirm_password'] = 'Konfirmasi password tidak cocok.';
    }

    // If no errors, create user
    if (empty($errors)) {
      $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

      $stmt = db()->prepare("INSERT INTO users (username, email, password, full_name, created_at) VALUES (?, ?, ?, ?, NOW())");

      if ($stmt->execute([$username, $email, $hashedPassword, $fullName])) {
        // Update statistics
        updateSiteStats('new_users');
        updateSiteStats('total_users');

        setFlash('success', 'Registrasi berhasil! Silakan login.');
        redirect(BASE_URL . '/auth/login.php');
      } else {
        $error = 'Terjadi kesalahan. Silakan coba lagi.';
      }
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
  <title>Daftar - <?= APP_NAME ?></title>
  <meta name="description" content="Buat akun SISMED Social Anda">
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
        <h1 class="auth-title">Buat Akun Baru</h1>
        <p class="auth-subtitle">Bergabung dengan komunitas SISMED</p>
      </div>

      <?php if ($error): ?>
        <div class="auth-alert error">
          <i class="bi bi-exclamation-circle"></i>
          <span><?= $error ?></span>
        </div>
      <?php endif; ?>

      <form class="auth-form" method="POST" action="" id="registerForm">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <div class="form-group">
          <label class="form-label">Nama Lengkap</label>
          <div class="input-group">
            <i class="bi bi-person input-icon"></i>
            <input type="text" name="full_name"
              class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>"
              placeholder="Masukkan nama lengkap" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
          </div>
          <?php if (isset($errors['full_name'])): ?>
            <div class="error-message"><i class="bi bi-exclamation-circle"></i> <?= $errors['full_name'] ?></div>
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label class="form-label">Username</label>
          <div class="input-group">
            <i class="bi bi-at input-icon"></i>
            <input type="text" name="username"
              class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>" placeholder="Pilih username"
              value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
          </div>
          <?php if (isset($errors['username'])): ?>
            <div class="error-message"><i class="bi bi-exclamation-circle"></i> <?= $errors['username'] ?></div>
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label class="form-label">Email</label>
          <div class="input-group">
            <i class="bi bi-envelope input-icon"></i>
            <input type="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
              placeholder="nama@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
          </div>
          <?php if (isset($errors['email'])): ?>
            <div class="error-message"><i class="bi bi-exclamation-circle"></i> <?= $errors['email'] ?></div>
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label class="form-label">Password</label>
          <div class="input-group">
            <i class="bi bi-lock input-icon"></i>
            <input type="password" name="password" id="password"
              class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" placeholder="Buat password"
              required>
            <i class="bi bi-eye toggle-password" onclick="togglePassword('password')"></i>
          </div>
          <div class="password-strength" id="passwordStrength"></div>
          <div class="password-strength-text" id="passwordStrengthText"></div>
          <?php if (isset($errors['password'])): ?>
            <div class="error-message"><i class="bi bi-exclamation-circle"></i> <?= $errors['password'] ?></div>
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label class="form-label">Konfirmasi Password</label>
          <div class="input-group">
            <i class="bi bi-lock input-icon"></i>
            <input type="password" name="confirm_password" id="confirmPassword"
              class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>"
              placeholder="Ulangi password" required>
            <i class="bi bi-eye toggle-password" onclick="togglePassword('confirmPassword')"></i>
          </div>
          <?php if (isset($errors['confirm_password'])): ?>
            <div class="error-message"><i class="bi bi-exclamation-circle"></i> <?= $errors['confirm_password'] ?></div>
          <?php endif; ?>
        </div>

        <button type="submit" class="auth-submit">
          <span>Daftar Sekarang</span>
          <i class="bi bi-arrow-right"></i>
        </button>
      </form>

      <div class="auth-footer">
        <p>Sudah punya akun? <a href="login.php">Masuk di sini</a></p>
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

    // Password strength indicator
    document.getElementById('password').addEventListener('input', function () {
      const password = this.value;
      const strengthContainer = document.getElementById('passwordStrength');
      const strengthText = document.getElementById('passwordStrengthText');

      let strength = 0;
      let text = '';

      if (password.length >= 8) strength++;
      if (/[a-z]/.test(password)) strength++;
      if (/[A-Z]/.test(password)) strength++;
      if (/[0-9]/.test(password)) strength++;
      if (/[^a-zA-Z0-9]/.test(password)) strength++;

      strengthContainer.innerHTML = '';

      for (let i = 0; i < 4; i++) {
        const bar = document.createElement('div');
        bar.className = 'strength-bar';

        if (password.length > 0) {
          if (strength <= 2 && i < strength) bar.classList.add('weak');
          else if (strength === 3 && i < 3) bar.classList.add('fair');
          else if (strength === 4 && i < 4) bar.classList.add('good');
          else if (strength >= 5) bar.classList.add('strong');
        }

        strengthContainer.appendChild(bar);
      }

      if (password.length === 0) text = '';
      else if (strength <= 2) text = 'Password lemah';
      else if (strength === 3) text = 'Password cukup';
      else if (strength === 4) text = 'Password baik';
      else text = 'Password kuat';

      strengthText.textContent = text;
    });
  </script>
</body>

</html>