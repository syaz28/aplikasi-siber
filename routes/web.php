<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\MasterDataController;
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
// AUTHENTICATED ROUTES
// ============================================

Route::middleware(['auth', 'verified'])->group(function () {
    
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
        Route::get('jenis-kejahatan/{kategoriId}', [MasterDataController::class, 'jenisKejahatan'])
            ->name('jenis-kejahatan');
        Route::get('jenis-kejahatan', [MasterDataController::class, 'jenisKejahatanAll'])
            ->name('jenis-kejahatan.all');
    });

    // ============================================
    // PROFILE (Laravel Breeze default)
    // ============================================
    
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Auth routes (Laravel Breeze)
require __DIR__.'/auth.php';
