<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminKategoriController;
use App\Http\Controllers\Admin\AdminLaporanController;
use App\Http\Controllers\Admin\AdminPersonelController;
use App\Http\Controllers\Admin\AdminUserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Routes untuk halaman admin (kelola personel, assign laporan ke subdit)
| Hanya dapat diakses oleh user dengan role: admin
|
*/

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    
    // Admin Dashboard
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    
    // Kelola Personel (CRUD) - NEW: Replaces User Management
    Route::resource('personels', AdminPersonelController::class);
    
    // Legacy: Kelola User (CRUD) - Keep for reference but hidden from nav
    Route::resource('users', AdminUserController::class);
    Route::post('users/{user}/reset-password', [AdminUserController::class, 'resetPassword'])
        ->name('users.reset-password');
    
    // Kelola Kategori Kejahatan (CRUD)
    Route::resource('kategori', AdminKategoriController::class);
    Route::post('kategori/{kategori}/toggle-status', [AdminKategoriController::class, 'toggleStatus'])
        ->name('kategori.toggle-status');
    
    // Kelola Laporan Masuk (assign ke subdit)
    Route::get('laporan', [AdminLaporanController::class, 'index'])->name('laporan.index');
    Route::get('laporan/{laporan}', [AdminLaporanController::class, 'show'])->name('laporan.show');
    Route::post('laporan/{laporan}/assign', [AdminLaporanController::class, 'assignSubdit'])->name('laporan.assign');
});
