<?php
/**
 * Notifications Page
 * PulTech Social Media Application
 */

require_once __DIR__ . '/../middleware/auth.php';

$currentUser = getCurrentUser();

// Mark all as read if requested
if (isset($_GET['mark_read'])) {
  $stmt = db()->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
  $stmt->execute([$currentUser['id']]);
  redirect(BASE_URL . '/user/notifications.php');
}

// Get notifications
$stmt = db()->prepare("
    SELECT n.*, u.username, u.avatar, u.full_name
    FROM notifications n
    LEFT JOIN users u ON n.from_user_id = u.id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
    LIMIT 50
");
$stmt->execute([$currentUser['id']]);
$notifications = $stmt->fetchAll();

$pageTitle = 'Notifikasi - ' . APP_NAME;
$currentPage = 'notifications';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="dashboard-main">
  <div class="dashboard-content" style="max-width: 600px;">
    <div class="d-flex justify-between align-center mb-4">
      <h1>Notifikasi</h1>
      <?php if (!empty($notifications)): ?>
        <a href="?mark_read=1" class="btn btn-secondary btn-sm">
          <i class="bi bi-check-all"></i> Tandai Semua Dibaca
        </a>
      <?php endif; ?>
    </div>

    <div class="card">
      <?php if (empty($notifications)): ?>
        <div class="empty-state">
          <i class="bi bi-bell empty-state-icon"></i>
          <h3 class="empty-state-title">Belum ada notifikasi</h3>
          <p class="empty-state-text">Kami akan memberitahu Anda saat ada aktivitas baru</p>
        </div>
      <?php else: ?>
        <?php foreach ($notifications as $notif): ?>
          <div class="notification-item <?= !$notif['is_read'] ? 'unread' : '' ?>">
            <?php if ($notif['from_user_id']): ?>
              <a href="<?= BASE_URL ?>/user/profile.php?id=<?= $notif['from_user_id'] ?>">
                <img src="<?= getAvatarUrl($notif['avatar']) ?>" alt="" class="avatar">
              </a>
            <?php else: ?>
              <div class="avatar"
                style="background: var(--primary-gradient); display: flex; align-items: center; justify-content: center; color: white;">
                <i class="bi bi-bell"></i>
              </div>
            <?php endif; ?>

            <div class="notification-content">
              <p class="notification-text">
                <?php if ($notif['from_user_id']): ?>
                  <a href="<?= BASE_URL ?>/user/profile.php?id=<?= $notif['from_user_id'] ?>">
                    <strong><?= htmlspecialchars($notif['full_name'] ?: $notif['username']) ?></strong>
                  </a>
                <?php endif; ?>

                <?php
                switch ($notif['type']) {
                  case 'like':
                    echo 'menyukai ';
                    if ($notif['reference_type'] === 'post') {
                      echo '<a href="' . BASE_URL . '/post.php?id=' . $notif['reference_id'] . '">postingan Anda</a>';
                    } elseif ($notif['reference_type'] === 'story') {
                      echo 'story Anda';
                    } else {
                      echo 'komentar Anda';
                    }
                    break;
                  case 'comment':
                    echo 'mengomentari <a href="' . BASE_URL . '/post.php?id=' . $notif['reference_id'] . '">postingan Anda</a>';
                    break;
                  case 'follow':
                    echo 'mulai mengikuti Anda';
                    break;
                  case 'mention':
                    echo 'menyebut Anda dalam sebuah postingan';
                    break;
                  case 'message':
                    echo 'mengirim Anda pesan';
                    break;
                  default:
                    echo htmlspecialchars($notif['message']);
                }
                ?>
              </p>
              <span class="notification-time"><?= timeAgo($notif['created_at']) ?></span>
            </div>

            <?php if (!$notif['is_read']): ?>
              <div style="width: 8px; height: 8px; background: var(--primary-color); border-radius: 50%; flex-shrink: 0;">
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>