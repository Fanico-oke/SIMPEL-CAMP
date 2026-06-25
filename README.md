<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-005C84?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/Bootstrap_Icons-563D7C?style=for-the-badge&logo=bootstrap&logoColor=white" alt="Bootstrap Icons">
  <img src="https://img.shields.io/badge/License-MIT-green?style=for-the-badge" alt="License">
</p>

<h1 align="center">⛺ SIMPEL-CAMP</h1>
<p align="center">
  <strong>Sistem Informasi Penyewaan Alat Camping</strong><br>
  Aplikasi web manajemen penyewaan alat camping dengan fitur lengkap untuk pelanggan, admin, dan super admin.
</p>

---

## 📋 Deskripsi

**SIMPEL-CAMP** adalah aplikasi web berbasis PHP untuk mengelola penyewaan alat-alat camping secara digital. Aplikasi ini menyediakan platform yang memudahkan pelanggan dalam menyewa alat camping serta membantu admin dalam mengelola inventaris, transaksi, dan laporan.

## ✨ Fitur Utama

### 👤 Pelanggan
- 🏕️ Katalog alat camping dengan pencarian & filter
- 🛒 Wishlist / Keranjang sewa
- 📋 Reservasi & pemesanan online
- 💳 Pembayaran dengan upload bukti transfer
- 📄 Cetak nota transaksi
- 🔄 Perpanjangan sewa
- 📊 Riwayat transaksi
- 🔔 Notifikasi real-time
- 🏅 Sistem member level

### 🛠️ Admin
- 📦 Kelola barang (CRUD + upload gambar)
- 👥 Kelola pengguna & member
- 💰 Manajemen transaksi & pembayaran
- 📊 Laporan & statistik
- 🎫 Kelola promo
- 📰 Kelola konten website
- ⚙️ Pengaturan sistem
- 🔔 Sistem notifikasi

### 👑 Super Admin
- 📊 Dashboard monitoring keseluruhan
- 📝 Log aktivitas sistem
- 👤 Manajemen profil

## 🛠️ Tech Stack

| Komponen | Teknologi |
|----------|-----------|
| **Backend** | PHP 8.x (Native OOP) |
| **Database** | MySQL / MariaDB |
| **Frontend** | HTML5, CSS3, JavaScript (Vanilla) |
| **Icons** | Bootstrap Icons |
| **Fonts** | Google Fonts (Inter, Outfit) |
| **Server** | Apache (Laragon / XAMPP) |

## 📁 Struktur Project

```
SIMPEL-CAMP/
├── api/                    # REST API endpoints
│   ├── barang.php
│   ├── reservasi.php
│   ├── transaksi.php
│   ├── pembayaran.php
│   ├── wishlist.php
│   └── ...
├── classes/                # OOP Model classes
│   ├── Barang.php
│   ├── Reservasi.php
│   ├── Transaksi.php
│   ├── User.php
│   └── ...
├── config/
│   ├── constants.php       # App config & DB credentials
│   └── database.php        # PDO connection
├── frontend/
│   ├── css/                # Stylesheets
│   ├── img/                # Gambar produk
│   ├── js/                 # JavaScript
│   └── uploads/            # User uploads
├── includes/
│   ├── header_glass.php    # Glassmorphism topbar
│   ├── sidebar.php         # Admin sidebar
│   ├── sidebar_pelanggan.php
│   ├── sidebar_superadmin.php
│   └── ...
├── pages/
│   ├── admin/              # 14 halaman admin
│   ├── pelanggan/          # 15 halaman pelanggan
│   └── superadmin/         # 3 halaman super admin
├── docs/                   # Dokumentasi project
├── migrations/             # Database migrations
├── index.php               # Landing page
├── login.php               # Autentikasi
├── register.php            # Registrasi
└── .htaccess               # URL rewriting
```

## 🚀 Instalasi

### Prasyarat
- PHP >= 8.0
- MySQL / MariaDB
- Apache Web Server (Laragon / XAMPP)

### Langkah Instalasi

1. **Clone repository**
   ```bash
   git clone https://github.com/Fanico-oke/SIMPEL-CAMP.git
   ```

2. **Pindahkan ke direktori web server**
   ```bash
   # Laragon
   mv SIMPEL-CAMP /path/to/laragon/www/pemweb

   # XAMPP
   mv SIMPEL-CAMP /path/to/xampp/htdocs/pemweb
   ```

3. **Buat database MySQL**
   ```sql
   CREATE DATABASE simpelcamp;
   ```

4. **Import database**
   - Buka phpMyAdmin
   - Pilih database `simpelcamp`
   - Import file SQL dari `docs/` atau jalankan migration

5. **Konfigurasi database**
   
   Edit file `config/constants.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'simpelcamp');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

6. **Akses aplikasi**
   ```
   http://localhost/pemweb
   ```

## 👥 Role & Akses

| Role | Akses | Deskripsi |
|------|-------|-----------|
| **Pelanggan** | `/pages/pelanggan/` | Sewa alat, pembayaran, riwayat |
| **Admin** | `/pages/admin/` | Kelola barang, transaksi, laporan |
| **Super Admin** | `/pages/superadmin/` | Monitoring & log aktivitas |

## 🎨 Desain UI

- **Glassmorphism** — Header topbar dengan efek kaca transparan
- **Floating Capsule Sidebar** — Navigasi sidebar melayang dengan animasi
- **Animated Transitions** — Animasi masuk halaman dan hover effects
- **Responsive Design** — Mendukung desktop dan mobile
- **Color Palette** — Hijau (#52B788) & Emas (#D4A373) sebagai identitas brand

## 📄 Lisensi

Project ini dibuat untuk keperluan akademik — Tugas Pemrograman Web.

## 👨‍💻 Tim Pengembang

**Kelompok 2** — Pemrograman Web

---

<p align="center">
  Made with ❤️ using PHP
</p>
