<?php
/**
 * Application Constants
 * SISMED Social Media Application
 */

// Base URL - auto-detect for both localhost and IP access
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('BASE_URL', $protocol . '://' . $host . '/sosmed');

// Directory paths
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('AVATAR_PATH', UPLOAD_PATH . '/avatars');
define('POST_PATH', UPLOAD_PATH . '/posts');
define('STORY_PATH', UPLOAD_PATH . '/stories');
define('MESSAGE_PATH', UPLOAD_PATH . '/messages');

// URL paths for uploads
define('UPLOAD_URL', BASE_URL . '/uploads');
define('AVATAR_URL', UPLOAD_URL . '/avatars');
define('POST_URL', UPLOAD_URL . '/posts');
define('STORY_URL', UPLOAD_URL . '/stories');
define('MESSAGE_URL', UPLOAD_URL . '/messages');

// File upload limits
define('MAX_IMAGE_SIZE', 10 * 1024 * 1024); // 10MB
define('MAX_VIDEO_SIZE', 100 * 1024 * 1024); // 100MB
define('MAX_VIDEO_DURATION', 60); // 60 seconds for posts
define('STORY_EXPIRY_HOURS', 24); // Stories expire after 24 hours

// Allowed file types
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/webm', 'video/quicktime']);

// Pagination
define('POSTS_PER_PAGE', 10);
define('COMMENTS_PER_PAGE', 20);
define('MESSAGES_PER_PAGE', 50);
define('USERS_PER_PAGE', 20);

// Session settings
define('SESSION_LIFETIME', 60 * 60 * 24 * 7); // 7 days

// App info
define('APP_NAME', 'SISMED');
define('APP_VERSION', '1.0.0');
define('COPYRIGHT', '© ' . date('Y') . ' SISMED. All rights reserved.');

// Time zone
date_default_timezone_set('Asia/Jakarta');
