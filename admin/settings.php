<?php
/**
 * Admin - Pengaturan (Settings)
 * SISMED Social Media Application
 */

require_once __DIR__ . '/../middleware/admin.php';

$currentUser = getCurrentUser();
$flash = getFlash();

// Get database statistics
$stmt = db()->query("SELECT COUNT(*) FROM users");
$totalUsers = $stmt->fetchColumn();

$stmt = db()->query("SELECT COUNT(*) FROM posts");
$totalPosts = $stmt->fetchColumn();

$stmt = db()->query("SELECT COUNT(*) FROM messages");
$totalMessages = $stmt->fetchColumn();

$stmt = db()->query("SELECT COUNT(*) FROM stories");
$totalStories = $stmt->fetchColumn();

$stmt = db()->query("SELECT COUNT(*) FROM comments");
$totalComments = $stmt->fetchColumn();

$stmt = db()->query("SELECT COUNT(*) FROM admin_logs");
$totalLogs = $stmt->fetchColumn();

// Get folder sizes
function getFolderSize($path)
{
  $size = 0;
  if (is_dir($path)) {
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
      if ($file->isFile()) {
        $size += $file->getSize();
      }
    }
  }
  return $size;
}

function formatBytes($bytes, $precision = 2)
{
  $units = ['B', 'KB', 'MB', 'GB', 'TB'];
  $bytes = max($bytes, 0);
  $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
  $pow = min($pow, count($units) - 1);
  $bytes /= pow(1024, $pow);
  return round($bytes, $precision) . ' ' . $units[$pow];
}

$avatarSize = formatBytes(getFolderSize(AVATAR_PATH));
$postSize = formatBytes(getFolderSize(POST_PATH));
$storySize = formatBytes(getFolderSize(STORY_PATH));
$messageSize = formatBytes(getFolderSize(MESSAGE_PATH));

$pageTitle = 'Pengaturan - ' . APP_NAME;
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?></title>
  <link rel="icon" href="<?= BASE_URL ?>/assets/images/logo.png">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/admin.css" rel="stylesheet">
</head>

<body>
  <div class="admin-wrapper">
    <!-- Admin Sidebar -->
    <aside class="admin-sidebar">
      <div class="admin-logo">
        <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="SISMED" style="height: 45px; width: auto;">
        <span>Admin Panel</span>
      </div>

      <nav class="admin-nav">
        <a href="<?= BASE_URL ?>/admin/index.php" class="admin-nav-item">
          <i class="bi bi-speedometer2"></i>
          <span>Dashboard</span>
        </a>
        <a href="<?= BASE_URL ?>/admin/users.php" class="admin-nav-item">
          <i class="bi bi-people"></i>
          <span>Pengguna</span>
        </a>
        <a href="<?= BASE_URL ?>/admin/posts.php" class="admin-nav-item">
          <i class="bi bi-grid"></i>
          <span>Postingan</span>
        </a>
        <a href="<?= BASE_URL ?>/admin/reports.php" class="admin-nav-item">
          <i class="bi bi-graph-up"></i>
          <span>Laporan</span>
        </a>

        <div class="admin-nav-section">Pengaturan</div>

        <a href="<?= BASE_URL ?>/admin/settings.php" class="admin-nav-item active">
          <i class="bi bi-gear"></i>
          <span>Pengaturan</span>
        </a>
        <a href="<?= BASE_URL ?>/admin/logs.php" class="admin-nav-item">
          <i class="bi bi-journal-text"></i>
          <span>Log Aktivitas</span>
        </a>

        <div class="admin-nav-section">Lainnya</div>

        <a href="<?= BASE_URL ?>/user/index.php" class="admin-nav-item">
          <i class="bi bi-house"></i>
          <span>Kembali ke App</span>
        </a>
        <a href="<?= BASE_URL ?>/auth/logout.php" class="admin-nav-item">
          <i class="bi bi-box-arrow-right"></i>
          <span>Keluar</span>
        </a>
      </nav>
    </aside>

    <!-- Admin Main -->
    <main class="admin-main">
      <header class="admin-header">
        <div class="admin-header-title">
          <h1>Pengaturan</h1>
          <p style="color: var(--text-muted); font-size: 0.875rem;">Informasi sistem dan konfigurasi aplikasi</p>
        </div>
      </header>

      <div class="admin-content">
        <?php if ($flash): ?>
          <div class="auth-alert <?= $flash['type'] ?>" style="margin-bottom: 24px;">
            <i class="bi bi-<?= $flash['type'] === 'success' ? 'check' : 'exclamation' ?>-circle"></i>
            <span><?= $flash['message'] ?></span>
          </div>
        <?php endif; ?>

        <div class="admin-grid">
          <!-- Application Info -->
          <div class="chart-card">
            <div class="chart-header">
              <h3 class="chart-title"><i class="bi bi-info-circle"></i> Informasi Aplikasi</h3>
            </div>
            <div class="settings-list">
              <div class="settings-item">
                <span class="settings-label">Nama Aplikasi</span>
                <span class="settings-value"><?= APP_NAME ?></span>
              </div>
              <div class="settings-item">
                <span class="settings-label">Versi</span>
                <span class="settings-value"><?= APP_VERSION ?></span>
              </div>
              <div class="settings-item">
                <span class="settings-label">Base URL</span>
                <span class="settings-value"><?= BASE_URL ?></span>
              </div>
              <div class="settings-item">
                <span class="settings-label">Timezone</span>
                <span class="settings-value"><?= date_default_timezone_get() ?></span>
              </div>
            </div>
          </div>

          <!-- System Info -->
          <div class="chart-card">
            <div class="chart-header">
              <h3 class="chart-title"><i class="bi bi-cpu"></i> Informasi Server</h3>
            </div>
            <div class="settings-list">
              <div class="settings-item">
                <span class="settings-label">PHP Version</span>
                <span class="settings-value"><?= phpversion() ?></span>
              </div>
              <div class="settings-item">
                <span class="settings-label">Server Software</span>
                <span class="settings-value"><?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ?></span>
              </div>
              <div class="settings-item">
                <span class="settings-label">Memory Limit</span>
                <span class="settings-value"><?= ini_get('memory_limit') ?></span>
              </div>
              <div class="settings-item">
                <span class="settings-label">Max Upload Size</span>
                <span class="settings-value"><?= ini_get('upload_max_filesize') ?></span>
              </div>
              <div class="settings-item">
                <span class="settings-label">Max Post Size</span>
                <span class="settings-value"><?= ini_get('post_max_size') ?></span>
              </div>
            </div>
          </div>
        </div>

        <div class="admin-grid" style="margin-top: 24px;">
          <!-- Upload Settings -->
          <div class="chart-card">
            <div class="chart-header">
              <h3 class="chart-title"><i class="bi bi-cloud-upload"></i> Pengaturan Upload</h3>
            </div>
            <div class="settings-list">
              <div class="settings-item">
                <span class="settings-label">Maks Ukuran Gambar</span>
                <span class="settings-value"><?= formatBytes(MAX_IMAGE_SIZE) ?></span>
              </div>
              <div class="settings-item">
                <span class="settings-label">Maks Ukuran Video</span>
                <span class="settings-value"><?= formatBytes(MAX_VIDEO_SIZE) ?></span>
              </div>
              <div class="settings-item">
                <span class="settings-label">Maks Durasi Video</span>
                <span class="settings-value"><?= MAX_VIDEO_DURATION ?> detik</span>
              </div>
              <div class="settings-item">
                <span class="settings-label">Expire Story</span>
                <span class="settings-value"><?= STORY_EXPIRY_HOURS ?> jam</span>
              </div>
              <div class="settings-item">
                <span class="settings-label">Tipe Gambar</span>
                <span class="settings-value"
                  style="font-size: 0.75rem;"><?= implode(', ', ALLOWED_IMAGE_TYPES) ?></span>
              </div>
              <div class="settings-item">
                <span class="settings-label">Tipe Video</span>
                <span class="settings-value"
                  style="font-size: 0.75rem;"><?= implode(', ', ALLOWED_VIDEO_TYPES) ?></span>
              </div>
            </div>
          </div>

          <!-- Storage Info -->
          <div class="chart-card">
            <div class="chart-header">
              <h3 class="chart-title"><i class="bi bi-hdd"></i> Penyimpanan</h3>
            </div>
            <div class="settings-list">
              <div class="settings-item">
                <span class="settings-label">Avatar</span>
                <span class="settings-value"><?= $avatarSize ?></span>
              </div>
              <div class="settings-item">
                <span class="settings-label">Postingan</span>
                <span class="settings-value"><?= $postSize ?></span>
              </div>
              <div class="settings-item">
                <span class="settings-label">Stories</span>
                <span class="settings-value"><?= $storySize ?></span>
              </div>
              <div class="settings-item">
                <span class="settings-label">Pesan</span>
                <span class="settings-value"><?= $messageSize ?></span>
              </div>
            </div>
          </div>
        </div>

        <div class="admin-grid" style="margin-top: 24px;">
          <!-- Database Stats -->
          <div class="chart-card">
            <div class="chart-header">
              <h3 class="chart-title"><i class="bi bi-database"></i> Statistik Database</h3>
            </div>
            <div class="settings-list">
              <div class="settings-item">
                <span class="settings-label">Total Users</span>
                <span class="settings-value"><?= number_format($totalUsers) ?></span>
              </div>
              <div class="settings-item">
                <span class="settings-label">Total Posts</span>
                <span class="settings-value"><?= number_format($totalPosts) ?></span>
              </div>
              <div class="settings-item">
                <span class="settings-label">Total Messages</span>
                <span class="settings-value"><?= number_format($totalMessages) ?></span>
              </div>
              <div class="settings-item">
                <span class="settings-label">Total Stories</span>
                <span class="settings-value"><?= number_format($totalStories) ?></span>
              </div>
              <div class="settings-item">
                <span class="settings-label">Total Comments</span>
                <span class="settings-value"><?= number_format($totalComments) ?></span>
              </div>
              <div class="settings-item">
                <span class="settings-label">Total Admin Logs</span>
                <span class="settings-value"><?= number_format($totalLogs) ?></span>
              </div>
            </div>
          </div>

          <!-- Pagination Settings -->
          <div class="chart-card">
            <div class="chart-header">
              <h3 class="chart-title"><i class="bi bi-list-ol"></i> Pengaturan Pagination</h3>
            </div>
            <div class="settings-list">
              <div class="settings-item">
                <span class="settings-label">Posts Per Page</span>
                <span class="settings-value"><?= POSTS_PER_PAGE ?></span>
              </div>
              <div class="settings-item">
                <span class="settings-label">Comments Per Page</span>
                <span class="settings-value"><?= COMMENTS_PER_PAGE ?></span>
              </div>
              <div class="settings-item">
                <span class="settings-label">Messages Per Page</span>
                <span class="settings-value"><?= MESSAGES_PER_PAGE ?></span>
              </div>
              <div class="settings-item">
                <span class="settings-label">Users Per Page</span>
                <span class="settings-value"><?= USERS_PER_PAGE ?></span>
              </div>
              <div class="settings-item">
                <span class="settings-label">Session Lifetime</span>
                <span class="settings-value"><?= SESSION_LIFETIME / 86400 ?> hari</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <style>
    .settings-list {
      padding: 16px;
    }

    .settings-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 0;
      border-bottom: 1px solid var(--border-color);
    }

    .settings-item:last-child {
      border-bottom: none;
    }

    .settings-label {
      font-weight: 500;
      color: var(--text-secondary);
    }

    .settings-value {
      color: var(--text-primary);
      font-family: monospace;
      background: var(--bg-tertiary);
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 0.875rem;
    }

    .chart-header .chart-title i {
      margin-right: 8px;
      color: var(--primary-color);
    }
  </style>
</body>

</html>