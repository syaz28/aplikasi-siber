-- ============================================
-- DATABASE SCHEMA REFACTORED
-- Sistem Laporan Kejahatan Siber - POLDA JATENG
-- ============================================
-- Konvensi: Bahasa Indonesia (kecuali field system-centric)
-- Data Wilayah: Single table sesuai cahyadsn/wilayah
-- Kepmendagri No 300.2.2-2138 Tahun 2025
-- Referensi: https://github.com/cahyadsn/wilayah
-- Format: MySQL / MariaDB
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- MASTER DATA: WILAYAH INDONESIA (Single Table)
-- Sumber: github.com/cahyadsn/wilayah
-- Format Kode:
-- - Provinsi: XX (2 char) → '33'
-- - Kabupaten/Kota: XX.XX (5 char) → '33.74'
-- - Kecamatan: XX.XX.XX (8 char) → '33.74.01'
-- - Kelurahan/Desa: XX.XX.XX.XXXX (13 char) → '33.74.01.1001'
-- ============================================

DROP TABLE IF EXISTS `wilayah`;
CREATE TABLE `wilayah` (
    `kode` VARCHAR(13) NOT NULL,
    `nama` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`kode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `wilayah_nama_idx` ON `wilayah` (`nama`);

-- ============================================
-- MASTER DATA: KEPOLISIAN
-- ============================================

DROP TABLE IF EXISTS `pangkat`;
CREATE TABLE `pangkat` (
    `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
    `kode` VARCHAR(20) NOT NULL,
    `nama` VARCHAR(100) NOT NULL,
    `urutan` INT NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `pangkat_kode_unique` (`kode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `pangkat_urutan_index` ON `pangkat` (`urutan`);

DROP TABLE IF EXISTS `jabatan`;
CREATE TABLE `jabatan` (
    `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
    `nama` VARCHAR(100) NOT NULL,
    `deskripsi` TEXT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `jabatan_nama_unique` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- MASTER DATA: KEJAHATAN SIBER
-- ============================================

DROP TABLE IF EXISTS `kategori_kejahatan`;
CREATE TABLE `kategori_kejahatan` (
    `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
    `nama` VARCHAR(100) NOT NULL,
    `deskripsi` TEXT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `kategori_kejahatan_nama_unique` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `jenis_kejahatan`;
CREATE TABLE `jenis_kejahatan` (
    `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
    `kategori_kejahatan_id` INT UNSIGNED NOT NULL,
    `nama` VARCHAR(100) NOT NULL,
    `deskripsi` TEXT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `jenis_kejahatan_kategori_id_index` (`kategori_kejahatan_id`),
    UNIQUE KEY `jenis_kejahatan_kategori_nama_unique` (`kategori_kejahatan_id`, `nama`),
    CONSTRAINT `jenis_kejahatan_kategori_id_foreign` FOREIGN KEY (`kategori_kejahatan_id`) 
        REFERENCES `kategori_kejahatan` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DATA: ANGGOTA KEPOLISIAN
-- ============================================

DROP TABLE IF EXISTS `anggota`;
CREATE TABLE `anggota` (
    `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
    `pangkat_id` INT UNSIGNED NOT NULL,
    `jabatan_id` INT UNSIGNED NOT NULL,
    `nrp` VARCHAR(20) NOT NULL,
    `nama` VARCHAR(100) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `anggota_nrp_unique` (`nrp`),
    KEY `anggota_pangkat_id_index` (`pangkat_id`),
    KEY `anggota_jabatan_id_index` (`jabatan_id`),
    KEY `anggota_is_active_index` (`is_active`),
    CONSTRAINT `anggota_pangkat_id_foreign` FOREIGN KEY (`pangkat_id`) 
        REFERENCES `pangkat` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `anggota_jabatan_id_foreign` FOREIGN KEY (`jabatan_id`) 
        REFERENCES `jabatan` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DATA: USERS (AUTHENTICATION)
-- Tetap English karena Laravel convention
-- ============================================

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
    `anggota_id` INT UNSIGNED NULL,
    `email` VARCHAR(100) NOT NULL,
    `email_verified_at` TIMESTAMP NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('superadmin', 'admin', 'operator') NOT NULL DEFAULT 'operator',
    `remember_token` VARCHAR(100) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `users_email_unique` (`email`),
    KEY `users_anggota_id_index` (`anggota_id`),
    KEY `users_role_index` (`role`),
    CONSTRAINT `users_anggota_id_foreign` FOREIGN KEY (`anggota_id`) 
        REFERENCES `anggota` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DATA: ORANG (PELAPOR / KORBAN / TERSANGKA)
-- ============================================

DROP TABLE IF EXISTS `orang`;
CREATE TABLE `orang` (
    `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
    `nik` CHAR(16) NOT NULL,
    `nama` VARCHAR(100) NOT NULL,
    `tempat_lahir` VARCHAR(100) NOT NULL,
    `tanggal_lahir` DATE NOT NULL,
    `jenis_kelamin` ENUM('Laki-laki', 'Perempuan') NOT NULL,
    `pekerjaan` VARCHAR(100) NOT NULL,
    `telepon` VARCHAR(20) NOT NULL,
    `email` VARCHAR(100) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `orang_nik_unique` (`nik`),
    KEY `orang_telepon_index` (`telepon`),
    KEY `orang_nama_index` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alamat dengan kode wilayah denormalized untuk kemudahan query
DROP TABLE IF EXISTS `alamat`;
CREATE TABLE `alamat` (
    `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
    `orang_id` INT UNSIGNED NOT NULL,
    `jenis_alamat` ENUM('ktp', 'domisili') NOT NULL,
    `kode_provinsi` VARCHAR(2) NOT NULL COMMENT 'Contoh: 33',
    `kode_kabupaten` VARCHAR(5) NOT NULL COMMENT 'Contoh: 33.74',
    `kode_kecamatan` VARCHAR(8) NOT NULL COMMENT 'Contoh: 33.74.01',
    `kode_kelurahan` VARCHAR(13) NOT NULL COMMENT 'Contoh: 33.74.01.1001',
    `detail_alamat` TEXT NOT NULL COMMENT 'Jalan, RT/RW, No. Rumah',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `alamat_orang_id_index` (`orang_id`),
    KEY `alamat_kode_provinsi_index` (`kode_provinsi`),
    KEY `alamat_kode_kabupaten_index` (`kode_kabupaten`),
    KEY `alamat_kode_kecamatan_index` (`kode_kecamatan`),
    KEY `alamat_kode_kelurahan_index` (`kode_kelurahan`),
    UNIQUE KEY `alamat_orang_jenis_unique` (`orang_id`, `jenis_alamat`),
    CONSTRAINT `alamat_orang_id_foreign` FOREIGN KEY (`orang_id`) 
        REFERENCES `orang` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `alamat_kode_provinsi_foreign` FOREIGN KEY (`kode_provinsi`) 
        REFERENCES `wilayah` (`kode`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `alamat_kode_kabupaten_foreign` FOREIGN KEY (`kode_kabupaten`) 
        REFERENCES `wilayah` (`kode`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `alamat_kode_kecamatan_foreign` FOREIGN KEY (`kode_kecamatan`) 
        REFERENCES `wilayah` (`kode`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `alamat_kode_kelurahan_foreign` FOREIGN KEY (`kode_kelurahan`) 
        REFERENCES `wilayah` (`kode`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DATA: LAPORAN KEJAHATAN SIBER
-- ============================================

DROP TABLE IF EXISTS `laporan`;
CREATE TABLE `laporan` (
    `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
    `nomor_stpa` VARCHAR(50) NULL,
    `tanggal_laporan` DATETIME NOT NULL,
    `pelapor_id` INT UNSIGNED NOT NULL,
    `hubungan_pelapor` ENUM('diri_sendiri', 'keluarga', 'kuasa_hukum', 'teman', 'rekan_kerja', 'lainnya') NOT NULL DEFAULT 'diri_sendiri',
    `petugas_id` INT UNSIGNED NOT NULL,
    `jenis_kejahatan_id` INT UNSIGNED NOT NULL,
    
    -- Lokasi kejadian dengan kode wilayah denormalized
    `kode_provinsi_kejadian` VARCHAR(2) NULL COMMENT 'Contoh: 33',
    `kode_kabupaten_kejadian` VARCHAR(5) NULL COMMENT 'Contoh: 33.74',
    `kode_kecamatan_kejadian` VARCHAR(8) NULL COMMENT 'Contoh: 33.74.01',
    `kode_kelurahan_kejadian` VARCHAR(13) NULL COMMENT 'Contoh: 33.74.01.1001',
    `alamat_kejadian` TEXT NULL,
    
    `waktu_kejadian` DATETIME NOT NULL,
    `modus` TEXT NOT NULL,
    `status` ENUM('draft', 'submitted', 'verified', 'investigating', 'closed', 'rejected') NOT NULL DEFAULT 'draft',
    `catatan` TEXT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `created_by` BIGINT UNSIGNED NULL,
    `updated_by` BIGINT UNSIGNED NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `laporan_nomor_stpa_unique` (`nomor_stpa`),
    KEY `laporan_pelapor_id_index` (`pelapor_id`),
    KEY `laporan_petugas_id_index` (`petugas_id`),
    KEY `laporan_jenis_kejahatan_id_index` (`jenis_kejahatan_id`),
    KEY `laporan_kode_provinsi_kejadian_index` (`kode_provinsi_kejadian`),
    KEY `laporan_kode_kabupaten_kejadian_index` (`kode_kabupaten_kejadian`),
    KEY `laporan_kode_kecamatan_kejadian_index` (`kode_kecamatan_kejadian`),
    KEY `laporan_kode_kelurahan_kejadian_index` (`kode_kelurahan_kejadian`),
    KEY `laporan_tanggal_laporan_index` (`tanggal_laporan`),
    KEY `laporan_status_index` (`status`),
    CONSTRAINT `laporan_pelapor_id_foreign` FOREIGN KEY (`pelapor_id`) 
        REFERENCES `orang` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `laporan_petugas_id_foreign` FOREIGN KEY (`petugas_id`) 
        REFERENCES `anggota` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `laporan_jenis_kejahatan_id_foreign` FOREIGN KEY (`jenis_kejahatan_id`) 
        REFERENCES `jenis_kejahatan` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `laporan_kode_provinsi_kejadian_foreign` FOREIGN KEY (`kode_provinsi_kejadian`) 
        REFERENCES `wilayah` (`kode`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `laporan_kode_kabupaten_kejadian_foreign` FOREIGN KEY (`kode_kabupaten_kejadian`) 
        REFERENCES `wilayah` (`kode`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `laporan_kode_kecamatan_kejadian_foreign` FOREIGN KEY (`kode_kecamatan_kejadian`) 
        REFERENCES `wilayah` (`kode`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `laporan_kode_kelurahan_kejadian_foreign` FOREIGN KEY (`kode_kelurahan_kejadian`) 
        REFERENCES `wilayah` (`kode`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `laporan_created_by_foreign` FOREIGN KEY (`created_by`) 
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `laporan_updated_by_foreign` FOREIGN KEY (`updated_by`) 
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DATA: KORBAN PER LAPORAN
-- ============================================

DROP TABLE IF EXISTS `korban`;
CREATE TABLE `korban` (
    `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
    `laporan_id` INT UNSIGNED NOT NULL,
    `orang_id` INT UNSIGNED NOT NULL,
    `kerugian_nominal` DECIMAL(20,2) NOT NULL DEFAULT 0,
    `kerugian_terbilang` VARCHAR(255) NULL,
    `keterangan` TEXT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `korban_laporan_id_index` (`laporan_id`),
    KEY `korban_orang_id_index` (`orang_id`),
    UNIQUE KEY `korban_laporan_orang_unique` (`laporan_id`, `orang_id`),
    CONSTRAINT `korban_laporan_id_foreign` FOREIGN KEY (`laporan_id`) 
        REFERENCES `laporan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `korban_orang_id_foreign` FOREIGN KEY (`orang_id`) 
        REFERENCES `orang` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DATA: TERSANGKA PER LAPORAN
-- ============================================

DROP TABLE IF EXISTS `tersangka`;
CREATE TABLE `tersangka` (
    `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
    `laporan_id` INT UNSIGNED NOT NULL,
    `orang_id` INT UNSIGNED NULL,
    `catatan` TEXT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `tersangka_laporan_id_index` (`laporan_id`),
    KEY `tersangka_orang_id_index` (`orang_id`),
    CONSTRAINT `tersangka_laporan_id_foreign` FOREIGN KEY (`laporan_id`) 
        REFERENCES `laporan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `tersangka_orang_id_foreign` FOREIGN KEY (`orang_id`) 
        REFERENCES `orang` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DATA: IDENTITAS DIGITAL TERSANGKA
-- ============================================

DROP TABLE IF EXISTS `identitas_tersangka`;
CREATE TABLE `identitas_tersangka` (
    `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
    `tersangka_id` INT UNSIGNED NOT NULL,
    `jenis` ENUM('telepon', 'rekening', 'sosmed', 'email', 'ewallet', 'kripto', 'marketplace', 'website', 'lainnya') NOT NULL,
    `nilai` VARCHAR(255) NOT NULL,
    `platform` VARCHAR(100) NULL,
    `nama_akun` VARCHAR(100) NULL,
    `catatan` TEXT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `identitas_tersangka_tersangka_id_index` (`tersangka_id`),
    KEY `identitas_tersangka_nilai_index` (`nilai`),
    KEY `identitas_tersangka_jenis_index` (`jenis`),
    KEY `identitas_tersangka_jenis_nilai_index` (`jenis`, `nilai`),
    CONSTRAINT `identitas_tersangka_tersangka_id_foreign` FOREIGN KEY (`tersangka_id`) 
        REFERENCES `tersangka` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DATA: LAMPIRAN BUKTI
-- ============================================

DROP TABLE IF EXISTS `lampiran`;
CREATE TABLE `lampiran` (
    `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
    `laporan_id` INT UNSIGNED NOT NULL,
    `nama_file` VARCHAR(255) NOT NULL,
    `path_file` VARCHAR(500) NOT NULL,
    `jenis_file` ENUM('gambar', 'dokumen', 'screenshot', 'video', 'audio', 'lainnya') NOT NULL,
    `ukuran_file` INT UNSIGNED NULL,
    `deskripsi` TEXT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `lampiran_laporan_id_index` (`laporan_id`),
    KEY `lampiran_jenis_file_index` (`jenis_file`),
    CONSTRAINT `lampiran_laporan_id_foreign` FOREIGN KEY (`laporan_id`) 
        REFERENCES `laporan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- SEED DATA: PANGKAT KEPOLISIAN
-- ============================================

INSERT INTO `pangkat` (`kode`, `nama`, `urutan`, `created_at`, `updated_at`) VALUES
('JENDERAL', 'Jenderal Polisi', 1, NOW(), NOW()),
('KOMJEN', 'Komisaris Jenderal Polisi', 2, NOW(), NOW()),
('IRJEN', 'Inspektur Jenderal Polisi', 3, NOW(), NOW()),
('BRIGJEN', 'Brigadir Jenderal Polisi', 4, NOW(), NOW()),
('KOMBES', 'Komisaris Besar Polisi', 5, NOW(), NOW()),
('AKBP', 'Ajun Komisaris Besar Polisi', 6, NOW(), NOW()),
('KOMPOL', 'Komisaris Polisi', 7, NOW(), NOW()),
('AKP', 'Ajun Komisaris Polisi', 8, NOW(), NOW()),
('IPTU', 'Inspektur Polisi Satu', 9, NOW(), NOW()),
('IPDA', 'Inspektur Polisi Dua', 10, NOW(), NOW()),
('AIPTU', 'Ajun Inspektur Polisi Satu', 11, NOW(), NOW()),
('AIPDA', 'Ajun Inspektur Polisi Dua', 12, NOW(), NOW()),
('BRIPKA', 'Brigadir Polisi Kepala', 13, NOW(), NOW()),
('BRIGADIR', 'Brigadir Polisi', 14, NOW(), NOW()),
('BRIPTU', 'Brigadir Polisi Satu', 15, NOW(), NOW()),
('BRIPDA', 'Brigadir Polisi Dua', 16, NOW(), NOW());

-- ============================================
-- SEED DATA: JABATAN
-- ============================================

INSERT INTO `jabatan` (`nama`, `deskripsi`, `created_at`, `updated_at`) VALUES
('Kanit', 'Kepala Unit', NOW(), NOW()),
('Kasubnit', 'Kepala Sub Unit', NOW(), NOW()),
('Penyidik', 'Penyidik', NOW(), NOW()),
('Penyidik Pembantu', 'Penyidik Pembantu', NOW(), NOW()),
('Analis', 'Analis', NOW(), NOW()),
('Operator', 'Operator', NOW(), NOW()),
('BA PIKET', 'Bintara Piket', NOW(), NOW());

-- ============================================
-- SEED DATA: KATEGORI KEJAHATAN SIBER
-- ============================================

INSERT INTO `kategori_kejahatan` (`nama`, `deskripsi`, `is_active`, `created_at`, `updated_at`) VALUES
('Penipuan Online', 'Kejahatan penipuan yang dilakukan melalui media digital/online', 1, NOW(), NOW()),
('Pemerasan Digital', 'Kejahatan pemerasan dengan memanfaatkan teknologi digital', 1, NOW(), NOW()),
('Pencurian Data', 'Kejahatan pencurian data pribadi atau data sensitif', 1, NOW(), NOW()),
('Akses Ilegal', 'Akses tidak sah ke sistem komputer atau jaringan', 1, NOW(), NOW()),
('Konten Ilegal', 'Penyebaran konten ilegal melalui media digital', 1, NOW(), NOW()),
('Perjudian Online', 'Perjudian yang dilakukan melalui platform digital', 1, NOW(), NOW()),
('Kejahatan Perbankan Digital', 'Kejahatan yang menargetkan sistem perbankan digital', 1, NOW(), NOW()),
('Lainnya', 'Kejahatan siber lainnya yang tidak termasuk kategori di atas', 1, NOW(), NOW());

-- ============================================
-- SEED DATA: JENIS KEJAHATAN SIBER
-- ============================================

-- Penipuan Online (kategori_kejahatan_id = 1)
INSERT INTO `jenis_kejahatan` (`kategori_kejahatan_id`, `nama`, `deskripsi`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Penipuan Investasi Bodong', 'Penipuan berkedok investasi dengan iming-iming keuntungan besar', 1, NOW(), NOW()),
(1, 'Penipuan Jual Beli Online', 'Penipuan dalam transaksi jual beli online (barang tidak dikirim/tidak sesuai)', 1, NOW(), NOW()),
(1, 'Penipuan Berkedok Undian', 'Penipuan dengan modus pemenang undian palsu', 1, NOW(), NOW()),
(1, 'Penipuan Romance Scam', 'Penipuan dengan modus hubungan romantis/asmara palsu', 1, NOW(), NOW()),
(1, 'Penipuan Berkedok Lowongan Kerja', 'Penipuan dengan modus tawaran pekerjaan palsu', 1, NOW(), NOW()),
(1, 'Penipuan Berkedok Pinjaman Online', 'Penipuan mengatasnamakan pinjaman online ilegal', 1, NOW(), NOW()),
(1, 'Penipuan Kripto/Aset Digital', 'Penipuan terkait cryptocurrency atau aset digital lainnya', 1, NOW(), NOW()),
(1, 'Penipuan Phishing', 'Penipuan untuk mendapatkan data sensitif melalui link/website palsu', 1, NOW(), NOW());

-- Pemerasan Digital (kategori_kejahatan_id = 2)
INSERT INTO `jenis_kejahatan` (`kategori_kejahatan_id`, `nama`, `deskripsi`, `is_active`, `created_at`, `updated_at`) VALUES
(2, 'Sextortion', 'Pemerasan menggunakan konten intim korban', 1, NOW(), NOW()),
(2, 'Ransomware', 'Pemerasan dengan mengenkripsi data korban', 1, NOW(), NOW()),
(2, 'Ancaman Penyebaran Data', 'Pemerasan dengan ancaman menyebarkan data pribadi', 1, NOW(), NOW()),
(2, 'Pemerasan via Sosial Media', 'Pemerasan melalui platform media sosial', 1, NOW(), NOW());

-- Pencurian Data (kategori_kejahatan_id = 3)
INSERT INTO `jenis_kejahatan` (`kategori_kejahatan_id`, `nama`, `deskripsi`, `is_active`, `created_at`, `updated_at`) VALUES
(3, 'Pencurian Identitas', 'Pencurian data identitas untuk disalahgunakan', 1, NOW(), NOW()),
(3, 'Pencurian Data Kartu Kredit', 'Pencurian data kartu kredit/debit', 1, NOW(), NOW()),
(3, 'Pembobolan Akun', 'Pengambilalihan akun digital korban', 1, NOW(), NOW()),
(3, 'Social Engineering', 'Manipulasi psikologis untuk mendapatkan data sensitif', 1, NOW(), NOW());

-- Akses Ilegal (kategori_kejahatan_id = 4)
INSERT INTO `jenis_kejahatan` (`kategori_kejahatan_id`, `nama`, `deskripsi`, `is_active`, `created_at`, `updated_at`) VALUES
(4, 'Hacking', 'Akses ilegal ke sistem komputer', 1, NOW(), NOW()),
(4, 'Defacing Website', 'Mengubah tampilan website tanpa izin', 1, NOW(), NOW()),
(4, 'DDoS Attack', 'Serangan untuk melumpuhkan layanan', 1, NOW(), NOW());

-- Konten Ilegal (kategori_kejahatan_id = 5)
INSERT INTO `jenis_kejahatan` (`kategori_kejahatan_id`, `nama`, `deskripsi`, `is_active`, `created_at`, `updated_at`) VALUES
(5, 'Penyebaran Konten Pornografi', 'Penyebaran konten pornografi termasuk revenge porn', 1, NOW(), NOW()),
(5, 'Cyberbullying', 'Perundungan melalui media digital', 1, NOW(), NOW()),
(5, 'Hoax/Berita Bohong', 'Penyebaran berita bohong/disinformasi', 1, NOW(), NOW()),
(5, 'SARA', 'Penyebaran konten berbau SARA', 1, NOW(), NOW()),
(5, 'Penghinaan/Pencemaran Nama Baik', 'Penghinaan atau pencemaran nama baik via digital', 1, NOW(), NOW());

-- Perjudian Online (kategori_kejahatan_id = 6)
INSERT INTO `jenis_kejahatan` (`kategori_kejahatan_id`, `nama`, `deskripsi`, `is_active`, `created_at`, `updated_at`) VALUES
(6, 'Judi Online', 'Perjudian melalui platform online/website', 1, NOW(), NOW()),
(6, 'Slot Online Ilegal', 'Permainan slot online ilegal', 1, NOW(), NOW());

-- Kejahatan Perbankan Digital (kategori_kejahatan_id = 7)
INSERT INTO `jenis_kejahatan` (`kategori_kejahatan_id`, `nama`, `deskripsi`, `is_active`, `created_at`, `updated_at`) VALUES
(7, 'Skimming', 'Pencurian data kartu melalui alat skimmer', 1, NOW(), NOW()),
(7, 'SIM Swap', 'Pengambilalihan nomor HP untuk akses perbankan', 1, NOW(), NOW()),
(7, 'Pembobolan Mobile Banking', 'Pembobolan aplikasi mobile banking', 1, NOW(), NOW()),
(7, 'Pembobolan Internet Banking', 'Pembobolan akses internet banking', 1, NOW(), NOW());

-- Lainnya (kategori_kejahatan_id = 8)
INSERT INTO `jenis_kejahatan` (`kategori_kejahatan_id`, `nama`, `deskripsi`, `is_active`, `created_at`, `updated_at`) VALUES
(8, 'Kejahatan Siber Lainnya', 'Jenis kejahatan siber lain yang tidak terkategori', 1, NOW(), NOW());

-- ============================================
-- SEED DATA: WILAYAH (Contoh Jawa Tengah)
-- Data lengkap: https://github.com/cahyadsn/wilayah
-- ============================================

INSERT INTO `wilayah` (`kode`, `nama`) VALUES
-- Provinsi
('33', 'Jawa Tengah'),
-- Kabupaten/Kota
('33.74', 'Kota Semarang'),
('33.75', 'Kota Salatiga'),
('33.22', 'Kabupaten Semarang'),
-- Kecamatan di Kota Semarang
('33.74.01', 'Semarang Tengah'),
('33.74.02', 'Semarang Utara'),
('33.74.03', 'Semarang Timur'),
('33.74.04', 'Gayamsari'),
('33.74.05', 'Genuk'),
-- Kelurahan di Kec. Semarang Tengah
('33.74.01.1001', 'Miroto'),
('33.74.01.1002', 'Brumbungan'),
('33.74.01.1003', 'Jagalan'),
('33.74.01.1004', 'Kranggan'),
('33.74.01.1005', 'Gabahan');

-- ============================================
-- END OF SCHEMA
-- Data wilayah lengkap Indonesia dapat diimport dari:
-- https://github.com/cahyadsn/wilayah
-- Sesuai Kepmendagri No 300.2.2-2138 Tahun 2025
-- ============================================
