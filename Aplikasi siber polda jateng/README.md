# 🔄 Database Refactoring Plan
## Sistem Laporan Kejahatan Siber - POLDA JATENG

---

## 📋 Daftar Isi

1. [Konvensi Penamaan](#-konvensi-penamaan)
2. [Referensi Data Wilayah](#-referensi-data-wilayah)
3. [Keputusan Arsitektur](#-keputusan-arsitektur)
4. [Struktur Database](#-struktur-database)
5. [Penjelasan Relasi](#-penjelasan-relasi)
6. [Manfaat Dashboard & Reporting](#-manfaat-dashboard--reporting)

---

## 📝 Konvensi Penamaan

### Bahasa Indonesia
Semua nama tabel dan kolom menggunakan **Bahasa Indonesia**, kecuali field system-centric.

### Field System-Centric (Tetap English)
```
created_at, updated_at, deleted_at
created_by, updated_by, deleted_by
submitted_at, submitted_by
verified_at, verified_by
is_active, is_deleted
```

### Contoh Penamaan
| English | Bahasa Indonesia |
|---------|------------------|
| provinces | wilayah |
| police_officers | anggota |
| persons | orang |
| cyber_reports | laporan |
| suspects | tersangka |
| victims | korban |

---

## 🗺️ Referensi Data Wilayah

### Sumber Data
Data wilayah menggunakan standar **Kepmendagri No 300.2.2-2138 Tahun 2025**.

**Repository Referensi:** [cahyadsn/wilayah](https://github.com/cahyadsn/wilayah)

### Struktur Tabel Wilayah (Single Table)

Mengikuti struktur dari cahyadsn/wilayah:

```sql
CREATE TABLE wilayah (
    kode VARCHAR(13) NOT NULL PRIMARY KEY,
    nama VARCHAR(100) NOT NULL
);
```

### Format Kode Wilayah (Kemendagri)

| Level | Nama | Format Kode | Contoh |
|-------|------|-------------|--------|
| 1 | Provinsi | `XX` | `33` (Jawa Tengah) |
| 2 | Kabupaten/Kota | `XX.XX` | `33.74` (Kota Semarang) |
| 3 | Kecamatan | `XX.XX.XX` | `33.74.01` (Semarang Tengah) |
| 4 | Kelurahan/Desa | `XX.XX.XX.XXXX` | `33.74.01.1001` (Miroto) |

### Hierarki Kode

```
Kode Kelurahan: 33.74.01.1001

├── 33          → Provinsi (Jawa Tengah)
├── 33.74       → Kabupaten/Kota (Kota Semarang)
├── 33.74.01    → Kecamatan (Semarang Tengah)
└── 33.74.01.1001 → Kelurahan (Miroto)
```

### Struktur Alamat (Denormalized)

Di tabel `alamat` dan `laporan`, simpan **semua kode wilayah** untuk kemudahan query:

```sql
alamat
├── kode_provinsi     -- '33'
├── kode_kabupaten    -- '33.74'
├── kode_kecamatan    -- '33.74.01'
├── kode_kelurahan    -- '33.74.01.1001'
└── detail_alamat     -- 'Jl. Pemuda No. 1 RT 01/02'
```

**Keuntungan:**
- Query per level tanpa parsing string
- Filter by provinsi/kabupaten langsung
- Dashboard/reporting lebih cepat

---

## 🎯 Keputusan Arsitektur

### 1. Relasi `users` ↔ `anggota`

```
users
├── id
├── anggota_id (FK, nullable) ← User BISA tapi TIDAK HARUS anggota polisi
└── ...

anggota (police_officers)
├── id
├── pangkat_id (FK)
├── jabatan_id (FK)
└── ...
```

**Catatan:**
- User bisa standalone (admin sistem, operator non-polisi)
- User bisa terhubung ke anggota polisi
- Anggota polisi bisa exist tanpa user (data referensi)

### 2. Relasi `orang` (persons)

```
orang
├── id
├── nik (UNIQUE)
└── ...

Digunakan oleh:
├── laporan.pelapor_id → orang yang MELAPOR
├── korban.orang_id → orang yang menjadi KORBAN
└── tersangka.orang_id → orang yang menjadi TERSANGKA
```

**Catatan:**
- `orang` adalah entitas eksternal (bukan user sistem)
- Jika anggota polisi menjadi pelapor/tersangka, akan diinput sebagai `orang` baru
- Lookup menggunakan NIK jika diperlukan

### 3. Pelapor vs Korban

```
┌─────────────────────────────────────────────────────────────┐
│                        LAPORAN                              │
├─────────────────────────────────────────────────────────────┤
│  pelapor_id ─────────────────────────────→ orang            │
│  hubungan_pelapor (diri_sendiri/keluarga/kuasa_hukum/dll)   │
├─────────────────────────────────────────────────────────────┤
│                           │                                 │
│                           ▼                                 │
│  ┌─────────────────────────────────────────────────────┐    │
│  │                    korban (1:N)                     │    │
│  │  ├── orang_id ───────────────────────→ orang        │    │
│  │  ├── kerugian_nominal                               │    │
│  │  └── keterangan                                     │    │
│  └─────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────┘
```

**Skenario:**
| Skenario | pelapor_id | hubungan_pelapor | korban |
|----------|------------|------------------|--------|
| Melapor untuk diri sendiri | Orang A | diri_sendiri | Orang A |
| Melapor untuk orang lain | Orang A | keluarga | Orang B |
| Melapor untuk banyak korban | Orang A | kuasa_hukum | Orang B, C, D |

---

## 📊 Struktur Database

### Diagram Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│                     MASTER DATA WILAYAH (Single Table)              │
│  wilayah (kode, nama) - Sumber: github.com/cahyadsn/wilayah         │
│  Format: 33 | 33.74 | 33.74.01 | 33.74.01.1001                      │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                   MASTER DATA KEPOLISIAN                            │
│  pangkat ──┐                                                        │
│            ├──→ anggota ←── users (nullable)                        │
│  jabatan ──┘                                                        │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                   MASTER DATA KEJAHATAN                             │
│  kategori_kejahatan → jenis_kejahatan                               │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                      DATA ORANG & ALAMAT                            │
│  orang ←──── alamat ────→ wilayah (via kode_kelurahan)              │
│    │         (kode_provinsi, kode_kabupaten,                        │
│    │          kode_kecamatan, kode_kelurahan)                       │
│    │                                                                │
│    ├── Sebagai PELAPOR (laporan.pelapor_id)                         │
│    ├── Sebagai KORBAN (korban.orang_id)                             │
│    └── Sebagai TERSANGKA (tersangka.orang_id)                       │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                       LAPORAN KEJAHATAN                             │
│                                                                     │
│  laporan ──────────────────────────────────────────────────┐        │
│    │ (pelapor_id → orang)                                  │        │
│    │ (petugas_id → anggota)                                │        │
│    │ (jenis_kejahatan_id → jenis_kejahatan)                │        │
│    │ (kode_kelurahan_kejadian → wilayah)                   │        │
│    │                                                       │        │
│    ├─→ korban (1:N) ──────────────────→ orang              │        │
│    │     └── kerugian per korban                           │        │
│    │                                                       │        │
│    ├─→ tersangka (1:N) ───────────────→ orang (nullable)   │        │
│    │     └── identitas_tersangka (1:N)                     │        │
│    │                                                       │        │
│    └─→ lampiran (1:N)                                      │        │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

### Daftar Tabel (14 Tabel)

#### Master Data Wilayah (1 Tabel) - Sesuai cahyadsn/wilayah
| No | Tabel | Deskripsi |
|----|-------|-----------|
| 1 | `wilayah` | Single table: provinsi, kab/kota, kecamatan, kelurahan |

#### Master Data Kepolisian (2 Tabel)
| No | Tabel | Deskripsi |
|----|-------|-----------|
| 2 | `pangkat` | Pangkat kepolisian (AKBP, KOMPOL, dll) |
| 3 | `jabatan` | Jabatan (Kanit, Kasubnit, Penyidik, dll) |

#### Master Data Kejahatan (2 Tabel)
| No | Tabel | Deskripsi |
|----|-------|-----------|
| 4 | `kategori_kejahatan` | Kategori besar kejahatan |
| 5 | `jenis_kejahatan` | Jenis spesifik kejahatan |

#### Data Utama (9 Tabel)
| No | Tabel | Deskripsi |
|----|-------|-----------|
| 6 | `users` | Autentikasi (tetap English - Laravel) |
| 7 | `anggota` | Data anggota kepolisian |
| 8 | `orang` | Data orang (pelapor/korban/tersangka) |
| 9 | `alamat` | Alamat orang (dengan 4 kode wilayah) |
| 10 | `laporan` | Laporan kejahatan siber |
| 11 | `korban` | Korban per laporan |
| 12 | `tersangka` | Tersangka per laporan |
| 13 | `identitas_tersangka` | Identitas digital tersangka |
| 14 | `lampiran` | Lampiran bukti |

---

## 🔗 Penjelasan Relasi

### Relasi Utama

```
users ─────────────────┐
   │                   │ anggota_id (nullable)
   ▼                   ▼
pangkat ──→ anggota ←── jabatan
               │
               │ petugas_id
               ▼
           laporan ←───────────────── wilayah
               │                    (kode_kelurahan_kejadian)
    ┌──────────┼──────────┐
    │          │          │
    ▼          ▼          ▼
  korban   tersangka   lampiran
    │          │
    │          └──→ identitas_tersangka
    │
    └──────────────┐
                   ▼
                 orang ←── alamat ──→ wilayah
                   ▲        (kode_provinsi,
    ┌──────────────┘         kode_kabupaten,
    │                        kode_kecamatan,
laporan.pelapor_id           kode_kelurahan)
```

### Foreign Keys

| Tabel | Kolom | Referensi | On Delete |
|-------|-------|-----------|-----------|
| `users` | `anggota_id` | `anggota.id` | SET NULL |
| `anggota` | `pangkat_id` | `pangkat.id` | RESTRICT |
| `anggota` | `jabatan_id` | `jabatan.id` | RESTRICT |
| `alamat` | `kode_kelurahan` | `wilayah.kode` | RESTRICT |
| `laporan` | `pelapor_id` | `orang.id` | RESTRICT |
| `laporan` | `petugas_id` | `anggota.id` | RESTRICT |
| `laporan` | `jenis_kejahatan_id` | `jenis_kejahatan.id` | RESTRICT |
| `laporan` | `kode_kelurahan_kejadian` | `wilayah.kode` | SET NULL |
| `korban` | `laporan_id` | `laporan.id` | CASCADE |
| `korban` | `orang_id` | `orang.id` | RESTRICT |
| `tersangka` | `laporan_id` | `laporan.id` | CASCADE |
| `tersangka` | `orang_id` | `orang.id` | SET NULL |
| `identitas_tersangka` | `tersangka_id` | `tersangka.id` | CASCADE |

---

## 📈 Manfaat Dashboard & Reporting

### Query yang Bisa Dilakukan

#### 1. Laporan per Provinsi (Langsung tanpa JOIN wilayah)
```sql
SELECT 
    a.kode_provinsi,
    w.nama as nama_provinsi,
    COUNT(l.id) as total_laporan
FROM laporan l
JOIN alamat a ON ... -- jika query dari alamat pelapor
JOIN wilayah w ON w.kode = a.kode_provinsi
GROUP BY a.kode_provinsi;
```

#### 2. Laporan per Kabupaten/Kota
```sql
SELECT 
    kode_kabupaten_kejadian,
    w.nama as nama_kabupaten,
    COUNT(*) as total
FROM laporan l
JOIN wilayah w ON w.kode = l.kode_kabupaten_kejadian
WHERE l.kode_provinsi_kejadian = '33' -- Jawa Tengah
GROUP BY kode_kabupaten_kejadian;
```

#### 3. Rekening Paling Sering Dilaporkan
```sql
SELECT 
    it.nilai as nomor_rekening, 
    it.platform as bank, 
    COUNT(*) as total_laporan
FROM identitas_tersangka it
WHERE it.jenis = 'rekening'
GROUP BY it.nilai, it.platform
ORDER BY total_laporan DESC
LIMIT 10;
```

#### 4. Total Kerugian per Wilayah
```sql
SELECT 
    l.kode_kabupaten_kejadian,
    w.nama as kabupaten_kota,
    COUNT(l.id) as jumlah_kasus,
    SUM(k.kerugian_nominal) as total_kerugian
FROM laporan l
JOIN korban k ON k.laporan_id = l.id
JOIN wilayah w ON w.kode = l.kode_kabupaten_kejadian
GROUP BY l.kode_kabupaten_kejadian
ORDER BY total_kerugian DESC;
```

### Dashboard Metrics

| Metric | Sumber Data |
|--------|-------------|
| Total Laporan Hari Ini | `laporan` WHERE DATE(tanggal_laporan) = CURDATE() |
| Total Korban | COUNT dari `korban` |
| Total Kerugian | SUM dari `korban.kerugian_nominal` |
| Top Jenis Kejahatan | GROUP BY `jenis_kejahatan_id` |
| Top Rekening Dilaporkan | GROUP BY `identitas_tersangka` WHERE jenis='rekening' |
| Peta Kejahatan per Provinsi | GROUP BY `kode_provinsi_kejadian` |
| Peta Kejahatan per Kabupaten | GROUP BY `kode_kabupaten_kejadian` |

---

## 📁 File dalam Folder REFACTORY

| File | Deskripsi |
|------|-----------|
| `README.md` | Dokumen ini |
| `erd_refactored.md` | ERD format Mermaid |
| `erd_refactored.dbml` | ERD format DBML (untuk dbdiagram.io) |
| `erd_refactored.puml` | ERD format PlantUML |
| `schema_refactored.sql` | DDL lengkap + seed data |

---

## 📚 Referensi

- **Data Wilayah Indonesia:** [github.com/cahyadsn/wilayah](https://github.com/cahyadsn/wilayah)
- **Kepmendagri No 300.2.2-2138 Tahun 2025:** Pemberian Dan Pemutakhiran Kode, Data Wilayah Administrasi Pemerintahan, Dan Pulau
- **Demo Data Wilayah:** [wilayah.cahyadsn.com](https://wilayah.cahyadsn.com/)

---

*Dokumen ini dibuat untuk refactoring Sistem Laporan Kejahatan Siber POLDA JATENG*
*Konvensi: Bahasa Indonesia untuk tabel/kolom, English untuk field system-centric*
*Data Wilayah: Single table sesuai cahyadsn/wilayah*
