# SIMPEL-CAMP — App Flow & Core Features v2.0

> Sistem Informasi Penyewaan Peralatan Camping  
> Stack: PHP Native + Bootstrap 5.3 + MySQL  
> Terakhir diperbarui: 17 Juni 2026

---

## 📑 Daftar Diagram

| # | Diagram | Format | File |
|---|---------|--------|------|
| 1 | Use Case Diagram (Original) | JPEG | `use_case_original.jpeg` |
| 2 | Use Case Diagram (Extended) | PNG | `use_case_extended.png` |
| 3 | Flowchart Pelanggan | PNG | `flowchart_pelanggan.png` |
| 4 | Flowchart Admin | PNG | `flowchart_admin.png` |
| 5 | Flowchart Super Admin | PNG | `flowchart_superadmin.png` |
| 6 | Activity Diagram (Swimlane) | HTML | `activity_diagram.html` |
| 7 | Sequence Diagram (6 diagram) | HTML | `sequence_diagrams.html` |
| 8 | ERD (15 tabel) | HTML | `erd_diagram.html` |
| 9 | Sitemap | HTML | `sitemap_diagram.html` |
| 10 | Status Lifecycle | HTML | `status_lifecycle.html` |
| 11 | Sequence 1 Registrasi | PNG | `seq_1_registrasi_login.png` |

> Semua file tersimpan di `docs/app_flow/`. File HTML buka di browser untuk render diagram + klik kanan → Save image.

---

## 📋 Ringkasan Proyek

| Item | Detail |
|------|--------|
| **Nama Sistem** | SIMPEL-CAMP |
| **Teknologi** | PHP Native, Bootstrap 5.3, JavaScript, Laragon |
| **Arsitektur** | Multi-user (3 roles: Pelanggan, Admin, Super Admin) |
| **Tipe** | Web-based application |

---

## 📐 Use Case Diagram

### Use Case Diagram Awal (Original)

![Use Case Diagram Original - dari dokumen awal dengan 2 aktor (Pelanggan & Admin) dan 17 use case](C:/Users/DELL/.gemini/antigravity/brain/79510e7a-b400-4d41-a76c-e0ee7ee2fc59/use_case_original.jpeg)

---

### Use Case Diagram Lengkap (Extended)

Berikut use case diagram yang diperluas sesuai seluruh fitur yang sudah dibangun (29 use case + 4 aktor):

![Use Case Diagram Extended - 4 aktor (Pengguna, Pelanggan, Admin, Super Admin) dengan 29 use case dan relasi extend/inherit](C:/Users/DELL/.gemini/antigravity/brain/79510e7a-b400-4d41-a76c-e0ee7ee2fc59/use_case_diagram_1781686831738.png)

#### Perbandingan Original vs Extended

| Use Case Original (17) | ✅ | Use Case Tambahan Baru (12) |
|-------------------------|:--:|---------------------------|
| Registrasi | ✅ | 🆕 Melakukan Pembayaran Online |
| Login | ✅ | 🆕 Melihat Nota Transaksi |
| Logout | ✅ | 🆕 Melihat Notifikasi |
| Melihat E-Katalog | ✅ | 🆕 Mengelola Profil |
| Melihat Stok | ✅ | 🆕 Melihat Info Member |
| Melakukan Reservasi | ✅ | 🆕 Mengelola Promo |
| Mengajukan Perpanjangan Sewa | ✅ | 🆕 Melihat Dashboard |
| Melihat Status Perpanjangan | ✅ | 🆕 Kelola Konten Website |
| Melihat Riwayat Penyewaan | ✅ | 🆕 Kelola Pengguna |
| Mengelola Data Barang | ✅ | 🆕 Pengaturan Sistem |
| Mengelola Data Pelanggan | ✅ | 🆕 Melihat Log Aktivitas |
| Mengelola Data Reservasi | ✅ | 🆕 Role Super Admin |
| Menginput Transaksi Langsung | ✅ | |
| Konfirmasi Perpanjangan Sewa | ✅ | |
| Memproses Pengembalian Barang | ✅ | |
| Melihat Laporan Penjualan (+cetak) | ✅ | |
| Melihat Laporan Keuangan (+cetak) | ✅ | |

---

## 🔄 Flowchart

### Flowchart Alur Pelanggan

![Flowchart Pelanggan - Alur lengkap dari login, katalog, reservasi, pembayaran, perpanjangan, sampai pengembalian](C:/Users/DELL/.gemini/antigravity/brain/79510e7a-b400-4d41-a76c-e0ee7ee2fc59/flowchart_pelanggan_1781686858867.png)

**Keterangan Simbol:**
| Simbol | Warna | Fungsi |
|--------|-------|--------|
| Oval (rounded) | 🔵 Biru muda | Start / End |
| Rectangle | 🟠 Oranye muda | Process |
| Diamond | 🔵 Biru muda | Decision (Ya/Tidak) |
| Parallelogram | 🟡 Kuning muda | Input / Output |

---

### Flowchart Alur Admin

![Flowchart Admin - Alur lengkap: kelola barang, reservasi approve/reject, transaksi walk-in, perpanjangan, pengembalian + denda, laporan + cetak](C:/Users/DELL/.gemini/antigravity/brain/79510e7a-b400-4d41-a76c-e0ee7ee2fc59/flowchart_admin_v2_1781687008291.png)

---

### Flowchart Alur Super Admin

![Flowchart Super Admin - Alur: akses semua fitur admin, kelola konten website, kelola pengguna, pengaturan sistem, log aktivitas](C:/Users/DELL/.gemini/antigravity/brain/79510e7a-b400-4d41-a76c-e0ee7ee2fc59/flowchart_superadmin_1781687035901.png)

---

## 📋 Activity Diagram (Swimlane)

Activity diagram menggunakan swimlane per aktor (Pelanggan / Sistem / Admin) dengan simbol UML standar:
- ● Start/End node
- Rectangle = Action/Process
- ◇ Diamond = Decision
- ═ Synchronization bar

> 📂 Buka file: **[activity_diagram.html](file:///d:/laragon/www/pemweb/docs/app_flow/activity_diagram.html)** di browser

**Isi 2 diagram:**
1. **Activity Diagram 1** — Proses Reservasi & Pembayaran (Login → Katalog → Reservasi → Bayar → Aktif)
2. **Activity Diagram 2** — Perpanjangan & Pengembalian (Perpanjangan → Approval → Pengembalian → Cek Denda → Selesai)

---

## 🗄️ ERD (Entity Relationship Diagram)

```mermaid
erDiagram
    USERS {
        int id PK
        string nama
        string email UK
        string password
        string no_telp
        string alamat
        enum role "pelanggan|admin|superadmin"
        enum status "aktif|nonaktif"
        datetime created_at
        datetime last_login
    }

    KATEGORI {
        int id PK
        string nama
        string deskripsi
        string icon
    }

    BARANG {
        int id PK
        int kategori_id FK
        string nama
        string deskripsi
        string gambar
        decimal harga_per_hari
        int stok_total
        int stok_tersedia
        enum status "tersedia|habis|maintenance"
        datetime created_at
    }

    RESERVASI {
        int id PK
        string kode_reservasi UK
        int user_id FK
        date tanggal_mulai
        date tanggal_selesai
        decimal total_biaya
        decimal deposit
        enum status "pending|disetujui|ditolak|aktif|selesai|batal"
        text catatan
        datetime created_at
    }

    DETAIL_RESERVASI {
        int id PK
        int reservasi_id FK
        int barang_id FK
        int jumlah
        decimal harga_satuan
        decimal subtotal
    }

    TRANSAKSI {
        int id PK
        string kode_transaksi UK
        int reservasi_id FK
        int user_id FK
        enum tipe "online|walk_in"
        decimal total_bayar
        decimal denda
        enum status "menunggu_bayar|dibayar|aktif|selesai"
        datetime created_at
    }

    PEMBAYARAN {
        int id PK
        int transaksi_id FK
        enum metode "transfer|ewallet|qris|cash"
        decimal jumlah
        string bukti_bayar
        enum status "pending|dikonfirmasi|ditolak"
        datetime tanggal_bayar
    }

    PERPANJANGAN {
        int id PK
        int reservasi_id FK
        date tanggal_lama
        date tanggal_baru
        int tambahan_hari
        decimal biaya_tambahan
        enum metode_bayar "transfer|ewallet|qris"
        string alasan
        enum status "pending|disetujui|ditolak"
        string alasan_tolak
        datetime created_at
    }

    PENGEMBALIAN {
        int id PK
        int transaksi_id FK
        date tanggal_kembali
        int hari_terlambat
        string kondisi_barang
        decimal denda
        text catatan
        datetime created_at
    }

    PROMO {
        int id PK
        string kode UK
        string nama
        enum tipe "persentase|nominal"
        decimal nilai
        date mulai
        date selesai
        int kuota
        enum status "aktif|nonaktif|expired"
    }

    NOTIFIKASI {
        int id PK
        int user_id FK
        string judul
        text pesan
        string tipe
        boolean is_read
        datetime created_at
    }

    LOG_AKTIVITAS {
        int id PK
        int user_id FK
        string aksi
        string detail
        string ip_address
        datetime created_at
    }

    MEMBER_LEVEL {
        int id PK
        int user_id FK
        enum level "regular|bronze|silver|gold"
        int total_transaksi
        decimal total_sewa
        int poin
        datetime updated_at
    }

    PENGATURAN {
        int id PK
        string key UK
        string value
        string kategori
        string deskripsi
    }

    KONTEN {
        int id PK
        string section
        string key
        text value
        datetime updated_at
    }

    USERS ||--o{ RESERVASI : "membuat"
    USERS ||--o{ TRANSAKSI : "memiliki"
    USERS ||--o{ NOTIFIKASI : "menerima"
    USERS ||--o{ LOG_AKTIVITAS : "tercatat"
    USERS ||--o| MEMBER_LEVEL : "memiliki"

    KATEGORI ||--o{ BARANG : "memiliki"

    RESERVASI ||--|{ DETAIL_RESERVASI : "berisi"
    BARANG ||--o{ DETAIL_RESERVASI : "disewa"
    RESERVASI ||--o| TRANSAKSI : "menjadi"
    RESERVASI ||--o{ PERPANJANGAN : "diperpanjang"

    TRANSAKSI ||--o{ PEMBAYARAN : "dibayar"
    TRANSAKSI ||--o| PENGEMBALIAN : "dikembalikan"
```

---

## 🗺️ Sitemap

```mermaid
graph TD
    ROOT["🌐 SIMPEL-CAMP"]

    ROOT --> PUBLIC["📄 Halaman Publik"]
    PUBLIC --> PUB1["index.php<br>Landing Page"]
    PUBLIC --> PUB2["login.php"]
    PUBLIC --> PUB3["register.php"]
    PUBLIC --> PUB4["logout.php"]

    ROOT --> CUST["🛒 Pelanggan<br>14 Halaman"]
    CUST --> C1["dashboard.php"]
    CUST --> C2["katalog.php"]
    CUST --> C3["detail_barang.php"]
    CUST --> C4["reservasi.php"]
    CUST --> C5["pemesanan.php"]
    CUST --> C6["pembayaran.php"]
    CUST --> C7["transaksi.php"]
    CUST --> C8["perpanjangan.php"]
    CUST --> C9["status_perpanjangan.php"]
    CUST --> C10["riwayat.php"]
    CUST --> C11["nota.php"]
    CUST --> C12["notifikasi.php"]
    CUST --> C13["profil.php"]
    CUST --> C14["member.php"]

    ROOT --> ADM["⚙️ Admin<br>26 Halaman"]
    ADM --> A1["dashboard.php"]
    ADM --> A2["Kelola Barang"]
    A2 --> A2a["kelola_barang.php"]
    A2 --> A2b["barang.php"]
    A2 --> A2c["kategori.php"]
    ADM --> A3["pelanggan.php"]
    ADM --> A4["Transaksi"]
    A4 --> A4a["transaksi.php"]
    A4 --> A4b["reservasi.php"]
    A4 --> A4c["transaksi_langsung.php"]
    A4 --> A4d["perpanjangan.php"]
    A4 --> A4e["pengembalian.php"]
    A4 --> A4f["nota.php"]
    ADM --> A5["Laporan"]
    A5 --> A5a["laporan_penjualan.php"]
    A5 --> A5b["laporan_keuangan.php"]
    ADM --> A6["promo.php"]
    ADM --> A7["kelola_konten.php"]
    ADM --> A8["kelola_pengguna.php"]
    ADM --> A9["sistem.php"]

    ROOT --> SA["🔑 Super Admin<br>3 + Admin"]
    SA --> S1["dashboard.php"]
    SA --> S2["log_aktivitas.php"]
    SA --> S3["profil.php"]
    SA -.->|"akses penuh"| ADM
```

---

## 🔄 Sequence Diagram — Alur Detail

### Flow 1: Registrasi & Login

```mermaid
sequenceDiagram
    actor P as Pengguna
    participant R as register.php
    participant DB as Database
    participant L as login.php
    participant D as Dashboard

    P->>R: Isi form registrasi
    R->>DB: Cek duplikasi email
    R->>DB: INSERT user (role=pelanggan)
    DB-->>R: Success
    R-->>P: Redirect ke login
    P->>L: Input email & password
    L->>DB: SELECT * WHERE email & password
    DB-->>L: User data + role
    L-->>D: Redirect sesuai role
```

### Flow 2: Reservasi Online

```mermaid
sequenceDiagram
    actor P as Pelanggan
    participant K as Katalog
    participant R as Reservasi
    participant PM as Pemesanan
    participant DB as Database
    participant A as Admin

    P->>K: Lihat katalog + cek stok
    P->>R: Form reservasi (tanggal, durasi, qty)
    R->>R: Kalkulator biaya otomatis
    P->>PM: Konfirmasi pemesanan
    PM->>DB: INSERT reservasi (status=pending)
    DB-->>A: Notifikasi reservasi baru
    A-->>DB: Approve/Reject
    DB-->>P: Notifikasi status
```

### Flow 3: Pembayaran

```mermaid
sequenceDiagram
    actor P as Pelanggan
    participant B as Pembayaran
    participant DB as Database
    participant A as Admin

    P->>B: Pilih metode (Transfer/E-Wallet/QRIS)
    P->>B: Upload bukti transfer
    B->>DB: INSERT pembayaran (status=pending)
    DB-->>A: Notifikasi pembayaran masuk
    A-->>DB: Konfirmasi pembayaran
    DB-->>P: Status → AKTIF
    DB->>DB: UPDATE stok barang (-qty)
```

### Flow 4: Perpanjangan Sewa

```mermaid
sequenceDiagram
    actor P as Pelanggan
    participant PP as Perpanjangan
    participant DB as Database
    participant A as Admin

    P->>PP: Form (tanggal baru, alasan)
    PP->>PP: Kalkulator biaya tambahan
    Note over PP: Metode: Transfer/E-Wallet/QRIS (NO CASH)
    PP->>DB: INSERT perpanjangan (status=pending)
    DB-->>A: Notifikasi
    A-->>DB: Approve/Reject
    DB-->>P: Notifikasi hasil
```

### Flow 5: Pengembalian

```mermaid
sequenceDiagram
    actor P as Pelanggan
    participant A as Admin
    participant PG as Pengembalian
    participant DB as Database

    P->>A: Kembalikan barang
    A->>PG: Cek kondisi barang
    PG->>PG: Kalkulator denda otomatis
    alt Tepat waktu + baik
        DB-->>P: Deposit dikembalikan
    else Terlambat / rusak
        DB-->>P: Denda dikenakan
    end
    DB->>DB: UPDATE stok (+qty)
```

### Flow 6: Walk-in

```mermaid
sequenceDiagram
    actor C as Pelanggan Walk-in
    participant A as Admin
    participant T as Transaksi Baru
    participant DB as Database

    C->>A: Datang ke toko
    A->>T: Input data + pilih barang
    T->>T: Kalkulator biaya
    A->>T: Metode bayar (Cash/Transfer/E-Wallet)
    T->>DB: INSERT transaksi (status=aktif)
    DB->>DB: UPDATE stok (-qty)
    DB-->>A: Generate nota
```

---

## 📊 Status Lifecycle Transaksi

```mermaid
stateDiagram-v2
    [*] --> Pending: Pelanggan reservasi
    Pending --> Disetujui: Admin approve
    Pending --> Ditolak: Admin reject
    Ditolak --> [*]
    Disetujui --> Menunggu_Bayar
    Menunggu_Bayar --> Dibayar: Upload bukti
    Dibayar --> Aktif: Admin konfirmasi
    Aktif --> Perpanjangan: Request
    Perpanjangan --> Aktif: Disetujui
    Aktif --> Dikembalikan: Barang kembali
    Dikembalikan --> Selesai: OK
    Dikembalikan --> Denda: Terlambat
    Denda --> Selesai: Dibayar
    Selesai --> [*]
```

---

## ⭐ Core Features

### A. Pelanggan (14 Halaman)

| # | Fitur | Deskripsi |
|---|-------|-----------|
| 1 | Dashboard | Ringkasan transaksi, notifikasi, grafik |
| 2 | E-Katalog | Browse barang, filter, search, promo |
| 3 | Detail Barang | Info, foto, harga, stok, tombol sewa |
| 4 | Reservasi | Form + kalkulator biaya otomatis |
| 5 | Pemesanan | Ringkasan sebelum bayar |
| 6 | Pembayaran | Upload bukti (Transfer/E-Wallet/QRIS) |
| 7 | Transaksi | Daftar aktif & riwayat |
| 8 | Perpanjangan | Form + estimasi biaya (TANPA CASH) |
| 9 | Status Perpanjangan | Tracking pending/disetujui/ditolak |
| 10 | Riwayat | History transaksi selesai |
| 11 | Nota | Cetak nota |
| 12 | Notifikasi | List notifikasi |
| 13 | Profil | Edit data diri |
| 14 | Member | Level & benefit |

### B. Admin (26 Halaman)

| # | Menu | Fitur |
|---|------|-------|
| 1 | Dashboard | Statistik, grafik, barang populer |
| 2-5 | Kelola Barang | CRUD barang + kategori |
| 6 | Data Pelanggan | Daftar + detail per pelanggan |
| 7-13 | Transaksi | Reservasi, walk-in, perpanjangan, pengembalian, nota |
| 14-16 | Laporan | Penjualan & keuangan + cetak |
| 17 | Kelola Konten | Beranda, Tentang, FAQ, Footer |
| 18 | Promo | CRUD promo (terpisah dari konten) |
| 19 | Kelola Pengguna | CRUD user + role + status |
| 20 | Sistem | Umum, sewa, keamanan, health, log |

### C. Super Admin (3 + akses Admin)

| # | Fitur |
|---|-------|
| 1 | Dashboard global + revenue chart |
| 2 | Log aktivitas (audit trail) |
| 3 | Profil |
| + | Akses penuh ke semua halaman Admin |

---

## 🔐 Metode Pembayaran

| Fitur | Cash | Transfer | E-Wallet | QRIS |
|-------|:----:|:--------:|:--------:|:----:|
| Walk-in (Admin) | ✅ | ✅ | ✅ | ❌ |
| Reservasi Online | ❌ | ✅ | ✅ | ✅ |
| Perpanjangan | ❌ | ✅ | ✅ | ✅ |
| Denda | ✅ | ✅ | ✅ | ❌ |

---

## 🎨 Design System

| Token | Nilai | Penggunaan |
|-------|-------|------------|
| Primary Dark | `#1B4332` | Sidebar, heading |
| Primary Mid | `#2D6A4F` | Button, accent |
| Primary Light | `#52B788` | Hover, badge |
| Gold | `#D4A373` | Super Admin accent |
| Font Heading | `Outfit` | h1-h6 |
| Font Body | `Inter` | Paragraf, teks |
| Font Mono | `JetBrains Mono` | Harga, ID, kode |

---

## 📋 Checklist Backend

- [ ] Setup database MySQL + 15 tabel (ERD di atas)
- [ ] Autentikasi (login, register, session, RBAC)
- [ ] CRUD Barang + upload foto
- [ ] CRUD Kategori
- [ ] Reservasi: create, approve, reject
- [ ] Transaksi: create, update status
- [ ] Pembayaran: upload bukti, konfirmasi
- [ ] Perpanjangan: create, approve, reject, kalkulasi
- [ ] Pengembalian: proses, kondisi, denda
- [ ] Laporan: query + export/cetak
- [ ] Notifikasi: create, mark read
- [ ] Promo: CRUD + validasi periode
- [ ] Member: level up otomatis
- [ ] Pengaturan sistem: key-value store
- [ ] Log aktivitas: auto-log
- [ ] Kelola konten: CRUD
- [ ] Kelola pengguna: CRUD + toggle status
