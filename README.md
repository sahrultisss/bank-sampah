# Bank Sampah — Sistem Tabungan Sampah Digital (Sekolah)

Aplikasi web untuk mengelola bank sampah di lingkungan sekolah: data
pengguna, jenis sampah & stok, transaksi setoran, pengajuan tarik saldo,
dan laporan — dengan 4 peran (role): **admin**, **guru**, **siswa**, dan
**pengepul**.

Dibuat dengan **PHP native** (tanpa framework) + **MySQL**, cocok dijalankan
di XAMPP / Laragon.

## 1. Persiapan

1. Pastikan sudah terpasang **XAMPP** atau **Laragon** (Apache + MySQL + PHP).
2. Salin folder project ini ke folder web server, contoh:
   - XAMPP: `C:\xampp\htdocs\bank_sampah`
   - Laragon: `C:\laragon\www\bank_sampah`
3. Buka **VS Code**, lalu `File > Open Folder...` pilih folder project.

Nama folder bebas — `baseUrl()` di `includes/auth.php` otomatis mendeteksi
nama folder project, jadi tidak perlu diedit manual.

## 2. Membuat Database

1. Jalankan Apache & MySQL dari XAMPP/Laragon.
2. Buka **phpMyAdmin** (`http://localhost/phpmyadmin`).
3. Klik tab **Import**, pilih file `database/bank_sampah.sql`, klik **Go**.
   File ini otomatis membuat database `bank_sampah`, semua tabel, dan data
   contoh (drop & create ulang kalau database dengan nama sama sudah ada).

## 3. Konfigurasi Koneksi

Cek `config/database.php` — default: `DB_HOST=localhost`, `DB_NAME=bank_sampah`,
`DB_USER=root`, `DB_PASS=` (kosong). Sesuaikan kalau instalasi MySQL-mu beda.

## 4. Menjalankan Aplikasi

Buka `http://localhost/<nama-folder-project>/` di browser.

## 5. Akun Contoh (Seed Data)

Semua akun contoh pakai password: **password123**

| Role     | Username    |
|----------|-------------|
| Admin    | `admin`     |
| Pengepul | `pengepul1` |
| Guru     | `guru1`     |
| Siswa    | `siswa1`    |

> Kalau login gagal dengan pesan "username atau password salah" padahal
> sudah benar, jalankan `reset_password.php` sekali (lihat instruksi di
> dalam file itu), lalu **hapus filenya** setelah berhasil. Ini mengatasi
> ketidakcocokan format hash bcrypt antar sistem operasi.

Guru & siswa baru juga bisa mendaftar sendiri lewat halaman **Daftar**
di form login.

## 6. Struktur Folder

```
├── admin/       Kelola pengguna, jenis sampah, transaksi, approve tarik saldo, laporan
├── pengepul/    Input transaksi setor sampah, lihat stok
├── anggota/     Dashboard guru & siswa (saldo, riwayat, ajukan tarik saldo)
├── auth/        Login, register, logout
├── config/      Koneksi database
├── includes/    Helper, autentikasi, layout header/footer
├── assets/      CSS (tema "buku tabungan / ledger") & JS (modal)
├── database/    File SQL skema + data contoh
└── index.php    Redirect otomatis sesuai role setelah login
```

## 7. Skema Database (ringkas)

- **users** — `id_user, nama, username, password, saldo, role`
  role: `admin | guru | siswa | pengepul`. Saldo nempel langsung di user
  (dipakai oleh guru & siswa).
- **sampah** — `id_sampah, jenis_sampah, harga_per_kg, stok_kg`
- **transaksi** — `id_transaksi, id_user, id_sampah, tipe, berat_kg, total_rp, tanggal`
  1 baris = 1 kali setor 1 jenis sampah. Setiap transaksi otomatis:
  menambah `saldo` user & menambah `stok_kg` sampah terkait.
- **tarik_saldo** — `id_tarik, id_user, nominal, tanggal, status`
  status: `pending | disetujui | ditolak`. Guru/siswa mengajukan, **admin**
  yang menyetujui/menolak. Saldo baru dipotong saat disetujui.

## 8. Alur Pemakaian Singkat

1. **Pengepul** mencatat setoran sampah siswa/guru lewat "Input Setor" →
   saldo pemilik bertambah otomatis, stok sampah ikut bertambah.
2. **Guru/Siswa** memantau saldo & riwayat di dashboard masing-masing, dan
   bisa mengajukan tarik saldo kapan saja (statusnya "pending").
3. **Admin** meninjau pengajuan tarik saldo di menu "Tarik Saldo" →
   klik **Setujui** (saldo terpotong) atau **Tolak**.
4. **Admin** juga bisa mengelola seluruh data pengguna, jenis sampah, dan
   melihat laporan bulanan (bisa dicetak jadi PDF lewat print browser).

## 9. Fitur

- Multi-role dengan proteksi akses per halaman (admin, guru, siswa, pengepul).
- Guru & siswa berbagi halaman yang sama (folder `anggota/`) karena
  fungsinya identik — cukup dibedakan lewat kolom `role`.
- Transaksi setor otomatis hitung nominal dari harga di database (bukan
  dari input browser), jadi tidak bisa dimanipulasi dari sisi klien.
- Alur persetujuan tarik saldo (pending → disetujui/ditolak oleh admin).
- Laporan bulanan + grafik (Chart.js) + cetak PDF lewat `window.print()`
  (tidak butuh instalasi library PDF tambahan).
- Guru/siswa bisa mendaftar akun sendiri.

## 10. Catatan Pengembangan Lanjutan (opsional)

- Kalau butuh PDF asli (bukan print-to-PDF), tambahkan library TCPDF/FPDF
  lewat Composer.
- Password akun contoh di-hash dengan `password_hash()` PHP (bcrypt).
