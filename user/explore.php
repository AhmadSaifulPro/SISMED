<?php
/**
 * Explore Page
 * PulTech Social Media Application
 */

require_once __DIR__ . '/../middleware/auth.php';

$currentUser = getCurrentUser();

// Get trending/popular posts
$stmt = db()->prepare("
    SELECT p.*, u.username, u.avatar, u.full_name,
           (SELECT COUNT(*) FROM likes WHERE likeable_type = 'post' AND likeable_id = p.id) as likes_count,
           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count,
           (SELECT id FROM likes WHERE likeable_type = 'post' AND likeable_id = p.id AND user_id = ?) as user_liked
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.visibility = 'public'
    ORDER BY (likes_count + comments_count) DESC, p.created_at DESC
    LIMIT 30
");
$stmt->execute([$currentUser['id']]);
$posts = $stmt->fetchAll();

$pageTitle = 'Jelajahi - ' . APP_NAME;
$currentPage = 'explore';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="dashboard-main">
  <div class="dashboard-content" style="max-width: 1200px;">
    <!-- Search Header -->
    <div class="explore-header">
      <h1>Jelajahi</h1>
      <div class="explore-search-container">
        <div class="explore-search">
          <i class="bi bi-search"></i>
          <input type="text" placeholder="Cari pengguna..." id="exploreSearch" autocomplete="off">
        </div>
        <div class="search-results" id="searchResults" style="display: none;"></div>
      </div>
    </div>

    <?php if (empty($posts)): ?>
      <div class="empty-state">
        <i class="bi bi-compass empty-state-icon"></i>
        <h3 class="empty-state-title">Belum ada postingan</h3>
        <p class="empty-state-text">Jadilah yang pertama membuat postingan publik!</p>
      </div>
    <?php else: ?>
      <!-- Posts Grid -->
      <div class="explore-grid">
        <?php foreach ($posts as $post): ?>
          <div class="explore-item" onclick="window.location.href='<?= BASE_URL ?>/post.php?id=<?= $post['id'] ?>'">
            <?php if ($post['media_type'] === 'video'): ?>
              <video src="<?= POST_URL ?>/<?= $post['media_url'] ?>"></video>
              <div class="explore-type"><i class="bi bi-play-fill"></i></div>
            <?php elseif ($post['media_url']): ?>
              <img src="<?= POST_URL ?>/<?= $post['media_url'] ?>" alt="Post">
            <?php else: ?>
              <div class="explore-text">
                <?= htmlspecialchars(substr($post['content'], 0, 150)) ?>
              </div>
            <?php endif; ?>
            <div class="explore-overlay">
              <span><i class="bi bi-heart-fill"></i> <?= formatNumber($post['likes_count']) ?></span>
              <span><i class="bi bi-chat-fill"></i> <?= formatNumber($post['comments_count']) ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</main>

<style>
  .explore-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-bottom: 24px;
    flex-wrap: wrap;
  }

  .explore-header h1 {
    margin: 0;
  }

  .explore-search {
    flex: 1;
    max-width: 400px;
    position: relative;
    display: flex;
    align-items: center;
  }

  .explore-search i {
    position: absolute;
    left: 16px;
    color: var(--text-muted);
  }

  .explore-search input {
    width: 100%;
    padding: 12px 16px 12px 44px;
    border: 2px solid var(--border-color);
    border-radius: 50px;
    background: var(--bg-tertiary);
    font-size: 0.9375rem;
    transition: border-color 0.3s, background 0.3s;
  }

  .explore-search input:focus {
    border-color: var(--primary-color);
    background: var(--bg-secondary);
    outline: none;
  }

  .explore-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
    border-radius: 12px;
    overflow: hidden;
  }

  .explore-item {
    aspect-ratio: 1;
    position: relative;
    cursor: pointer;
    overflow: hidden;
    background: var(--bg-tertiary);
  }

  .explore-item img,
  .explore-item video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
  }

  .explore-item:hover img,
  .explore-item:hover video {
    transform: scale(1.05);
  }

  .explore-text {
    padding: 24px;
    font-size: 0.9375rem;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    text-align: center;
    background: var(--primary-gradient);
    line-height: 1.5;
  }

  .explore-type {
    position: absolute;
    top: 12px;
    right: 12px;
    color: white;
    font-size: 1.25rem;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
  }

  .explore-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 24px;
    color: white;
    font-weight: 600;
    opacity: 0;
    transition: opacity 0.3s;
  }

  .explore-item:hover .explore-overlay {
    opacity: 1;
  }

  @media (max-width: 1024px) {
    .explore-grid {
      grid-template-columns: repeat(3, 1fr);
    }
  }

  @media (max-width: 768px) {
    .explore-header {
      flex-direction: column;
      align-items: stretch;
    }

    .explore-search {
      max-width: 100%;
    }

    .explore-grid {
      grid-template-columns: repeat(2, 1fr);
      gap: 4px;
    }
  }
</style>

<style>
.explore-search-container {
  position: relative;
  flex: 1;
  max-width: 400px;
}

.search-results {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  margin-top: 8px;
  max-height: 400px;
  overflow-y: auto;
  box-shadow: var(--shadow-lg);
  z-index: 100;
}

.search-result-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  cursor: pointer;
  transition: background 0.2s;
}

.search-result-item:hover {
  background: var(--bg-tertiary);
}

.search-result-item img {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  object-fit: cover;
}

.search-result-info {
  flex: 1;
}

.search-result-name {
  font-weight: 600;
  color: var(--text-primary);
}

.search-result-username {
  font-size: 0.875rem;
  color: var(--text-muted);
}

.search-no-results {
  padding: 24px;
  text-align: center;
  color: var(--text-muted);
}
</style>

<script>
const BASE_URL = document.querySelector('meta[name="base-url"]')?.content || '/sosmed';
let searchTimeout;

document.getElementById('exploreSearch').addEventListener('input', function() {
  const query = this.value.trim();
  const resultsContainer = document.getElementById('searchResults');
  
  clearTimeout(searchTimeout);
  
  if (query.length < 2) {
    resultsContainer.style.display = 'none';
    return;
  }
  
  searchTimeout = setTimeout(async () => {
    try {
      const response = await fetch(`${BASE_URL}/api/users.php?search=${encodeURIComponent(query)}`);
      const data = await response.json();
      
      if (data.success && data.users.length > 0) {
        resultsContainer.innerHTML = data.users.map(user => `
          <a href="${BASE_URL}/user/profile.php?id=${user.id}" class="search-result-item">
            <img src="${user.avatar_url}" alt="${user.username}">
            <div class="search-result-info">
              <div class="search-result-name">${user.full_name || user.username}</div>
              <div class="search-result-username">@${user.username}</div>
            </div>
            ${user.is_following ? '<span class="badge">Mengikuti</span>' : ''}
          </a>
        `).join('');
        resultsContainer.style.display = 'block';
      } else {
        resultsContainer.innerHTML = '<div class="search-no-results">Tidak ada hasil ditemukan</div>';
        resultsContainer.style.display = 'block';
      }
    } catch (error) {
      console.error('Search error:', error);
    }
  }, 300);
});

// Close search results when clicking outside
document.addEventListener('click', function(e) {
  const container = document.querySelector('.explore-search-container');
  if (!container.contains(e.target)) {
    document.getElementById('searchResults').style.display = 'none';
  }
});

// Show results on focus if there's a query
document.getElementById('exploreSearch').addEventListener('focus', function() {
  if (this.value.trim().length >= 2) {
    document.getElementById('searchResults').style.display = 'block';
  }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>