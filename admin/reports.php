<?php
/**
 * Admin - Laporan (Reports)
 * SISMED Social Media Application
 */

require_once __DIR__ . '/../middleware/admin.php';

$currentUser = getCurrentUser();

// Date range filter
$period = $_GET['period'] ?? '7';
$startDate = date('Y-m-d', strtotime("-{$period} days"));
$endDate = date('Y-m-d');

// Total Users
$stmt = db()->query("SELECT COUNT(*) FROM users WHERE is_active = 1");
$totalUsers = $stmt->fetchColumn();

$stmt = db()->prepare("SELECT COUNT(*) FROM users WHERE DATE(created_at) >= ?");
$stmt->execute([$startDate]);
$newUsers = $stmt->fetchColumn();

// Total Posts
$stmt = db()->query("SELECT COUNT(*) FROM posts");
$totalPosts = $stmt->fetchColumn();

$stmt = db()->prepare("SELECT COUNT(*) FROM posts WHERE DATE(created_at) >= ?");
$stmt->execute([$startDate]);
$newPosts = $stmt->fetchColumn();

// Total Messages
$stmt = db()->query("SELECT COUNT(*) FROM messages");
$totalMessages = $stmt->fetchColumn();

$stmt = db()->prepare("SELECT COUNT(*) FROM messages WHERE DATE(created_at) >= ?");
$stmt->execute([$startDate]);
$newMessages = $stmt->fetchColumn();

// Total Stories
$stmt = db()->query("SELECT COUNT(*) FROM stories");
$totalStories = $stmt->fetchColumn();

// Total Comments
$stmt = db()->query("SELECT COUNT(*) FROM comments");
$totalComments = $stmt->fetchColumn();

// Total Likes
$stmt = db()->query("SELECT COUNT(*) FROM likes");
$totalLikes = $stmt->fetchColumn();

// User growth data
$userGrowth = [];
for ($i = intval($period) - 1; $i >= 0; $i--) {
  $date = date('Y-m-d', strtotime("-$i days"));
  $stmt = db()->prepare("SELECT COUNT(*) FROM users WHERE DATE(created_at) = ?");
  $stmt->execute([$date]);
  $userGrowth[] = [
    'date' => date('d M', strtotime($date)),
    'count' => $stmt->fetchColumn()
  ];
}

// Post activity data
$postActivity = [];
for ($i = intval($period) - 1; $i >= 0; $i--) {
  $date = date('Y-m-d', strtotime("-$i days"));
  $stmt = db()->prepare("SELECT COUNT(*) FROM posts WHERE DATE(created_at) = ?");
  $stmt->execute([$date]);
  $postActivity[] = [
    'date' => date('d M', strtotime($date)),
    'count' => $stmt->fetchColumn()
  ];
}

// Top Users by Posts
$stmt = db()->query("
    SELECT u.id, u.username, u.avatar, u.full_name, COUNT(p.id) as post_count
    FROM users u
    LEFT JOIN posts p ON u.id = p.user_id
    WHERE u.is_active = 1
    GROUP BY u.id
    ORDER BY post_count DESC
    LIMIT 5
");
$topUsersByPosts = $stmt->fetchAll();

// Top Users by Followers
$stmt = db()->query("
    SELECT u.id, u.username, u.avatar, u.full_name, COUNT(f.id) as follower_count
    FROM users u
    LEFT JOIN follows f ON u.id = f.following_id
    WHERE u.is_active = 1
    GROUP BY u.id
    ORDER BY follower_count DESC
    LIMIT 5
");
$topUsersByFollowers = $stmt->fetchAll();

// Content Distribution
$stmt = db()->query("
    SELECT media_type, COUNT(*) as count 
    FROM posts 
    GROUP BY media_type
");
$contentDistribution = $stmt->fetchAll();

$pageTitle = 'Laporan - ' . APP_NAME;
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
        <a href="<?= BASE_URL ?>/admin/reports.php" class="admin-nav-item active">
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
          <h1>Laporan</h1>
          <p style="color: var(--text-muted); font-size: 0.875rem;">Statistik dan analisis platform</p>
        </div>
        <div class="admin-header-actions">
          <form method="GET" class="d-flex gap-2">
            <select name="period" class="form-control" style="width: auto;" onchange="this.form.submit()">
              <option value="7" <?= $period == '7' ? 'selected' : '' ?>>7 Hari Terakhir</option>
              <option value="30" <?= $period == '30' ? 'selected' : '' ?>>30 Hari Terakhir</option>
              <option value="90" <?= $period == '90' ? 'selected' : '' ?>>90 Hari Terakhir</option>
            </select>
          </form>
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
              <i class="bi bi-arrow-up"></i> +<?= $newUsers ?> dalam <?= $period ?> hari
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
              <i class="bi bi-arrow-up"></i> +<?= $newPosts ?> dalam <?= $period ?> hari
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
            <div class="stat-card-change positive">
              <i class="bi bi-arrow-up"></i> +<?= $newMessages ?> dalam <?= $period ?> hari
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-card-header">
              <div>
                <div class="stat-card-value"><?= number_format($totalStories) ?></div>
                <div class="stat-card-label">Total Stories</div>
              </div>
              <div class="stat-card-icon orange">
                <i class="bi bi-circle"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- Secondary Stats -->
        <div class="stats-grid" style="margin-top: 16px;">
          <div class="stat-card">
            <div class="stat-card-header">
              <div>
                <div class="stat-card-value"><?= number_format($totalComments) ?></div>
                <div class="stat-card-label">Total Komentar</div>
              </div>
              <div class="stat-card-icon" style="background: rgba(236, 72, 153, 0.1); color: #ec4899;">
                <i class="bi bi-chat-left-text"></i>
              </div>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-card-header">
              <div>
                <div class="stat-card-value"><?= number_format($totalLikes) ?></div>
                <div class="stat-card-label">Total Likes</div>
              </div>
              <div class="stat-card-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                <i class="bi bi-heart"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- Charts -->
        <div class="admin-grid" style="margin-top: 24px;">
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

        <!-- Content Distribution & Top Users -->
        <div class="admin-grid" style="margin-top: 24px;">
          <div class="chart-card">
            <div class="chart-header">
              <h3 class="chart-title">Distribusi Konten</h3>
            </div>
            <canvas id="contentChart" height="200"></canvas>
          </div>

          <div class="data-table-card">
            <div class="data-table-header">
              <h3 style="font-weight: 600;">Top Pengguna (Postingan)</h3>
            </div>
            <table class="data-table">
              <thead>
                <tr>
                  <th>Pengguna</th>
                  <th>Postingan</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($topUsersByPosts as $user): ?>
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
                    <td><span class="status-badge active"><?= $user['post_count'] ?></span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Top Users by Followers -->
        <div class="admin-grid" style="margin-top: 24px;">
          <div class="data-table-card">
            <div class="data-table-header">
              <h3 style="font-weight: 600;">Top Pengguna (Followers)</h3>
            </div>
            <table class="data-table">
              <thead>
                <tr>
                  <th>Pengguna</th>
                  <th>Followers</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($topUsersByFollowers as $user): ?>
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
                    <td><span class="status-badge"
                        style="background: rgba(102, 126, 234, 0.1); color: #667eea;"><?= $user['follower_count'] ?></span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div class="chart-card">
            <div class="chart-header">
              <h3 class="chart-title">Ringkasan Periode</h3>
            </div>
            <div class="activity-list">
              <div class="activity-item">
                <div class="activity-icon" style="background: rgba(102, 126, 234, 0.1); color: #667eea;">
                  <i class="bi bi-calendar-range"></i>
                </div>
                <div class="activity-content">
                  <div class="activity-text">Periode Laporan</div>
                  <div class="activity-time"><?= date('d M Y', strtotime($startDate)) ?> -
                    <?= date('d M Y', strtotime($endDate)) ?></div>
                </div>
              </div>
              <div class="activity-item">
                <div class="activity-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                  <i class="bi bi-graph-up-arrow"></i>
                </div>
                <div class="activity-content">
                  <div class="activity-text">Rata-rata Post/Hari</div>
                  <div class="activity-time"><?= round($newPosts / max($period, 1), 1) ?> postingan</div>
                </div>
              </div>
              <div class="activity-item">
                <div class="activity-icon" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                  <i class="bi bi-person-plus"></i>
                </div>
                <div class="activity-content">
                  <div class="activity-text">Rata-rata User Baru/Hari</div>
                  <div class="activity-time"><?= round($newUsers / max($period, 1), 1) ?> pengguna</div>
                </div>
              </div>
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

    // Content Distribution Chart
    new Chart(document.getElementById('contentChart'), {
      type: 'doughnut',
      data: {
        labels: <?= json_encode(array_column($contentDistribution, 'media_type')) ?>,
        datasets: [{
          data: <?= json_encode(array_column($contentDistribution, 'count')) ?>,
          backgroundColor: ['#667eea', '#10b981', '#f59e0b', '#ef4444'],
          borderWidth: 0
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'bottom'
          }
        }
      }
    });
  </script>
</body>

</html>