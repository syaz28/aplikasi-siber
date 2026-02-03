<?php

namespace App\Http\Controllers\AdminSubdit;

use App\Http\Controllers\Controller;
use App\Models\Laporan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        ]);
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
