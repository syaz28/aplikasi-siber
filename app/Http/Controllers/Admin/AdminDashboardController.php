<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Laporan;
use App\Models\Tersangka;
use App\Models\Korban;
use App\Models\IdentitasTersangka;
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

        // =============================================
        // PELAPOR & TERSANGKA SUMMARY (Global - Admin View)
        // =============================================
        $pelaporTersangkaSummary = $this->getPelaporTersangkaSummary();

        return Inertia::render('Admin/Dashboard', [
            'userStats' => $userStats,
            'laporanStats' => $laporanStats,
            'laporanBaru' => $laporanBaru,
            'aktivitasTerakhir' => $aktivitasTerakhir,
            'pelaporTersangkaSummary' => $pelaporTersangkaSummary,
        ]);
    }

    /**
     * Get pelapor and tersangka summary for admin dashboard
     */
    private function getPelaporTersangkaSummary(): array
    {
        // Total unique pelapor
        $totalPelapor = Laporan::distinct('pelapor_id')->count('pelapor_id');

        // Total korban
        $totalKorban = Korban::count();

        // Total kerugian
        $totalKerugian = Korban::sum('kerugian_nominal');

        // Tersangka stats
        $totalTersangka = Tersangka::count();
        $unidentifiedTersangka = Tersangka::whereNull('orang_id')->count();

        // Linked tersangka calculation
        $linkedIdentities = IdentitasTersangka::select('jenis', 'nilai', DB::raw('COUNT(*) as count'))
            ->groupBy('jenis', 'nilai')
            ->having('count', '>', 1)
            ->get();

        $linkedTersangkaIds = collect();
        foreach ($linkedIdentities as $dup) {
            $ids = IdentitasTersangka::where('jenis', $dup->jenis)
                ->where('nilai', $dup->nilai)
                ->pluck('tersangka_id');
            $linkedTersangkaIds = $linkedTersangkaIds->merge($ids);
        }
        $linkedCount = $linkedTersangkaIds->unique()->count();

        // Tersangka per subdit
        $tersangkaBySubdit = Tersangka::query()
            ->join('laporan', 'tersangka.laporan_id', '=', 'laporan.id')
            ->select('laporan.assigned_subdit', DB::raw('COUNT(tersangka.id) as total'))
            ->whereNotNull('laporan.assigned_subdit')
            ->groupBy('laporan.assigned_subdit')
            ->orderBy('laporan.assigned_subdit')
            ->get()
            ->pluck('total', 'assigned_subdit')
            ->toArray();

        // Top 5 identity types
        $topIdentityTypes = IdentitasTersangka::select('jenis', DB::raw('COUNT(*) as total'))
            ->groupBy('jenis')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn($i) => [
                'jenis' => $i->jenis,
                'total' => $i->total,
            ]);

        return [
            'total_pelapor' => $totalPelapor,
            'total_korban' => $totalKorban,
            'total_kerugian' => $totalKerugian,
            'total_tersangka' => $totalTersangka,
            'unidentified_tersangka' => $unidentifiedTersangka,
            'linked_tersangka' => $linkedCount,
            'linked_groups' => $linkedIdentities->count(),
            'tersangka_by_subdit' => [
                'subdit_1' => $tersangkaBySubdit[1] ?? 0,
                'subdit_2' => $tersangkaBySubdit[2] ?? 0,
                'subdit_3' => $tersangkaBySubdit[3] ?? 0,
            ],
            'top_identity_types' => $topIdentityTypes->toArray(),
        ];
    }
}
