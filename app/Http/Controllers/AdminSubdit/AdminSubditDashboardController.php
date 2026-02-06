<?php

namespace App\Http\Controllers\AdminSubdit;

use App\Http\Controllers\Controller;
use App\Models\Laporan;
use App\Models\Tersangka;
use App\Models\Korban;
use App\Models\IdentitasTersangka;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminSubditDashboardController extends Controller
{
    /**
     * Display the admin subdit dashboard.
     * Focus on Unit Management (distributing cases to Unit 1-5).
     */
    public function index()
    {
        $user = Auth::user();
        $subdit = $user->subdit;

        // === STATS CARDS ===
        
        // Pending Disposisi: Cases assigned to this subdit but not yet assigned to a unit
        $pendingDisposisi = Laporan::where('assigned_subdit', $subdit)
            ->whereNull('disposisi_unit')
            ->count();

        // Total Proses: Active cases (not finished)
        $totalProses = Laporan::where('assigned_subdit', $subdit)
            ->whereNotIn('status', ['SP3', 'RJ', 'Tahap II'])
            ->count();

        // Selesai Bulan Ini: Cases finished this month
        $selesaiBulanIni = Laporan::where('assigned_subdit', $subdit)
            ->whereIn('status', ['SP3', 'RJ', 'Tahap II'])
            ->whereMonth('updated_at', Carbon::now()->month)
            ->whereYear('updated_at', Carbon::now()->year)
            ->count();

        // === TABLE - PENDING DISPOSISI (Action Needed) ===
        $pendingLaporans = Laporan::with(['pelapor', 'kategoriKejahatan'])
            ->where('assigned_subdit', $subdit)
            ->whereNull('disposisi_unit')
            ->orderBy('tanggal_laporan', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($laporan) {
                return [
                    'id' => $laporan->id,
                    'nomor_stpa' => $laporan->nomor_stpa,
                    'pelapor_nama' => $laporan->pelapor?->nama ?? '-',
                    'tanggal_laporan' => $laporan->tanggal_laporan,
                    'kategori' => $laporan->kategoriKejahatan?->nama ?? '-',
                ];
            });

        // === CHART - UNIT WORKLOAD ===
        $unitWorkload = Laporan::where('assigned_subdit', $subdit)
            ->whereNotNull('disposisi_unit')
            ->whereNotIn('status', ['SP3', 'RJ', 'Tahap II']) // Only active cases
            ->selectRaw('disposisi_unit, COUNT(*) as total')
            ->groupBy('disposisi_unit')
            ->orderBy('disposisi_unit')
            ->pluck('total', 'disposisi_unit')
            ->toArray();

        // Format for ApexCharts - ensure all 5 units are represented
        $unitLabels = ['Unit 1', 'Unit 2', 'Unit 3', 'Unit 4', 'Unit 5'];
        $unitData = [];
        for ($i = 1; $i <= 5; $i++) {
            $unitData[] = $unitWorkload[$i] ?? 0;
        }

        // === UNIT OPTIONS for dropdown ===
        $unitOptions = [
            ['value' => 1, 'label' => 'Unit 1'],
            ['value' => 2, 'label' => 'Unit 2'],
            ['value' => 3, 'label' => 'Unit 3'],
            ['value' => 4, 'label' => 'Unit 4'],
            ['value' => 5, 'label' => 'Unit 5'],
        ];

        // === PELAPOR & TERSANGKA SUMMARY (Subdit Scope) ===
        $pelaporTersangkaStats = $this->getPelaporTersangkaStats($subdit);

        return Inertia::render('AdminSubdit/Dashboard', [
            'stats' => [
                'pending_disposisi' => $pendingDisposisi,
                'total_proses' => $totalProses,
                'selesai_bulan_ini' => $selesaiBulanIni,
            ],
            'pendingLaporans' => $pendingLaporans,
            'unitChart' => [
                'labels' => $unitLabels,
                'data' => $unitData,
            ],
            'unitOptions' => $unitOptions,
            'subdit' => $subdit,
            'pelaporTersangkaStats' => $pelaporTersangkaStats,
        ]);
    }

    /**
     * Get pelapor and tersangka statistics for the subdit
     */
    private function getPelaporTersangkaStats($subdit): array
    {
        // Get laporan IDs for this subdit
        $laporanIds = Laporan::where('assigned_subdit', $subdit)->pluck('id');

        // Total pelapor unik (by pelapor_id)
        $totalPelapor = Laporan::where('assigned_subdit', $subdit)
            ->whereNotNull('pelapor_id')
            ->distinct('pelapor_id')
            ->count('pelapor_id');

        // Total korban
        $totalKorban = Korban::whereIn('laporan_id', $laporanIds)->count();

        // Total kerugian
        $totalKerugian = Korban::whereIn('laporan_id', $laporanIds)->sum('kerugian_nominal');

        // Tersangka stats
        $totalTersangka = Tersangka::whereIn('laporan_id', $laporanIds)->count();
        $unidentifiedTersangka = Tersangka::whereIn('laporan_id', $laporanIds)
            ->whereNull('orang_id')
            ->count();

        // Linked tersangka in this subdit's cases
        $tersangkaIdsInSubdit = Tersangka::whereIn('laporan_id', $laporanIds)->pluck('id');
        
        $linkedIdentities = IdentitasTersangka::select('jenis', 'nilai', DB::raw('COUNT(*) as count'))
            ->whereIn('tersangka_id', $tersangkaIdsInSubdit)
            ->groupBy('jenis', 'nilai')
            ->having('count', '>', 1)
            ->get();

        $linkedTersangkaIds = collect();
        foreach ($linkedIdentities as $dup) {
            $ids = IdentitasTersangka::where('jenis', $dup->jenis)
                ->where('nilai', $dup->nilai)
                ->whereIn('tersangka_id', $tersangkaIdsInSubdit)
                ->pluck('tersangka_id');
            $linkedTersangkaIds = $linkedTersangkaIds->merge($ids);
        }
        $linkedCount = $linkedTersangkaIds->unique()->count();

        // Top 3 identity types
        $identityTypes = IdentitasTersangka::select('jenis', DB::raw('COUNT(*) as count'))
            ->whereIn('tersangka_id', $tersangkaIdsInSubdit)
            ->groupBy('jenis')
            ->orderByDesc('count')
            ->limit(3)
            ->get()
            ->map(fn($i) => [
                'jenis' => $i->jenis,
                'count' => $i->count,
            ]);

        return [
            'total_pelapor' => $totalPelapor,
            'total_korban' => $totalKorban,
            'total_kerugian' => $totalKerugian,
            'total_tersangka' => $totalTersangka,
            'unidentified_tersangka' => $unidentifiedTersangka,
            'linked_tersangka' => $linkedCount,
            'linked_groups' => $linkedIdentities->count(),
            'identity_types' => $identityTypes->toArray(),
        ];
    }

    /**
     * Assign a laporan to a specific unit.
     */
    public function assignUnit(Request $request, $id)
    {
        $request->validate([
            'unit' => 'required|integer|min:1|max:5',
        ]);

        $user = Auth::user();
        $laporan = Laporan::where('assigned_subdit', $user->subdit)
            ->findOrFail($id);

        $laporan->update([
            'disposisi_unit' => $request->unit,
        ]);

        return back()->with('success', 'Laporan berhasil didisposisi ke Unit ' . $request->unit);
    }
}
