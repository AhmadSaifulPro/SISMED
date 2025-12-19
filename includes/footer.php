<!-- Mobile Navigation -->
<nav class="mobile-nav">
  <div class="mobile-nav-items">
    <a href="<?= BASE_URL ?>/user/index.php"
      class="mobile-nav-item <?= ($currentPage ?? '') === 'home' ? 'active' : '' ?>">
      <i class="bi bi-house"></i>
      <span>Home</span>
    </a>
    <a href="<?= BASE_URL ?>/user/explore.php"
      class="mobile-nav-item <?= ($currentPage ?? '') === 'explore' ? 'active' : '' ?>">
      <i class="bi bi-search"></i>
      <span>Explore</span>
    </a>
    <a href="<?= BASE_URL ?>/user/messages.php"
      class="mobile-nav-item <?= ($currentPage ?? '') === 'messages' ? 'active' : '' ?>">
      <i class="bi bi-chat"></i>
      <span>Pesan</span>
    </a>
    <a href="<?= BASE_URL ?>/user/notifications.php"
      class="mobile-nav-item <?= ($currentPage ?? '') === 'notifications' ? 'active' : '' ?>">
      <i class="bi bi-heart"></i>
      <span>Notifikasi</span>
    </a>
    <div class="mobile-nav-item mobile-menu-trigger" onclick="toggleMobileMenu()">
      <i class="bi bi-three-dots"></i>
      <span>Lainnya</span>
    </div>
  </div>

  <!-- Mobile More Menu -->
  <div class="mobile-more-menu" id="mobileMoreMenu">
    <a href="<?= BASE_URL ?>/user/profile.php" class="mobile-menu-item">
      <i class="bi bi-person"></i> Profil Saya
    </a>
    <a href="<?= BASE_URL ?>/user/settings.php" class="mobile-menu-item">
      <i class="bi bi-gear"></i> Pengaturan
    </a>
    <a href="<?= BASE_URL ?>/user/edit-profile.php" class="mobile-menu-item">
      <i class="bi bi-pencil"></i> Edit Profil
    </a>
    <div class="mobile-menu-divider"></div>
    <a href="<?= BASE_URL ?>/auth/logout.php" class="mobile-menu-item mobile-menu-logout">
      <i class="bi bi-box-arrow-right"></i> Logout
    </a>
  </div>
</nav>

<script>
  function toggleMobileMenu() {
    const menu = document.getElementById('mobileMoreMenu');
    menu.classList.toggle('show');
  }

  // Close menu when clicking outside
  document.addEventListener('click', function (e) {
    const menu = document.getElementById('mobileMoreMenu');
    const trigger = document.querySelector('.mobile-menu-trigger');
    if (menu && trigger && !menu.contains(e.target) && !trigger.contains(e.target)) {
      menu.classList.remove('show');
    }
  });
</script>

<!-- Toast Container -->
<div id="toastContainer" class="toast-container"></div>

<!-- Scripts -->
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>

<?php if (isset($extraJs)): ?>
  <?php foreach ($extraJs as $js): ?>
    <script src="<?= $js ?>"></script>
  <?php endforeach; ?>
<?php endif; ?>
</body>

</html>