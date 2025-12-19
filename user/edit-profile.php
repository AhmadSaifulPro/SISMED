<?php
/**
 * Edit Profile Page
 * PulTech Social Media Application
 */

require_once __DIR__ . '/../middleware/auth.php';

$currentUser = getCurrentUser();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $fullName = sanitize($_POST['full_name'] ?? '');
  $username = sanitize($_POST['username'] ?? '');
  $bio = sanitize($_POST['bio'] ?? '');

  // Validate username
  if (empty($username)) {
    $error = 'Username harus diisi';
  } elseif ($username !== $currentUser['username']) {
    $stmt = db()->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->execute([$username, $currentUser['id']]);
    if ($stmt->fetch()) {
      $error = 'Username sudah digunakan';
    }
  }

  // Handle avatar upload
  $avatar = $currentUser['avatar'];
  if (!empty($_FILES['avatar']['name'])) {
    $result = uploadFile($_FILES['avatar'], AVATAR_PATH, ALLOWED_IMAGE_TYPES, MAX_IMAGE_SIZE);
    if ($result['success']) {
      // Delete old avatar
      if ($currentUser['avatar'] && $currentUser['avatar'] !== 'default.png') {
        deleteFile(AVATAR_PATH . '/' . $currentUser['avatar']);
      }
      $avatar = $result['filename'];
    } else {
      $error = 'Gagal upload avatar: ' . $result['message'];
    }
  }

  // Handle cover photo upload
  $coverPhoto = $currentUser['cover_photo'];
  if (!empty($_FILES['cover_photo']['name'])) {
    $result = uploadFile($_FILES['cover_photo'], AVATAR_PATH, ALLOWED_IMAGE_TYPES, MAX_IMAGE_SIZE);
    if ($result['success']) {
      if ($currentUser['cover_photo']) {
        deleteFile(AVATAR_PATH . '/' . $currentUser['cover_photo']);
      }
      $coverPhoto = $result['filename'];
    } else {
      $error = 'Gagal upload cover photo: ' . $result['message'];
    }
  }

  if (!$error) {
    $stmt = db()->prepare("UPDATE users SET full_name = ?, username = ?, bio = ?, avatar = ?, cover_photo = ? WHERE id = ?");
    if ($stmt->execute([$fullName, $username, $bio, $avatar, $coverPhoto, $currentUser['id']])) {
      $success = 'Profil berhasil diperbarui';
      // Refresh user data
      $stmt = db()->prepare("SELECT * FROM users WHERE id = ?");
      $stmt->execute([$currentUser['id']]);
      $currentUser = $stmt->fetch();
    } else {
      $error = 'Gagal memperbarui profil';
    }
  }
}

$pageTitle = 'Edit Profil - ' . APP_NAME;
$currentPage = 'profile';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="dashboard-main">
  <div class="dashboard-content" style="max-width: 600px;">
    <h1 style="margin-bottom: 24px;">Edit Profil</h1>

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

    <form method="POST" enctype="multipart/form-data">
      <!-- Cover Photo -->
      <div class="card" style="margin-bottom: 24px; overflow: hidden;">
        <div style="height: 150px; background: var(--primary-gradient); position: relative;">
          <?php if ($currentUser['cover_photo']): ?>
            <img src="<?= AVATAR_URL ?>/<?= $currentUser['cover_photo'] ?>" alt="Cover"
              style="width: 100%; height: 100%; object-fit: cover;">
          <?php endif; ?>
          <label
            style="position: absolute; bottom: 12px; right: 12px; background: rgba(0,0,0,0.5); color: white; padding: 8px 16px; border-radius: 20px; cursor: pointer;">
            <i class="bi bi-camera"></i> Ubah Cover
            <input type="file" name="cover_photo" accept="image/*" style="display: none;">
          </label>
        </div>

        <div class="card-body" style="position: relative;">
          <div style="position: absolute; top: -50px; left: 24px;">
            <label style="cursor: pointer; display: block; position: relative;">
              <img src="<?= getAvatarUrl($currentUser['avatar']) ?>" alt="Avatar"
                style="width: 100px; height: 100px; border-radius: 50%; border: 4px solid var(--bg-secondary); object-fit: cover;">
              <div
                style="position: absolute; bottom: 0; right: 0; width: 32px; height: 32px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                <i class="bi bi-camera"></i>
              </div>
              <input type="file" name="avatar" accept="image/*" style="display: none;">
            </label>
          </div>

          <div style="margin-left: 130px; padding-top: 8px;">
            <div style="font-weight: 600;">
              <?= htmlspecialchars($currentUser['full_name'] ?: $currentUser['username']) ?></div>
            <div style="color: var(--text-muted);">@<?= htmlspecialchars($currentUser['username']) ?></div>
          </div>
        </div>
      </div>

      <!-- Profile Info -->
      <div class="card">
        <div class="card-body">
          <div class="form-group">
            <label class="form-label">Nama Lengkap</label>
            <input type="text" name="full_name" class="form-control"
              value="<?= htmlspecialchars($currentUser['full_name'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control"
              value="<?= htmlspecialchars($currentUser['username']) ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label">Bio</label>
            <textarea name="bio" class="form-control" rows="3"
              placeholder="Ceritakan tentang dirimu..."><?= htmlspecialchars($currentUser['bio'] ?? '') ?></textarea>
          </div>

          <div class="d-flex gap-3">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-check"></i> Simpan Perubahan
            </button>
            <a href="<?= BASE_URL ?>/user/profile.php" class="btn btn-secondary">Batal</a>
          </div>
        </div>
      </div>
    </form>
  </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>