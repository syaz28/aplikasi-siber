# 📋 PROJECT CONTEXT - Sistem Laporan Kejahatan Siber POLDA JATENG

---

## 🏢 Tentang Aplikasi

**Nama:** Sistem Laporan Kejahatan Siber (STPA)  
**Organisasi:** POLDA Jawa Tengah - Ditreskrimsus (Direktorat Reserse Kriminal Khusus)  
**Teknologi:** Laravel 12 + Inertia.js + Vue 3 + Tailwind CSS  
**Database:** MySQL  

### Fungsi Utama
1. **Pencatatan laporan kejahatan siber** dari masyarakat
2. **Generate dokumen STPA** (Surat Tanda Penerimaan Aduan) dalam format PDF
3. **Workflow manajemen kasus** - dari pelaporan hingga penyelidikan
4. **Deteksi residivis** - identifikasi tersangka berulang via identitas digital
5. **Dashboard analytics** - statistik kejahatan per wilayah

---

## 👥 Roles & Permissions

| Role | Akses | Fungsi |
|------|-------|--------|
| `admin` | `/admin/*` | Kelola user, assign laporan ke subdit |
| `petugas` | `/laporan/*`, `/dashboard` | Input laporan, cetak STPA |
| `admin_subdit` | `/min-ops/*`, `/dashboard` | Kelola kasus di subdit, disposisi ke unit |
| `pimpinan` | `/dashboard` | Lihat statistik dan laporan |

---

## 🗄️ Database Schema

### ERD (Entity Relationship Diagram)

```
┌─────────────┐       ┌─────────────┐       ┌─────────────┐
│    users    │       │   laporan   │       │    orang    │
│ (petugas)   │       │  (reports)  │       │  (persons)  │
├─────────────┤       ├─────────────┤       ├─────────────┤
│ id          │       │ id          │       │ id          │
│ name        │       │ nomor_stpa  │◄──────│ nik         │
│ email       │       │ tanggal     │       │ nama        │
│ role        │       │ pelapor_id ─┼──────►│ tempat_lahir│
│ subdit (1-3)│◄──────┤ petugas_id  │       │ tanggal_lahir
│ unit (1-5)  │       │ kategori_id │       │ jenis_kelamin
│ pangkat     │       │ status      │       │ pekerjaan   │
│ jabatan     │       │ modus       │       │ telepon     │
└─────────────┘       │ assigned_   │       └─────────────┘
                      │   subdit    │              │
                      │ disposisi_  │              │
                      │   unit      │              │
                      └──────┬──────┘              │
                             │                     │
              ┌──────────────┼──────────────┐     │
              ▼              ▼              ▼     │
        ┌──────────┐   ┌──────────┐   ┌──────────┐
        │  korban  │   │ tersangka│   │  alamat  │
        │ (victims)│   │(suspects)│   │(addresses)
        ├──────────┤   ├──────────┤   ├──────────┤
        │ id       │   │ id       │   │ id       │
        │laporan_id│   │laporan_id│   │ orang_id │
        │ orang_id─┼──►│ orang_id │   │jenis_alamat
        │ kerugian │   │ catatan  │   │ provinsi │
        │_nominal  │   └────┬─────┘   │ kabupaten│
        └──────────┘        │         │ kecamatan│
                            ▼         │ kelurahan│
                    ┌───────────────┐ └──────────┘
                    │  identitas_   │
                    │  tersangka    │
                    │(digital IDs)  │
                    ├───────────────┤
                    │ id            │
                    │ tersangka_id  │
                    │ jenis ────────┼─► telepon|rekening|sosmed|
                    │ nilai         │   email|ewallet|kripto|
                    │ platform      │   marketplace|website|lainnya
                    │ nama_akun     │
                    └───────────────┘
```

### Tabel Utama

#### 1. `laporan` - Laporan Kejahatan Siber
```php
// Status workflow
STATUS_PENYELIDIKAN = 'Penyelidikan'  // Status awal
STATUS_PENYIDIKAN   = 'Penyidikan'
STATUS_TAHAP_I      = 'Tahap I'       // Berkas ke kejaksaan
STATUS_TAHAP_II     = 'Tahap II'      // Penyerahan tersangka
STATUS_SP3          = 'SP3'           // Surat Perintah Penghentian Penyidikan
STATUS_RJ           = 'RJ'            // Restorative Justice
STATUS_DIVERSI      = 'Diversi'       // Pengalihan penyelesaian

// Hubungan pelapor dengan korban
HUBUNGAN_DIRI_SENDIRI = 'diri_sendiri' // Pelapor = Korban
HUBUNGAN_KELUARGA     = 'keluarga'
HUBUNGAN_KUASA_HUKUM  = 'kuasa_hukum'
HUBUNGAN_TEMAN        = 'teman'
HUBUNGAN_REKAN_KERJA  = 'rekan_kerja'
HUBUNGAN_LAINNYA      = 'lainnya'
```

**Kolom penting:**
- `nomor_stpa` - Nomor STPA (format: STPA/001/I/2026/Ditressiber)
- `pelapor_id` - FK ke tabel `orang`
- `petugas_id` - FK ke tabel `users`
- `assigned_subdit` - Subdit yang menangani (1-3)
- `disposisi_unit` - Unit yang menangani (1-5)
- `modus` - Modus operandi kejahatan

#### 2. `orang` - Data Orang (Pelapor/Korban)
- Menyimpan data pribadi (NIK, nama, tanggal lahir, dll)
- Bisa menjadi pelapor (`laporan.pelapor_id`)
- Bisa menjadi korban (`korban.orang_id`)
- Bisa menjadi tersangka (`tersangka.orang_id` - nullable)

#### 3. `korban` - Korban Per Laporan
- Relasi: `laporan` 1:N `korban`
- Menyimpan `kerugian_nominal` dan `kerugian_terbilang`
- Total kerugian dihitung dari SUM semua korban

#### 4. `tersangka` - Tersangka Per Laporan
- Relasi: `laporan` 1:N `tersangka`
- `orang_id` NULLABLE (tersangka bisa belum teridentifikasi)
- Catatan tambahan tentang tersangka

#### 5. `identitas_tersangka` - Identitas Digital Tersangka ⭐
- Relasi: `tersangka` 1:N `identitas_tersangka`
- **Fitur kunci untuk Deteksi Residivis**
- Jenis identitas: telepon, rekening, sosmed, email, ewallet, kripto, marketplace, website, lainnya
- `nilai` = nomor/username/ID
- `platform` = Instagram, BCA, DANA, dll

---

## 📁 Struktur File Penting

### Backend (Laravel)

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── LaporanController.php        # ⭐ CRUD laporan untuk petugas
│   │   ├── DashboardController.php      # Dashboard & statistik
│   │   ├── MasterDataController.php     # API dropdown (wilayah, dll)
│   │   ├── Admin/
│   │   │   ├── AdminLaporanController.php    # Admin assign ke subdit
│   │   │   ├── AdminUserController.php       # Kelola user
│   │   │   └── AdminKategoriController.php   # Kelola kategori kejahatan
│   │   └── AdminSubdit/
│   │       └── CaseManagementController.php  # ⭐ Admin Subdit kelola kasus
│   ├── Middleware/
│   │   └── RoleMiddleware.php           # Cek role user
│   └── Requests/
│       └── StoreLaporanRequest.php      # Validasi input laporan
├── Models/
│   ├── Laporan.php              # ⭐ Model utama
│   ├── Orang.php                # Data orang
│   ├── Korban.php               # Korban per laporan
│   ├── Tersangka.php            # Tersangka per laporan
│   ├── IdentitasTersangka.php   # ⭐ Identitas digital tersangka
│   ├── User.php                 # User/petugas
│   ├── Alamat.php               # Alamat orang
│   ├── Wilayah.php              # Master wilayah Indonesia
│   ├── KategoriKejahatan.php    # Kategori: Penipuan Online, dll
│   └── Lampiran.php             # File lampiran
├── Services/
│   ├── StpaFpdiService.php      # Generate PDF STPA
│   └── TerbilangService.php     # Konversi angka ke terbilang
└── Helpers/
    └── StpaNumberGenerator.php  # Generate nomor STPA

routes/
├── web.php                      # ⭐ Route petugas & admin_subdit
├── admin.php                    # Route admin
└── auth.php                     # Route login/register

database/
├── migrations/                  # Struktur tabel
└── seeders/                     # Data awal
```

### Frontend (Vue 3 + Inertia)

```
resources/js/
├── Layouts/
│   ├── SidebarLayout.vue        # ⭐ Layout petugas (sidebar kiri)
│   └── AdminLayout.vue          # Layout admin
├── Pages/
│   ├── Dashboard.vue            # Dashboard dengan chart
│   ├── Laporan/
│   │   ├── Index.vue            # Daftar arsip laporan
│   │   ├── Create.vue           # ⭐ Form input laporan (multi-step)
│   │   ├── Show.vue             # Detail laporan + residivis
│   │   └── Edit.vue             # Edit laporan
│   ├── Admin/
│   │   ├── Dashboard.vue        # Dashboard admin
│   │   ├── Laporan/
│   │   │   ├── Index.vue        # Daftar laporan masuk
│   │   │   └── Show.vue         # Detail + assign subdit
│   │   ├── Users/               # CRUD user
│   │   └── Kategori/            # CRUD kategori kejahatan
│   └── AdminSubdit/
│       ├── Index.vue            # Daftar kasus di subdit
│       └── Show.vue             # Detail kasus + disposisi unit
├── Components/
│   ├── SearchableSelect.vue     # Dropdown dengan search
│   ├── FormattedInput.vue       # Input dengan format (NIK, rupiah)
│   └── ToastContainer.vue       # Notifikasi toast
└── Composables/
    └── useToast.js              # Hook notifikasi
```

---

## 🔄 Workflow Aplikasi

### 1. Input Laporan (Petugas)
```
[Petugas] → /laporan/create (Form Multi-Step)
    │
    ├── Step 1: Data Pelapor (NIK, nama, alamat)
    ├── Step 2: Data Korban (1 atau lebih, kerugian)
    ├── Step 3: Data Tersangka + Identitas Digital
    ├── Step 4: Data Kejadian (waktu, lokasi, modus)
    └── Step 5: Lampiran (opsional)
    │
    ▼
[Laporan tersimpan] → Status: "Penyelidikan"
```

### 2. Assign ke Subdit (Admin)
```
[Admin] → /admin/laporan → Lihat laporan masuk
    │
    └── Pilih Subdit (1/2/3) → Assign
    │
    ▼
[Laporan.assigned_subdit = 1/2/3]
```

### 3. Disposisi ke Unit (Admin Subdit)
```
[Admin Subdit] → /min-ops → Lihat kasus di subditnya
    │
    └── Pilih Unit (1-5) → Disposisi
    │
    ▼
[Laporan.disposisi_unit = 1-5]
[Laporan.status = diupdate sesuai progress]
```

### 4. Cetak STPA
```
[User] → /laporan/{id}/pdf
    │
    ▼
[StpaFpdiService] → Generate PDF dengan template
    │
    ▼
[Download PDF]
```

---

## ⭐ Fitur Khusus: Deteksi Residivis

### Cara Kerja
Sistem mencocokkan **identitas digital tersangka** antar laporan untuk mendeteksi tersangka berulang.

```php
// Logika di Controller:
private function detectRecidivist(Laporan $laporan): array
{
    // Jenis yang perlu cocokkan nilai + platform
    $needsPlatformMatch = ['sosmed', 'ewallet', 'rekening', 'marketplace', 'kripto'];
    
    foreach ($laporan->tersangka as $tersangka) {
        foreach ($tersangka->identitas as $identitas) {
            // Query: cari identitas dengan nilai sama di laporan LAIN
            $query = IdentitasTersangka::where('nilai', $identitas->nilai)
                ->whereHas('tersangka', function ($q) use ($laporanId) {
                    $q->where('laporan_id', '!=', $laporanId);
                });
            
            // Sosmed: @hacker di Instagram ≠ @hacker di Twitter
            if (in_array($identitas->jenis, $needsPlatformMatch)) {
                $query->where('platform', $identitas->platform);
            }
            
            // Jika ditemukan → tandai sebagai RESIDIVIS
        }
    }
}
```

### Logika Matching

| Jenis | Match By | Contoh |
|-------|----------|--------|
| Telepon | Nilai saja | 081234567890 = 081234567890 ✅ |
| Email | Nilai saja | hacker@gmail.com ✅ |
| Sosmed | Nilai + Platform | @hacker (IG) ≠ @hacker (Twitter) ❌ |
| Rekening | Nilai + Platform | 1234567890 (BCA) ≠ 1234567890 (BRI) ❌ |
| E-Wallet | Nilai + Platform | 0812... (DANA) ≠ 0812... (OVO) ❌ |

### Tampilan UI
Jika terdeteksi residivis:
```
┌──────────────────────────────────────────────────────────────┐
│ 🔴 ⚠️ RESIDIVIS - Terdeteksi di 3 Kasus Lain    [Lihat ▼]   │
├──────────────────────────────────────────────────────────────┤
│ • Media Sosial: @hacker (Instagram)                          │
│   Perkara: STPA/001/I/2026 | Subdit 1 | Status: Penyelidikan │
│                                                              │
│ • Media Sosial: @hacker (Instagram)                          │
│   Perkara: STPA/002/I/2026 | Subdit 2 | Status: Penyidikan   │
└──────────────────────────────────────────────────────────────┘
```

---

## 🔑 API Endpoints Penting

### Laporan CRUD
| Method | Endpoint | Controller | Fungsi |
|--------|----------|------------|--------|
| GET | `/laporan` | LaporanController@index | Daftar arsip laporan |
| GET | `/laporan/create` | LaporanController@create | Form input laporan |
| POST | `/laporan` | LaporanController@store | Simpan laporan baru |
| GET | `/laporan/{id}` | LaporanController@show | Detail laporan |
| GET | `/laporan/{id}/pdf` | LaporanController@cetakPdf | Generate PDF STPA |

### Master Data API
| Method | Endpoint | Fungsi |
|--------|----------|--------|
| GET | `/api/master/form-init` | Semua dropdown data sekaligus |
| GET | `/api/master/provinsi` | Daftar provinsi |
| GET | `/api/master/kabupaten/{kodeProvinsi}` | Kabupaten per provinsi |
| GET | `/api/master/kecamatan/{kodeKabupaten}` | Kecamatan per kabupaten |
| GET | `/api/master/kelurahan/{kodeKecamatan}` | Kelurahan per kecamatan |
| GET | `/api/master/platforms` | Platform per jenis identitas |

### Admin Subdit
| Method | Endpoint | Fungsi |
|--------|----------|--------|
| GET | `/min-ops` | Daftar kasus di subdit |
| GET | `/min-ops/kasus/{id}` | Detail kasus |
| PATCH | `/min-ops/kasus/{id}/unit` | Disposisi ke unit |
| PATCH | `/min-ops/kasus/{id}/status` | Update status kasus |

---

## 📦 Dependencies

### Backend (composer.json)
- **Laravel 12** - Framework PHP
- **Inertia.js** - Bridge Laravel ↔ Vue
- **DOMPDF** - Generate PDF
- **FPDI** - PDF template overlay

### Frontend (package.json)
- **Vue 3** - Frontend framework
- **Inertia Vue 3** - SPA adapter
- **Tailwind CSS** - Styling
- **Chart.js** - Dashboard charts
- **Leaflet** - Maps

---

## 🚀 Cara Menjalankan

```bash
# Install dependencies
composer install
npm install

# Setup database
cp .env.example .env
php artisan key:generate
php artisan migrate --seed

# Jalankan
php artisan serve   # Backend: http://localhost:8000
npm run dev         # Frontend dengan hot reload
```

---

## 📝 Catatan untuk AI

### Saat diminta memodifikasi kode:
1. **Selalu cek file terkait** - Model, Controller, dan Vue component biasanya saling berkaitan
2. **Perhatikan eager loading** - Laporan punya banyak relasi yang perlu di-load
3. **Frontend pakai Inertia** - Data dikirim sebagai props, bukan API terpisah
4. **Multi-step form** - Create.vue punya logic wizard yang kompleks

### File yang sering diubah:
- `app/Http/Controllers/LaporanController.php` - Logic laporan
- `resources/js/Pages/Laporan/Create.vue` - Form input
- `resources/js/Pages/*/Show.vue` - Halaman detail
- `app/Models/Laporan.php` - Relasi dan konstanta

### Naming convention:
- Model: PascalCase singular (`Laporan`, `Korban`)
- Table: snake_case singular (`laporan`, `korban`)
- Controller: PascalCase + Controller (`LaporanController`)
- Vue: PascalCase (`Create.vue`, `Show.vue`)

---

*Dokumen ini di-generate untuk membantu AI memahami konteks projek.*
*Last updated: January 2026*
