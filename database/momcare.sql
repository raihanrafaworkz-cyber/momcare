-- =====================================================================
-- MOMCARE (Management of Maternal Care)
-- Basis data pemantauan kunjungan ANC. Untuk MySQL / MariaDB (XAMPP).
-- Cara pakai: phpMyAdmin > Import > pilih file ini. Database "momcare"
-- dibuat otomatis. Semua tanggal data demo dihitung dari CURDATE(),
-- jadi usia kehamilan contoh selalu masuk akal kapan pun diimpor.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS momcare CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE momcare;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS pengaturan_pengingat;
DROP TABLE IF EXISTS notifikasi;
DROP TABLE IF EXISTS nifas;
DROP TABLE IF EXISTS persiapan_persalinan;
DROP TABLE IF EXISTS ringkasan_dokter;
DROP TABLE IF EXISTS kunjungan;
DROP TABLE IF EXISTS jadwal_anc;
DROP TABLE IF EXISTS kehamilan;
DROP TABLE IF EXISTS kontak_keluarga;
DROP TABLE IF EXISTS ibu_hamil;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS puskesmas;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- 1. Puskesmas
-- ---------------------------------------------------------------------
CREATE TABLE puskesmas (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama       VARCHAR(100) NOT NULL,
  alamat     VARCHAR(200) NULL,
  telepon    VARCHAR(30)  NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. Pengguna (ibu hamil, tenaga kesehatan, kader, admin puskesmas)
-- ---------------------------------------------------------------------
CREATE TABLE users (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  puskesmas_id   INT UNSIGNED NULL,
  role           ENUM('ibu','nakes','kader','admin') NOT NULL,
  jabatan        VARCHAR(30)  NULL,
  nama           VARCHAR(100) NOT NULL,
  no_hp          VARCHAR(20)  NOT NULL,
  email          VARCHAR(120) NULL,
  password_hash  VARCHAR(255) NOT NULL,
  aktif          TINYINT(1)   NOT NULL DEFAULT 1,
  created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_no_hp (no_hp),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role (role),
  CONSTRAINT fk_users_puskesmas FOREIGN KEY (puskesmas_id) REFERENCES puskesmas (id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. Profil ibu hamil
-- ---------------------------------------------------------------------
CREATE TABLE ibu_hamil (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id        INT UNSIGNED NOT NULL,
  nik            VARCHAR(16)  NULL,
  no_jkn         VARCHAR(20)  NULL,
  tanggal_lahir  DATE         NULL,
  alamat         VARCHAR(200) NULL,
  gol_darah      ENUM('A','B','AB','O') NULL,
  rhesus         ENUM('+','-') NULL,
  persetujuan_at DATETIME     NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ibu_user (user_id),
  CONSTRAINT fk_ibu_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. Kontak keluarga (suami atau keluarga lain untuk pengingat cadangan)
-- ---------------------------------------------------------------------
CREATE TABLE kontak_keluarga (
  id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ibu_id    INT UNSIGNED NOT NULL,
  nama      VARCHAR(100) NOT NULL,
  hubungan  VARCHAR(30)  NOT NULL DEFAULT 'Suami',
  no_hp     VARCHAR(20)  NOT NULL,
  PRIMARY KEY (id),
  KEY idx_kontak_ibu (ibu_id),
  CONSTRAINT fk_kontak_ibu FOREIGN KEY (ibu_id) REFERENCES ibu_hamil (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. Kehamilan (HPHT menjadi dasar semua jadwal)
-- ---------------------------------------------------------------------
CREATE TABLE kehamilan (
  id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ibu_id              INT UNSIGNED NOT NULL,
  puskesmas_id        INT UNSIGNED NOT NULL,
  hpht                DATE         NOT NULL,
  hpl                 DATE         NOT NULL,
  gravida             TINYINT UNSIGNED NOT NULL DEFAULT 1,
  paritas             TINYINT UNSIGNED NOT NULL DEFAULT 0,
  abortus             TINYINT UNSIGNED NOT NULL DEFAULT 0,
  bb_sebelum_hamil    DECIMAL(4,1) NULL,
  status              ENUM('aktif','selesai') NOT NULL DEFAULT 'aktif',
  tanggal_persalinan  DATE         NULL,
  created_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_kehamilan_ibu (ibu_id, status),
  KEY idx_kehamilan_puskesmas (puskesmas_id, status),
  CONSTRAINT fk_kehamilan_ibu FOREIGN KEY (ibu_id) REFERENCES ibu_hamil (id) ON DELETE CASCADE,
  CONSTRAINT fk_kehamilan_puskesmas FOREIGN KEY (puskesmas_id) REFERENCES puskesmas (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6. Jadwal ANC K1..K6 (periode pelaksanaan dari HPHT)
--    K1 0-12 mgg, K2 12-24, K3 24-28, K4 28-36, K5 36-38, K6 38-42
-- ---------------------------------------------------------------------
CREATE TABLE jadwal_anc (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  kehamilan_id   INT UNSIGNED NOT NULL,
  kunjungan_ke   TINYINT UNSIGNED NOT NULL,
  tanggal_mulai  DATE NOT NULL,
  tanggal_akhir  DATE NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_jadwal (kehamilan_id, kunjungan_ke),
  CONSTRAINT fk_jadwal_kehamilan FOREIGN KEY (kehamilan_id) REFERENCES kehamilan (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 7. Kunjungan ANC yang sudah dilakukan (diisi tenaga kesehatan)
--    Status jadwal (selesai / perlu dilakukan / terlambat) dihitung
--    aplikasi dari ada tidaknya baris di tabel ini.
-- ---------------------------------------------------------------------
CREATE TABLE kunjungan (
  id                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  kehamilan_id          INT UNSIGNED NOT NULL,
  jadwal_id             INT UNSIGNED NOT NULL,
  tanggal               DATE NOT NULL,
  nakes_id              INT UNSIGNED NULL,
  puskesmas_id          INT UNSIGNED NULL,
  td_sistol             SMALLINT UNSIGNED NULL,
  td_diastol            SMALLINT UNSIGNED NULL,
  berat_badan           DECIMAL(4,1) NULL,
  lila                  DECIMAL(4,1) NULL,
  tfu                   DECIMAL(4,1) NULL,
  djj                   SMALLINT UNSIGNED NULL,
  hb                    DECIMAL(3,1) NULL,
  periksa_protein_urin  TINYINT(1) NOT NULL DEFAULT 0,
  skrining_jiwa         TINYINT(1) NOT NULL DEFAULT 0,
  ttd_diberikan         TINYINT(1) NOT NULL DEFAULT 0,
  usg_dilakukan         TINYINT(1) NOT NULL DEFAULT 0,
  rujukan               TINYINT(1) NOT NULL DEFAULT 0,
  catatan_rujukan       VARCHAR(255) NULL,
  catatan_nakes         VARCHAR(500) NULL,
  tanggal_kembali       DATE NULL,
  created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_kunjungan_jadwal (jadwal_id),
  KEY idx_kunjungan_kehamilan (kehamilan_id, tanggal),
  CONSTRAINT fk_kunjungan_kehamilan FOREIGN KEY (kehamilan_id) REFERENCES kehamilan (id) ON DELETE CASCADE,
  CONSTRAINT fk_kunjungan_jadwal FOREIGN KEY (jadwal_id) REFERENCES jadwal_anc (id) ON DELETE CASCADE,
  CONSTRAINT fk_kunjungan_nakes FOREIGN KEY (nakes_id) REFERENCES users (id) ON DELETE SET NULL,
  CONSTRAINT fk_kunjungan_puskesmas FOREIGN KEY (puskesmas_id) REFERENCES puskesmas (id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 8. Ringkasan pemeriksaan dokter (hanya kesimpulan, satu per trimester)
-- ---------------------------------------------------------------------
CREATE TABLE ringkasan_dokter (
  id                          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  kehamilan_id                INT UNSIGNED NOT NULL,
  trimester                   TINYINT UNSIGNED NOT NULL,
  tanggal                     DATE NOT NULL,
  dokter_id                   INT UNSIGNED NULL,
  risiko_preeklampsia         ENUM('rendah','sedang','tinggi') NULL,
  usg_status                  ENUM('normal','curiga') NULL,
  hpl_usg                     DATE NULL,
  skrining_jiwa_rujukan       TINYINT(1) NOT NULL DEFAULT 0,
  skrining_dm_tindak_lanjut   TINYINT(1) NOT NULL DEFAULT 0,
  rekomendasi_tempat          ENUM('FKTP','FKRTL') NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ringkasan (kehamilan_id, trimester),
  CONSTRAINT fk_ringkasan_kehamilan FOREIGN KEY (kehamilan_id) REFERENCES kehamilan (id) ON DELETE CASCADE,
  CONSTRAINT fk_ringkasan_dokter FOREIGN KEY (dokter_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 9. Persiapan persalinan (P4K, diisi ibu)
-- ---------------------------------------------------------------------
CREATE TABLE persiapan_persalinan (
  kehamilan_id   INT UNSIGNED NOT NULL,
  tempat         VARCHAR(120) NULL,
  penolong       VARCHAR(120) NULL,
  pendamping     VARCHAR(120) NULL,
  biaya          VARCHAR(120) NULL,
  transportasi   VARCHAR(120) NULL,
  donor_darah    VARCHAR(120) NULL,
  updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (kehamilan_id),
  CONSTRAINT fk_persiapan_kehamilan FOREIGN KEY (kehamilan_id) REFERENCES kehamilan (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 10. Kunjungan nifas KF1..KF4 (diisi tenaga kesehatan)
-- ---------------------------------------------------------------------
CREATE TABLE nifas (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  kehamilan_id  INT UNSIGNED NOT NULL,
  kf_ke         TINYINT UNSIGNED NOT NULL,
  tanggal       DATE NOT NULL,
  nakes_id      INT UNSIGNED NULL,
  keadaan_ibu   VARCHAR(200) NULL,
  masalah_nifas VARCHAR(200) NULL,
  vitamin_a     TINYINT(1) NOT NULL DEFAULT 0,
  kb            VARCHAR(80) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_nifas (kehamilan_id, kf_ke),
  CONSTRAINT fk_nifas_kehamilan FOREIGN KEY (kehamilan_id) REFERENCES kehamilan (id) ON DELETE CASCADE,
  CONSTRAINT fk_nifas_nakes FOREIGN KEY (nakes_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 11. Notifikasi pengingat (log pengingat H-7, H-1, hari-H)
-- ---------------------------------------------------------------------
CREATE TABLE notifikasi (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id       INT UNSIGNED NOT NULL,
  jadwal_id     INT UNSIGNED NOT NULL,
  jenis         ENUM('H-7','H-1','H0') NOT NULL,
  judul         VARCHAR(120) NOT NULL,
  pesan         VARCHAR(255) NOT NULL,
  tanggal_kirim DATE NOT NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_notifikasi (jadwal_id, jenis),
  KEY idx_notifikasi_user (user_id, tanggal_kirim),
  CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_notif_jadwal FOREIGN KEY (jadwal_id) REFERENCES jadwal_anc (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 12. Pengaturan pengingat per ibu
-- ---------------------------------------------------------------------
CREATE TABLE pengaturan_pengingat (
  user_id             INT UNSIGNED NOT NULL,
  pengingat_kunjungan TINYINT(1) NOT NULL DEFAULT 1,
  pengingat_ttd       TINYINT(1) NOT NULL DEFAULT 1,
  jam_ttd             TIME NOT NULL DEFAULT '07:00:00',
  kirim_ke_keluarga   TINYINT(1) NOT NULL DEFAULT 1,
  kanal               ENUM('whatsapp','sms','notifikasi') NOT NULL DEFAULT 'whatsapp',
  PRIMARY KEY (user_id),
  CONSTRAINT fk_pengaturan_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB;


-- =====================================================================
-- DATA DEMO
-- Semua akun memakai kata sandi: momcare123
--   admin@momcare.test   admin puskesmas
--   bidan@momcare.test   bidan (bisa mencatat kunjungan)
--   dokter@momcare.test  dokter
--   kader@momcare.test   kader (hanya melihat status)
--   081234567890         ibu hamil Sari Wulandari
--   081234567891         ibu hamil Dewi Lestari
--   081234567892         ibu hamil Rina Anggraini
-- =====================================================================

INSERT INTO puskesmas (id, nama, alamat, telepon) VALUES
(1, 'Puskesmas Melati', 'Jl. Melati No. 1 (alamat contoh)', '031-0000000');

INSERT INTO users (id, puskesmas_id, role, jabatan, nama, no_hp, email, password_hash) VALUES
(1, 1, 'admin', 'Admin Puskesmas', 'Admin Puskesmas Melati', '081200000001', 'admin@momcare.test',  '$2y$10$1Keu/xU4sVW/j6dW0UivCOuGKSqe7YSW5CdwhaJDcJyQej/Vbgkna'),
(2, 1, 'nakes', 'Bidan',  'Bidan Ratna',  '081200000002', 'bidan@momcare.test',  '$2y$10$1Keu/xU4sVW/j6dW0UivCOuGKSqe7YSW5CdwhaJDcJyQej/Vbgkna'),
(3, 1, 'nakes', 'Dokter', 'dr. Andi',     '081200000003', 'dokter@momcare.test', '$2y$10$1Keu/xU4sVW/j6dW0UivCOuGKSqe7YSW5CdwhaJDcJyQej/Vbgkna'),
(4, 1, 'kader', 'Kader Posyandu', 'Kader Siti', '081200000004', 'kader@momcare.test', '$2y$10$1Keu/xU4sVW/j6dW0UivCOuGKSqe7YSW5CdwhaJDcJyQej/Vbgkna'),
(5, 1, 'ibu', NULL, 'Sari Wulandari',  '081234567890', NULL, '$2y$10$1Keu/xU4sVW/j6dW0UivCOuGKSqe7YSW5CdwhaJDcJyQej/Vbgkna'),
(6, 1, 'ibu', NULL, 'Dewi Lestari',    '081234567891', NULL, '$2y$10$1Keu/xU4sVW/j6dW0UivCOuGKSqe7YSW5CdwhaJDcJyQej/Vbgkna'),
(7, 1, 'ibu', NULL, 'Rina Anggraini',  '081234567892', NULL, '$2y$10$1Keu/xU4sVW/j6dW0UivCOuGKSqe7YSW5CdwhaJDcJyQej/Vbgkna');

INSERT INTO ibu_hamil (id, user_id, no_jkn, tanggal_lahir, alamat, gol_darah, rhesus, persetujuan_at) VALUES
(1, 5, '0000000000001', '1998-03-14', 'RT 02 RW 03 (alamat contoh)', 'B', '+', NOW()),
(2, 6, '0000000000002', '2000-07-21', 'RT 04 RW 01 (alamat contoh)', 'O', '+', NOW()),
(3, 7, NULL,            '1995-11-02', 'RT 01 RW 05 (alamat contoh)', 'A', '+', NOW());

INSERT INTO kontak_keluarga (ibu_id, nama, hubungan, no_hp) VALUES
(1, 'Dedi Kurniawan', 'Suami', '081200000011'),
(2, 'Bayu Pratama',   'Suami', '081200000012'),
(3, 'Agus Santoso',   'Suami', '081200000013');

-- Kehamilan demo: Sari 29 minggu 1 hari, Dewi 27 minggu 1 hari, Rina 32 minggu 1 hari
SET @h1 = DATE_SUB(CURDATE(), INTERVAL 204 DAY);
SET @h2 = DATE_SUB(CURDATE(), INTERVAL 190 DAY);
SET @h3 = DATE_SUB(CURDATE(), INTERVAL 225 DAY);

INSERT INTO kehamilan (id, ibu_id, puskesmas_id, hpht, hpl, gravida, paritas, abortus, bb_sebelum_hamil, status) VALUES
(1, 1, 1, @h1, DATE_ADD(@h1, INTERVAL 280 DAY), 2, 1, 0, 52.0, 'aktif'),
(2, 2, 1, @h2, DATE_ADD(@h2, INTERVAL 280 DAY), 1, 0, 0, 49.5, 'aktif'),
(3, 3, 1, @h3, DATE_ADD(@h3, INTERVAL 280 DAY), 3, 2, 0, 55.0, 'aktif');

-- Jadwal K1..K6 untuk setiap kehamilan
INSERT INTO jadwal_anc (kehamilan_id, kunjungan_ke, tanggal_mulai, tanggal_akhir)
SELECT k.id, w.ke,
       DATE_ADD(k.hpht, INTERVAL w.mulai * 7 DAY),
       DATE_SUB(DATE_ADD(k.hpht, INTERVAL w.akhir * 7 DAY), INTERVAL 1 DAY)
FROM kehamilan k
CROSS JOIN (
  SELECT 1 AS ke, 0 AS mulai, 12 AS akhir
  UNION ALL SELECT 2, 12, 24
  UNION ALL SELECT 3, 24, 28
  UNION ALL SELECT 4, 28, 36
  UNION ALL SELECT 5, 36, 38
  UNION ALL SELECT 6, 38, 42
) w;

-- Kunjungan Sari: K1 (dokter + USG), K2, K3 sudah selesai; K4 sedang berjalan
INSERT INTO kunjungan (kehamilan_id, jadwal_id, tanggal, nakes_id, puskesmas_id, td_sistol, td_diastol, berat_badan, lila, tfu, djj, hb,
                       periksa_protein_urin, skrining_jiwa, ttd_diberikan, usg_dilakukan, rujukan, catatan_nakes, tanggal_kembali) VALUES
(1, (SELECT id FROM jadwal_anc WHERE kehamilan_id = 1 AND kunjungan_ke = 1), DATE_ADD(@h1, INTERVAL 68 DAY), 3, 1, 108, 68, 53.0, 25.0, NULL, NULL, 12.1, 1, 1, 1, 1, 0,
 'Kehamilan sesuai usia. Mulai minum tablet tambah darah setiap hari.', DATE_ADD(@h1, INTERVAL 110 DAY)),
(1, (SELECT id FROM jadwal_anc WHERE kehamilan_id = 1 AND kunjungan_ke = 2), DATE_ADD(@h1, INTERVAL 110 DAY), 2, 1, 110, 70, 55.4, 25.0, 16.0, 148, 11.9, 1, 0, 1, 0, 0,
 'Kondisi Ibu dan janin baik. Lanjutkan tablet tambah darah.', DATE_ADD(@h1, INTERVAL 174 DAY)),
(1, (SELECT id FROM jadwal_anc WHERE kehamilan_id = 1 AND kunjungan_ke = 3), DATE_ADD(@h1, INTERVAL 174 DAY), 2, 1, 110, 70, 58.5, 25.5, 24.0, 144, 11.8, 1, 1, 1, 0, 0,
 'Kondisi Ibu dan janin baik. Lanjutkan minum tablet tambah darah setiap hari.', DATE_ADD(CURDATE(), INTERVAL 18 DAY));

-- Kunjungan Dewi: K1, K2 selesai; K3 sedang berjalan (perlu dilakukan)
INSERT INTO kunjungan (kehamilan_id, jadwal_id, tanggal, nakes_id, puskesmas_id, td_sistol, td_diastol, berat_badan, lila, tfu, djj, hb,
                       periksa_protein_urin, skrining_jiwa, ttd_diberikan, usg_dilakukan, rujukan, catatan_nakes, tanggal_kembali) VALUES
(2, (SELECT id FROM jadwal_anc WHERE kehamilan_id = 2 AND kunjungan_ke = 1), DATE_ADD(@h2, INTERVAL 60 DAY), 3, 1, 104, 66, 50.2, 24.5, NULL, NULL, 12.4, 1, 1, 1, 1, 0,
 'Kehamilan pertama, kondisi baik.', DATE_ADD(@h2, INTERVAL 112 DAY)),
(2, (SELECT id FROM jadwal_anc WHERE kehamilan_id = 2 AND kunjungan_ke = 2), DATE_ADD(@h2, INTERVAL 112 DAY), 2, 1, 106, 68, 52.8, 24.5, 16.0, 150, 12.0, 1, 0, 1, 0, 0,
 'Kondisi baik.', DATE_ADD(CURDATE(), INTERVAL 3 DAY));

-- Kunjungan Rina: K1, K2 selesai; K3 terlewat (periode berakhir tanpa kunjungan)
INSERT INTO kunjungan (kehamilan_id, jadwal_id, tanggal, nakes_id, puskesmas_id, td_sistol, td_diastol, berat_badan, lila, tfu, djj, hb,
                       periksa_protein_urin, skrining_jiwa, ttd_diberikan, usg_dilakukan, rujukan, catatan_nakes, tanggal_kembali) VALUES
(3, (SELECT id FROM jadwal_anc WHERE kehamilan_id = 3 AND kunjungan_ke = 1), DATE_ADD(@h3, INTERVAL 70 DAY), 3, 1, 112, 72, 56.0, 26.0, NULL, NULL, 11.6, 1, 1, 1, 1, 0,
 'Kehamilan ketiga. Perlu dipantau kadar Hb.', DATE_ADD(@h3, INTERVAL 140 DAY)),
(3, (SELECT id FROM jadwal_anc WHERE kehamilan_id = 3 AND kunjungan_ke = 2), DATE_ADD(@h3, INTERVAL 140 DAY), 2, 1, 114, 74, 58.7, 26.0, 20.0, 146, 11.2, 1, 0, 1, 0, 0,
 'Hb sedikit rendah, tablet tambah darah dilanjutkan.', DATE_ADD(@h3, INTERVAL 170 DAY));

-- Ringkasan dokter trimester 1 untuk Sari
INSERT INTO ringkasan_dokter (kehamilan_id, trimester, tanggal, dokter_id, risiko_preeklampsia, usg_status, hpl_usg, skrining_jiwa_rujukan, skrining_dm_tindak_lanjut, rekomendasi_tempat) VALUES
(1, 1, DATE_ADD(@h1, INTERVAL 68 DAY), 3, 'rendah', 'normal', DATE_ADD(@h1, INTERVAL 280 DAY), 0, 0, NULL);

INSERT INTO persiapan_persalinan (kehamilan_id, tempat, penolong, pendamping, biaya, transportasi, donor_darah) VALUES
(1, 'Puskesmas Melati', 'Bidan Ratna', 'Suami (Bapak Dedi)', 'Kartu JKN aktif', NULL, NULL),
(2, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'Puskesmas Melati', NULL, NULL, 'Kartu JKN aktif', NULL, NULL);

INSERT INTO pengaturan_pengingat (user_id, pengingat_kunjungan, pengingat_ttd, jam_ttd, kirim_ke_keluarga, kanal) VALUES
(5, 1, 1, '07:00:00', 1, 'whatsapp'),
(6, 1, 1, '07:00:00', 1, 'whatsapp'),
(7, 1, 1, '07:00:00', 0, 'sms');
