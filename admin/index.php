<?php
/**
 * Admin Dashboard
 * PulTech Social Media Application
 */

require_once __DIR__ . '/../middleware/admin.php';

$currentUser = getCurrentUser();

// Get statistics
$today = date('Y-m-d');
$lastWeek = date('Y-m-d', strtotime('-7 days'));
$lastMonth = date('Y-m-d', strtotime('-30 days'));

// Total Users
$stmt = db()->query("SELECT COUNT(*) FROM users WHERE is_active = 1");
$totalUsers = $stmt->fetchColumn();

$stmt = db()->prepare("SELECT COUNT(*) FROM users WHERE DATE(created_at) = ?");
$stmt->execute([$today]);
$newUsersToday = $stmt->fetchColumn();

// Total Posts
$stmt = db()->query("SELECT COUNT(*) FROM posts");
$totalPosts = $stmt->fetchColumn();

$stmt = db()->prepare("SELECT COUNT(*) FROM posts WHERE DATE(created_at) = ?");
$stmt->execute([$today]);
$newPostsToday = $stmt->fetchColumn();

// Total Messages
$stmt = db()->query("SELECT COUNT(*) FROM messages");
$totalMessages = $stmt->fetchColumn();

// Total Stories
$stmt = db()->query("SELECT COUNT(*) FROM stories WHERE expires_at > NOW()");
$activeStories = $stmt->fetchColumn();

// User growth last 7 days
$userGrowth = [];
for ($i = 6; $i >= 0; $i--) {
  $date = date('Y-m-d', strtotime("-$i days"));
  $stmt = db()->prepare("SELECT COUNT(*) FROM users WHERE DATE(created_at) = ?");
  $stmt->execute([$date]);
  $userGrowth[] = [
    'date' => date('d M', strtotime($date)),
    'count' => $stmt->fetchColumn()
  ];
}

// Post activity last 7 days
$postActivity = [];
for ($i = 6; $i >= 0; $i--) {
  $date = date('Y-m-d', strtotime("-$i days"));
  $stmt = db()->prepare("SELECT COUNT(*) FROM posts WHERE DATE(created_at) = ?");
  $stmt->execute([$date]);
  $postActivity[] = [
    'date' => date('d M', strtotime($date)),
    'count' => $stmt->fetchColumn()
  ];
}

// Recent Users
$stmt = db()->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
$recentUsers = $stmt->fetchAll();

// Recent Posts
$stmt = db()->query("
    SELECT p.*, u.username, u.avatar 
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    ORDER BY p.created_at DESC 
    LIMIT 5
");
$recentPosts = $stmt->fetchAll();

// Admin Logs
$stmt = db()->query("
    SELECT al.*, u.username 
    FROM admin_logs al 
    JOIN users u ON al.admin_id = u.id 
    ORDER BY al.created_at DESC 
    LIMIT 10
");
$adminLogs = $stmt->fetchAll();

$pageTitle = 'Admin Dashboard - ' . APP_NAME;
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
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        <a href="<?= BASE_URL ?>/admin/index.php" class="admin-nav-item active">
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

        <a href="<?= BASE_URL ?>/admin/settings.php" class="admin-nav-item">
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
          <h1>Dashboard</h1>
          <p style="color: var(--text-muted); font-size: 0.875rem;">Selamat datang kembali,
            <?= htmlspecialchars($currentUser['username']) ?>!
          </p>
        </div>
        <div class="admin-header-actions">
          <span style="color: var(--text-muted); font-size: 0.875rem;"><?= date('l, d F Y') ?></span>
          <img src="<?= getAvatarUrl($currentUser['avatar']) ?>" alt="Admin" class="avatar">
        </div>
      </header>

      <div class="admin-content">
        <!-- Stats Grid -->
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-card-header">
              <div>
                <div class="stat-card-value"><?= number_format($totalUsers) ?></div>
                <div class="stat-card-label">Total Pengguna</div>
              </div>
              <div class="stat-card-icon purple">
                <i class="bi bi-people"></i>
              </div>
            </div>
            <div class="stat-card-change positive">
              <i class="bi bi-arrow-up"></i> +<?= $newUsersToday ?> hari ini
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-card-header">
              <div>
                <div class="stat-card-value"><?= number_format($totalPosts) ?></div>
                <div class="stat-card-label">Total Postingan</div>
              </div>
              <div class="stat-card-icon green">
                <i class="bi bi-grid"></i>
              </div>
            </div>
            <div class="stat-card-change positive">
              <i class="bi bi-arrow-up"></i> +<?= $newPostsToday ?> hari ini
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-card-header">
              <div>
                <div class="stat-card-value"><?= number_format($totalMessages) ?></div>
                <div class="stat-card-label">Total Pesan</div>
              </div>
              <div class="stat-card-icon blue">
                <i class="bi bi-chat-dots"></i>
              </div>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-card-header">
              <div>
                <div class="stat-card-value"><?= number_format($activeStories) ?></div>
                <div class="stat-card-label">Story Aktif</div>
              </div>
              <div class="stat-card-icon orange">
                <i class="bi bi-circle"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- Charts -->
        <div class="admin-grid">
          <div class="chart-card">
            <div class="chart-header">
              <h3 class="chart-title">Pertumbuhan Pengguna</h3>
            </div>
            <canvas id="userGrowthChart" height="200"></canvas>
          </div>

          <div class="chart-card">
            <div class="chart-header">
              <h3 class="chart-title">Aktivitas Postingan</h3>
            </div>
            <canvas id="postActivityChart" height="200"></canvas>
          </div>
        </div>

        <!-- Recent Data -->
        <div class="admin-grid" style="margin-top: 24px;">
          <!-- Recent Users -->
          <div class="data-table-card">
            <div class="data-table-header">
              <h3 style="font-weight: 600;">Pengguna Terbaru</h3>
              <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-sm btn-secondary">Lihat Semua</a>
            </div>
            <table class="data-table">
              <thead>
                <tr>
                  <th>Pengguna</th>
                  <th>Tanggal Daftar</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentUsers as $user): ?>
                  <tr>
                    <td>
                      <div class="user-cell">
                        <img src="<?= getAvatarUrl($user['avatar']) ?>" alt="">
                        <div>
                          <div style="font-weight: 500;"><?= htmlspecialchars($user['full_name'] ?: $user['username']) ?>
                          </div>
                          <div style="font-size: 0.75rem; color: var(--text-muted);">
                            @<?= htmlspecialchars($user['username']) ?></div>
                        </div>
                      </div>
                    </td>
                    <td><?= formatDate($user['created_at']) ?></td>
                    <td>
                      <span class="status-badge <?= $user['is_active'] ? 'active' : 'inactive' ?>">
                        <?= $user['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <!-- Recent Activity -->
          <div class="chart-card">
            <div class="chart-header">
              <h3 class="chart-title">Aktivitas Terakhir</h3>
            </div>
            <div class="activity-list">
              <?php if (empty($adminLogs)): ?>
                <p class="text-center text-muted p-3">Belum ada aktivitas</p>
              <?php else: ?>
                <?php foreach ($adminLogs as $log): ?>
                  <div class="activity-item">
                    <div class="activity-icon user">
                      <i class="bi bi-person"></i>
                    </div>
                    <div class="activity-content">
                      <div class="activity-text">
                        <strong><?= htmlspecialchars($log['username']) ?></strong> <?= htmlspecialchars($log['action']) ?>
                      </div>
                      <div class="activity-time"><?= timeAgo($log['created_at']) ?></div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script>
    // User Growth Chart
    new Chart(document.getElementById('userGrowthChart'), {
      type: 'line',
      data: {
        labels: <?= json_encode(array_column($userGrowth, 'date')) ?>,
        datasets: [{
          label: 'Pengguna Baru',
          data: <?= json_encode(array_column($userGrowth, 'count')) ?>,
          borderColor: '#667eea',
          backgroundColor: 'rgba(102, 126, 234, 0.1)',
          fill: true,
          tension: 0.4
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: false }
        },
        scales: {
          y: { beginAtZero: true }
        }
      }
    });

    // Post Activity Chart
    new Chart(document.getElementById('postActivityChart'), {
      type: 'bar',
      data: {
        labels: <?= json_encode(array_column($postActivity, 'date')) ?>,
        datasets: [{
          label: 'Postingan',
          data: <?= json_encode(array_column($postActivity, 'count')) ?>,
          backgroundColor: '#10b981',
          borderRadius: 8
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: false }
        },
        scales: {
          y: { beginAtZero: true }
        }
      }
    });
  </script>
</body>

</html>