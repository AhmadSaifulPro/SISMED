<?php
/**
 * Single Post View
 * PulTech Social Media Application
 */

require_once __DIR__ . '/middleware/auth.php';

$postId = intval($_GET['id'] ?? 0);
$currentUser = getCurrentUser();

if ($postId <= 0) {
  redirect(BASE_URL . '/user/index.php');
}

// Get post
$stmt = db()->prepare("
    SELECT p.*, u.username, u.avatar, u.full_name,
           (SELECT COUNT(*) FROM likes WHERE likeable_type = 'post' AND likeable_id = p.id) as likes_count,
           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count,
           (SELECT COUNT(*) FROM shares WHERE post_id = p.id) as shares_count,
           (SELECT id FROM likes WHERE likeable_type = 'post' AND likeable_id = p.id AND user_id = ?) as user_liked,
           (SELECT id FROM follows WHERE follower_id = ? AND following_id = p.user_id) as is_following
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.id = ?
");
$stmt->execute([$currentUser['id'], $currentUser['id'], $postId]);
$post = $stmt->fetch();

if (!$post) {
  setFlash('error', 'Postingan tidak ditemukan');
  redirect(BASE_URL . '/user/index.php');
}

// Get comments
$stmt = db()->prepare("
    SELECT c.*, u.username, u.avatar, u.full_name
    FROM comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.post_id = ? AND c.parent_id IS NULL
    ORDER BY c.created_at ASC
");
$stmt->execute([$postId]);
$comments = $stmt->fetchAll();

$pageTitle = htmlspecialchars($post['full_name'] ?: $post['username']) . ' on PulTech';
$currentPage = '';

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<main class="dashboard-main">
  <div class="dashboard-content" style="max-width: 600px;">
    <a href="<?= BASE_URL ?>/user/index.php" class="btn btn-ghost mb-3">
      <i class="bi bi-arrow-left"></i> Kembali
    </a>

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

        <?php if ($post['user_id'] != $currentUser['id'] && !$post['is_following']): ?>
          <button class="btn btn-primary btn-sm" onclick="followUser(<?= $post['user_id'] ?>, this)">Ikuti</button>
        <?php endif; ?>
      </header>

      <?php if (!empty($post['content'])): ?>
        <div class="post-content">
          <p class="post-text" style="font-size: 1.125rem;"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
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
        <button class="post-action-btn" onclick="document.getElementById('commentInput').focus()">
          <i class="bi bi-chat"></i>
          <span>Komentar</span>
        </button>
        <button class="post-action-btn" onclick="sharePost(<?= $post['id'] ?>)">
          <i class="bi bi-share"></i>
          <span>Bagikan</span>
        </button>
      </div>

      <!-- Comments Section -->
      <div class="comments-section" style="display: block;">
        <div class="comments-list">
          <?php if (empty($comments)): ?>
            <p class="text-center text-muted p-3">Belum ada komentar. Jadilah yang pertama!</p>
          <?php else: ?>
            <?php foreach ($comments as $comment): ?>
              <div class="comment-item">
                <a href="<?= BASE_URL ?>/user/profile.php?id=<?= $comment['user_id'] ?>">
                  <img src="<?= getAvatarUrl($comment['avatar']) ?>" alt="" class="avatar avatar-sm">
                </a>
                <div style="flex: 1;">
                  <div class="comment-bubble">
                    <a href="<?= BASE_URL ?>/user/profile.php?id=<?= $comment['user_id'] ?>" class="comment-author">
                      <?= htmlspecialchars($comment['full_name'] ?: $comment['username']) ?>
                    </a>
                    <p class="comment-text"><?= nl2br(htmlspecialchars($comment['content'])) ?></p>
                  </div>
                  <div class="comment-meta">
                    <span><?= timeAgo($comment['created_at']) ?></span>
                    <button onclick="likeComment(<?= $comment['id'] ?>, this)"><?= $comment['likes_count'] ?> suka</button>
                    <button onclick="replyTo('<?= htmlspecialchars($comment['username']) ?>')">Balas</button>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <div class="comment-input-wrapper">
          <img src="<?= getAvatarUrl($currentUser['avatar']) ?>" alt="Avatar" class="avatar avatar-sm">
          <input type="text" id="commentInput" class="comment-input" placeholder="Tulis komentar..."
            onkeypress="if(event.key==='Enter') submitPostComment(this)">
          <button class="comment-submit" onclick="submitPostComment(document.getElementById('commentInput'))">
            <i class="bi bi-send"></i>
          </button>
        </div>
      </div>
    </article>
  </div>
</main>

<!-- Share Modal -->
<div class="modal-backdrop" id="shareModal">
  <div class="modal-content" style="max-width: 400px;">
    <div class="modal-header">
      <h3 class="modal-title">Bagikan</h3>
      <div class="modal-close" onclick="closeModal('shareModal')">
        <i class="bi bi-x-lg"></i>
      </div>
    </div>
    <div class="modal-body">
      <div class="d-flex gap-3 justify-center mb-4">
        <a href="#" onclick="shareToWhatsApp()" style="text-align: center;">
          <div
            style="width: 56px; height: 56px; background: #25D366; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px;">
            <i class="bi bi-whatsapp" style="font-size: 1.5rem; color: white;"></i>
          </div>
          <span style="font-size: 0.75rem;">WhatsApp</span>
        </a>
        <a href="#" onclick="shareToFacebook()" style="text-align: center;">
          <div
            style="width: 56px; height: 56px; background: #1877F2; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px;">
            <i class="bi bi-facebook" style="font-size: 1.5rem; color: white;"></i>
          </div>
          <span style="font-size: 0.75rem;">Facebook</span>
        </a>
        <a href="#" onclick="shareToTwitter()" style="text-align: center;">
          <div
            style="width: 56px; height: 56px; background: #1DA1F2; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px;">
            <i class="bi bi-twitter" style="font-size: 1.5rem; color: white;"></i>
          </div>
          <span style="font-size: 0.75rem;">Twitter</span>
        </a>
      </div>
      <div class="input-group">
        <input type="text" id="shareUrl" class="form-control" value="<?= BASE_URL ?>/post.php?id=<?= $post['id'] ?>"
          readonly>
        <button type="button" class="btn btn-primary"
          onclick="copyToClipboard(document.getElementById('shareUrl').value, this)">
          <i class="bi bi-clipboard"></i> Salin
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  const BASE_URL = '<?= BASE_URL ?>';
  const postId = <?= $postId ?>;

  async function submitPostComment(input) {
    const content = input.value.trim();
    if (!content) return;

    try {
      const response = await fetch(`${BASE_URL}/api/comments.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
          post_id: postId,
          content: content
        })
      });

      const data = await response.json();

      if (data.success) {
        location.reload();
      } else {
        showToast(data.message || 'Gagal menambahkan komentar', 'error');
      }
    } catch (error) {
      showToast('Terjadi kesalahan', 'error');
    }
  }

  function replyTo(username) {
    const input = document.getElementById('commentInput');
    input.value = `@${username} `;
    input.focus();
  }

  function shareToWhatsApp() {
    const url = document.getElementById('shareUrl').value;
    window.open(`https://wa.me/?text=${encodeURIComponent('Lihat postingan ini! ' + url)}`, '_blank');
  }

  function shareToFacebook() {
    const url = document.getElementById('shareUrl').value;
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`, '_blank');
  }

  function shareToTwitter() {
    const url = document.getElementById('shareUrl').value;
    window.open(`https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent('Lihat postingan ini!')}`, '_blank');
  }
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>