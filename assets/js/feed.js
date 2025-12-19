/**
 * PulTech Social - Feed JavaScript
 * NOTE: Requires app.js to be loaded first for BASE_URL
 */

// Toggle Comments
function toggleComments(postId) {
  const section = document.getElementById(`comments${postId}`);
  if (section) {
    if (section.style.display === 'none') {
      section.style.display = 'block';
      loadComments(postId);
    } else {
      section.style.display = 'none';
    }
  }
}

// Load Comments
async function loadComments(postId) {
  const container = document.getElementById(`commentsList${postId}`);
  if (!container) return;

  container.innerHTML = '<div class="text-center p-3"><div class="spinner"></div></div>';

  try {
    const response = await fetch(`${BASE_URL}/api/comments.php?post_id=${postId}`);
    const data = await response.json();

    if (data.success && data.comments.length > 0) {
      container.innerHTML = data.comments.map(comment => renderComment(comment)).join('');
    } else {
      container.innerHTML = '<p class="text-center text-muted p-3">Belum ada komentar</p>';
    }
  } catch (error) {
    container.innerHTML = '<p class="text-center text-error p-3">Gagal memuat komentar</p>';
  }
}

// Render Comment
function renderComment(comment) {
  const replies = comment.replies ? comment.replies.map(r => renderComment(r)).join('') : '';

  return `
        <div class="comment-item" data-comment-id="${comment.id}">
            <img src="${comment.avatar_url}" alt="${comment.username}" class="avatar avatar-sm">
            <div style="flex: 1;">
                <div class="comment-bubble">
                    <span class="comment-author">${comment.full_name || comment.username}</span>
                    <p class="comment-text">${comment.content}</p>
                </div>
                <div class="comment-meta">
                    <span>${comment.time_ago}</span>
                    <button onclick="likeComment(${comment.id}, this)">${comment.likes_count} suka</button>
                    <button onclick="replyToComment(${comment.id}, '${comment.username}')">Balas</button>
                </div>
                ${replies ? `<div class="comment-replies">${replies}</div>` : ''}
            </div>
        </div>
    `;
}

// Submit Comment
async function submitComment(postId, input) {
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
      input.value = '';
      loadComments(postId);
      showToast('Komentar berhasil ditambahkan', 'success');
    } else {
      showToast(data.message || 'Gagal menambahkan komentar', 'error');
    }
  } catch (error) {
    showToast('Terjadi kesalahan', 'error');
  }
}

// Reply to Comment
function replyToComment(commentId, username) {
  const postCard = document.querySelector(`[data-comment-id="${commentId}"]`).closest('.post-card');
  const input = postCard.querySelector('.comment-input');
  input.value = `@${username} `;
  input.focus();
  input.dataset.replyTo = commentId;
}

// Like Comment
async function likeComment(commentId, button) {
  try {
    const response = await fetch(`${BASE_URL}/api/likes.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({
        type: 'comment',
        id: commentId
      })
    });

    const data = await response.json();

    if (data.success) {
      button.textContent = `${data.likes_count} suka`;
    }
  } catch (error) {
    console.error('Like comment error:', error);
  }
}

// Preview Post Media
function previewPostMedia(input) {
  const file = input.files[0];
  if (!file) return;

  const preview = document.getElementById('mediaPreview');
  const imagePreview = document.getElementById('imagePreview');
  const videoPreview = document.getElementById('videoPreview');

  preview.style.display = 'block';

  if (file.type.startsWith('image/')) {
    imagePreview.style.display = 'block';
    videoPreview.style.display = 'none';

    const reader = new FileReader();
    reader.onload = (e) => {
      imagePreview.src = e.target.result;
    };
    reader.readAsDataURL(file);
  } else if (file.type.startsWith('video/')) {
    imagePreview.style.display = 'none';
    videoPreview.style.display = 'block';

    // Check video duration
    const video = document.createElement('video');
    video.preload = 'metadata';
    video.onloadedmetadata = () => {
      if (video.duration > 60) {
        showToast('Video tidak boleh lebih dari 1 menit', 'error');
        input.value = '';
        preview.style.display = 'none';
        return;
      }
      videoPreview.src = URL.createObjectURL(file);
    };
    video.src = URL.createObjectURL(file);
  }
}

// Remove Media
function removeMedia() {
  document.getElementById('postMedia').value = '';
  document.getElementById('mediaPreview').style.display = 'none';
  document.getElementById('imagePreview').style.display = 'none';
  document.getElementById('videoPreview').style.display = 'none';
}

// Preview Story Media
function previewStoryMedia(input) {
  const file = input.files[0];
  if (!file) return;

  const placeholder = document.getElementById('storyUploadPlaceholder');
  const imagePreview = document.getElementById('storyImagePreview');
  const videoPreview = document.getElementById('storyVideoPreview');

  placeholder.style.display = 'none';

  if (file.type.startsWith('image/')) {
    imagePreview.style.display = 'block';
    videoPreview.style.display = 'none';

    const reader = new FileReader();
    reader.onload = (e) => {
      imagePreview.src = e.target.result;
    };
    reader.readAsDataURL(file);
  } else if (file.type.startsWith('video/')) {
    imagePreview.style.display = 'none';
    videoPreview.style.display = 'block';
    videoPreview.src = URL.createObjectURL(file);
  }
}

// Create Post Form Submit
document.getElementById('createPostForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();

  const form = e.target;
  const formData = new FormData(form);
  const submitBtn = form.querySelector('[type="submit"]');

  submitBtn.disabled = true;
  submitBtn.innerHTML = '<div class="spinner" style="width: 20px; height: 20px;"></div>';

  try {
    const response = await fetch(`${BASE_URL}/api/posts.php`, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: formData
    });

    const data = await response.json();

    if (data.success) {
      showToast('Postingan berhasil dibuat!', 'success');
      closeModal('createPostModal');
      form.reset();
      removeMedia();
      location.reload();
    } else {
      showToast(data.message || 'Gagal membuat postingan', 'error');
    }
  } catch (error) {
    showToast('Terjadi kesalahan', 'error');
  } finally {
    submitBtn.disabled = false;
    submitBtn.innerHTML = '<i class="bi bi-send"></i> Posting';
  }
});

// Create Story Form Submit
document.getElementById('createStoryForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();

  const form = e.target;
  const formData = new FormData(form);
  const submitBtn = form.querySelector('[type="submit"]');

  // Debug: log form data
  console.log('Story form data:');
  for (let [key, value] of formData.entries()) {
    console.log(`  ${key}:`, value);
  }

  submitBtn.disabled = true;
  submitBtn.textContent = 'Membuat...';

  try {
    const response = await fetch(`${BASE_URL}/api/stories.php`, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: formData
    });

    console.log('Story response status:', response.status);
    const data = await response.json();
    console.log('Story response data:', data);

    if (data.success) {
      showToast('Story berhasil dibuat!', 'success');
      closeModal('createStoryModal');
      location.reload();
    } else {
      showToast(data.message || 'Gagal membuat story', 'error');
    }
  } catch (error) {
    console.error('Story creation error:', error);
    showToast('Terjadi kesalahan saat membuat story', 'error');
  } finally {
    submitBtn.disabled = false;
    submitBtn.textContent = 'Bagikan Story';
  }
});

// Delete Post
async function deletePost(postId) {
  if (!confirm('Apakah Anda yakin ingin menghapus postingan ini?')) return;

  try {
    const response = await fetch(`${BASE_URL}/api/posts.php?id=${postId}`, {
      method: 'DELETE',
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    const data = await response.json();

    if (data.success) {
      document.querySelector(`[data-post-id="${postId}"]`).remove();
      showToast('Postingan berhasil dihapus', 'success');
    } else {
      showToast(data.message || 'Gagal menghapus postingan', 'error');
    }
  } catch (error) {
    showToast('Terjadi kesalahan', 'error');
  }
}

// Share Functions
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

function shareToTelegram() {
  const url = document.getElementById('shareUrl').value;
  window.open(`https://t.me/share/url?url=${encodeURIComponent(url)}&text=${encodeURIComponent('Lihat postingan ini!')}`, '_blank');
}

// Open Story Viewer
function openStoryViewer(userId) {
  // TODO: Implement story viewer modal
  window.location.href = `${BASE_URL}/user/story.php?user_id=${userId}`;
}

// Open Create Story Modal
function openCreateStoryModal() {
  const modal = document.getElementById('createStoryModal');
  if (modal) {
    document.getElementById('storyUploadPlaceholder').style.display = 'flex';
    document.getElementById('storyImagePreview').style.display = 'none';
    document.getElementById('storyVideoPreview').style.display = 'none';
    modal.classList.add('show');
  }
}
