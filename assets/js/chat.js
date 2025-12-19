/**
 * PulTech Social - Chat JavaScript
 * Real-time messaging with fast polling
 */

let pollingController = null;
let lastMessageId = 0;
let notificationPollingInterval = null;

function selectChat(userId) {
  window.location.href = `${BASE_URL}/user/messages.php?user_id=${userId}`;
}

async function loadMessages(userId) {
  const container = document.getElementById('chatMessages');
  if (!container) return;

  try {
    const response = await fetch(`${BASE_URL}/api/messages.php?action=messages&user_id=${userId}`);
    const data = await response.json();

    if (data.success) {
      if (data.messages && data.messages.length > 0) {
        container.innerHTML = data.messages.map(msg => renderMessage(msg)).join('');
        lastMessageId = data.messages[data.messages.length - 1].id;
        scrollToBottom();
      } else {
        container.innerHTML = `
          <div class="text-center p-4 text-muted">
            <p>Belum ada pesan. Mulai percakapan!</p>
          </div>
        `;
      }
    }
  } catch (error) {
    console.error('Load messages error:', error);
    container.innerHTML = '<div class="text-center p-4 text-error">Gagal memuat pesan</div>';
  }
}

function renderMessage(msg) {
  const isMedia = msg.media_type !== 'text' && msg.media_url;

  let content = '';
  if (isMedia) {
    if (msg.media_type === 'image') {
      content = `<img src="${msg.media_url}" alt="Media" style="max-width: 100%; border-radius: 8px; margin-bottom: 4px;">`;
    } else if (msg.media_type === 'video') {
      content = `<video src="${msg.media_url}" controls style="max-width: 100%; border-radius: 8px; margin-bottom: 4px;"></video>`;
    }
  }

  if (msg.message) {
    content += `<div>${escapeHtml(msg.message)}</div>`;
  }

  const time = msg.time || new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

  return `
    <div class="message message-${msg.direction}" data-message-id="${msg.id}">
      ${content}
      <div class="message-time">${time}</div>
    </div>
  `;
}

function escapeHtml(text) {
  if (!text) return '';
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

function scrollToBottom() {
  const container = document.getElementById('chatMessages');
  if (container) {
    requestAnimationFrame(() => {
      container.scrollTop = container.scrollHeight;
    });
  }
}

// FAST Send Message - No delay, instant display
async function sendMessage() {
  const input = document.getElementById('messageInput');
  const messageText = input.value.trim();
  const fileInput = document.getElementById('chatMedia');
  const sendBtn = document.querySelector('.chat-input-container .btn-primary');

  if (!messageText && (!fileInput || !fileInput.files.length)) return;

  // Store values before clearing
  const messageToSend = messageText;
  const currentTime = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

  // Clear input IMMEDIATELY
  input.value = '';

  // Disable buttons briefly
  if (sendBtn) sendBtn.disabled = true;

  // Generate temp ID
  const tempId = 'temp-' + Date.now();

  // Get container and remove empty state
  const container = document.getElementById('chatMessages');
  const emptyMsg = container.querySelector('.text-muted');
  if (emptyMsg) emptyMsg.remove();

  // INSTANT display - show message immediately with actual time (no "Mengirim...")
  const messageHtml = `
    <div class="message message-sent" data-message-id="${tempId}">
      <div>${escapeHtml(messageToSend)}</div>
      <div class="message-time">${currentTime}</div>
    </div>
  `;
  container.insertAdjacentHTML('beforeend', messageHtml);
  scrollToBottom();

  // Prepare form data
  const formData = new FormData();
  formData.append('receiver_id', selectedUserId);
  formData.append('message', messageToSend);

  if (fileInput && fileInput.files.length > 0) {
    formData.append('media', fileInput.files[0]);
  }

  // Send to server in background (fire and forget style, but track for error handling)
  try {
    const response = await fetch(`${BASE_URL}/api/messages.php`, {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: formData
    });

    const data = await response.json();

    if (data.success && data.message) {
      // Update temp message with real ID
      const tempMsg = container.querySelector(`[data-message-id="${tempId}"]`);
      if (tempMsg) {
        tempMsg.setAttribute('data-message-id', data.message.id || data.message_id);
      }
      lastMessageId = data.message_id || data.message.id;

      // Clear file input
      if (fileInput) fileInput.value = '';
      hideChatMediaPreview();
    } else if (!data.success) {
      // Remove failed message and show error
      const tempMsg = container.querySelector(`[data-message-id="${tempId}"]`);
      if (tempMsg) tempMsg.remove();
      showToast(data.message || 'Gagal mengirim pesan', 'error');
    }
  } catch (error) {
    console.error('Send message error:', error);
    // Remove temp message on network error
    const tempMsg = container.querySelector(`[data-message-id="${tempId}"]`);
    if (tempMsg) tempMsg.remove();
    showToast('Gagal mengirim. Periksa koneksi.', 'error');
  } finally {
    if (sendBtn) sendBtn.disabled = false;
    input.focus();
  }
}

// Start fast polling
function startPolling(userId) {
  console.log('Starting fast polling for user:', userId);
  pollMessages(userId);
}

// Fast polling - 500ms interval for near-realtime
async function pollMessages(userId) {
  if (pollingController) {
    pollingController.abort();
  }
  pollingController = new AbortController();

  try {
    const response = await fetch(
      `${BASE_URL}/api/messages.php?action=poll&user_id=${userId}&last_id=${lastMessageId}`,
      { signal: pollingController.signal }
    );

    const data = await response.json();

    if (data.success && data.messages && data.messages.length > 0) {
      const container = document.getElementById('chatMessages');
      if (!container) return;

      // Remove empty placeholder
      const emptyMsg = container.querySelector('.text-muted');
      if (emptyMsg) emptyMsg.remove();

      let addedNew = false;
      data.messages.forEach(msg => {
        // Skip if already exists
        if (!container.querySelector(`[data-message-id="${msg.id}"]`)) {
          container.insertAdjacentHTML('beforeend', renderMessage(msg));
          addedNew = true;
        }
      });

      if (addedNew) {
        lastMessageId = data.messages[data.messages.length - 1].id;
        scrollToBottom();
      }
    }

    // Continue polling - 500ms for fast updates
    setTimeout(() => pollMessages(userId), 500);
  } catch (error) {
    if (error.name !== 'AbortError') {
      console.error('Polling error:', error);
      setTimeout(() => pollMessages(userId), 1000);
    }
  }
}

// Media preview
function previewChatMedia(input) {
  if (!input.files.length) return;

  const container = document.getElementById('chatMessages');
  const preview = document.createElement('div');
  preview.id = 'mediaPreviewIndicator';
  preview.className = 'message message-sent';
  preview.style.opacity = '0.6';
  preview.innerHTML = `
    <div style="display: flex; align-items: center; gap: 8px;">
      <i class="bi bi-image"></i>
      <span>${input.files[0].name}</span>
      <button onclick="hideChatMediaPreview()" style="background: none; border: none; cursor: pointer;">
        <i class="bi bi-x"></i>
      </button>
    </div>
  `;
  container.appendChild(preview);
  scrollToBottom();
}

function hideChatMediaPreview() {
  const preview = document.getElementById('mediaPreviewIndicator');
  if (preview) preview.remove();
  const fileInput = document.getElementById('chatMedia');
  if (fileInput) fileInput.value = '';
}

// Open new chat modal
function openNewChatModal() {
  const modal = document.getElementById('newChatModal');
  if (modal) {
    modal.classList.add('show');
    const searchInput = document.getElementById('searchUserInput');
    if (searchInput) {
      searchInput.value = '';
      searchInput.focus();
    }
    const results = document.getElementById('userSearchResults');
    if (results) results.innerHTML = '';
  }
}

// Search users for new chat
let searchTimeout;
async function searchUsers(query) {
  clearTimeout(searchTimeout);

  const resultsContainer = document.getElementById('userSearchResults');
  if (!resultsContainer) return;

  if (query.length < 2) {
    resultsContainer.innerHTML = '';
    return;
  }

  searchTimeout = setTimeout(async () => {
    try {
      const response = await fetch(`${BASE_URL}/api/users.php?search=${encodeURIComponent(query)}`);
      const data = await response.json();

      if (data.success && data.users.length > 0) {
        resultsContainer.innerHTML = data.users.map(user => `
          <div class="suggestion-item" onclick="selectChat(${user.id})" style="cursor: pointer;">
            <img src="${user.avatar_url}" alt="" class="avatar">
            <div class="suggestion-info">
              <div class="suggestion-name">${user.full_name || user.username}</div>
              <div class="suggestion-meta">@${user.username}</div>
            </div>
          </div>
        `).join('');
      } else {
        resultsContainer.innerHTML = '<p class="text-center text-muted p-3">Tidak ada hasil</p>';
      }
    } catch (error) {
      console.error('Search error:', error);
    }
  }, 200);
}

// Sidebar chat search
let sidebarSearchTimeout;
document.getElementById('chatSearch')?.addEventListener('input', function () {
  const query = this.value.trim();
  const resultsContainer = document.getElementById('chatUserResults');

  clearTimeout(sidebarSearchTimeout);

  if (query.length < 2) {
    if (resultsContainer) resultsContainer.style.display = 'none';
    document.querySelectorAll('.chat-item').forEach(item => item.style.display = 'flex');
    return;
  }

  // Filter existing conversations
  document.querySelectorAll('.chat-item').forEach(item => {
    const name = item.querySelector('.chat-item-name')?.textContent.toLowerCase() || '';
    item.style.display = name.includes(query.toLowerCase()) ? 'flex' : 'none';
  });

  // Search for new users
  sidebarSearchTimeout = setTimeout(async () => {
    try {
      const response = await fetch(`${BASE_URL}/api/users.php?search=${encodeURIComponent(query)}`);
      const data = await response.json();

      if (data.success && data.users.length > 0 && resultsContainer) {
        resultsContainer.innerHTML = `
          <div style="padding: 8px 12px; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Pengguna Lain</div>
          ${data.users.map(user => `
            <div class="chat-user-result-item" onclick="selectChat(${user.id})" style="display: flex; align-items: center; gap: 12px; padding: 10px 12px; cursor: pointer;">
              <img src="${user.avatar_url}" alt="" class="avatar avatar-sm">
              <div>
                <div style="font-weight: 500;">${user.full_name || user.username}</div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">@${user.username}</div>
              </div>
            </div>
          `).join('')}
        `;
        resultsContainer.style.display = 'block';
      }
    } catch (error) {
      console.error('Search error:', error);
    }
  }, 200);
});

// Close search results when clicking outside
document.addEventListener('click', function (e) {
  const chatSearch = document.querySelector('.chat-search');
  const resultsContainer = document.getElementById('chatUserResults');
  if (chatSearch && resultsContainer && !chatSearch.contains(e.target)) {
    resultsContainer.style.display = 'none';
  }
});

