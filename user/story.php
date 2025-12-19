<?php
/**
 * Story Viewer Page
 * PulTech Social Media Application
 */

require_once __DIR__ . '/../middleware/auth.php';

$currentUser = getCurrentUser();
$userId = intval($_GET['user_id'] ?? 0);

if ($userId <= 0) {
  redirect(BASE_URL . '/user/index.php');
}

// Clean up expired stories first
cleanupExpiredStories();

// Get user's stories
$stmt = db()->prepare("
    SELECT s.*, u.username, u.avatar, u.full_name
    FROM stories s
    JOIN users u ON s.user_id = u.id
    WHERE s.user_id = ? AND s.expires_at > NOW()
    ORDER BY s.created_at ASC
");
$stmt->execute([$userId]);
$stories = $stmt->fetchAll();

// Add remaining time to each story
foreach ($stories as &$story) {
  $story['remaining_time'] = getStoryRemainingTime($story['expires_at']);
}
unset($story);

if (empty($stories)) {
  redirect(BASE_URL . '/user/index.php');
}

// Check if owner is viewing their own story
$isOwner = $userId === $currentUser['id'];
$viewers = [];

if ($isOwner) {
  // Get unique viewers for owner's stories with view count
  $storyIds = array_column($stories, 'id');
  $placeholders = implode(',', array_fill(0, count($storyIds), '?'));
  $stmt = db()->prepare("
    SELECT sv.viewer_id, 
           COUNT(*) as view_count,
           MAX(sv.created_at) as last_viewed_at,
           u.username, u.avatar, u.full_name
    FROM story_views sv
    JOIN users u ON sv.viewer_id = u.id
    WHERE sv.story_id IN ($placeholders)
    GROUP BY sv.viewer_id, u.username, u.avatar, u.full_name
    ORDER BY last_viewed_at DESC
    LIMIT 50
  ");
  $stmt->execute($storyIds);
  $viewers = $stmt->fetchAll();

  foreach ($viewers as &$viewer) {
    $viewer['avatar_url'] = getAvatarUrl($viewer['avatar']);
    $viewer['time_ago'] = timeAgo($viewer['last_viewed_at']);
  }
} else {
  // Record view for non-owners
  foreach ($stories as $story) {
    $stmt = db()->prepare("INSERT IGNORE INTO story_views (story_id, viewer_id, created_at) VALUES (?, ?, NOW())");
    $stmt->execute([$story['id'], $currentUser['id']]);

    // Update view count
    db()->prepare("UPDATE stories SET views_count = views_count + 1 WHERE id = ?")->execute([$story['id']]);
  }
}

$storyUser = $stories[0];
// Count unique viewers, not total views
$totalUniqueViewers = count($viewers);
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Story - <?= htmlspecialchars($storyUser['username']) ?></title>
  <link rel="icon" href="<?= BASE_URL ?>/assets/images/logo.png">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: #000;
      color: white;
      overflow: hidden;
    }

    .story-viewer {
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .story-container {
      width: 100%;
      max-width: 420px;
      height: 100vh;
      max-height: 800px;
      position: relative;
      background: #1a1a1a;
    }

    .story-progress {
      position: absolute;
      top: 12px;
      left: 12px;
      right: 12px;
      display: flex;
      gap: 4px;
      z-index: 20;
    }

    .progress-bar {
      flex: 1;
      height: 3px;
      background: rgba(255, 255, 255, 0.3);
      border-radius: 2px;
      overflow: hidden;
    }

    .progress-bar-fill {
      height: 100%;
      background: white;
      width: 0;
      transition: width 0.1s linear;
    }

    .progress-bar.viewed .progress-bar-fill {
      width: 100%;
    }

    .progress-bar.active .progress-bar-fill {
      animation: progress 5s linear forwards;
    }

    @keyframes progress {
      from {
        width: 0;
      }

      to {
        width: 100%;
      }
    }

    .story-header {
      position: absolute;
      top: 24px;
      left: 12px;
      right: 12px;
      display: flex;
      align-items: center;
      gap: 12px;
      z-index: 20;
    }

    .story-header img {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      border: 2px solid white;
    }

    .story-header-info {
      flex: 1;
    }

    .story-header-name {
      font-weight: 600;
      font-size: 0.875rem;
    }

    .story-header-time {
      font-size: 0.75rem;
      opacity: 0.7;
    }

    .story-close {
      width: 36px;
      height: 36px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      background: rgba(0, 0, 0, 0.3);
      cursor: pointer;
      text-decoration: none;
      color: white;
    }

    .story-content {
      width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .story-content img,
    .story-content video {
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
    }

    .story-text-content {
      padding: 24px;
      font-size: 1.5rem;
      text-align: center;
      width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .story-nav {
      position: absolute;
      top: 0;
      bottom: 0;
      width: 30%;
      z-index: 10;
      cursor: pointer;
    }

    .story-nav.prev {
      left: 0;
    }

    .story-nav.next {
      right: 0;
    }

    .story-footer {
      position: absolute;
      bottom: 24px;
      left: 12px;
      right: 12px;
      display: flex;
      align-items: center;
      gap: 12px;
      z-index: 20;
    }

    .story-reply-input {
      flex: 1;
      background: rgba(255, 255, 255, 0.2);
      border: 1px solid rgba(255, 255, 255, 0.3);
      border-radius: 24px;
      padding: 10px 16px;
      color: white;
      font-size: 0.875rem;
    }

    .story-reply-input::placeholder {
      color: rgba(255, 255, 255, 0.6);
    }

    .story-action {
      width: 44px;
      height: 44px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.25rem;
      cursor: pointer;
    }

    /* Viewers Panel */
    .viewers-panel {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      background: linear-gradient(to top, rgba(0, 0, 0, 0.95), rgba(0, 0, 0, 0.8));
      backdrop-filter: blur(10px);
      border-radius: 20px 20px 0 0;
      max-height: 60%;
      z-index: 30;
      animation: slideUp 0.3s ease;
    }

    @keyframes slideUp {
      from {
        transform: translateY(100%);
      }

      to {
        transform: translateY(0);
      }
    }

    .viewers-panel-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px 20px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .viewers-panel-header h4 {
      margin: 0;
      font-size: 1rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .viewers-list {
      max-height: 300px;
      overflow-y: auto;
      padding: 12px 16px;
    }

    .viewer-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 10px 0;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .viewer-item:last-child {
      border-bottom: none;
    }

    .viewer-item img {
      width: 44px;
      height: 44px;
      border-radius: 50%;
      object-fit: cover;
    }

    .viewer-info {
      flex: 1;
    }

    .viewer-name {
      font-weight: 500;
      font-size: 0.9rem;
    }

    .viewer-time {
      font-size: 0.75rem;
      color: rgba(255, 255, 255, 0.5);
    }

    @media (min-width: 768px) {
      .story-container {
        border-radius: 12px;
        overflow: hidden;
      }
    }

    /* Mobile specific - fix footer position */
    @media (max-width: 767px) {
      .story-viewer {
        height: 100vh;
        height: 100dvh;
        /* Dynamic viewport height for mobile */
      }

      .story-container {
        max-height: 100vh;
        max-height: 100dvh;
        height: 100%;
      }

      .story-footer {
        bottom: 30px;
        left: 16px;
        right: 16px;
        padding-bottom: env(safe-area-inset-bottom, 0);
      }

      .story-reply-input {
        font-size: 16px;
        /* Prevent zoom on iOS */
      }

      .viewers-panel {
        max-height: 50%;
        bottom: 0;
      }
    }
  </style>
</head>

<body>
  <div class="story-viewer">
    <div class="story-container" id="storyContainer">
      <!-- Progress bars -->
      <div class="story-progress">
        <?php foreach ($stories as $index => $story): ?>
          <div class="progress-bar <?= $index === 0 ? 'active' : '' ?>" data-index="<?= $index ?>">
            <div class="progress-bar-fill"></div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Header -->
      <div class="story-header">
        <a href="<?= BASE_URL ?>/user/profile.php?id=<?= $userId ?>">
          <img src="<?= getAvatarUrl($storyUser['avatar']) ?>" alt="">
        </a>
        <div class="story-header-info">
          <div class="story-header-name"><?= htmlspecialchars($storyUser['full_name'] ?: $storyUser['username']) ?>
          </div>
          <div class="story-header-time" id="storyTime"><?= timeAgo($stories[0]['created_at']) ?></div>
        </div>
        <a href="<?= BASE_URL ?>/user/index.php" class="story-close">
          <i class="bi bi-x-lg"></i>
        </a>
      </div>

      <!-- Navigation -->
      <div class="story-nav prev" onclick="prevStory()"></div>
      <div class="story-nav next" onclick="nextStory()"></div>

      <!-- Content -->
      <div class="story-content" id="storyContent">
        <!-- Content loaded via JS -->
      </div>

      <!-- Footer for non-owner -->
      <?php if (!$isOwner): ?>
        <div class="story-footer">
          <input type="text" class="story-reply-input" placeholder="Kirim pesan..." id="replyInput"
            onkeypress="if(event.key==='Enter') sendReply()">
          <div class="story-action" onclick="likeStory()">
            <i class="bi bi-heart" id="likeIcon"></i>
          </div>
        </div>
      <?php else: ?>
        <!-- Owner footer with views count -->
        <div class="story-footer" style="justify-content: center;">
          <div class="story-action" onclick="toggleViewersPanel()" style="display: flex; align-items: center; gap: 8px;">
            <i class="bi bi-eye"></i>
            <span id="viewsCount"><?= $totalUniqueViewers ?></span>
          </div>
        </div>

        <!-- Viewers Panel -->
        <div id="viewersPanel" class="viewers-panel" style="display: none;">
          <div class="viewers-panel-header">
            <h4><i class="bi bi-eye"></i> Dilihat oleh</h4>
            <button onclick="toggleViewersPanel()" class="story-close" style="width: 28px; height: 28px;">
              <i class="bi bi-x"></i>
            </button>
          </div>
          <div class="viewers-list">
            <?php if (empty($viewers)): ?>
              <p style="text-align: center; color: rgba(255,255,255,0.6); padding: 20px;">Belum ada yang melihat story ini
              </p>
            <?php else: ?>
              <?php foreach ($viewers as $viewer): ?>
                <div class="viewer-item">
                  <img src="<?= $viewer['avatar_url'] ?>" alt="">
                  <div class="viewer-info">
                    <div class="viewer-name"><?= htmlspecialchars($viewer['full_name'] ?: $viewer['username']) ?></div>
                    <div class="viewer-time">
                      <?= $viewer['time_ago'] ?>
                      <?php if ($viewer['view_count'] > 1): ?>
                        <span style="opacity: 0.7;"> • <?= $viewer['view_count'] ?>x dilihat</span>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    const stories = <?= json_encode($stories) ?>;
    const BASE_URL = '<?= BASE_URL ?>';
    const STORY_URL = '<?= STORY_URL ?>';
    let currentIndex = 0;
    let timer;
    let isPaused = false;

    function showStory(index) {
      if (index < 0 || index >= stories.length) {
        window.location.href = `${BASE_URL}/user/index.php`;
        return;
      }

      currentIndex = index;
      const story = stories[index];
      const container = document.getElementById('storyContent');

      // Update progress bars
      document.querySelectorAll('.progress-bar').forEach((bar, i) => {
        bar.classList.remove('active', 'viewed');
        if (i < index) bar.classList.add('viewed');
        if (i === index) bar.classList.add('active');
      });

      // Update time
      document.getElementById('storyTime').textContent = story.time_ago || 'Just now';

      // Show content
      if (story.media_type === 'video') {
        container.innerHTML = `<video src="${STORY_URL}/${story.media_url}" autoplay muted playsinline onended="nextStory()"></video>`;
      } else if (story.media_url) {
        container.innerHTML = `<img src="${STORY_URL}/${story.media_url}" alt="Story">`;
        startTimer();
      } else {
        container.innerHTML = `<div class="story-text-content" style="background: ${story.background_color}">${escapeHtml(story.content)}</div>`;
        startTimer();
      }
    }

    function startTimer() {
      clearTimeout(timer);
      if (!isPaused) {
        timer = setTimeout(nextStory, 5000);
      }
    }

    function nextStory() {
      showStory(currentIndex + 1);
    }

    function prevStory() {
      showStory(currentIndex - 1);
    }

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    // Pause on hold
    document.getElementById('storyContainer').addEventListener('mousedown', () => {
      isPaused = true;
      document.querySelector('.progress-bar.active .progress-bar-fill').style.animationPlayState = 'paused';
    });

    document.getElementById('storyContainer').addEventListener('mouseup', () => {
      isPaused = false;
      document.querySelector('.progress-bar.active .progress-bar-fill').style.animationPlayState = 'running';
    });

    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowLeft') prevStory();
      if (e.key === 'ArrowRight') nextStory();
      if (e.key === 'Escape') window.location.href = `${BASE_URL}/user/index.php`;
    });

    // Like story
    async function likeStory() {
      const icon = document.getElementById('likeIcon');
      try {
        const response = await fetch(`${BASE_URL}/api/likes.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ type: 'story', id: stories[currentIndex].id })
        });
        const data = await response.json();
        if (data.liked) {
          icon.classList.remove('bi-heart');
          icon.classList.add('bi-heart-fill');
          icon.style.color = '#ef4444';
        } else {
          icon.classList.remove('bi-heart-fill');
          icon.classList.add('bi-heart');
          icon.style.color = '';
        }
      } catch (e) { }
    }

    // Send reply
    async function sendReply() {
      const input = document.getElementById('replyInput');
      const message = input.value.trim();
      if (!message) return;

      const formData = new FormData();
      formData.append('receiver_id', stories[0].user_id);
      formData.append('message', message);

      try {
        await fetch(`${BASE_URL}/api/messages.php`, { method: 'POST', body: formData });
        input.value = '';
        input.placeholder = 'Pesan terkirim! Mengalihkan...';
        // Redirect to chat with this user after short delay
        setTimeout(() => {
          window.location.href = `${BASE_URL}/user/messages.php?user=${stories[0].user_id}`;
        }, 1000);
      } catch (e) { }
    }

    // Toggle viewers panel (for story owner)
    function toggleViewersPanel() {
      const panel = document.getElementById('viewersPanel');
      if (panel) {
        panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        // Pause story timer when panel is open
        isPaused = panel.style.display === 'block';
      }
    }

    // Initialize
    showStory(0);
  </script>
</body>

</html>