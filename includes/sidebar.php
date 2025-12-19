<?php
/**
 * Sidebar Navigation Component
 * PulTech Social Media Application
 */

$currentUser = getCurrentUser();
$unreadNotifications = 0;
$unreadMessages = 0;

if ($currentUser) {
  // Count unread notifications
  $stmt = db()->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
  $stmt->execute([$currentUser['id']]);
  $unreadNotifications = $stmt->fetchColumn();

  // Count unread messages
  $stmt = db()->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
  $stmt->execute([$currentUser['id']]);
  $unreadMessages = $stmt->fetchColumn();
}
?>
<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <a href="<?= BASE_URL ?>/user/index.php" class="sidebar-logo">
      <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="SISMED" style="height: 40px; width: auto;">
    </a>
  </div>

  <nav class="sidebar-nav">
    <a href="<?= BASE_URL ?>/user/index.php" class="nav-item <?= ($currentPage ?? '') === 'home' ? 'active' : '' ?>">
      <i class="bi bi-house<?= ($currentPage ?? '') === 'home' ? '-fill' : '' ?>"></i>
      <span>Beranda</span>
    </a>

    <a href="<?= BASE_URL ?>/user/explore.php"
      class="nav-item <?= ($currentPage ?? '') === 'explore' ? 'active' : '' ?>">
      <i class="bi bi-compass<?= ($currentPage ?? '') === 'explore' ? '-fill' : '' ?>"></i>
      <span>Jelajahi</span>
    </a>

    <a href="<?= BASE_URL ?>/user/messages.php"
      class="nav-item <?= ($currentPage ?? '') === 'messages' ? 'active' : '' ?>">
      <i class="bi bi-chat-dots<?= ($currentPage ?? '') === 'messages' ? '-fill' : '' ?>"></i>
      <span>Pesan</span>
      <?php if ($unreadMessages > 0): ?>
        <span class="badge badge-error"
          style="margin-left: auto;"><?= $unreadMessages > 99 ? '99+' : $unreadMessages ?></span>
      <?php endif; ?>
    </a>

    <a href="<?= BASE_URL ?>/user/notifications.php"
      class="nav-item <?= ($currentPage ?? '') === 'notifications' ? 'active' : '' ?>">
      <i class="bi bi-heart<?= ($currentPage ?? '') === 'notifications' ? '-fill' : '' ?>"></i>
      <span>Notifikasi</span>
      <?php if ($unreadNotifications > 0): ?>
        <span class="badge badge-error"
          style="margin-left: auto;"><?= $unreadNotifications > 99 ? '99+' : $unreadNotifications ?></span>
      <?php endif; ?>
    </a>

    <a href="#" class="nav-item" onclick="openCreatePostModal(); return false;">
      <i class="bi bi-plus-square"></i>
      <span>Buat Postingan</span>
    </a>

    <a href="<?= BASE_URL ?>/user/profile.php"
      class="nav-item <?= ($currentPage ?? '') === 'profile' ? 'active' : '' ?>">
      <i class="bi bi-person<?= ($currentPage ?? '') === 'profile' ? '-fill' : '' ?>"></i>
      <span>Profil</span>
    </a>

    <?php if (isAdmin()): ?>
      <div
        style="margin-top: 20px; padding: 10px 20px; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">
        Admin</div>
      <a href="<?= BASE_URL ?>/admin/index.php" class="nav-item">
        <i class="bi bi-speedometer2"></i>
        <span>Dashboard Admin</span>
      </a>
    <?php endif; ?>
  </nav>

  <div class="sidebar-footer">
    <div class="dropdown" id="userDropdown">
      <div class="nav-item" onclick="toggleDropdown('userDropdown', event)" style="cursor: pointer;">
        <img src="<?= getAvatarUrl($currentUser['avatar'] ?? '') ?>" alt="Avatar" class="avatar avatar-sm">
        <span style="flex: 1;"><?= htmlspecialchars($currentUser['username'] ?? 'User') ?></span>
        <i class="bi bi-three-dots"></i>
      </div>
      <div class="dropdown-menu">
        <a href="<?= BASE_URL ?>/user/settings.php" class="dropdown-item">
          <i class="bi bi-gear"></i>
          <span>Pengaturan</span>
        </a>
        <div class="dropdown-divider"></div>
        <a href="<?= BASE_URL ?>/auth/logout.php" class="dropdown-item" style="color: var(--error-color);">
          <i class="bi bi-box-arrow-right"></i>
          <span>Keluar</span>
        </a>
      </div>
    </div>
  </div>
</aside>

<!-- Sidebar Overlay for Mobile -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>