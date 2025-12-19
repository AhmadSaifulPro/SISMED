<?php
/**
 * User Profile Page
 * PulTech Social Media Application
 */

require_once __DIR__ . '/../middleware/auth.php';

$currentUser = getCurrentUser();
$profileId = intval($_GET['id'] ?? $currentUser['id']);

// Get profile user
$stmt = db()->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1");
$stmt->execute([$profileId]);
$profileUser = $stmt->fetch();

if (!$profileUser) {
  setFlash('error', 'Pengguna tidak ditemukan');
  redirect(BASE_URL . '/user/index.php');
}

$isOwnProfile = $profileId === $currentUser['id'];

// Check if following
$isFollowing = false;
if (!$isOwnProfile) {
  $stmt = db()->prepare("SELECT id FROM follows WHERE follower_id = ? AND following_id = ?");
  $stmt->execute([$currentUser['id'], $profileId]);
  $isFollowing = $stmt->fetch() ? true : false;
}

// Get stats
$stmt = db()->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
$stmt->execute([$profileId]);
$postsCount = $stmt->fetchColumn();

$stmt = db()->prepare("SELECT COUNT(*) FROM follows WHERE following_id = ?");
$stmt->execute([$profileId]);
$followersCount = $stmt->fetchColumn();

$stmt = db()->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ?");
$stmt->execute([$profileId]);
$followingCount = $stmt->fetchColumn();

// Get user posts
$stmt = db()->prepare("
    SELECT p.*, 
           (SELECT COUNT(*) FROM likes WHERE likeable_type = 'post' AND likeable_id = p.id) as likes_count,
           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count
    FROM posts p
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
    LIMIT 20
");
$stmt->execute([$profileId]);
$posts = $stmt->fetchAll();

$pageTitle = ($profileUser['full_name'] ?: $profileUser['username']) . ' - ' . APP_NAME;
$currentPage = 'profile';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="dashboard-main">
  <div class="dashboard-content" style="max-width: 900px;">
    <!-- Profile Header -->
    <div class="profile-header">
      <div class="profile-cover">
        <?php if ($profileUser['cover_photo']): ?>
          <img src="<?= AVATAR_URL ?>/<?= $profileUser['cover_photo'] ?>" alt="Cover">
        <?php endif; ?>
      </div>
      <div class="profile-info">
        <div class="profile-avatar">
          <img src="<?= getAvatarUrl($profileUser['avatar']) ?>"
            alt="<?= htmlspecialchars($profileUser['username']) ?>">
        </div>

        <div class="d-flex justify-between align-center" style="margin-bottom: 16px;">
          <div>
            <h1 class="profile-name"><?= htmlspecialchars($profileUser['full_name'] ?: $profileUser['username']) ?></h1>
            <span class="profile-username">@<?= htmlspecialchars($profileUser['username']) ?></span>
          </div>

          <div class="profile-actions">
            <?php if ($isOwnProfile): ?>
              <a href="<?= BASE_URL ?>/user/edit-profile.php" class="btn btn-secondary">
                <i class="bi bi-pencil"></i> Edit Profil
              </a>
            <?php else: ?>
              <button class="btn <?= $isFollowing ? 'btn-secondary' : 'btn-primary' ?>"
                onclick="followUser(<?= $profileId ?>, this)">
                <?= $isFollowing ? 'Mengikuti' : 'Ikuti' ?>
              </button>
              <a href="<?= BASE_URL ?>/user/messages.php?user_id=<?= $profileId ?>" class="btn btn-secondary">
                <i class="bi bi-chat"></i> Pesan
              </a>
            <?php endif; ?>
          </div>
        </div>

        <?php if ($profileUser['bio']): ?>
          <p class="profile-bio"><?= nl2br(htmlspecialchars($profileUser['bio'])) ?></p>
        <?php endif; ?>

        <div class="profile-stats">
          <div class="profile-stat">
            <span class="profile-stat-value"><?= formatNumber($postsCount) ?></span>
            <span class="profile-stat-label">Postingan</span>
          </div>
          <div class="profile-stat" style="cursor: pointer;" onclick="showFollowersModal()">
            <span class="profile-stat-value"><?= formatNumber($followersCount) ?></span>
            <span class="profile-stat-label">Pengikut</span>
          </div>
          <div class="profile-stat" style="cursor: pointer;" onclick="showFollowingModal()">
            <span class="profile-stat-value"><?= formatNumber($followingCount) ?></span>
            <span class="profile-stat-label">Mengikuti</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Profile Tabs -->
    <div class="card" style="margin-bottom: 24px;">
      <div class="d-flex" style="border-bottom: 1px solid var(--border-color);">
        <button class="btn btn-ghost profile-tab active" data-tab="posts" style="flex: 1; border-radius: 0;">
          <i class="bi bi-grid-3x3"></i> Postingan
        </button>
        <button class="btn btn-ghost profile-tab" data-tab="media" style="flex: 1; border-radius: 0;">
          <i class="bi bi-play-btn"></i> Media
        </button>
        <button class="btn btn-ghost profile-tab" data-tab="liked" style="flex: 1; border-radius: 0;">
          <i class="bi bi-heart"></i> Disukai
        </button>
      </div>
    </div>

    <!-- Posts Grid -->
    <div class="posts-grid" id="postsGrid">
      <?php if (empty($posts)): ?>
        <div class="empty-state" style="grid-column: 1 / -1;">
          <i class="bi bi-camera empty-state-icon"></i>
          <h3 class="empty-state-title">Belum ada postingan</h3>
          <?php if ($isOwnProfile): ?>
            <p class="empty-state-text">Bagikan momen pertamamu!</p>
            <button class="btn btn-primary" onclick="openCreatePostModal()">
              <i class="bi bi-plus"></i> Buat Postingan
            </button>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <?php foreach ($posts as $post): ?>
          <div class="post-grid-item" onclick="openPostModal(<?= $post['id'] ?>)">
            <?php if ($post['media_type'] === 'video'): ?>
              <video src="<?= POST_URL ?>/<?= $post['media_url'] ?>"></video>
              <div class="post-grid-overlay">
                <i class="bi bi-play-fill"></i>
              </div>
            <?php elseif ($post['media_url']): ?>
              <img src="<?= POST_URL ?>/<?= $post['media_url'] ?>" alt="Post">
            <?php else: ?>
              <div class="post-grid-text">
                <?= htmlspecialchars(substr($post['content'], 0, 100)) ?>
              </div>
            <?php endif; ?>
            <div class="post-grid-stats">
              <span><i class="bi bi-heart-fill"></i> <?= formatNumber($post['likes_count']) ?></span>
              <span><i class="bi bi-chat-fill"></i> <?= formatNumber($post['comments_count']) ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</main>

<style>
  .posts-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 4px;
  }

  .post-grid-item {
    aspect-ratio: 1;
    position: relative;
    cursor: pointer;
    overflow: hidden;
    background: var(--bg-tertiary);
  }

  .post-grid-item img,
  .post-grid-item video {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .post-grid-text {
    padding: 16px;
    font-size: 0.875rem;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    text-align: center;
    background: var(--primary-gradient);
    color: white;
  }

  .post-grid-overlay {
    position: absolute;
    top: 8px;
    right: 8px;
    color: white;
    font-size: 1.25rem;
  }

  .post-grid-stats {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(transparent, rgba(0, 0, 0, 0.7));
    padding: 24px 8px 8px;
    display: flex;
    justify-content: center;
    gap: 16px;
    color: white;
    font-size: 0.875rem;
    opacity: 0;
    transition: opacity 0.3s;
  }

  .post-grid-item:hover .post-grid-stats {
    opacity: 1;
  }

  .profile-tab.active {
    border-bottom: 2px solid var(--primary-color);
    color: var(--primary-color);
  }

  @media (max-width: 576px) {
    .posts-grid {
      grid-template-columns: repeat(3, 1fr);
      gap: 2px;
    }
  }
</style>

<script>
  // Tab switching
  document.querySelectorAll('.profile-tab').forEach(tab => {
    tab.addEventListener('click', function () {
      document.querySelectorAll('.profile-tab').forEach(t => t.classList.remove('active'));
      this.classList.add('active');
      // TODO: Load different content based on tab
    });
  });

  // Open post modal - use global BASE_URL from app.js
  function openPostModal(postId) {
    window.location.href = `${BASE_URL}/post.php?id=${postId}`;
  }

  function showFollowersModal() {
    // TODO: Show followers modal
  }

  function showFollowingModal() {
    // TODO: Show following modal
  }
</script>

<!-- Create Post Modal -->
<div class="modal-backdrop" id="createPostModal">
  <div class="modal-content" style="max-width: 560px;">
    <div class="modal-header">
      <h3 class="modal-title">Buat Postingan</h3>
      <button type="button" class="modal-close" onclick="closeModal('createPostModal')">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>
    <form id="createPostForm" enctype="multipart/form-data">
      <div class="modal-body">
        <div class="d-flex gap-3 mb-3">
          <img src="<?= getAvatarUrl($currentUser['avatar']) ?>" alt="Avatar" class="avatar">
          <div>
            <strong><?= htmlspecialchars($currentUser['full_name'] ?? $currentUser['username']) ?></strong>
            <select name="visibility" class="form-control"
              style="padding: 4px 8px; font-size: 0.75rem; margin-top: 4px;">
              <option value="public">🌐 Publik</option>
              <option value="followers">👥 Pengikut</option>
              <option value="private">🔒 Hanya saya</option>
            </select>
          </div>
        </div>

        <textarea name="content" class="form-control" rows="4" placeholder="Apa yang sedang kamu pikirkan?"
          style="border: none; resize: none; font-size: 1.125rem;"></textarea>

        <div id="mediaPreview" style="display: none; margin-top: 16px;">
          <div style="position: relative;">
            <img id="imagePreview" src="" alt="Preview" style="max-width: 100%; border-radius: 12px; display: none;">
            <video id="videoPreview" controls style="max-width: 100%; border-radius: 12px; display: none;"></video>
            <button type="button" onclick="removeMedia()"
              style="position: absolute; top: 8px; right: 8px; width: 32px; height: 32px; border-radius: 50%; background: rgba(0,0,0,0.7); color: white; border: none; cursor: pointer;">
              <i class="bi bi-x"></i>
            </button>
          </div>
        </div>

        <input type="file" name="media" id="postMedia" accept="image/*,video/*" style="display: none;"
          onchange="previewPostMedia(this)">
      </div>
      <div class="modal-footer" style="justify-content: space-between;">
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-ghost btn-icon" onclick="document.getElementById('postMedia').click()">
            <i class="bi bi-image" style="color: #10b981;"></i>
          </button>
          <button type="button" class="btn btn-ghost btn-icon" onclick="document.getElementById('postMedia').click()">
            <i class="bi bi-camera-video" style="color: #ef4444;"></i>
          </button>
        </div>
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-send"></i> Posting
        </button>
      </div>
    </form>
  </div>
</div>

<?php
$extraJs = [BASE_URL . '/assets/js/feed.js'];
include __DIR__ . '/../includes/footer.php';
?>