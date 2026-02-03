<?php

use App\Http\Controllers\AdminSubdit\AdminSubditDashboardController;
use App\Http\Controllers\AdminSubdit\CaseManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\PawasController;
use App\Http\Controllers\Pimpinan\PimpinanDashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Sistem Laporan Kejahatan Siber - POLDA JATENG
|
*/

// ============================================
// PUBLIC ROUTES
// ============================================

// Redirect root to login page
Route::get('/', function () {
    return redirect()->route('login');
});

// ============================================
// AUTHENTICATED ROUTES (Sistem Pelaporan)
// Untuk: petugas only (Entry & Arsip Laporan)
// ============================================

// Pawas Selection (for petugas role - no middleware)
Route::middleware(['auth', 'verified', 'role:petugas'])->prefix('pawas')->name('pawas.')->group(function () {
    Route::get('/select', [PawasController::class, 'select'])->name('select');
    Route::post('/select', [PawasController::class, 'store'])->name('store');
    Route::post('/clear', [PawasController::class, 'clear'])->name('clear');
    Route::get('/search', [PawasController::class, 'search'])->name('search');
});

Route::middleware(['auth', 'verified', 'role:petugas', 'pawas.selected'])->group(function () {
    
    // Dashboard with statistics
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');
    
    // Dashboard API routes
    Route::prefix('api/dashboard')->name('dashboard.')->group(function () {
        Route::get('per-kabupaten', [DashboardController::class, 'getLaporanPerKabupaten'])
            ->name('per-kabupaten');
        Route::get('per-kecamatan', [DashboardController::class, 'getLaporanPerKecamatan'])
            ->name('per-kecamatan');
        Route::get('kerugian-per-bulan', [DashboardController::class, 'getKerugianPerBulan'])
            ->name('kerugian-per-bulan');
        Route::get('export', [DashboardController::class, 'exportStatistics'])
            ->name('export');
    });

    // ============================================
    // LAPORAN KEJAHATAN SIBER
    // ============================================
    
    // Resource routes for Laporan CRUD
    Route::resource('laporan', LaporanController::class);
    
    // Additional Laporan routes
    Route::prefix('laporan')->name('laporan.')->group(function () {
        // PDF Generation
        Route::get('{id}/pdf', [LaporanController::class, 'cetakPdf'])
            ->name('pdf');
        
        // Suspect linkage search
        Route::post('search-suspect', [LaporanController::class, 'searchSuspect'])
            ->name('search-suspect');
        
        // Attachments
        Route::post('{id}/lampiran', [LaporanController::class, 'uploadLampiran'])
            ->name('lampiran.store');
        Route::delete('{id}/lampiran/{lampiranId}', [LaporanController::class, 'deleteLampiran'])
            ->name('lampiran.destroy');
        
        // Admin: Assign laporan to subdit
        Route::post('{id}/assign-subdit', [LaporanController::class, 'assignSubdit'])
            ->name('assign-subdit');
    });

    // ============================================
    // MASTER DATA API (for dropdowns)
    // ============================================
    
    Route::prefix('api/master')->name('master.')->group(function () {
        // Form initialization (all dropdown data in one request)
        Route::get('form-init', [MasterDataController::class, 'formInit'])
            ->name('form-init');
        
        // Wilayah (hierarchical)
        Route::get('wilayah/{parentKode?}', [MasterDataController::class, 'wilayah'])
            ->name('wilayah');
        Route::get('provinsi', [MasterDataController::class, 'provinsi'])
            ->name('provinsi');
        Route::get('kabupaten/{kodeProvinsi}', [MasterDataController::class, 'kabupaten'])
            ->name('kabupaten');
        Route::get('kabupaten-all', [MasterDataController::class, 'kabupatenAll'])
            ->name('kabupaten-all');
        Route::get('kecamatan/{kodeKabupaten}', [MasterDataController::class, 'kecamatan'])
            ->name('kecamatan');
        Route::get('kelurahan/{kodeKecamatan}', [MasterDataController::class, 'kelurahan'])
            ->name('kelurahan');
        
        // Police data
        Route::get('pangkat', [MasterDataController::class, 'pangkat'])
            ->name('pangkat');
        Route::get('jabatan', [MasterDataController::class, 'jabatan'])
            ->name('jabatan');
        Route::get('anggota', [MasterDataController::class, 'anggota'])
            ->name('anggota');
        Route::get('anggota/{id}', [MasterDataController::class, 'anggotaShow'])
            ->name('anggota.show');
        
        // Crime types
        Route::get('kategori-kejahatan', [MasterDataController::class, 'kategoriKejahatan'])
            ->name('kategori-kejahatan');
        
        // Platforms (for identitas tersangka dependent dropdown)
        Route::get('platforms', [MasterDataController::class, 'getPlatforms'])
            ->name('platforms');
        
        // Countries & Phone Codes (for WNA dropdown and phone input)
        Route::get('countries', [MasterDataController::class, 'getCountries'])
            ->name('countries');
        Route::get('phone-codes', [MasterDataController::class, 'getPhoneCodes'])
            ->name('phone-codes');
    });

    // ============================================
    // PROFILE (Laravel Breeze default)
    // ============================================
    
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ============================================
// ADMIN SUBDIT ROUTES
// Untuk: admin_subdit only
// ============================================

Route::middleware(['auth', 'verified', 'role:admin_subdit'])->prefix('subdit')->name('subdit.')->group(function () {
    
    // Dashboard (Unit Management Focus)
    Route::get('/dashboard', [AdminSubditDashboardController::class, 'index'])->name('dashboard');
    Route::patch('/dashboard/{id}/assign-unit', [AdminSubditDashboardController::class, 'assignUnit'])->name('assign-unit');
});

Route::middleware(['auth', 'verified', 'role:admin_subdit'])->prefix('min-ops')->name('min-ops.')->group(function () {
    
    // Case Management (Manajemen Kasus)
    Route::get('/', [CaseManagementController::class, 'index'])->name('index');
    Route::get('/kasus/{id}', [CaseManagementController::class, 'show'])->name('show');
    Route::patch('/kasus/{id}/unit', [CaseManagementController::class, 'updateUnit'])->name('update-unit');
    Route::patch('/kasus/{id}/status', [CaseManagementController::class, 'updateStatus'])->name('update-status');
});

// ============================================
// PIMPINAN ROUTES (Executive Dashboard)
// Untuk: pimpinan only
// ============================================

Route::middleware(['auth', 'verified', 'role:pimpinan'])->prefix('pimpinan')->name('pimpinan.')->group(function () {
    
    // Executive Dashboard - Profiling & Demographics
    Route::get('/dashboard', [PimpinanDashboardController::class, 'index'])->name('dashboard');
});

// Auth routes (Laravel Breeze)
require __DIR__.'/auth.php';
