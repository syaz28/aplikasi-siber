# 🚔 SIBER JATENG v2

## Sistem Informasi Laporan Kejahatan Siber - Polda Jawa Tengah

> Sistem internal untuk pengelolaan laporan kejahatan siber di Direktorat Reserse Siber Polda Jawa Tengah.

![Laravel](https://img.shields.io/badge/Laravel-11-red?logo=laravel)
![Vue.js](https://img.shields.io/badge/Vue.js-3-green?logo=vue.js)
![TailwindCSS](https://img.shields.io/badge/TailwindCSS-3-blue?logo=tailwindcss)
![PHP](https://img.shields.io/badge/PHP-8.2+-purple?logo=php)

---

## 📋 Daftar Isi

1. [Tentang Proyek](#-tentang-proyek)
2. [Fitur Utama](#-fitur-utama)
3. [Tech Stack](#️-tech-stack)
4. [Arsitektur Sistem](#-arsitektur-sistem)
5. [Struktur Database](#️-struktur-database)
6. [Alur Aplikasi](#-alur-aplikasi)
7. [Instalasi](#-instalasi)
8. [Cara Penggunaan](#-cara-penggunaan)
9. [API Endpoints](#-api-endpoints)
10. [Komponen UI](#-komponen-ui)
11. [Developer](#-developer)
12. [Lisensi](#-lisensi)

---

## 🎯 Tentang Proyek

**SIBER JATENG** adalah sistem informasi internal yang digunakan untuk:

- ✅ Mencatat laporan kejahatan siber dari masyarakat
- ✅ Menghasilkan dokumen **STPA** (Surat Tanda Penerimaan Aduan) otomatis dalam format PDF
- ✅ Mendeteksi keterkaitan tersangka antar laporan (**Suspect Linkage**)
- ✅ Mengelola arsip kasus kejahatan siber
- ✅ Dashboard analitik untuk monitoring

### Permasalahan yang Diselesaikan

| Sebelum | Sesudah |
|---------|---------|
| ❌ Pencatatan manual yang memakan waktu | ✅ Form digital dengan auto-fill |
| ❌ Kesulitan tracking tersangka | ✅ Suspect linkage detection otomatis |
| ❌ Dokumen STPA diketik ulang | ✅ PDF generation otomatis |
| ❌ Data tersebar tidak terstruktur | ✅ Database terpusat & ternormalisasi |

---

## ✨ Fitur Utama

### 1. 📝 Multi-Step Form Entry
- Form **4 langkah** dengan progress indicator visual
- **Real-time validation** saat mengetik
- **Auto-save draft** setiap 30 detik ke localStorage
- **Review summary** sebelum submit final

### 2. 🔍 Searchable Dropdowns
- Semua dropdown bisa diketik untuk search
- Keyboard navigation (↑↓ Enter Escape)
- Loading indicator saat fetch data
- Applied to: Wilayah, Petugas, Jenis Kejahatan

### 3. 👤 Data Pelapor & Korban
- Support **multi-korban** per laporan
- Alamat dengan **cascading dropdown** wilayah Indonesia (83,000+ lokasi)
- **Auto-format**: NIK (1234 5678 9012 3456), Telepon (0812-3456-7890)
- **Copy data pelapor → korban** jika pelapor adalah korban

### 4. 🔴 Data Tersangka & Identitas Digital
- Support **multi-tersangka** per laporan
- Setiap tersangka bisa punya banyak identitas (No HP, Rekening, Sosmed)
- **Suspect Linkage Detection:** Otomatis deteksi tersangka yang sama di laporan lain

### 5. 📄 PDF Generation (STPA)
- Generate dokumen STPA otomatis menggunakan **FPDI template overlay**
- Data terisi lengkap dari database
- Format nomor STPA dengan **bulan Romawi**
- **Auto-scaling** untuk modus operandi yang panjang

### 6. 💾 Smart UX Features
- **Default Petugas**: Petugas yang dipilih disimpan, auto-load di form berikutnya
- **Toast Notifications**: Feedback visual untuk sukses/error
- **Draft Recovery**: Modal untuk lanjutkan draft yang belum selesai
- **Large Touch Targets**: Button minimal 44px untuk kemudahan klik

### 7. 📊 Arsip & Management
- List laporan dengan search & filter
- Stats cards (Total, Draft, Diajukan)
- Edit & Delete dengan konfirmasi
- Pagination

---

## 🛠️ Tech Stack

| Layer | Technology | Version |
|-------|------------|---------|
| **Backend** | Laravel | 11.x |
| **Frontend** | Vue.js + Inertia.js | 3.x |
| **Styling** | TailwindCSS | 3.x |
| **Database** | MySQL / SQLite | 8.0 / 3.x |
| **PDF** | FPDI + FPDF | 2.x |
| **Auth** | Laravel Breeze | - |
| **Build** | Vite | 6.x |

### Dependencies Utama

**Backend (composer.json):**
```
laravel/framework: ^11.0
inertiajs/inertia-laravel: ^2.0
setasign/fpdf: ^1.8
setasign/fpdi: ^2.6
```

**Frontend (package.json):**
```
vue: ^3.5.0
@inertiajs/vue3: ^2.0.0
tailwindcss: ^3.2.1
axios: ^1.11.0
```

---

## 🏗️ Arsitektur Sistem

```
┌─────────────────────────────────────────────────────────────┐
│                      BROWSER (User)                         │
│              Vue 3 + TailwindCSS + Inertia                  │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    INERTIA.JS ADAPTER                       │
│            (Bridge between Laravel & Vue)                   │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                      LARAVEL 11                             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │  Controllers │  │   Services   │  │    Models    │      │
│  │ (API/Pages)  │  │ (PDF/Logic)  │  │  (Eloquent)  │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                     MySQL / SQLite                          │
│                    (14 Custom Tables)                       │
└─────────────────────────────────────────────────────────────┘
```

### Struktur Folder

```
siber-jateng-v2/
├── app/
│   ├── Http/Controllers/       # Controllers
│   ├── Models/                 # Eloquent Models (14 models)
│   ├── Services/               # Business Logic
│   │   ├── StpaFpdiService.php # PDF Generator
│   │   └── TerbilangService.php
│   └── Templates/              # PDF Template
│       └── template_stpa.pdf
├── database/
│   ├── migrations/             # Database schema
│   ├── seeders/                # Master data seeders
│   └── wilayah.sql             # 83,000+ wilayah Indonesia
├── resources/js/
│   ├── Components/             # Vue Components
│   │   ├── SearchableSelect.vue
│   │   ├── FormattedInput.vue
│   │   ├── ToastContainer.vue
│   │   └── ReviewSummary.vue
│   ├── Composables/            # Vue Composables
│   │   ├── useToast.js
│   │   └── useFormStorage.js
│   ├── Layouts/                # Page Layouts
│   │   └── SidebarLayout.vue
│   └── Pages/                  # Page Components
│       ├── Auth/
│       ├── Laporan/
│       │   ├── Create.vue      # Multi-step form
│       │   ├── Index.vue       # Archive list
│       │   ├── Show.vue        # Detail view
│       │   └── Edit.vue        # Edit form
│       └── Dashboard.vue
└── routes/
    ├── web.php                 # Web routes
    └── api.php                 # API routes
```

---

## 🗃️ Struktur Database

### ERD (Entity Relationship)

```
                    ┌─────────────┐
                    │   wilayah   │
                    │ (83K rows)  │
                    └──────┬──────┘
                           │
     ┌─────────────────────┼─────────────────────┐
     │                     │                     │
     ▼                     ▼                     ▼
┌─────────┐         ┌─────────────┐        ┌──────────┐
│ pangkat │         │   alamat    │        │ jabatan  │
│ (16 rk) │         │             │        │ (7 pos)  │
└────┬────┘         └──────┬──────┘        └────┬─────┘
     │                     │                    │
     └──────────┬──────────┘                    │
                │                               │
                ▼                               │
         ┌─────────────┐                        │
         │   anggota   │◄───────────────────────┘
         │ (penyidik)  │
         └──────┬──────┘
                │
                ▼
         ┌─────────────┐
         │   users     │
         │ (login)     │
         └─────────────┘

┌───────────────────────────────────────────────────────────┐
│                         ORANG                             │
│   (Bisa jadi: Pelapor / Korban / Tersangka)              │
└────────────────────────┬──────────────────────────────────┘
                         │
         ┌───────────────┼───────────────┐
         │               │               │
         ▼               ▼               ▼
    ┌─────────┐    ┌──────────┐    ┌───────────┐
    │ LAPORAN │    │  KORBAN  │    │ TERSANGKA │
    └────┬────┘    └──────────┘    └─────┬─────┘
         │                               │
         │                               ▼
         │                      ┌──────────────────┐
         │                      │ IDENTITAS        │
         │                      │ TERSANGKA        │
         └──────────────────────┤ (HP, Rek, Sosmed)│
                                └──────────────────┘
```

### Tabel Utama

| Tabel | Deskripsi | Records |
|-------|-----------|---------|
| `wilayah` | Provinsi → Kelurahan (hierarchical) | 83,000+ |
| `pangkat` | Pangkat polisi (AKBP, KOMPOL, dll) | 16 |
| `jabatan` | Jabatan penyidik | 7 |
| `kategori_kejahatan` | Kategori kejahatan siber | 8 |
| `jenis_kejahatan` | Jenis kejahatan detail | 31 |
| `anggota` | Data penyidik | 7+ |
| `users` | User login | - |
| `orang` | Data person (pelapor/korban/tersangka) | - |
| `alamat` | Alamat lengkap dengan denormalized wilayah | - |
| `laporan` | Laporan kejahatan (main entity) | - |
| `korban` | Data korban per laporan (1:N) | - |
| `tersangka` | Data tersangka per laporan (1:N) | - |
| `identitas_tersangka` | Identitas digital tersangka (1:N) | - |
| `lampiran` | File bukti | - |

### Key Design Patterns

1. **Reporter ≠ Victim Pattern**
   - `orang` table generic untuk semua person
   - `laporan.pelapor_id` → siapa yang MELAPOR
   - `korban` table → siapa yang MENJADI KORBAN

2. **Multi-Identity Suspect**
   - `tersangka` bisa punya banyak `identitas_tersangka`
   - Types: telepon, rekening, sosmed, email, ewallet

3. **Denormalized Geography**
   - `alamat` menyimpan semua kode wilayah untuk fast queries
   - Tidak perlu JOIN untuk dashboard analytics

---

## 🔄 Alur Aplikasi

### Alur Input Laporan

```
┌──────────────┐     ┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│   STEP 1     │────▶│   STEP 2     │────▶│   STEP 3     │────▶│   STEP 4     │
│ Administrasi │     │ Data Pelapor │     │ Kejadian &   │     │ Tersangka &  │
│              │     │   + Alamat   │     │   Korban     │     │    Modus     │
└──────────────┘     └──────────────┘     └──────────────┘     └──────┬───────┘
                                                                      │
                                                               REVIEW & SUBMIT
                                                                      │
                                    ┌─────────────────────────────────┘
                                    ▼
                          ┌──────────────────┐
                          │ Review Summary   │
                          │ (Step 5)         │
                          └────────┬─────────┘
                                   │
                     ┌─────────────┴─────────────┐
                     ▼                           ▼
            ┌──────────────┐           ┌──────────────┐
            │   SUCCESS    │           │ Generate PDF │
            │  Save to DB  │           │    (STPA)    │
            └──────────────┘           └──────────────┘
```

### Auto-Save Flow

```
Form Input ──▶ 30 detik ──▶ localStorage.setItem('draft')
                                      │
                                      ▼
                          User buka form lagi
                                      │
                                      ▼
                          ┌───────────────────┐
                          │ Draft ditemukan!  │
                          │ Lanjutkan / Buang │
                          └───────────────────┘
```

---

## 🚀 Instalasi

### Prasyarat

- PHP 8.2+
- Composer 2.x
- Node.js 18+ & npm
- MySQL 8.0 atau SQLite
- Laragon (recommended) atau XAMPP

### Langkah Instalasi

```bash
# 1. Clone repository
git clone [repository-url]
cd siber-jateng-v2

# 2. Install PHP dependencies
composer install

# 3. Install JavaScript dependencies
npm install

# 4. Setup environment
cp .env.example .env
php artisan key:generate

# 5. Configure database (edit .env)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=siber_jateng_v2
DB_USERNAME=root
DB_PASSWORD=

# 6. Create database
# Via HeidiSQL/phpMyAdmin: CREATE DATABASE siber_jateng_v2;

# 7. Run migrations
php artisan migrate

# 8. Seed master data
php artisan db:seed

# 9. Import wilayah Indonesia (opsional, 83K records)
php artisan db:seed --class=WilayahImportSeeder

# 10. Build frontend
npm run build

# 11. Start development servers
php artisan serve      # Terminal 1: http://localhost:8000
npm run dev            # Terminal 2: Vite dev server (hot reload)
```

### Quick Start (Development)

```bash
# Jika sudah pernah setup, cukup:
php artisan serve
npm run dev
```

---

## 📱 Cara Penggunaan

### Login
1. Buka http://localhost:8000
2. Register akun baru atau login
3. Redirect otomatis ke form entry

### Input Laporan Baru
1. **Step 1 - Administrasi:** Pilih petugas penyidik (akan diingat untuk laporan berikutnya)
2. **Step 2 - Data Pelapor:** Isi identitas + alamat dengan searchable dropdown
3. **Step 3 - Kejadian & Korban:** Pilih jenis kejahatan, tambah korban, isi kerugian
4. **Step 4 - Tersangka & Modus:** Tambah tersangka dengan identitas digital, isi kronologi
5. **Step 5 - Review:** Periksa semua data, konfirmasi & simpan
6. **Hasil:** PDF STPA otomatis ter-generate dan terbuka di tab baru

### Arsip Kasus
- Lihat daftar semua laporan
- Filter berdasarkan status
- Search by nama/NIK/nomor STPA
- Edit atau hapus laporan
- Download PDF STPA

---

## 🔌 API Endpoints

### Authentication

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/login` | Halaman login |
| POST | `/login` | Proses login |
| GET | `/register` | Halaman register |
| POST | `/register` | Proses register |
| POST | `/logout` | Logout |

### Laporan

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/laporan` | List semua laporan (paginated) |
| GET | `/laporan/create` | Form entry laporan |
| POST | `/laporan` | Simpan laporan baru |
| GET | `/laporan/{id}` | Detail laporan |
| GET | `/laporan/{id}/edit` | Form edit laporan |
| PUT | `/laporan/{id}` | Update laporan |
| DELETE | `/laporan/{id}` | Hapus laporan |
| GET | `/laporan/{id}/pdf` | Generate PDF STPA |
| POST | `/laporan/search-suspect` | Cari suspect linkage |

### Master Data API

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/api/master/form-init` | Semua dropdown data (bulk) |
| GET | `/api/master/provinsi` | Daftar provinsi |
| GET | `/api/master/kabupaten/{kode}` | Kabupaten by provinsi |
| GET | `/api/master/kecamatan/{kode}` | Kecamatan by kabupaten |
| GET | `/api/master/kelurahan/{kode}` | Kelurahan by kecamatan |
| GET | `/api/master/kategori-kejahatan` | Kategori kejahatan |
| GET | `/api/master/jenis-kejahatan/{id}` | Jenis by kategori |
| GET | `/api/master/anggota` | Daftar petugas |

---

## 🎨 Komponen UI

### Vue Components

| Component | Deskripsi |
|-----------|-----------|
| `SearchableSelect.vue` | Dropdown dengan search & keyboard navigation |
| `FormattedInput.vue` | Input dengan auto-format (NIK, Phone, Currency) |
| `ToastContainer.vue` | Toast notification system |
| `ReviewSummary.vue` | Summary data sebelum submit |
| `StepIndicator.vue` | Progress indicator multi-step form |
| `TerbilangInput.vue` | Currency input dengan preview terbilang |

### Composables

| Composable | Deskripsi |
|------------|-----------|
| `useToast.js` | Toast notification API |
| `useFormStorage.js` | localStorage utilities (draft, settings) |

### Custom TailwindCSS Colors

```css
tactical-bg: #f8fafc
tactical-border: #e2e8f0
tactical-accent: #3b82f6 (blue)
tactical-success: #22c55e (green)
tactical-danger: #ef4444 (red)
tactical-warning: #f59e0b (amber)
navy: #1e3a5f (dark blue)
```

---

## 👨‍💻 Developer

**[Nama Anda]**  
Mahasiswa Magang - Ditressiber Polda Jawa Tengah  
Periode: Januari 2026 - Juni 2026

### Tech Contact
- Email: [your-email]
- GitHub: [your-github]

---

## 📄 Lisensi

Hak Cipta © 2026 Direktorat Reserse Siber Polda Jawa Tengah  
*Untuk penggunaan internal saja. Tidak untuk didistribusikan.*

---

## 📝 Changelog

### v2.0.0 (Januari 2026)
- ✨ Migrasi dari React ke Vue.js
- ✨ Searchable dropdowns dengan keyboard navigation
- ✨ Auto-format input (NIK, Telepon, Currency)
- ✨ Default petugas (localStorage)
- ✨ Draft auto-save setiap 30 detik
- ✨ Toast notifications
- ✨ Review summary sebelum submit
- ✨ Real-time validation
- ✨ Edit & Delete di arsip
- 🔧 Database schema 14 tabel
- 🔧 PDF STPA dengan FPDI template overlay
- 🔧 Wilayah Indonesia 83,000+ records
