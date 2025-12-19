<?php
/**
 * Messages / Chat Page
 * PulTech Social Media Application
 */

require_once __DIR__ . '/../middleware/auth.php';

$currentUser = getCurrentUser();
$selectedUserId = intval($_GET['user_id'] ?? 0);
$selectedUser = null;

if ($selectedUserId > 0) {
  $stmt = db()->prepare("SELECT id, username, avatar, full_name FROM users WHERE id = ? AND is_active = 1");
  $stmt->execute([$selectedUserId]);
  $selectedUser = $stmt->fetch();
}

// Get conversations
$stmt = db()->prepare("
    SELECT 
        CASE WHEN c.user1_id = ? THEN c.user2_id ELSE c.user1_id END as other_user_id,
        u.username, u.avatar, u.full_name,
        m.message as last_message,
        m.created_at as last_message_time,
        (SELECT COUNT(*) FROM messages WHERE sender_id = u.id AND receiver_id = ? AND is_read = 0) as unread_count
    FROM conversations c
    JOIN users u ON (CASE WHEN c.user1_id = ? THEN c.user2_id ELSE c.user1_id END) = u.id
    LEFT JOIN messages m ON c.last_message_id = m.id
    WHERE c.user1_id = ? OR c.user2_id = ?
    ORDER BY c.updated_at DESC
");
$stmt->execute([$currentUser['id'], $currentUser['id'], $currentUser['id'], $currentUser['id'], $currentUser['id']]);
$conversations = $stmt->fetchAll();

$pageTitle = 'Pesan - ' . APP_NAME;
$currentPage = 'messages';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="dashboard-main">
  <div class="chat-container">
    <!-- Chat Sidebar -->
    <div class="chat-sidebar" id="chatSidebar">
      <div class="chat-sidebar-header">
        <h2 style="font-size: 1.25rem; font-weight: 600;">Pesan</h2>
        <button class="btn btn-ghost btn-icon" onclick="openNewChatModal()">
          <i class="bi bi-pencil-square"></i>
        </button>
      </div>

      <div class="chat-search" style="padding: 12px; position: relative;">
        <input type="text" class="form-control" placeholder="Cari pengguna untuk chat..." id="chatSearch"
          autocomplete="off">
        <div class="chat-user-results" id="chatUserResults" style="display: none;"></div>
      </div>

      <div class="chat-list" id="chatList">
        <?php if (empty($conversations)): ?>
          <div class="text-center p-4 text-muted">
            <i class="bi bi-chat-dots" style="font-size: 2rem;"></i>
            <p style="margin-top: 8px;">Belum ada percakapan</p>
          </div>
        <?php else: ?>
          <?php foreach ($conversations as $conv): ?>
            <div class="chat-item <?= $selectedUserId == $conv['other_user_id'] ? 'active' : '' ?>"
              onclick="selectChat(<?= $conv['other_user_id'] ?>)" data-user-id="<?= $conv['other_user_id'] ?>">
              <img src="<?= getAvatarUrl($conv['avatar']) ?>" alt="<?= htmlspecialchars($conv['username']) ?>"
                class="avatar">
              <div class="chat-item-info">
                <div class="chat-item-name"><?= htmlspecialchars($conv['full_name'] ?: $conv['username']) ?></div>
                <div class="chat-item-preview"><?= htmlspecialchars($conv['last_message'] ?? 'Mulai percakapan') ?></div>
              </div>
              <div style="text-align: right;">
                <div class="chat-item-time"><?= $conv['last_message_time'] ? timeAgo($conv['last_message_time']) : '' ?>
                </div>
                <?php if ($conv['unread_count'] > 0): ?>
                  <span class="badge badge-error" style="margin-top: 4px;"><?= $conv['unread_count'] ?></span>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Chat Main -->
    <div class="chat-main" id="chatMain">
      <?php if ($selectedUser): ?>
        <div class="chat-header">
          <button class="btn btn-ghost btn-icon d-none" id="backBtn" onclick="showChatList()">
            <i class="bi bi-arrow-left"></i>
          </button>
          <a href="<?= BASE_URL ?>/user/profile.php?id=<?= $selectedUser['id'] ?>">
            <img src="<?= getAvatarUrl($selectedUser['avatar']) ?>" alt="" class="avatar">
          </a>
          <div class="chat-header-info" style="flex: 1;">
            <div style="font-weight: 600;">
              <?= htmlspecialchars($selectedUser['full_name'] ?: $selectedUser['username']) ?>
            </div>
            <div style="font-size: 0.75rem; color: var(--text-muted);" id="userStatus">Online</div>
          </div>
          <div class="dropdown" id="chatOptionsDropdown">
            <button class="btn btn-ghost btn-icon" onclick="toggleDropdown('chatOptionsDropdown')">
              <i class="bi bi-three-dots-vertical"></i>
            </button>
            <div class="dropdown-menu">
              <a href="<?= BASE_URL ?>/user/profile.php?id=<?= $selectedUser['id'] ?>" class="dropdown-item">
                <i class="bi bi-person"></i> Lihat Profil
              </a>
              <a href="#" class="dropdown-item" onclick="deleteConversation(<?= $selectedUser['id'] ?>)">
                <i class="bi bi-trash"></i> Hapus Percakapan
              </a>
            </div>
          </div>
        </div>

        <div class="chat-messages" id="chatMessages">
          <div class="text-center p-4">
            <div class="spinner"></div>
          </div>
        </div>

        <div class="chat-input-container">
          <button class="btn btn-ghost btn-icon" onclick="document.getElementById('chatMedia').click()">
            <i class="bi bi-image"></i>
          </button>
          <input type="file" id="chatMedia" accept="image/*,video/*" style="display: none;"
            onchange="previewChatMedia(this)">
          <input type="text" class="chat-input" id="messageInput" placeholder="Ketik pesan..."
            onkeypress="if(event.key==='Enter') sendMessage()">
          <button class="btn btn-primary btn-icon" onclick="sendMessage()">
            <i class="bi bi-send"></i>
          </button>
        </div>
      <?php else: ?>
        <div class="d-flex flex-column align-center justify-center h-100 text-center p-4">
          <i class="bi bi-chat-dots" style="font-size: 4rem; color: var(--text-muted);"></i>
          <h3 style="margin: 16px 0 8px;">Pesan Anda</h3>
          <p style="color: var(--text-muted); margin-bottom: 24px;">Pilih percakapan atau mulai chat baru</p>
          <button class="btn btn-primary" onclick="openNewChatModal()">
            <i class="bi bi-plus"></i> Pesan Baru
          </button>
        </div>
      <?php endif; ?>
    </div>
  </div>
</main>

<!-- New Chat Modal -->
<div class="modal-backdrop" id="newChatModal">
  <div class="modal-content" style="max-width: 400px;">
    <div class="modal-header">
      <h3 class="modal-title">Pesan Baru</h3>
      <button type="button" class="modal-close" onclick="closeModal('newChatModal')">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>
    <div class="modal-body">
      <input type="text" class="form-control" id="searchUserInput" placeholder="Cari pengguna..."
        oninput="searchUsers(this.value)">
      <div id="userSearchResults" style="margin-top: 16px; max-height: 300px; overflow-y: auto;">
      </div>
    </div>
  </div>
</div>

<style>
  .chat-user-results {
    position: absolute;
    top: 100%;
    left: 12px;
    right: 12px;
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    margin-top: 4px;
    max-height: 300px;
    overflow-y: auto;
    box-shadow: var(--shadow-lg);
    z-index: 100;
  }

  .suggestion-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    transition: background 0.2s;
  }

  .suggestion-item:hover {
    background: var(--bg-tertiary);
  }

  .suggestion-name {
    font-weight: 500;
    color: var(--text-primary);
  }

  .suggestion-meta {
    font-size: 0.75rem;
    color: var(--text-muted);
  }

  /* Fix for mobile chat list styling */
  .chat-item {
    text-decoration: none !important;
    position: relative;
  }

  .chat-item::before,
  .chat-item::after {
    display: none !important;
  }

  .chat-item * {
    text-decoration: none !important;
  }

  @media (max-width: 768px) {
    .chat-container {
      flex-direction: column;
      height: calc(100vh - 140px);
    }

    .chat-sidebar {
      width: 100%;
      height: auto;
      max-height: 100%;
      border-right: none;
      border-bottom: 1px solid var(--border-color);
    }

    .chat-item {
      padding: 12px 16px;
      border-bottom: 1px solid var(--border-color);
      text-decoration: none !important;
    }

    .chat-item:last-child {
      border-bottom: none;
    }

    .chat-main {
      display: none;
    }

    .chat-sidebar.hidden {
      display: none;
    }

    .chat-sidebar.hidden+.chat-main {
      display: flex;
      flex-direction: column;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 90px; /* Increased to 90px as requested */
      background: var(--bg-primary);
      z-index: 100;
      height: auto !important;
      /* Let top/bottom define height */
    }

    .chat-messages {
      flex: 1;
      overflow-y: auto;
      padding-bottom: 10px;
      -webkit-overflow-scrolling: touch;
    }

    .chat-input-container {
      flex-shrink: 0;
      padding: 10px 16px;
      padding-bottom: calc(10px + env(safe-area-inset-bottom));
      /* Add safe area padding */
      background: var(--bg-primary);
      border-top: 1px solid var(--border-color);
      min-height: 70px;
      /* Ensure minimum height */
      display: flex;
      align-items: center;
      gap: 8px;
    }

    /* Override main dashboard padding for this page */
    body .dashboard-main {
      padding-bottom: 0 !important;
      height: 100vh;
      overflow: hidden;
    }
  }
</style>

<script src="<?= BASE_URL ?>/assets/js/chat.js"></script>
<script>
  const currentUserId = <?= $currentUser['id'] ?>;
  const selectedUserId = <?= $selectedUserId ?>;
  const BASE_URL = '<?= BASE_URL ?>';

  if (selectedUserId > 0) {
    loadMessages(selectedUserId);
    startPolling(selectedUserId);
  }

  // Responsive
  function updateLayout() {
    if (window.innerWidth <= 768) {
      document.getElementById('backBtn')?.classList.remove('d-none');
      if (selectedUserId > 0) {
        document.getElementById('chatSidebar').classList.add('hidden');
      }
    } else {
      document.getElementById('backBtn')?.classList.add('d-none');
      document.getElementById('chatSidebar').classList.remove('hidden');
    }
  }

  window.addEventListener('resize', updateLayout);
  updateLayout();

  function showChatList() {
    document.getElementById('chatSidebar').classList.remove('hidden');
  }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>