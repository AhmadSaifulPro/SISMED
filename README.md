# 📸 SISMED - Sistem Informasi Sosial Media

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP Version">
  <img src="https://img.shields.io/badge/MySQL-8.0+-4479A1?style=flat-square&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/Chart.js-4.0-FF6384?style=flat-square&logo=chartdotjs&logoColor=white" alt="Chart.js">
  <img src="https://img.shields.io/badge/License-MIT-green?style=flat-square" alt="License">
</p>

<p align="center">
  <strong>Platform sosial media modern untuk berbagi momen berharga</strong>
</p>

---

## 📋 Daftar Isi

- [Apa itu SISMED?](#-apa-itu-sismed)
- [Perbandingan dengan Sosial Media Populer](#-perbandingan-dengan-sosial-media-populer)
- [Fitur](#-fitur)
- [Persyaratan Sistem](#-persyaratan-sistem)
- [Instalasi](#-instalasi)
- [Konfigurasi](#-konfigurasi)
- [Struktur Folder](#-struktur-folder)
- [API Endpoints](#-api-endpoints)
- [Screenshots](#-screenshots)
- [Lisensi](#-lisensi)

---

## 🤔 Apa itu SISMED?

**SISMED (Sistem Informasi Sosial Media)** adalah platform sosial media berbasis web yang dibangun menggunakan teknologi **PHP Native** dan **MySQL**. Aplikasi ini dirancang sebagai implementasi fungsional dari konsep-konsep yang ada pada platform sosial media populer seperti Instagram, Facebook, dan TikTok.

### Mengapa SISMED Dibuat?

SISMED dikembangkan dengan beberapa tujuan utama:

1. **📚 Tujuan Edukasi** - Sebagai media pembelajaran untuk memahami bagaimana aplikasi sosial media bekerja dari sisi teknis, mulai dari database design, authentication, file handling, hingga real-time messaging.

2. **🏢 Solusi Komunitas** - Dapat digunakan sebagai platform sosial media internal untuk organisasi, sekolah, kampus, atau komunitas yang ingin memiliki platform sendiri tanpa bergantung pada layanan pihak ketiga.

3. **🔧 Customizable** - Karena dibangun dengan PHP Native, aplikasi ini mudah dimodifikasi dan dikustomisasi sesuai kebutuhan spesifik pengguna.

4. **💡 Open Architecture** - Struktur kode yang jelas dan terdokumentasi memudahkan developer untuk mempelajari dan mengembangkan fitur baru.

### Siapa yang Cocok Menggunakan SISMED?

| Target Pengguna | Kegunaan |
|-----------------|----------|
| **Mahasiswa/Pelajar** | Belajar konsep web development, database, dan arsitektur aplikasi |
| **Developer Pemula** | Memahami struktur aplikasi sosial media real-world |
| **Organisasi/Komunitas** | Platform komunikasi internal yang private dan terkontrol |
| **Startup** | Base code untuk dikembangkan menjadi produk komersial |

---

## 📊 Perbandingan dengan Sosial Media Populer

Banyak orang bertanya: *"Apa bedanya SISMED dengan Instagram, Facebook, WhatsApp, atau TikTok?"*

Berikut adalah perbandingan lengkapnya:

### 🎯 Tujuan & Skala

| Aspek | IG, FB, WA, TikTok | SISMED |
|-------|-------------------|--------|
| **Tujuan Utama** | Platform komersial untuk miliar pengguna dengan monetisasi iklan | Sistem Informasi untuk pembelajaran & komunitas |
| **Skala Pengguna** | Miliaran user global | Ratusan hingga ribuan user (komunitas) |
| **Infrastruktur** | Data centers di seluruh dunia | Single server, self-hosted |
| **Tim Pengembang** | Ratusan hingga ribuan engineer | Bisa dikembangkan 1-5 developer |

### 🛠️ Arsitektur Teknis

| Komponen | Platform Komersial | SISMED |
|----------|-------------------|--------|
| **Backend** | Microservices (Java, Python, Erlang, Go, C++) | **PHP Native** - mudah dipelajari |
| **Database** | NoSQL + SQL hybrid, sharding, replication | **MySQL** - SQL tradisional |
| **Real-time** | WebSocket + custom protocols | **Long Polling** - sederhana & efektif |
| **Storage** | CDN global, object storage (AWS S3) | **Local filesystem** |
| **Caching** | Redis, Memcached, custom solutions | Session-based caching |

### 📚 Nilai Pembelajaran

Dengan mempelajari SISMED, Anda akan memahami:

| Konsep | Apa yang Dipelajari |
|--------|---------------------|
| **Database Design** | Relasi antar tabel: users, posts, comments, likes, follows, messages |
| **Authentication** | Session-based auth, middleware pattern, password hashing |
| **File Upload** | Handling avatar, media posts, validasi tipe & ukuran file |
| **REST API** | Endpoint design untuk berbagai fitur CRUD |
| **Security** | SQL injection prevention, XSS protection, CSRF tokens |
| **MVC Pattern** | Separation of concerns dalam struktur folder |
| **Real-time Features** | Implementasi chat dengan long polling |

### 💡 Kesimpulan

> **SISMED adalah implementasi fungsional** dari konsep sosial media yang bertujuan untuk pembelajaran dan penggunaan komunitas. Sementara Instagram, Facebook, dan TikTok adalah **produk komersial skala enterprise** dengan infrastruktur dan tim yang jauh lebih besar.
>
> Dengan SISMED, Anda bisa memahami **"behind the scenes"** bagaimana platform sosial media bekerja, sekaligus memiliki platform sendiri yang bisa dikustomisasi sesuai kebutuhan.

---

## ✨ Fitur

### 👤 Fitur Pengguna

| Fitur | Deskripsi |
|-------|-----------|
| **Autentikasi** | Register, login, logout, lupa password, ganti password dengan middleware keamanan |
| **Profil** | Edit profil, avatar, cover photo, dan bio |
| **Postingan** | Upload foto, video (max 1 menit), dan teks dengan berbagai opsi privasi |
| **Stories** | Buat story yang otomatis hilang dalam 24 jam dengan auto-cleanup |
| **Like** | Sukai postingan, komentar, dan story |
| **Komentar** | Komentar dan balas komentar (nested comments) |
| **Follow** | Ikuti pengguna lain untuk melihat konten mereka |
| **Chat Real-time** | Kirim pesan langsung dengan dukungan gambar/video |
| **Share** | Bagikan postingan ke WhatsApp, Facebook, Twitter, Telegram |
| **Notifikasi** | Notifikasi untuk like, komentar, follow, dan pesan |
| **Explore** | Jelajahi postingan populer |

### 👨‍💼 Fitur Admin

| Fitur | Deskripsi |
|-------|-----------|
| **Dashboard** | Statistik pengguna, postingan, pesan dengan grafik interaktif (Chart.js) |
| **Manajemen Pengguna** | Aktifkan/nonaktifkan, jadikan admin, hapus pengguna |
| **Manajemen Postingan** | Lihat, filter, dan hapus postingan |
| **Laporan** | Statistik lengkap, grafik pertumbuhan, top users, distribusi konten |
| **Pengaturan** | Info aplikasi, server, upload settings, storage, database stats |
| **Log Aktivitas** | Pantau aktivitas admin dengan filter tanggal, action, dan pagination |

---

## 💻 Persyaratan Sistem

- **PHP** >= 8.0
- **MySQL** >= 5.7 atau MariaDB >= 10.3
- **Apache** atau **Nginx** Web Server
- **mod_rewrite** enabled (Apache)
- **FFmpeg** (opsional, untuk validasi durasi video)

### Ekstensi PHP yang Diperlukan

```
- pdo_mysql
- gd atau imagick
- fileinfo
- mbstring
- json
```

---

## 🚀 Instalasi

### 1. Clone atau Download

```bash
# Clone repository
git clone https://github.com/pultech/sosmed.git

# Atau download dan ekstrak ke folder web server
# Contoh: c:\laragon\www\sosmed
```

### 2. Buat Database

```sql
CREATE DATABASE sosmed CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. Import Schema Database

```bash
# Menggunakan command line
mysql -u root -p sosmed < database/schema.sql

# Atau import melalui phpMyAdmin
```

### 4. Konfigurasi Database

Edit file `config/database.php`:

```php
private $host = 'localhost';
private $dbname = 'sosmed';
private $username = 'root';
private $password = '';
```

### 5. Konfigurasi Base URL

Edit file `config/constants.php`:

```php
define('BASE_URL', 'http://localhost/sosmed');
```

### 6. Buat Folder Upload

```bash
mkdir -p uploads/avatars uploads/posts uploads/stories uploads/messages
chmod 755 uploads -R
```

### 7. Akses Aplikasi

Buka browser dan akses: `http://localhost/sosmed`

### 8. Login Admin Default

```
Email: admin@pultech.com
Password: Admin123
```

> ⚠️ **Penting**: Segera ubah password admin setelah login pertama!

---

## ⚙️ Konfigurasi

### Konfigurasi Email (Opsional)

Edit file `config/mail.php` untuk mengaktifkan fitur reset password via email:

```php
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'your-email@gmail.com');
define('MAIL_PASSWORD', 'your-app-password');
```

### Konfigurasi Upload

Edit file `config/constants.php`:

```php
define('MAX_IMAGE_SIZE', 10 * 1024 * 1024); // 10MB
define('MAX_VIDEO_SIZE', 100 * 1024 * 1024); // 100MB
define('MAX_VIDEO_DURATION', 60); // 60 detik
define('STORY_EXPIRY_HOURS', 24); // 24 jam (story auto-hilang)
```

> 💡 **Story Auto-Cleanup**: Stories yang sudah expired (lebih dari 24 jam) akan otomatis dihapus dari database dan file medianya akan dihapus dari storage.

---

## 📁 Struktur Folder

```
sosmed/
├── admin/                  # Halaman admin panel
│   ├── index.php          # Dashboard admin (statistik & grafik)
│   ├── users.php          # Manajemen pengguna
│   ├── posts.php          # Manajemen postingan
│   ├── reports.php        # Laporan statistik & analisis
│   ├── settings.php       # Pengaturan sistem
│   └── logs.php           # Log aktivitas admin
├── api/                    # API endpoints
│   ├── posts.php          # CRUD postingan
│   ├── comments.php       # CRUD komentar
│   ├── likes.php          # Toggle like
│   ├── follows.php        # Follow/unfollow
│   ├── stories.php        # CRUD stories (dengan auto-cleanup expired)
│   ├── messages.php       # Real-time chat
│   ├── notifications.php  # Notifikasi
│   └── shares.php         # Share postingan
├── assets/
│   ├── css/               # Stylesheet
│   │   ├── style.css      # Main styles
│   │   ├── auth.css       # Auth pages
│   │   ├── dashboard.css  # Dashboard
│   │   └── admin.css      # Admin panel
│   ├── js/                # JavaScript
│   │   ├── app.js         # Main JS
│   │   ├── feed.js        # Feed functionality
│   │   └── chat.js        # Real-time chat
│   └── img/               # Static images
├── auth/                   # Authentication pages
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   ├── forgot-password.php
│   └── reset-password.php
├── config/                 # Configuration files
│   ├── database.php       # Database connection
│   ├── constants.php      # App constants
│   └── mail.php           # Email config
├── database/
│   └── schema.sql         # Database schema
├── includes/               # Shared components
│   ├── header.php
│   ├── footer.php
│   ├── sidebar.php
│   └── functions.php      # Helper functions
├── middleware/             # Auth middleware
│   ├── auth.php           # Require login
│   ├── guest.php          # Guest only
│   └── admin.php          # Admin only
├── uploads/                # User uploads
│   ├── avatars/
│   ├── posts/
│   ├── stories/
│   └── messages/
├── user/                   # User pages
│   ├── index.php          # Feed/Dashboard
│   ├── profile.php        # User profile
│   ├── edit-profile.php   # Edit profile
│   ├── settings.php       # Account settings
│   ├── messages.php       # Chat
│   ├── notifications.php  # Notifications
│   ├── story.php          # Story viewer
│   └── explore.php        # Explore posts
├── index.php              # Main entry
└── README.md              # Documentation
```

---

## 🔌 API Endpoints

### Posts API (`/api/posts.php`)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/api/posts.php` | Ambil daftar postingan |
| GET | `/api/posts.php?user_id=1` | Ambil postingan user tertentu |
| POST | `/api/posts.php` | Buat postingan baru |
| DELETE | `/api/posts.php?id=1` | Hapus postingan |

### Comments API (`/api/comments.php`)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/api/comments.php?post_id=1` | Ambil komentar postingan |
| POST | `/api/comments.php` | Tambah komentar |
| DELETE | `/api/comments.php?id=1` | Hapus komentar |

### Likes API (`/api/likes.php`)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| POST | `/api/likes.php` | Toggle like (post/comment/story) |

### Follows API (`/api/follows.php`)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/api/follows.php?type=followers` | Ambil daftar followers |
| POST | `/api/follows.php` | Toggle follow/unfollow |

### Messages API (`/api/messages.php`)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/api/messages.php` | Ambil daftar percakapan |
| GET | `/api/messages.php?action=messages&user_id=1` | Ambil pesan |
| GET | `/api/messages.php?action=poll&user_id=1` | Long polling untuk real-time |
| POST | `/api/messages.php` | Kirim pesan |

---

## 📱 Screenshots

### Halaman Login
Desain modern dengan glassmorphism effect dan animasi gradient background.

### Dashboard / Feed
Tampilan feed dengan stories, create post, dan postingan dari following.

### Profil Pengguna
Grid layout untuk postingan dengan statistik followers/following.

### Chat Real-time
Antarmuka chat responsif dengan dukungan media.

### Admin Dashboard
Panel admin dengan grafik statistik dan monitoring aktivitas.

---

## 🔒 Keamanan

Aplikasi ini menerapkan berbagai langkah keamanan:

- ✅ **Password Hashing** - Menggunakan `password_hash()` dengan bcrypt
- ✅ **SQL Injection Prevention** - PDO prepared statements
- ✅ **XSS Protection** - `htmlspecialchars()` untuk output
- ✅ **CSRF Protection** - Token-based form validation
- ✅ **File Upload Security** - Validasi tipe file, ukuran, dan sanitasi nama
- ✅ **Session Security** - Secure session handling

---

## 🛠️ Development

### Menjalankan dengan Laragon

1. Pastikan Laragon sudah terinstall
2. Copy folder `sosmed` ke `c:\laragon\www\`
3. Start Apache dan MySQL dari Laragon
4. Akses `http://localhost/sosmed`

### Debugging

Aktifkan error reporting di `config/constants.php`:

```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

---

## 📄 Lisensi

© 2024 **SISMED / PulTech**. All rights reserved.

Aplikasi ini dilindungi oleh hak cipta. Penggunaan, modifikasi, dan distribusi hanya diperbolehkan dengan izin tertulis.

---

## 🤝 Kontribusi

Kontribusi selalu diterima! Silakan buat pull request atau laporkan issue.

---

## 📧 Kontak

Untuk pertanyaan dan dukungan, hubungi:
- Email: support@pultech.com
- Website: https://pultech.com

---

<p align="center">
  Made with ❤️ by <strong>SISMED Team</strong>
</p>
