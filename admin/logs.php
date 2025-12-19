<?php
/**
 * Admin - Log Aktivitas (Activity Log)
 * SISMED Social Media Application
 */

require_once __DIR__ . '/../middleware/admin.php';

$currentUser = getCurrentUser();

// Pagination and filters
$page = intval($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';
$adminFilter = $_GET['admin'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Build query
$where = [];
$params = [];

if ($search) {
  $where[] = "(al.action LIKE ? OR al.description LIKE ?)";
  $params[] = "%$search%";
  $params[] = "%$search%";
}

if ($adminFilter) {
  $where[] = "al.admin_id = ?";
  $params[] = $adminFilter;
}

if ($dateFrom) {
  $where[] = "DATE(al.created_at) >= ?";
  $params[] = $dateFrom;
}

if ($dateTo) {
  $where[] = "DATE(al.created_at) <= ?";
  $params[] = $dateTo;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$stmt = db()->prepare("SELECT COUNT(*) FROM admin_logs al $whereClause");
$stmt->execute($params);
$totalLogs = $stmt->fetchColumn();
$totalPages = ceil($totalLogs / $limit);

// Get logs
$params[] = $limit;
$params[] = $offset;
$stmt = db()->prepare("
    SELECT al.*, u.username, u.avatar
    FROM admin_logs al
    JOIN users u ON al.admin_id = u.id
    $whereClause
    ORDER BY al.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get all admins for filter
$stmt = db()->query("SELECT id, username, full_name FROM users WHERE role = 'admin' ORDER BY username");
$admins = $stmt->fetchAll();

// Get action types for stats
$stmt = db()->query("
    SELECT action, COUNT(*) as count 
    FROM admin_logs 
    GROUP BY action 
    ORDER BY count DESC 
    LIMIT 5
");
$topActions = $stmt->fetchAll();

$pageTitle = 'Log Aktivitas - ' . APP_NAME;
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

        <a href="<?= BASE_URL ?>/admin/settings.php" class="admin-nav-item">
          <i class="bi bi-gear"></i>
          <span>Pengaturan</span>
        </a>
        <a href="<?= BASE_URL ?>/admin/logs.php" class="admin-nav-item active">
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
          <h1>Log Aktivitas</h1>
          <p style="color: var(--text-muted); font-size: 0.875rem;">Total: <?= number_format($totalLogs) ?> log</p>
        </div>
      </header>

      <div class="admin-content">
        <!-- Stats Cards -->
        <div class="stats-grid" style="margin-bottom: 24px;">
          <?php foreach (array_slice($topActions, 0, 4) as $action): ?>
            <div class="stat-card">
              <div class="stat-card-header">
                <div>
                  <div class="stat-card-value"><?= number_format($action['count']) ?></div>
                  <div class="stat-card-label"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $action['action']))) ?>
                  </div>
                </div>
                <div class="stat-card-icon purple">
                  <i class="bi bi-activity"></i>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Filters -->
        <div class="chart-card" style="margin-bottom: 24px;">
          <form method="GET" class="d-flex gap-3" style="flex-wrap: wrap; padding: 16px;">
            <div style="flex: 1; min-width: 200px;">
              <input type="text" name="search" class="form-control" placeholder="Cari action atau deskripsi..."
                value="<?= htmlspecialchars($search) ?>">
            </div>
            <select name="admin" class="form-control" style="width: 150px;">
              <option value="">Semua Admin</option>
              <?php foreach ($admins as $admin): ?>
                <option value="<?= $admin['id'] ?>" <?= $adminFilter == $admin['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($admin['username']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <input type="date" name="date_from" class="form-control" style="width: 150px;"
              value="<?= htmlspecialchars($dateFrom) ?>" placeholder="Dari tanggal">
            <input type="date" name="date_to" class="form-control" style="width: 150px;"
              value="<?= htmlspecialchars($dateTo) ?>" placeholder="Sampai tanggal">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-search"></i> Cari
            </button>
            <a href="<?= BASE_URL ?>/admin/logs.php" class="btn btn-secondary">Reset</a>
          </form>
        </div>

        <!-- Logs Table -->
        <div class="data-table-card">
          <table class="data-table">
            <thead>
              <tr>
                <th>Admin</th>
                <th>Aksi</th>
                <th>Deskripsi</th>
                <th>IP Address</th>
                <th>Waktu</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($logs)): ?>
                <tr>
                  <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);">
                    <i class="bi bi-journal-x" style="font-size: 2rem; display: block; margin-bottom: 8px;"></i>
                    Tidak ada log aktivitas
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($logs as $log): ?>
                  <tr>
                    <td>
                      <div class="user-cell">
                        <img src="<?= getAvatarUrl($log['avatar']) ?>" alt="">
                        <div>
                          <div style="font-weight: 500;">@<?= htmlspecialchars($log['username']) ?></div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <span class="action-badge <?= getActionClass($log['action']) ?>">
                        <?= htmlspecialchars($log['action']) ?>
                      </span>
                    </td>
                    <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                      <?= htmlspecialchars($log['description'] ?: '-') ?>
                    </td>
                    <td>
                      <code
                        style="font-size: 0.75rem; background: var(--bg-tertiary); padding: 2px 6px; border-radius: 4px;">
                            <?= htmlspecialchars($log['ip_address'] ?: '-') ?>
                          </code>
                    </td>
                    <td>
                      <div style="font-size: 0.875rem;"><?= date('d M Y', strtotime($log['created_at'])) ?></div>
                      <div style="font-size: 0.75rem; color: var(--text-muted);">
                        <?= date('H:i:s', strtotime($log['created_at'])) ?></div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
          <div class="d-flex justify-center gap-2" style="margin-top: 24px;">
            <?php if ($page > 1): ?>
              <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&admin=<?= $adminFilter ?>&date_from=<?= $dateFrom ?>&date_to=<?= $dateTo ?>"
                class="btn btn-secondary btn-sm">
                <i class="bi bi-chevron-left"></i>
              </a>
            <?php endif; ?>

            <?php
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);
            for ($i = $startPage; $i <= $endPage; $i++):
              ?>
              <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&admin=<?= $adminFilter ?>&date_from=<?= $dateFrom ?>&date_to=<?= $dateTo ?>"
                class="btn <?= $i === $page ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                <?= $i ?>
              </a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
              <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&admin=<?= $adminFilter ?>&date_from=<?= $dateFrom ?>&date_to=<?= $dateTo ?>"
                class="btn btn-secondary btn-sm">
                <i class="bi bi-chevron-right"></i>
              </a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </main>
  </div>

  <style>
    .action-badge {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 0.75rem;
      font-weight: 500;
      text-transform: uppercase;
    }

    .action-badge.delete {
      background: rgba(239, 68, 68, 0.1);
      color: #ef4444;
    }

    .action-badge.create,
    .action-badge.add {
      background: rgba(16, 185, 129, 0.1);
      color: #10b981;
    }

    .action-badge.update,
    .action-badge.edit {
      background: rgba(245, 158, 11, 0.1);
      color: #f59e0b;
    }

    .action-badge.login,
    .action-badge.view {
      background: rgba(102, 126, 234, 0.1);
      color: #667eea;
    }

    .action-badge.default {
      background: var(--bg-tertiary);
      color: var(--text-secondary);
    }
  </style>
</body>

</html>

<?php
function getActionClass($action)
{
  $action = strtolower($action);
  if (strpos($action, 'delete') !== false)
    return 'delete';
  if (strpos($action, 'create') !== false || strpos($action, 'add') !== false)
    return 'create';
  if (strpos($action, 'update') !== false || strpos($action, 'edit') !== false)
    return 'update';
  if (strpos($action, 'login') !== false || strpos($action, 'view') !== false)
    return 'login';
  return 'default';
}
?>