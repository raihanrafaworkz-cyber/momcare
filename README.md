# MOMCARE (Management of Maternal Care)

Web pengingat kunjungan ANC (K1 sampai K6) dan arsip digital data penting ibu hamil
untuk puskesmas / posyandu. PHP 8.1+ polos + MySQL/MariaDB, siap jalan di XAMPP.

## Cara pasang di XAMPP (Windows)

1. Pastikan XAMPP memakai **PHP 8.1 atau lebih baru** (cek di `http://localhost/dashboard/phpinfo.php`).
2. Buka XAMPP Control Panel, **Start** `Apache` dan `MySQL`.
3. Salin folder ini ke `C:\xampp\htdocs\` dan pastikan namanya **`momcare`**
   (jadi `C:\xampp\htdocs\momcare\login.php` ada).
4. Buka `http://localhost/phpmyadmin` > tab **Import** > pilih `database/momcare.sql` > **Import**.
   Database `momcare` terbuat otomatis beserta data demo.
5. Buka **http://localhost/momcare** dan masuk dengan akun demo di bawah.

Bila XAMPP Anda memakai kata sandi MySQL atau folder dengan nama lain, ubah `config.php`
(`DB_USER`, `DB_PASS`, `BASE_URL`).

## Akun demo (kata sandi semua: `momcare123`)

| Peran | Login | Keterangan |
|---|---|---|
| Ibu hamil | `081234567890` | Sari, 29 minggu, K4 sedang berjalan |
| Ibu hamil | `081234567891` | Dewi, 27 minggu, K3 perlu dilakukan |
| Ibu hamil | `081234567892` | Rina, 32 minggu, K3 terlambat |
| Bidan | `bidan@momcare.test` | mendaftarkan ibu, mencatat kunjungan |
| Dokter | `dokter@momcare.test` | sama dengan bidan + ringkasan dokter (K1, K5) |
| Kader | `kader@momcare.test` | hanya melihat status kunjungan |
| Admin | `admin@momcare.test` | akses seperti bidan |

Tanggal data demo dihitung dari tanggal saat database di-import, jadi usia kehamilan contoh
selalu masuk akal. Import ulang `momcare.sql` untuk mengembalikan data demo.

## Fitur

**Ibu hamil:** beranda (usia kehamilan, hitung mundur, kunjungan berikutnya), jadwal K1-K6,
detail kunjungan, riwayat + grafik berat badan + cetak, hasil dokter per trimester,
persiapan persalinan (P4K), jadwal nifas KF1-KF4, tanda bahaya, profil, pengaturan pengingat,
kontak keluarga, ganti kata sandi.

**Bidan / dokter:** dasbor (jumlah ibu, perlu kunjungan, terlambat, filter + cari), daftarkan ibu
(jadwal K1-K6 dibuat otomatis dari HPHT), catat kunjungan (hanya data penting), ringkasan dokter.

**Kader:** dasbor status kunjungan agar bisa mengingatkan ibu yang terlambat.

## Aturan jadwal

Usia kehamilan = (hari ini - HPHT) / 7. HPL = HPHT + 280 hari.

| Kunjungan | Periode (minggu) | Catatan |
|---|---|---|
| K1 | 0 - 12 | dengan dokter (USG) |
| K2 | 12 - 24 | |
| K3 | 24 - 28 | |
| K4 | 28 - 36 | |
| K5 | 36 - 38 | dengan dokter |
| K6 | 38 - 42 | |

Status: **Selesai** (ada data kunjungan), **Perlu dilakukan** (hari ini di dalam periode),
**Akan datang**, **Terlambat** (periode lewat tanpa kunjungan). Pengingat H-7, H-1, dan hari-H
dicatat di tabel `notifikasi` dan tampil di beranda ibu.

## Keamanan yang sudah ada

Kata sandi di-hash (`password_hash`), semua query memakai prepared statement, semua keluaran
di-escape, formulir memakai token CSRF, akses dibatasi per peran dan per puskesmas.

## Batasan versi ini

- Pengiriman WhatsApp / SMS **belum** terhubung. Pengaturan kanal tersimpan, pengingat tampil di aplikasi.
  Untuk mengirim sungguhan perlu layanan pesan (mis. gateway WhatsApp) dan penjadwal (cron) yang
  membaca `jadwal_anc` dan `pengaturan_pengingat`.
- Login memakai kata sandi, bukan OTP. Ibu yang didaftarkan mendapat kata sandi awal acak (tampil sekali).
- Form input persalinan dan nifas oleh petugas belum dibuat (tabel dan tampilan untuk ibu sudah ada).
- Hapus akun demo dan ubah `APP_DEMO` menjadi `false` di `config.php` sebelum dipakai dengan data asli.
  Data kesehatan asli memerlukan persetujuan ibu, HTTPS, dan pencadangan rutin.

## Struktur

```
config.php            pengaturan database dan BASE_URL
database/momcare.sql  skema + data demo
includes/             logika (anc.php), database, autentikasi, tampilan, ikon
assets/css/style.css  tema hot pink
ibu/                  halaman ibu hamil
nakes/                halaman bidan, dokter, kader, admin
```
