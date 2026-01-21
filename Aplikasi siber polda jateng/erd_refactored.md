# Entity Relationship Diagram (Refactored)
## Sistem Laporan Kejahatan Siber - POLDA JATENG
### Konvensi: Bahasa Indonesia | Data Wilayah: Single Table (cahyadsn/wilayah)

**Referensi Data Wilayah:** [github.com/cahyadsn/wilayah](https://github.com/cahyadsn/wilayah)

---

## Format Kode Wilayah (Kemendagri)

| Level | Nama | Format | Contoh |
|-------|------|--------|--------|
| 1 | Provinsi | `XX` | `33` (Jawa Tengah) |
| 2 | Kabupaten/Kota | `XX.XX` | `33.74` (Kota Semarang) |
| 3 | Kecamatan | `XX.XX.XX` | `33.74.01` (Semarang Tengah) |
| 4 | Kelurahan/Desa | `XX.XX.XX.XXXX` | `33.74.01.1001` (Miroto) |

---

## ERD - Master Data Wilayah (Single Table)

```mermaid
erDiagram
    WILAYAH {
        varchar_13 kode PK "Kode hierarkis Kemendagri"
        varchar_100 nama "Nama wilayah"
    }
```

**Contoh Data:**
```
kode          | nama
--------------+------------------
33            | Jawa Tengah
33.74         | Kota Semarang
33.74.01      | Semarang Tengah
33.74.01.1001 | Miroto
```

---

## ERD - Master Data Kepolisian & Kejahatan

```mermaid
erDiagram
    PANGKAT {
        integer id PK
        varchar kode UK "AKBP, KOMPOL, dll"
        varchar nama "Nama Lengkap Pangkat"
        integer urutan "Hierarki"
    }

    JABATAN {
        integer id PK
        varchar nama UK "Nama Jabatan"
        text deskripsi
    }

    KATEGORI_KEJAHATAN {
        integer id PK
        varchar nama UK "Nama Kategori"
        text deskripsi
        boolean is_active
    }

    JENIS_KEJAHATAN {
        integer id PK
        integer kategori_kejahatan_id FK
        varchar nama "Nama Jenis"
        text deskripsi
        boolean is_active
    }

    KATEGORI_KEJAHATAN ||--o{ JENIS_KEJAHATAN : "memiliki"
```

---

## ERD - Users & Anggota

```mermaid
erDiagram
    USERS {
        integer id PK
        integer anggota_id FK "Nullable"
        varchar email UK
        varchar password
        varchar role "superadmin | admin | operator"
        boolean is_active
    }

    ANGGOTA {
        integer id PK
        integer pangkat_id FK
        integer jabatan_id FK
        varchar nrp UK "Nomor Registrasi Pokok"
        varchar nama
        boolean is_active
    }

    PANGKAT ||--o{ ANGGOTA : "memiliki"
    JABATAN ||--o{ ANGGOTA : "memiliki"
    USERS }o--|| ANGGOTA : "bisa_menjadi"
```

---

## ERD - Orang & Alamat (Denormalized Wilayah)

```mermaid
erDiagram
    ORANG {
        integer id PK
        char_16 nik UK "NIK 16 digit UNIQUE"
        varchar nama
        varchar tempat_lahir
        date tanggal_lahir
        varchar jenis_kelamin
        varchar pekerjaan
        varchar telepon
        varchar email
    }

    ALAMAT {
        integer id PK
        integer orang_id FK
        varchar jenis_alamat "ktp | domisili"
        varchar_2 kode_provinsi FK "33"
        varchar_5 kode_kabupaten FK "33.74"
        varchar_8 kode_kecamatan FK "33.74.01"
        varchar_13 kode_kelurahan FK "33.74.01.1001"
        text detail_alamat "Jalan RT RW No"
    }

    WILAYAH {
        varchar_13 kode PK
        varchar nama
    }

    ORANG ||--o{ ALAMAT : "memiliki"
    WILAYAH ||--o{ ALAMAT : "kode_provinsi"
    WILAYAH ||--o{ ALAMAT : "kode_kabupaten"
    WILAYAH ||--o{ ALAMAT : "kode_kecamatan"
    WILAYAH ||--o{ ALAMAT : "kode_kelurahan"
```

---

## ERD - Laporan Kejahatan (Core)

```mermaid
erDiagram
    ORANG {
        integer id PK
        char_16 nik UK
        varchar nama
    }

    ANGGOTA {
        integer id PK
        varchar nrp UK
        varchar nama
    }

    JENIS_KEJAHATAN {
        integer id PK
        varchar nama
    }

    WILAYAH {
        varchar_13 kode PK
        varchar nama
    }

    LAPORAN {
        integer id PK
        varchar nomor_stpa UK "Nomor STPA"
        datetime tanggal_laporan "Tanggal dan waktu laporan"
        integer pelapor_id FK
        varchar hubungan_pelapor
        integer petugas_id FK
        integer jenis_kejahatan_id FK
        varchar_2 kode_provinsi_kejadian FK
        varchar_5 kode_kabupaten_kejadian FK
        varchar_8 kode_kecamatan_kejadian FK
        varchar_13 kode_kelurahan_kejadian FK
        text alamat_kejadian
        datetime waktu_kejadian
        text modus
        varchar status
        text catatan
    }

    KORBAN {
        integer id PK
        integer laporan_id FK
        integer orang_id FK
        decimal kerugian_nominal
        varchar kerugian_terbilang
        text keterangan
    }

    TERSANGKA {
        integer id PK
        integer laporan_id FK
        integer orang_id FK "Nullable"
        text catatan
    }

    IDENTITAS_TERSANGKA {
        integer id PK
        integer tersangka_id FK
        varchar jenis
        varchar nilai
        varchar platform
        varchar nama_akun
        text catatan
    }

    LAMPIRAN {
        integer id PK
        integer laporan_id FK
        varchar nama_file
        varchar path_file
        varchar jenis_file
        integer ukuran_file
        text deskripsi
    }

    ORANG ||--o{ LAPORAN : "melapor"
    ANGGOTA ||--o{ LAPORAN : "menangani"
    JENIS_KEJAHATAN ||--o{ LAPORAN : "dikategorikan"
    WILAYAH ||--o{ LAPORAN : "kode_provinsi_kejadian"
    WILAYAH ||--o{ LAPORAN : "kode_kabupaten_kejadian"
    WILAYAH ||--o{ LAPORAN : "kode_kecamatan_kejadian"
    WILAYAH ||--o{ LAPORAN : "kode_kelurahan_kejadian"
    
    LAPORAN ||--o{ KORBAN : "memiliki"
    ORANG ||--o{ KORBAN : "menjadi_korban"
    
    LAPORAN ||--o{ TERSANGKA : "memiliki"
    ORANG ||--o{ TERSANGKA : "dicurigai"
    TERSANGKA ||--o{ IDENTITAS_TERSANGKA : "memiliki"
    
    LAPORAN ||--o{ LAMPIRAN : "memiliki"
```

---

## ERD - Full Diagram

```mermaid
erDiagram
    %% WILAYAH (Single Table)
    WILAYAH {
        varchar_13 kode PK
        varchar_100 nama
    }

    %% USERS & ANGGOTA
    USERS }o--|| ANGGOTA : "bisa_menjadi"
    PANGKAT ||--o{ ANGGOTA : "pangkat"
    JABATAN ||--o{ ANGGOTA : "jabatan"

    %% KEJAHATAN
    KATEGORI_KEJAHATAN ||--o{ JENIS_KEJAHATAN : "memiliki"

    %% ORANG & ALAMAT
    ORANG ||--o{ ALAMAT : "memiliki"
    WILAYAH ||--o{ ALAMAT : "wilayah"

    %% LAPORAN
    ORANG ||--o{ LAPORAN : "melapor"
    ANGGOTA ||--o{ LAPORAN : "menangani"
    JENIS_KEJAHATAN ||--o{ LAPORAN : "jenis"
    WILAYAH ||--o{ LAPORAN : "lokasi"

    %% KORBAN
    LAPORAN ||--o{ KORBAN : "memiliki"
    ORANG ||--o{ KORBAN : "menjadi"

    %% TERSANGKA
    LAPORAN ||--o{ TERSANGKA : "memiliki"
    ORANG ||--o{ TERSANGKA : "dicurigai"
    TERSANGKA ||--o{ IDENTITAS_TERSANGKA : "memiliki"

    %% LAMPIRAN
    LAPORAN ||--o{ LAMPIRAN : "memiliki"

    PANGKAT {
        integer id PK
        varchar kode UK
        varchar nama
        integer urutan
    }

    JABATAN {
        integer id PK
        varchar nama UK
        text deskripsi
    }

    KATEGORI_KEJAHATAN {
        integer id PK
        varchar nama UK
        text deskripsi
        boolean is_active
    }

    JENIS_KEJAHATAN {
        integer id PK
        integer kategori_kejahatan_id FK
        varchar nama
        text deskripsi
        boolean is_active
    }

    USERS {
        integer id PK
        integer anggota_id FK
        varchar email UK
        varchar password
        varchar role
        boolean is_active
    }

    ANGGOTA {
        integer id PK
        integer pangkat_id FK
        integer jabatan_id FK
        varchar nrp UK
        varchar nama
        boolean is_active
    }

    ORANG {
        integer id PK
        char_16 nik UK
        varchar nama
        varchar tempat_lahir
        date tanggal_lahir
        varchar jenis_kelamin
        varchar pekerjaan
        varchar telepon
        varchar email
    }

    ALAMAT {
        integer id PK
        integer orang_id FK
        varchar jenis_alamat
        varchar_2 kode_provinsi FK
        varchar_5 kode_kabupaten FK
        varchar_8 kode_kecamatan FK
        varchar_13 kode_kelurahan FK
        text detail_alamat
    }

    LAPORAN {
        integer id PK
        varchar nomor_stpa UK
        datetime tanggal_laporan
        integer pelapor_id FK
        varchar hubungan_pelapor
        integer petugas_id FK
        integer jenis_kejahatan_id FK
        varchar_2 kode_provinsi_kejadian FK
        varchar_5 kode_kabupaten_kejadian FK
        varchar_8 kode_kecamatan_kejadian FK
        varchar_13 kode_kelurahan_kejadian FK
        text alamat_kejadian
        datetime waktu_kejadian
        text modus
        varchar status
        text catatan
    }

    KORBAN {
        integer id PK
        integer laporan_id FK
        integer orang_id FK
        decimal kerugian_nominal
        varchar kerugian_terbilang
        text keterangan
    }

    TERSANGKA {
        integer id PK
        integer laporan_id FK
        integer orang_id FK
        text catatan
    }

    IDENTITAS_TERSANGKA {
        integer id PK
        integer tersangka_id FK
        varchar jenis
        varchar nilai
        varchar platform
        varchar nama_akun
        text catatan
    }

    LAMPIRAN {
        integer id PK
        integer laporan_id FK
        varchar nama_file
        varchar path_file
        varchar jenis_file
        integer ukuran_file
        text deskripsi
    }
```

---

## Legenda

| Simbol | Keterangan |
|--------|------------|
| PK | Primary Key |
| FK | Foreign Key |
| UK | Unique Key |
| `\|\|--o{` | One-to-Many |
| `}o--\|\|` | Many-to-One (Optional) |

---

## Catatan Penting

1. **Tabel `wilayah` adalah Single Table** - berisi semua level (provinsi, kab/kota, kecamatan, kelurahan)
2. **Kode wilayah hierarkis** - dari `33.74.01.1001` bisa extract `33`, `33.74`, `33.74.01`
3. **Alamat denormalized** - simpan kode_provinsi, kode_kabupaten, kode_kecamatan, kode_kelurahan untuk kemudahan query
4. **`users.anggota_id` adalah NULLABLE** - User bisa standalone tanpa jadi anggota polisi
5. **`orang.nik` adalah UNIQUE** - Satu NIK = satu record orang
6. **Pelapor ≠ Korban** - Pelapor bisa mewakili korban lain
7. **`tersangka.orang_id` bisa NULL** - Untuk tersangka yang belum teridentifikasi
