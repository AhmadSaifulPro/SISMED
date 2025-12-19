<?php
/**
 * Admin - Post Management
 * PulTech Social Media Application
 */

require_once __DIR__ . '/../middleware/admin.php';

$currentUser = getCurrentUser();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $postId = intval($_POST['post_id'] ?? 0);

  if ($postId > 0 && $action === 'delete') {
    // Get post to delete media
    $stmt = db()->prepare("SELECT media_url FROM posts WHERE id = ?");
    $stmt->execute([$postId]);
    $post = $stmt->fetch();

    if ($post && $post['media_url']) {
      deleteFile(POST_PATH . '/' . $post['media_url']);
    }

    $stmt = db()->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->execute([$postId]);
    logAdminAction('delete_post', "Deleted post ID: $postId");
    setFlash('success', 'Postingan berhasil dihapus');
    redirect(BASE_URL . '/admin/posts.php');
  }
}

// Pagination and filters
$page = intval($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';
$type = $_GET['type'] ?? '';

// Build query
$where = [];
$params = [];

if ($search) {
  $where[] = "(p.content LIKE ? OR u.username LIKE ?)";
  $params[] = "%$search%";
  $params[] = "%$search%";
}

if ($type) {
  $where[] = "p.media_type = ?";
  $params[] = $type;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$stmt = db()->prepare("SELECT COUNT(*) FROM posts p JOIN users u ON p.user_id = u.id $whereClause");
$stmt->execute($params);
$totalPosts = $stmt->fetchColumn();
$totalPages = ceil($totalPosts / $limit);

// Get posts
$params[] = $limit;
$params[] = $offset;
$stmt = db()->prepare("
    SELECT p.*, u.username, u.avatar,
           (SELECT COUNT(*) FROM likes WHERE likeable_type = 'post' AND likeable_id = p.id) as likes,
           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments
    FROM posts p
    JOIN users u ON p.user_id = u.id
    $whereClause
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute($params);
$posts = $stmt->fetchAll();

$flash = getFlash();
$pageTitle = 'Kelola Postingan - ' . APP_NAME;
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
        <a href="<?= BASE_URL ?>/admin/posts.php" class="admin-nav-item active">
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
          <h1>Kelola Postingan</h1>
          <p style="color: var(--text-muted); font-size: 0.875rem;">Total: <?= number_format($totalPosts) ?> postingan
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
              <input type="text" name="search" class="form-control" placeholder="Cari postingan..."
                value="<?= htmlspecialchars($search) ?>">
            </div>
            <select name="type" class="form-control" style="width: 150px;">
              <option value="">Semua Tipe</option>
              <option value="image" <?= $type === 'image' ? 'selected' : '' ?>>Gambar</option>
              <option value="video" <?= $type === 'video' ? 'selected' : '' ?>>Video</option>
              <option value="text" <?= $type === 'text' ? 'selected' : '' ?>>Teks</option>
            </select>
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-search"></i> Cari
            </button>
            <a href="<?= BASE_URL ?>/admin/posts.php" class="btn btn-secondary">Reset</a>
          </form>
        </div>

        <!-- Posts Grid -->
        <div class="posts-admin-grid">
          <?php foreach ($posts as $post): ?>
            <div class="post-admin-card">
              <div class="post-admin-media">
                <?php if ($post['media_type'] === 'video'): ?>
                  <video src="<?= POST_URL ?>/<?= $post['media_url'] ?>"></video>
                  <div class="media-type-badge"><i class="bi bi-play-fill"></i></div>
                <?php elseif ($post['media_url']): ?>
                  <img src="<?= POST_URL ?>/<?= $post['media_url'] ?>" alt="">
                <?php else: ?>
                  <div class="post-admin-text"><?= htmlspecialchars(substr($post['content'], 0, 100)) ?></div>
                <?php endif; ?>
              </div>
              <div class="post-admin-info">
                <div class="d-flex align-center gap-2 mb-2">
                  <img src="<?= getAvatarUrl($post['avatar']) ?>" alt="" class="avatar avatar-sm">
                  <span style="font-weight: 500;">@<?= htmlspecialchars($post['username']) ?></span>
                </div>
                <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 8px;">
                  <?= htmlspecialchars(substr($post['content'] ?? 'No caption', 0, 50)) ?>...
                </p>
                <div class="d-flex gap-3" style="font-size: 0.75rem; color: var(--text-muted);">
                  <span><i class="bi bi-heart"></i> <?= $post['likes'] ?></span>
                  <span><i class="bi bi-chat"></i> <?= $post['comments'] ?></span>
                  <span><?= timeAgo($post['created_at']) ?></span>
                </div>
              </div>
              <div class="post-admin-actions">
                <a href="<?= BASE_URL ?>/post.php?id=<?= $post['id'] ?>" class="btn btn-ghost btn-sm">
                  <i class="bi bi-eye"></i> Lihat
                </a>
                <form method="POST" style="display: inline;" onsubmit="return confirm('Hapus postingan ini?')">
                  <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                  <input type="hidden" name="action" value="delete">
                  <button type="submit" class="btn btn-ghost btn-sm" style="color: var(--error-color);">
                    <i class="bi bi-trash"></i> Hapus
                  </button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
          <div class="d-flex justify-center gap-2" style="margin-top: 24px;">
            <?php for ($i = 1; $i <= min($totalPages, 10); $i++): ?>
              <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&type=<?= $type ?>"
                class="btn <?= $i === $page ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                <?= $i ?>
              </a>
            <?php endfor; ?>
          </div>
        <?php endif; ?>
      </div>
    </main>
  </div>

  <style>
    .posts-admin-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 20px;
    }

    .post-admin-card {
      background: var(--bg-secondary);
      border-radius: 12px;
      overflow: hidden;
      border: 1px solid var(--border-color);
    }

    .post-admin-media {
      aspect-ratio: 1;
      background: var(--bg-tertiary);
      position: relative;
    }

    .post-admin-media img,
    .post-admin-media video {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .post-admin-text {
      padding: 16px;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      background: var(--primary-gradient);
      color: white;
    }

    .media-type-badge {
      position: absolute;
      top: 8px;
      right: 8px;
      background: rgba(0, 0, 0, 0.6);
      color: white;
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 0.75rem;
    }

    .post-admin-info {
      padding: 16px;
    }

    .post-admin-actions {
      padding: 12px 16px;
      border-top: 1px solid var(--border-color);
      display: flex;
      gap: 8px;
    }
  </style>
</body>

</html>