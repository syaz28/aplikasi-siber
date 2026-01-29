<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Laporan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * AdminDashboardController
 * 
 * Dashboard untuk Admin - menampilkan statistik dan laporan masuk
 */
class AdminDashboardController extends Controller
{
    /**
     * Display admin dashboard.
     */
    public function index(Request $request): InertiaResponse
    {
        // Statistik User
        $userStats = [
            'total' => User::count(),
            'active' => User::where('is_active', true)->count(),
            'by_role' => [
                'admin' => User::where('role', 'admin')->count(),
                'petugas' => User::where('role', 'petugas')->count(),
                'admin_subdit' => User::where('role', 'admin_subdit')->count(),
                'pimpinan' => User::where('role', 'pimpinan')->count(),
            ],
        ];

        // Statistik Laporan
        $laporanStats = [
            'total' => Laporan::count(),
            'belum_ditugaskan' => Laporan::whereNull('assigned_subdit')->count(),
            'sudah_ditugaskan' => Laporan::whereNotNull('assigned_subdit')->count(),
            'by_subdit' => [
                'subdit_1' => Laporan::where('assigned_subdit', 1)->count(),
                'subdit_2' => Laporan::where('assigned_subdit', 2)->count(),
                'subdit_3' => Laporan::where('assigned_subdit', 3)->count(),
            ],
            'by_status' => [
                'penyelidikan' => Laporan::where('status', Laporan::STATUS_PENYELIDIKAN)->count(),
                'penyidikan' => Laporan::where('status', Laporan::STATUS_PENYIDIKAN)->count(),
                'tahap_1' => Laporan::where('status', Laporan::STATUS_TAHAP_I)->count(),
                'tahap_2' => Laporan::where('status', Laporan::STATUS_TAHAP_II)->count(),
            ],
        ];

        // Laporan terbaru yang belum ditugaskan (untuk quick action)
        $laporanBaru = Laporan::with(['pelapor', 'kategoriKejahatan', 'korban'])
            ->whereNull('assigned_subdit')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        // Aktivitas terakhir (laporan yang baru di-assign)
        $aktivitasTerakhir = Laporan::with(['pelapor', 'assignedBy'])
            ->whereNotNull('assigned_subdit')
            ->orderBy('assigned_at', 'desc')
            ->take(5)
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'userStats' => $userStats,
            'laporanStats' => $laporanStats,
            'laporanBaru' => $laporanBaru,
            'aktivitasTerakhir' => $aktivitasTerakhir,
        ]);
    }
}
