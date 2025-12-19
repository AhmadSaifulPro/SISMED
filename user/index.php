<?php
/**
 * User Dashboard / Feed
 * PulTech Social Media Application
 */

require_once __DIR__ . '/../middleware/auth.php';

$pageTitle = 'Beranda - ' . APP_NAME;
$currentPage = 'home';
$currentUser = getCurrentUser();

// Get stories from followed users and own stories
$stmt = db()->prepare("
    SELECT s.*, u.username, u.avatar, u.full_name
    FROM stories s
    JOIN users u ON s.user_id = u.id
    WHERE s.expires_at > NOW() 
    AND (s.user_id = ? OR s.user_id IN (SELECT following_id FROM follows WHERE follower_id = ?))
    ORDER BY s.user_id = ? DESC, s.created_at DESC
");
$stmt->execute([$currentUser['id'], $currentUser['id'], $currentUser['id']]);
$stories = $stmt->fetchAll();

// Group stories by user
$groupedStories = [];
foreach ($stories as $story) {
  $userId = $story['user_id'];
  if (!isset($groupedStories[$userId])) {
    $groupedStories[$userId] = [
      'user' => [
        'id' => $story['user_id'],
        'username' => $story['username'],
        'avatar' => $story['avatar'],
        'full_name' => $story['full_name']
      ],
      'stories' => []
    ];
  }
  $groupedStories[$userId]['stories'][] = $story;
}

// Get posts for feed
$stmt = db()->prepare("
    SELECT p.*, u.username, u.avatar, u.full_name,
           (SELECT COUNT(*) FROM likes WHERE likeable_type = 'post' AND likeable_id = p.id) as likes_count,
           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count,
           (SELECT COUNT(*) FROM shares WHERE post_id = p.id) as shares_count,
           (SELECT id FROM likes WHERE likeable_type = 'post' AND likeable_id = p.id AND user_id = ?) as user_liked
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.user_id = ? 
       OR p.user_id IN (SELECT following_id FROM follows WHERE follower_id = ?)
       OR p.visibility = 'public'
    ORDER BY p.created_at DESC
    LIMIT 20
");
$stmt->execute([$currentUser['id'], $currentUser['id'], $currentUser['id']]);
$posts = $stmt->fetchAll();

// Get suggested users
$stmt = db()->prepare("
    SELECT u.*, 
           (SELECT COUNT(*) FROM follows WHERE following_id = u.id) as followers_count
    FROM users u
    WHERE u.id != ? 
    AND u.id NOT IN (SELECT following_id FROM follows WHERE follower_id = ?)
    AND u.is_active = 1
    ORDER BY followers_count DESC
    LIMIT 5
");
$stmt->execute([$currentUser['id'], $currentUser['id']]);
$suggestions = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="dashboard-main">
  <div class="dashboard-content">
    <!-- Stories Section -->
    <section class="stories-section">
      <div class="stories-wrapper">
        <!-- Add Story -->
        <div class="story-card add-story" onclick="openCreateStoryModal()">
          <div class="add-icon">
            <i class="bi bi-plus"></i>
          </div>
          <span>Buat Story</span>
        </div>

        <?php foreach ($groupedStories as $userStories):
          $firstStory = $userStories['stories'][0];
          $isText = $firstStory['media_type'] === 'text';
          $isVideo = $firstStory['media_type'] === 'video';
          $isImage = $firstStory['media_type'] === 'image';
          $bgStyle = $isText ? 'background: ' . htmlspecialchars($firstStory['background_color'] ?? 'linear-gradient(135deg, #667eea, #764ba2)') . ';' : '';
          ?>
          <div class="story-card" onclick="openStoryViewer(<?= $userStories['user']['id'] ?>)">
            <?php if ($isText): ?>
              <!-- Text story with gradient background -->
              <div class="story-card-bg story-text-bg" style="<?= $bgStyle ?>">
                <span class="story-text-preview"><?= mb_substr(htmlspecialchars($firstStory['content']), 0, 50) ?></span>
              </div>
            <?php elseif ($isVideo): ?>
              <!-- Video story with video element -->
              <video class="story-card-bg" muted preload="metadata" style="object-fit: cover;">
                <source src="<?= STORY_URL . '/' . $firstStory['media_url'] ?>" type="video/mp4">
              </video>
              <div class="story-video-indicator"><i class="bi bi-play-fill"></i></div>
            <?php else: ?>
              <!-- Image story -->
              <img
                src="<?= !empty($firstStory['media_url']) ? STORY_URL . '/' . $firstStory['media_url'] : BASE_URL . '/assets/img/story-placeholder.jpg' ?>"
                alt="Story" class="story-card-bg">
            <?php endif; ?>
            <div class="story-card-overlay"></div>
            <img src="<?= getAvatarUrl($userStories['user']['avatar']) ?>"
              alt="<?= htmlspecialchars($userStories['user']['username']) ?>" class="story-card-avatar">
            <span class="story-card-username"><?= htmlspecialchars($userStories['user']['username']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- Create Post Card -->
    <div class="create-post-card">
      <div class="create-post-header">
        <img src="<?= getAvatarUrl($currentUser['avatar']) ?>" alt="Avatar" class="avatar">
        <button class="create-post-input" onclick="openCreatePostModal()">
          Apa yang sedang kamu pikirkan, <?= htmlspecialchars($currentUser['full_name'] ?? $currentUser['username']) ?>?
        </button>
      </div>
      <div class="create-post-actions">
        <button class="create-post-action photo" onclick="openCreatePostModal('image')">
          <i class="bi bi-image"></i>
          <span>Foto</span>
        </button>
        <button class="create-post-action video" onclick="openCreatePostModal('video')">
          <i class="bi bi-camera-video"></i>
          <span>Video</span>
        </button>
        <button class="create-post-action text" onclick="openCreatePostModal('text')">
          <i class="bi bi-card-text"></i>
          <span>Teks</span>
        </button>
      </div>
    </div>

    <!-- Posts Feed -->
    <div class="post-feed" id="postFeed">
      <?php if (empty($posts)): ?>
        <div class="empty-state">
          <i class="bi bi-camera empty-state-icon"></i>
          <h3 class="empty-state-title">Belum ada postingan</h3>
          <p class="empty-state-text">Mulai ikuti pengguna lain atau buat postingan pertamamu!</p>
          <button class="btn btn-primary" onclick="openCreatePostModal()">
            <i class="bi bi-plus"></i> Buat Postingan
          </button>
        </div>
      <?php else: ?>
        <?php foreach ($posts as $post): ?>
          <article class="post-card" data-post-id="<?= $post['id'] ?>">
            <header class="post-header">
              <a href="<?= BASE_URL ?>/user/profile.php?id=<?= $post['user_id'] ?>">
                <img src="<?= getAvatarUrl($post['avatar']) ?>" alt="<?= htmlspecialchars($post['username']) ?>"
                  class="avatar">
              </a>
              <div class="post-user-info">
                <a href="<?= BASE_URL ?>/user/profile.php?id=<?= $post['user_id'] ?>" class="post-user-name">
                  <?= htmlspecialchars($post['full_name'] ?? $post['username']) ?>
                </a>
                <div class="post-meta">
                  <span>@<?= htmlspecialchars($post['username']) ?></span>
                  <span>•</span>
                  <span><?= timeAgo($post['created_at']) ?></span>
                </div>
              </div>
              <div class="dropdown" id="postDropdown<?= $post['id'] ?>">
                <div class="post-options" onclick="toggleDropdown('postDropdown<?= $post['id'] ?>')">
                  <i class="bi bi-three-dots"></i>
                </div>
                <div class="dropdown-menu">
                  <?php if ($post['user_id'] == $currentUser['id']): ?>
                    <a href="#" class="dropdown-item" onclick="editPost(<?= $post['id'] ?>)">
                      <i class="bi bi-pencil"></i> Edit
                    </a>
                    <a href="#" class="dropdown-item" onclick="deletePost(<?= $post['id'] ?>)"
                      style="color: var(--error-color);">
                      <i class="bi bi-trash"></i> Hapus
                    </a>
                  <?php else: ?>
                    <a href="#" class="dropdown-item">
                      <i class="bi bi-bookmark"></i> Simpan
                    </a>
                    <a href="#" class="dropdown-item">
                      <i class="bi bi-flag"></i> Laporkan
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </header>

            <?php if (!empty($post['content'])): ?>
              <div class="post-content">
                <p class="post-text"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
              </div>
            <?php endif; ?>

            <?php if (!empty($post['media_url'])): ?>
              <div class="post-media-container">
                <?php if ($post['media_type'] === 'video'): ?>
                  <video class="post-media" controls>
                    <source src="<?= POST_URL ?>/<?= $post['media_url'] ?>" type="video/mp4">
                  </video>
                <?php else: ?>
                  <img src="<?= POST_URL ?>/<?= $post['media_url'] ?>" alt="Post media" class="post-media">
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <div class="post-stats">
              <span><?= formatNumber($post['likes_count']) ?> suka</span>
              <span><?= formatNumber($post['comments_count']) ?> komentar • <?= formatNumber($post['shares_count']) ?>
                dibagikan</span>
            </div>

            <div class="post-actions">
              <button class="post-action-btn <?= $post['user_liked'] ? 'liked' : '' ?>"
                onclick="likePost(<?= $post['id'] ?>, this)">
                <i class="bi bi-heart<?= $post['user_liked'] ? '-fill' : '' ?>"></i>
                <span>Suka</span>
              </button>
              <button class="post-action-btn" onclick="toggleComments(<?= $post['id'] ?>)">
                <i class="bi bi-chat"></i>
                <span>Komentar</span>
              </button>
              <button class="post-action-btn" onclick="sharePost(<?= $post['id'] ?>)">
                <i class="bi bi-share"></i>
                <span>Bagikan</span>
              </button>
            </div>

            <!-- Comments Section (hidden by default) -->
            <div class="comments-section" id="comments<?= $post['id'] ?>" style="display: none;">
              <div class="comments-list" id="commentsList<?= $post['id'] ?>">
                <!-- Comments loaded via AJAX -->
              </div>
              <div class="comment-input-wrapper">
                <img src="<?= getAvatarUrl($currentUser['avatar']) ?>" alt="Avatar" class="avatar avatar-sm">
                <input type="text" class="comment-input" placeholder="Tulis komentar..."
                  onkeypress="if(event.key==='Enter') submitComment(<?= $post['id'] ?>, this)">
                <button class="comment-submit" onclick="submitComment(<?= $post['id'] ?>, this.previousElementSibling)">
                  <i class="bi bi-send"></i>
                </button>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</main>

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

<!-- Create Story Modal -->
<div class="modal-backdrop" id="createStoryModal">
  <div class="modal-content" style="max-width: 420px;">
    <div class="modal-header">
      <h3 class="modal-title">Buat Story</h3>
      <button type="button" class="modal-close" onclick="closeModal('createStoryModal')">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>
    <form id="createStoryForm" enctype="multipart/form-data">
      <div class="modal-body">
        <!-- Story Type Selection -->
        <div class="story-type-selection" id="storyTypeSelection">
          <p style="text-align: center; color: var(--text-muted); margin-bottom: 16px;">Pilih jenis story</p>
          <div class="d-flex gap-3 justify-center">
            <button type="button" class="story-type-btn" onclick="selectStoryType('image')">
              <i class="bi bi-image" style="color: #10b981;"></i>
              <span>Foto</span>
            </button>
            <button type="button" class="story-type-btn" onclick="selectStoryType('video')">
              <i class="bi bi-camera-video" style="color: #ef4444;"></i>
              <span>Video</span>
            </button>
            <button type="button" class="story-type-btn" onclick="selectStoryType('text')">
              <i class="bi bi-card-text" style="color: #f59e0b;"></i>
              <span>Teks</span>
            </button>
          </div>
        </div>

        <!-- Media Preview -->
        <div id="storyPreviewContainer" style="display: none;">
          <div id="storyUploadPlaceholder"
            style="min-height: 300px; background: var(--bg-tertiary); border-radius: 12px; display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer;"
            onclick="document.getElementById('storyMedia').click()">
            <i class="bi bi-cloud-upload" style="font-size: 3rem; color: var(--text-muted);"></i>
            <p style="color: var(--text-muted); margin-top: 8px;">Klik untuk pilih file</p>
            <p style="color: var(--text-muted); font-size: 0.75rem;" id="storyVideoHint">Video maksimal 60 detik</p>
          </div>
          <img id="storyImagePreview" src=""
            style="max-width: 100%; max-height: 400px; display: none; border-radius: 12px; margin: 0 auto;">
          <video id="storyVideoPreview" controls
            style="max-width: 100%; max-height: 400px; display: none; border-radius: 12px;"></video>
        </div>

        <!-- Text Story -->
        <div id="storyTextContainer" style="display: none;">
          <textarea name="content" id="storyTextContent" class="form-control" rows="6" placeholder="Tulis story kamu..."
            style="font-size: 1.25rem; text-align: center; resize: none;"></textarea>
          <div style="margin-top: 12px;">
            <label style="font-size: 0.875rem; color: var(--text-muted);">Warna Background</label>
            <div class="d-flex gap-2 mt-2">
              <button type="button" class="color-btn active"
                style="background: linear-gradient(135deg, #667eea, #764ba2);"
                onclick="setStoryBg(this, 'linear-gradient(135deg, #667eea, #764ba2)')"></button>
              <button type="button" class="color-btn" style="background: linear-gradient(135deg, #f64f59, #c471ed);"
                onclick="setStoryBg(this, 'linear-gradient(135deg, #f64f59, #c471ed)')"></button>
              <button type="button" class="color-btn" style="background: linear-gradient(135deg, #11998e, #38ef7d);"
                onclick="setStoryBg(this, 'linear-gradient(135deg, #11998e, #38ef7d)')"></button>
              <button type="button" class="color-btn" style="background: linear-gradient(135deg, #ee0979, #ff6a00);"
                onclick="setStoryBg(this, 'linear-gradient(135deg, #ee0979, #ff6a00)')"></button>
              <button type="button" class="color-btn" style="background: #1a1a2e;"
                onclick="setStoryBg(this, '#1a1a2e')"></button>
            </div>
          </div>
        </div>

        <!-- Visibility Selector -->
        <div id="storyVisibilityContainer" style="display: none; margin-top: 16px;">
          <label style="font-size: 0.875rem; color: var(--text-muted); display: block; margin-bottom: 8px;">
            <i class="bi bi-globe"></i> Siapa yang bisa melihat?
          </label>
          <select name="visibility" id="storyVisibility" class="form-control" style="padding: 10px 12px;">
            <option value="public"><i class="bi bi-globe"></i> 🌍 Publik</option>
            <option value="followers">👥 Pengikut</option>
            <option value="private">🔒 Hanya Saya</option>
          </select>
        </div>

        <!-- Hidden fields -->
        <input type="hidden" name="background_color" id="storyBgColor"
          value="linear-gradient(135deg, #667eea, #764ba2)">
        <input type="hidden" name="media_type" id="storyMediaType" value="text">
        <input type="file" name="media" id="storyMedia" accept="image/*,video/*" style="display: none;"
          onchange="previewStoryMedia(this)">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('createStoryModal')">Batal</button>
        <button type="submit" class="btn btn-primary">Bagikan Story</button>
      </div>
    </form>
  </div>
</div>

<style>
  .story-type-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 20px 24px;
    background: var(--bg-tertiary);
    border: 2px solid var(--border-color);
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s;
  }

  .story-type-btn:hover {
    border-color: var(--primary-color);
    background: rgba(102, 126, 234, 0.1);
  }

  .story-type-btn i {
    font-size: 2rem;
  }

  .story-type-btn span {
    font-size: 0.875rem;
    font-weight: 500;
  }

  .color-btn {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 2px solid transparent;
    cursor: pointer;
    transition: transform 0.2s;
  }

  .color-btn:hover {
    transform: scale(1.1);
  }

  .color-btn.active {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 2px var(--bg-secondary), 0 0 0 4px var(--primary-color);
  }
</style>

<script>
  // Story modal functions - wait for DOM and app.js to be ready
  document.addEventListener('DOMContentLoaded', function () {
    // Make functions globally available
    window.selectStoryType = function (type) {
      document.getElementById('storyTypeSelection').style.display = 'none';

      if (type === 'text') {
        document.getElementById('storyTextContainer').style.display = 'block';
        document.getElementById('storyPreviewContainer').style.display = 'none';
        document.getElementById('storyMediaType').value = 'text';
      } else {
        document.getElementById('storyTextContainer').style.display = 'none';
        document.getElementById('storyPreviewContainer').style.display = 'block';
        document.getElementById('storyMediaType').value = type;
        document.getElementById('storyMedia').accept = type === 'image' ? 'image/*' : 'video/*';
        document.getElementById('storyMedia').click();
      }

      // Show visibility selector
      document.getElementById('storyVisibilityContainer').style.display = 'block';
    };

    window.setStoryBg = function (btn, color) {
      document.querySelectorAll('.color-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById('storyBgColor').value = color;
    };

    // Override closeModal to reset story modal when closed
    if (typeof closeModal === 'function') {
      const originalCloseModal = closeModal;
      window.closeModal = function (modalId) {
        if (modalId === 'createStoryModal') {
          const typeSelection = document.getElementById('storyTypeSelection');
          const previewContainer = document.getElementById('storyPreviewContainer');
          const textContainer = document.getElementById('storyTextContainer');
          const uploadPlaceholder = document.getElementById('storyUploadPlaceholder');
          const imagePreview = document.getElementById('storyImagePreview');
          const videoPreview = document.getElementById('storyVideoPreview');
          const visibilityContainer = document.getElementById('storyVisibilityContainer');

          if (typeSelection) typeSelection.style.display = 'block';
          if (previewContainer) previewContainer.style.display = 'none';
          if (textContainer) textContainer.style.display = 'none';
          if (uploadPlaceholder) uploadPlaceholder.style.display = 'flex';
          if (imagePreview) imagePreview.style.display = 'none';
          if (videoPreview) videoPreview.style.display = 'none';
          if (visibilityContainer) visibilityContainer.style.display = 'none';
        }
        originalCloseModal(modalId);
      };
    }
  });
</script>

<!-- Share Modal -->
<div class="modal-backdrop" id="shareModal">
  <div class="modal-content" style="max-width: 400px;">
    <div class="modal-header">
      <h3 class="modal-title">Bagikan</h3>
      <button type="button" class="modal-close" onclick="closeModal('shareModal')">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>
    <div class="modal-body">
      <div class="d-flex gap-3 justify-center mb-4">
        <a href="#" class="share-btn" onclick="shareToWhatsApp()" style="text-align: center;">
          <div
            style="width: 56px; height: 56px; background: #25D366; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px;">
            <i class="bi bi-whatsapp" style="font-size: 1.5rem; color: white;"></i>
          </div>
          <span style="font-size: 0.75rem;">WhatsApp</span>
        </a>
        <a href="#" class="share-btn" onclick="shareToFacebook()" style="text-align: center;">
          <div
            style="width: 56px; height: 56px; background: #1877F2; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px;">
            <i class="bi bi-facebook" style="font-size: 1.5rem; color: white;"></i>
          </div>
          <span style="font-size: 0.75rem;">Facebook</span>
        </a>
        <a href="#" class="share-btn" onclick="shareToTwitter()" style="text-align: center;">
          <div
            style="width: 56px; height: 56px; background: #1DA1F2; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px;">
            <i class="bi bi-twitter" style="font-size: 1.5rem; color: white;"></i>
          </div>
          <span style="font-size: 0.75rem;">Twitter</span>
        </a>
        <a href="#" class="share-btn" onclick="shareToTelegram()" style="text-align: center;">
          <div
            style="width: 56px; height: 56px; background: #0088CC; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px;">
            <i class="bi bi-telegram" style="font-size: 1.5rem; color: white;"></i>
          </div>
          <span style="font-size: 0.75rem;">Telegram</span>
        </a>
      </div>
      <div class="input-group">
        <input type="text" id="shareUrl" class="form-control" readonly>
        <button type="button" class="btn btn-primary"
          onclick="copyToClipboard(document.getElementById('shareUrl').value, this)">
          <i class="bi bi-clipboard"></i> Salin
        </button>
      </div>
    </div>
  </div>
</div>

<?php
$extraJs = [BASE_URL . '/assets/js/feed.js'];
include __DIR__ . '/../includes/footer.php';
?>