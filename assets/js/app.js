/**
 * PulTech Social - Main JavaScript
 */

// Base URL - use var to allow redeclaration in other scripts
var BASE_URL = document.querySelector('meta[name="base-url"]')?.content || '/sosmed';

// Toast Notification System
function showToast(message, type = 'info', duration = 3000) {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;

    const icons = {
        success: 'bi-check-circle-fill',
        error: 'bi-exclamation-circle-fill',
        warning: 'bi-exclamation-triangle-fill',
        info: 'bi-info-circle-fill'
    };

    toast.innerHTML = `
        <i class="bi ${icons[type]} toast-icon"></i>
        <span class="toast-message">${message}</span>
        <i class="bi bi-x toast-close" onclick="this.parentElement.remove()"></i>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'slideIn 0.3s ease reverse';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// Toggle Dropdown
function toggleDropdown(id, event) {
    // Stop event from bubbling to document click handler
    if (event) {
        event.stopPropagation();
    }

    const dropdown = document.getElementById(id);
    if (dropdown) {
        // Close other dropdowns first
        document.querySelectorAll('.dropdown.show').forEach(d => {
            if (d.id !== id) d.classList.remove('show');
        });

        // Toggle this dropdown
        dropdown.classList.toggle('show');
    }
}

// Close dropdowns when clicking outside
document.addEventListener('click', (e) => {
    if (!e.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown.show').forEach(d => d.classList.remove('show'));
    }
});

// Toggle Sidebar (Mobile)
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (sidebar) sidebar.classList.toggle('show');
    if (overlay) overlay.classList.toggle('show');
}

// AJAX Helper
async function ajax(url, options = {}) {
    const defaultOptions = {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        }
    };

    const config = { ...defaultOptions, ...options };

    if (config.data && config.method !== 'GET') {
        config.body = JSON.stringify(config.data);
        delete config.data;
    }

    try {
        const response = await fetch(url, config);
        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Request failed');
        }

        return data;
    } catch (error) {
        console.error('AJAX Error:', error);
        throw error;
    }
}

// Form Data AJAX
async function ajaxForm(url, formData) {
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Request failed');
        }

        return data;
    } catch (error) {
        console.error('AJAX Form Error:', error);
        throw error;
    }
}

// Like Post
async function likePost(postId, button) {
    try {
        const response = await ajax(`${BASE_URL}/api/likes.php`, {
            method: 'POST',
            data: { type: 'post', id: postId }
        });

        if (response.success) {
            button.classList.toggle('liked');
            const icon = button.querySelector('i');
            const count = button.querySelector('span');

            if (button.classList.contains('liked')) {
                icon.classList.remove('bi-heart');
                icon.classList.add('bi-heart-fill');
            } else {
                icon.classList.remove('bi-heart-fill');
                icon.classList.add('bi-heart');
            }

            if (count) {
                count.textContent = response.likes_count;
            }
        }
    } catch (error) {
        showToast('Gagal menyukai postingan', 'error');
    }
}

// Follow User
async function followUser(userId, button) {
    console.log('followUser called with userId:', userId);

    // Disable button during request
    button.disabled = true;
    const originalText = button.textContent;
    button.textContent = 'Loading...';

    try {
        const response = await ajax(`${BASE_URL}/api/follows.php`, {
            method: 'POST',
            data: { user_id: userId }
        });

        console.log('Follow response:', response);

        if (response.success) {
            if (response.following) {
                button.textContent = 'Mengikuti';
                button.classList.remove('btn-primary');
                button.classList.add('btn-secondary');
            } else {
                button.textContent = 'Ikuti';
                button.classList.remove('btn-secondary');
                button.classList.add('btn-primary');
            }

            // Update followers count on profile page
            const followersCountEl = document.querySelectorAll('.profile-stat-value')[1];
            if (followersCountEl && response.followers_count !== undefined) {
                followersCountEl.textContent = response.followers_count;
            }

            showToast(response.following ? 'Berhasil mengikuti' : 'Berhenti mengikuti', 'success');
        } else {
            button.textContent = originalText;
            showToast(response.message || 'Gagal mengikuti pengguna', 'error');
        }
    } catch (error) {
        console.error('Follow error:', error);
        button.textContent = originalText;
        showToast('Gagal mengikuti pengguna. Periksa koneksi.', 'error');
    } finally {
        button.disabled = false;
    }
}

// Share Post
function sharePost(postId) {
    const url = `${window.location.origin}${BASE_URL}/post.php?id=${postId}`;

    if (navigator.share) {
        navigator.share({
            title: 'PulTech Social',
            text: 'Lihat postingan ini!',
            url: url
        });
    } else {
        // Show share modal
        openShareModal(postId, url);
    }
}

// Open Share Modal
function openShareModal(postId, url) {
    const modal = document.getElementById('shareModal');
    if (modal) {
        modal.querySelector('#shareUrl').value = url;
        modal.classList.add('show');
    }
}

// Copy to Clipboard
function copyToClipboard(text, button) {
    navigator.clipboard.writeText(text).then(() => {
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="bi bi-check"></i> Disalin!';
        setTimeout(() => {
            button.innerHTML = originalText;
        }, 2000);
    });
}

// Close Modal
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
        // Reset form if exists
        const form = modal.querySelector('form');
        if (form) form.reset();
    }
}

// Open Create Post Modal
function openCreatePostModal(type = null) {
    const modal = document.getElementById('createPostModal');
    if (modal) {
        modal.classList.add('show');
        if (type === 'image' || type === 'video') {
            document.getElementById('postMedia').click();
        }
    }
}

// Open Create Story Modal
function openCreateStoryModal() {
    const modal = document.getElementById('createStoryModal');
    if (modal) {
        modal.classList.add('show');
        // Reset preview
        document.getElementById('storyUploadPlaceholder').style.display = 'flex';
        document.getElementById('storyImagePreview').style.display = 'none';
        document.getElementById('storyVideoPreview').style.display = 'none';
    }
}

// Open Story Viewer
function openStoryViewer(userId) {
    window.location.href = `${BASE_URL}/user/story.php?user_id=${userId}`;
}

// Format Number
function formatNumber(num) {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    }
    if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return num.toString();
}

// Time Ago
function timeAgo(datetime) {
    const now = new Date();
    const time = new Date(datetime);
    const diff = Math.floor((now - time) / 1000);

    if (diff < 60) return 'Baru saja';
    if (diff < 3600) return Math.floor(diff / 60) + ' menit lalu';
    if (diff < 86400) return Math.floor(diff / 3600) + ' jam lalu';
    if (diff < 604800) return Math.floor(diff / 86400) + ' hari lalu';
    if (diff < 2592000) return Math.floor(diff / 604800) + ' minggu lalu';
    if (diff < 31536000) return Math.floor(diff / 2592000) + ' bulan lalu';
    return Math.floor(diff / 31536000) + ' tahun lalu';
}

// Infinite Scroll
function initInfiniteScroll(container, loadMore) {
    let loading = false;
    let page = 1;

    window.addEventListener('scroll', async () => {
        if (loading) return;

        const scrollPos = window.innerHeight + window.scrollY;
        const threshold = document.body.offsetHeight - 500;

        if (scrollPos >= threshold) {
            loading = true;
            page++;

            try {
                await loadMore(page);
            } catch (error) {
                console.error('Load more error:', error);
            }

            loading = false;
        }
    });
}

// Image Preview
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (!preview) return;

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = (e) => {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Video Duration Check
function checkVideoDuration(input, maxDuration = 60) {
    return new Promise((resolve, reject) => {
        if (!input.files || !input.files[0]) {
            resolve(true);
            return;
        }

        const file = input.files[0];
        if (!file.type.startsWith('video/')) {
            resolve(true);
            return;
        }

        const video = document.createElement('video');
        video.preload = 'metadata';

        video.onloadedmetadata = () => {
            URL.revokeObjectURL(video.src);
            if (video.duration > maxDuration) {
                reject(new Error(`Video tidak boleh lebih dari ${maxDuration} detik`));
            } else {
                resolve(true);
            }
        };

        video.onerror = () => reject(new Error('Gagal memuat video'));
        video.src = URL.createObjectURL(file);
    });
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    // Auto-hide flash messages
    document.querySelectorAll('.auth-alert').forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

    // Start realtime notification polling for sidebar badges
    if (document.querySelector('.sidebar')) {
        startNotificationPolling();
    }
});

// ========== REALTIME SIDEBAR NOTIFICATION POLLING ==========
let notificationPollingInterval = null;

function startNotificationPolling() {
    // Initial update
    updateSidebarBadges();
    // Poll every 3 seconds
    notificationPollingInterval = setInterval(updateSidebarBadges, 3000);
}

async function updateSidebarBadges() {
    try {
        const response = await fetch(`${BASE_URL}/api/notifications.php?action=counts`);
        const data = await response.json();

        if (data.success) {
            // Update message badge
            if (data.unread_messages !== undefined) {
                updateNavBadge('pesan', data.unread_messages);
            }

            // Update notification badge
            if (data.unread_notifications !== undefined) {
                updateNavBadge('notifikasi', data.unread_notifications);
            }
        }
    } catch (error) {
        // Silently fail
    }
}

function updateNavBadge(menuName, count) {
    // Find nav item by text content
    const navItems = document.querySelectorAll('.nav-item');
    let targetNav = null;

    navItems.forEach(item => {
        const text = item.textContent.toLowerCase();
        if (text.includes(menuName)) {
            targetNav = item;
        }
    });

    if (!targetNav) return;

    let badge = targetNav.querySelector('.badge');

    if (count > 0) {
        const displayCount = count > 99 ? '99+' : count.toString();
        if (badge) {
            badge.textContent = displayCount;
        } else {
            badge = document.createElement('span');
            badge.className = 'badge badge-error';
            badge.style.cssText = 'margin-left: auto; background: #ef4444; color: white; padding: 2px 8px; border-radius: 20px; font-size: 0.75rem;';
            badge.textContent = displayCount;
            targetNav.appendChild(badge);
        }
    } else if (badge) {
        badge.remove();
    }
}
