# [Nama Aplikasi] — Scope Proyek

> Aplikasi berbagi gambar terinspirasi Pinterest, dibangun dengan arsitektur **LAMP** (Linux, Apache, MySQL, PHP) untuk proyek **Secure Programming**.
> Nama aplikasi belum final (kandidat: Mozaik).

---

## 1. Tujuan

Membangun aplikasi berbagi gambar sederhana yang **fokus pada penerapan keamanan**, bukan banyaknya fitur. Setiap fitur wajib memenuhi checklist keamanan di bagian 6 sebelum dianggap selesai.

---

## 2. Fitur (In Scope)

### 2.1 Login & Register
- Register: username, email, password, konfirmasi password
- Login dengan username atau email
- Logout
- Lockout sementara setelah 5x login gagal (misalnya 15 menit)

### 2.2 Profile
- Halaman profil publik: avatar, display name, username, bio
- Tab **Created** (pin yang diunggah user) dan **Boards** (board milik user)
- Board **private** hanya terlihat oleh pemiliknya

### 2.3 Home Page (Feed) + Search
- Feed global: grid pin terbaru (layout masonry), pagination / tombol "Load more"
- Search pin berdasarkan judul dan deskripsi
- Search user berdasarkan username

### 2.4 Create Post (Pin)
- Upload 1 gambar (JPG/PNG/WebP, maks 5 MB)
- Judul, deskripsi, link sumber (opsional, hanya `http`/`https`)
- Pilih board tujuan (opsional)
- Halaman detail pin: gambar besar, info pembuat, link sumber, tombol Save
- Edit dan hapus pin milik sendiri

### 2.5 Boards & Save *(tambahan — inti dari Pinterest)*
- Buat, edit, hapus board (nama, deskripsi, public/private)
- Save pin ke board milik sendiri, hapus pin dari board
- Halaman board: grid pin di dalam board

### 2.6 Settings
- Edit profil: display name, bio, avatar
- Ganti email (wajib memasukkan password saat ini)
- Ganti password (wajib memasukkan password lama)
- Hapus akun (wajib konfirmasi password)

### 2.7 Admin / Moderator *(tambahan — untuk demo access control)*
- Daftar user: blokir / aktifkan kembali
- Hapus pin yang melanggar
- Lihat audit log

---

## 3. Di Luar Scope (Out of Scope)

Tidak dikerjakan agar tim fokus ke keamanan:

- DM / chat, notifikasi
- Upload video
- Follow / unfollow, feed personal
- Like dan komentar *(boleh ditambah jika semua fitur in scope sudah selesai dan aman)*
- Reset password via email, login dengan Google/OAuth
- Pin dari URL (server mengunduh gambar) — rawan SSRF, hanya dikerjakan jika ada waktu untuk mitigasi lengkap

---

## 4. Skema Database (MySQL, InnoDB, utf8mb4)

```sql
users (
  id INT PK AUTO_INCREMENT,
  username VARCHAR(30) UNIQUE NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  display_name VARCHAR(50),
  bio VARCHAR(300),
  avatar_path VARCHAR(255),
  role ENUM('user','admin') DEFAULT 'user',
  is_active TINYINT(1) DEFAULT 1,
  failed_login_count INT DEFAULT 0,
  locked_until DATETIME NULL,
  created_at DATETIME, updated_at DATETIME
)

pins (
  id INT PK AUTO_INCREMENT,
  user_id INT FK -> users.id ON DELETE CASCADE,
  title VARCHAR(100) NOT NULL,
  description TEXT,
  image_path VARCHAR(255) NOT NULL,
  source_url VARCHAR(2048) NULL,
  created_at DATETIME, updated_at DATETIME,
  INDEX (created_at), INDEX (user_id)
)

boards (
  id INT PK AUTO_INCREMENT,
  user_id INT FK -> users.id ON DELETE CASCADE,
  name VARCHAR(50) NOT NULL,
  description VARCHAR(300),
  visibility ENUM('public','private') DEFAULT 'public',
  created_at DATETIME
)

board_pins (
  board_id INT FK -> boards.id ON DELETE CASCADE,
  pin_id INT FK -> pins.id ON DELETE CASCADE,
  saved_at DATETIME,
  PRIMARY KEY (board_id, pin_id)
)

audit_logs (
  id INT PK AUTO_INCREMENT,
  user_id INT NULL,
  action VARCHAR(50),        -- login_success, login_failed, password_changed, pin_deleted_by_admin, dll.
  detail VARCHAR(500),
  ip_address VARCHAR(45),
  user_agent VARCHAR(255),
  created_at DATETIME
)
```

---

## 5. Struktur Folder

```
project/
├── config/              # DI LUAR document root
│   └── config.php       # kredensial DB (jangan di-commit, pakai config.example.php)
├── src/                 # DI LUAR document root
│   ├── db.php           # koneksi PDO
│   ├── auth.php         # require_login(), require_admin(), current_user()
│   ├── csrf.php         # csrf_token(), csrf_verify()
│   ├── helpers.php      # e() = htmlspecialchars, redirect(), dll.
│   ├── validation.php
│   ├── upload.php       # validasi + re-encode gambar
│   └── audit.php        # log_action()
├── templates/           # header.php, footer.php, partials
├── public/              # DOCUMENT ROOT Apache
│   ├── index.php        # feed
│   ├── login.php, register.php, logout.php
│   ├── search.php
│   ├── pin.php, pin_create.php, pin_edit.php
│   ├── board.php, board_create.php
│   ├── profile.php, settings.php
│   ├── admin/
│   ├── assets/          # css, js
│   └── uploads/         # .htaccess: tanpa eksekusi PHP, tanpa directory listing
├── database/
│   └── schema.sql
└── README.md
```

---

## 6. Checklist Keamanan (Definition of Done)

Sebuah fitur **belum selesai** sebelum semua poin yang relevan terpenuhi:

- [ ] Semua query memakai **PDO prepared statements** (tanpa concat string)
- [ ] Semua output user di-escape dengan `e()` / `htmlspecialchars()`
- [ ] Semua form POST memakai **token CSRF**; aksi yang mengubah data tidak lewat GET
- [ ] **Otorisasi dicek di server**: user hanya bisa edit/hapus miliknya sendiri, board private hanya untuk pemilik, halaman admin hanya untuk role admin
- [ ] Input divalidasi di server (panjang, format, whitelist nilai)
- [ ] Update data memakai **whitelist field** (cegah user mengubah `role`, `user_id`, dll.)
- [ ] Error database tidak ditampilkan ke user

### Kontrol global
- Password: `password_hash()` / `password_verify()`
- Session: cookie `HttpOnly`, `Secure`, `SameSite=Lax`; `session_regenerate_id(true)` setelah login
- Header: `Content-Security-Policy`, `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy`
- Upload: cek MIME dari isi file (`finfo`), re-encode gambar dengan GD, rename acak, batas ukuran
- Link sumber: hanya skema `http`/`https`, link keluar diberi `rel="noopener noreferrer"`
- Apache/PHP: `display_errors=Off`, `expose_php=Off`, `ServerTokens Prod`, `Options -Indexes`
- Audit log: login berhasil/gagal, ganti password/email, hapus akun, aksi admin

---

## 7. Pembagian Tugas (contoh untuk 4 orang)

| Anggota | Modul | Fokus keamanan |
|---|---|---|
| **A** | Login, register, logout, settings (ganti email/password, hapus akun) | Hashing password, lockout brute force, session hardening |
| **B** | Create pin, detail pin, edit/hapus pin | Upload aman, validasi link sumber, otorisasi kepemilikan pin |
| **C** | Feed, search, boards & save | SQL injection di search, IDOR board private, pagination aman |
| **D** | Profile, admin panel, fondasi keamanan (CSRF, header, audit log, config Apache) | Access control role, CSP, konfigurasi server |

> Sesuaikan jika jumlah anggota berbeda. Modul D sebaiknya dipegang orang yang paling paham keamanan karena dipakai semua modul lain.

---

## 8. Tahapan Kerja

**Fase 0 — Fondasi (dikerjakan bersama, wajib selesai duluan)**
- Setup repo Git, `schema.sql`, `db.php`, `helpers.php`, `csrf.php`, `auth.php`, template header/footer
- Sepakati konvensi kode

**Fase 1 — Fitur inti**
- Setiap anggota mengerjakan modulnya masing-masing

**Fase 2 — Hardening & pengujian**
- Uji silang: setiap anggota mencoba menyerang modul anggota lain (SQLi, XSS, CSRF, IDOR, upload berbahaya)
- Perbaiki temuan, catat di laporan

**Fase 3 — Laporan & demo**
- Threat model singkat
- Pemetaan setiap kontrol ke kategori OWASP Top 10
- Demo perbandingan versi rentan vs versi aman untuk beberapa celah

---

## 9. Konvensi Tim

- Branch per fitur: `feature/auth`, `feature/pins`, dll.; merge ke `main` lewat Pull Request
- Minimal 1 anggota lain me-review PR dengan checklist di bagian 6
- `config.php` dan folder `uploads/` masuk `.gitignore`
- Komentar dan nama variabel konsisten (pilih bahasa Inggris atau Indonesia, jangan campur)
