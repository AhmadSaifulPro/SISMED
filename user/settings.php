<?php
/**
 * User Settings Page
 * PulTech Social Media Application
 */

require_once __DIR__ . '/../middleware/auth.php';

$currentUser = getCurrentUser();
$success = '';
$error = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  if ($_POST['action'] === 'change_password') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!password_verify($currentPassword, $currentUser['password'])) {
      $error = 'Password saat ini salah';
    } elseif (strlen($newPassword) < 8) {
      $error = 'Password baru minimal 8 karakter';
    } elseif ($newPassword !== $confirmPassword) {
      $error = 'Konfirmasi password tidak cocok';
    } else {
      $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
      $stmt = db()->prepare("UPDATE users SET password = ? WHERE id = ?");
      if ($stmt->execute([$hashedPassword, $currentUser['id']])) {
        $success = 'Password berhasil diubah';
      } else {
        $error = 'Gagal mengubah password';
      }
    }
  }
}

$pageTitle = 'Pengaturan - ' . APP_NAME;
$currentPage = 'settings';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="dashboard-main">
  <div class="dashboard-content" style="max-width: 600px;">
    <h1 style="margin-bottom: 24px;">Pengaturan</h1>

    <?php if ($success): ?>
      <div class="auth-alert success" style="margin-bottom: 24px;">
        <i class="bi bi-check-circle"></i>
        <span><?= $success ?></span>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="auth-alert error" style="margin-bottom: 24px;">
        <i class="bi bi-exclamation-circle"></i>
        <span><?= $error ?></span>
      </div>
    <?php endif; ?>

    <!-- Account Settings -->
    <div class="card" style="margin-bottom: 24px;">
      <div class="card-header">
        <h3 style="font-size: 1rem; font-weight: 600;">Informasi Akun</h3>
      </div>
      <div class="card-body">
        <div class="d-flex justify-between align-center mb-3">
          <div>
            <div style="font-weight: 500;">Email</div>
            <div style="color: var(--text-muted);"><?= htmlspecialchars($currentUser['email']) ?></div>
          </div>
        </div>
        <div class="d-flex justify-between align-center">
          <div>
            <div style="font-weight: 500;">Username</div>
            <div style="color: var(--text-muted);">@<?= htmlspecialchars($currentUser['username']) ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Change Password -->
    <div class="card" style="margin-bottom: 24px;">
      <div class="card-header">
        <h3 style="font-size: 1rem; font-weight: 600;">Ubah Password</h3>
      </div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="action" value="change_password">

          <div class="form-group">
            <label class="form-label">Password Saat Ini</label>
            <input type="password" name="current_password" class="form-control" required>
          </div>

          <div class="form-group">
            <label class="form-label">Password Baru</label>
            <input type="password" name="new_password" class="form-control" required>
          </div>

          <div class="form-group">
            <label class="form-label">Konfirmasi Password Baru</label>
            <input type="password" name="confirm_password" class="form-control" required>
          </div>

          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check"></i> Simpan Password
          </button>
        </form>
      </div>
    </div>

    <!-- Danger Zone -->
    <div class="card" style="border-color: var(--error-color);">
      <div class="card-header" style="background: rgba(239, 68, 68, 0.1);">
        <h3 style="font-size: 1rem; font-weight: 600; color: var(--error-color);">Zona Berbahaya</h3>
      </div>
      <div class="card-body">
        <p style="margin-bottom: 16px; color: var(--text-muted);">
          Setelah menghapus akun, semua data Anda akan dihapus secara permanen.
        </p>
        <button class="btn" style="background: var(--error-color); color: white;"
          onclick="if(confirm('Apakah Anda yakin ingin menghapus akun?')) alert('Fitur ini belum tersedia')">
          <i class="bi bi-trash"></i> Hapus Akun
        </button>
      </div>
    </div>
  </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>