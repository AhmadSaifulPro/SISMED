<?php
/**
 * Admin - User Management
 * PulTech Social Media Application
 */

require_once __DIR__ . '/../middleware/admin.php';

$currentUser = getCurrentUser();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $userId = intval($_POST['user_id'] ?? 0);

  if ($userId > 0) {
    switch ($action) {
      case 'toggle_status':
        $stmt = db()->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$userId]);
        logAdminAction('toggle_user_status', "Toggled status for user ID: $userId");
        setFlash('success', 'Status pengguna berhasil diubah');
        break;

      case 'make_admin':
        $stmt = db()->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
        $stmt->execute([$userId]);
        logAdminAction('make_admin', "Made user ID: $userId an admin");
        setFlash('success', 'Pengguna berhasil dijadikan admin');
        break;

      case 'remove_admin':
        $stmt = db()->prepare("UPDATE users SET role = 'user' WHERE id = ?");
        $stmt->execute([$userId]);
        logAdminAction('remove_admin', "Removed admin role from user ID: $userId");
        setFlash('success', 'Role admin berhasil dihapus');
        break;

      case 'delete':
        $stmt = db()->prepare("DELETE FROM users WHERE id = ? AND id != ?");
        $stmt->execute([$userId, $currentUser['id']]);
        logAdminAction('delete_user', "Deleted user ID: $userId");
        setFlash('success', 'Pengguna berhasil dihapus');
        break;
    }
    redirect(BASE_URL . '/admin/users.php');
  }
}

// Pagination and filters
$page = intval($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';
$role = $_GET['role'] ?? '';
$status = $_GET['status'] ?? '';

// Build query
$where = [];
$params = [];

if ($search) {
  $where[] = "(username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
  $params[] = "%$search%";
  $params[] = "%$search%";
  $params[] = "%$search%";
}

if ($role) {
  $where[] = "role = ?";
  $params[] = $role;
}

if ($status !== '') {
  $where[] = "is_active = ?";
  $params[] = $status;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$stmt = db()->prepare("SELECT COUNT(*) FROM users $whereClause");
$stmt->execute($params);
$totalUsers = $stmt->fetchColumn();
$totalPages = ceil($totalUsers / $limit);

// Get users
$params[] = $limit;
$params[] = $offset;
$stmt = db()->prepare("SELECT * FROM users $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->execute($params);
$users = $stmt->fetchAll();

$flash = getFlash();
$pageTitle = 'Kelola Pengguna - ' . APP_NAME;
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
        <a href="<?= BASE_URL ?>/admin/users.php" class="admin-nav-item active">
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
          <h1>Kelola Pengguna</h1>
          <p style="color: var(--text-muted); font-size: 0.875rem;">Total: <?= number_format($totalUsers) ?> pengguna
          </p>
        </div>
      </header>

      <div class="admin-content">
        <?php if ($flash): ?>
          <div class="auth-alert <?= $flash['type'] ?>" style="margin-bottom: 24px;">
            <i class="bi bi-<?= $flash['type'] === 'success' ? 'check' : 'exclamation' ?>-circle"></i>
            <span><?= $flash['message'] ?></span>
          </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="chart-card" style="margin-bottom: 24px;">
          <form method="GET" class="d-flex gap-3" style="flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px;">
              <input type="text" name="search" class="form-control" placeholder="Cari pengguna..."
                value="<?= htmlspecialchars($search) ?>">
            </div>
            <select name="role" class="form-control" style="width: 150px;">
              <option value="">Semua Role</option>
              <option value="user" <?= $role === 'user' ? 'selected' : '' ?>>User</option>
              <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
            <select name="status" class="form-control" style="width: 150px;">
              <option value="">Semua Status</option>
              <option value="1" <?= $status === '1' ? 'selected' : '' ?>>Aktif</option>
              <option value="0" <?= $status === '0' ? 'selected' : '' ?>>Nonaktif</option>
            </select>
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-search"></i> Cari
            </button>
            <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-secondary">Reset</a>
          </form>
        </div>

        <!-- Users Table -->
        <div class="data-table-card">
          <table class="data-table">
            <thead>
              <tr>
                <th>Pengguna</th>
                <th>Email</th>
                <th>Role</th>
                <th>Tanggal Daftar</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $user): ?>
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
                  <td><?= htmlspecialchars($user['email']) ?></td>
                  <td>
                    <span class="status-badge <?= $user['role'] === 'admin' ? 'active' : '' ?>">
                      <?= ucfirst($user['role']) ?>
                    </span>
                  </td>
                  <td><?= formatDate($user['created_at']) ?></td>
                  <td>
                    <span class="status-badge <?= $user['is_active'] ? 'active' : 'inactive' ?>">
                      <?= $user['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                    </span>
                  </td>
                  <td>
                    <div class="table-actions">
                      <a href="<?= BASE_URL ?>/user/profile.php?id=<?= $user['id'] ?>" class="table-action"
                        title="Lihat Profil">
                        <i class="bi bi-eye"></i>
                      </a>

                      <?php if ($user['id'] !== $currentUser['id']): ?>
                        <form method="POST" style="display: inline;">
                          <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                          <input type="hidden" name="action" value="toggle_status">
                          <button type="submit" class="table-action"
                            title="<?= $user['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>">
                            <i class="bi bi-<?= $user['is_active'] ? 'x-circle' : 'check-circle' ?>"></i>
                          </button>
                        </form>

                        <form method="POST" style="display: inline;">
                          <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                          <input type="hidden" name="action"
                            value="<?= $user['role'] === 'admin' ? 'remove_admin' : 'make_admin' ?>">
                          <button type="submit" class="table-action"
                            title="<?= $user['role'] === 'admin' ? 'Hapus Admin' : 'Jadikan Admin' ?>">
                            <i class="bi bi-<?= $user['role'] === 'admin' ? 'person-dash' : 'person-plus' ?>"></i>
                          </button>
                        </form>

                        <form method="POST" style="display: inline;" onsubmit="return confirm('Hapus pengguna ini?')">
                          <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                          <input type="hidden" name="action" value="delete">
                          <button type="submit" class="table-action" title="Hapus" style="color: var(--error-color);">
                            <i class="bi bi-trash"></i>
                          </button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
          <div class="d-flex justify-center gap-2" style="margin-top: 24px;">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&role=<?= $role ?>&status=<?= $status ?>"
                class="btn <?= $i === $page ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                <?= $i ?>
              </a>
            <?php endfor; ?>
          </div>
        <?php endif; ?>
      </div>
    </main>
  </div>
</body>

</html>