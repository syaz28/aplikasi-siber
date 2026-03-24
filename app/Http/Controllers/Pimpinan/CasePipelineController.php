<?php

namespace App\Http\Controllers\Pimpinan;

use App\Http\Controllers\Controller;
use App\Models\KategoriKejahatan;
use App\Models\Laporan;
use App\Models\Wilayah;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * CasePipelineController — Menu 3: Case Pipeline
 *
 * Tactical dashboard for tracking case flow across 7 statuses:
 *   Penyelidikan → Penyidikan → Tahap I → Tahap II → SP3 / RJ / Diversi
 *
 * Datasets:
 *   1. KEY_STATS      — KPIs (total, aktif, selesai, clearance rate)
 *   2. STATUS_RING    — Doughnut chart data (count per status, all 7 guaranteed)
 *   3. MONTHLY_TREND  — 12-month × 7 statuses multi-wave line chart
 *   4. SUBDIT_MATRIX  — Stacked horizontal bar (Subdit 1-3 × 7 statuses)
 *
 * Cross-filter bag: tahun, bulan, kategori_id, wilayah_kode, status, subdit
 */
class CasePipelineController extends Controller
{
    /**
     * The 7 case statuses in pipeline order.
     */
    private const STATUSES = [
        'Penyelidikan',
        'Penyidikan',
        'Tahap I',
        'Tahap II',
        'SP3',
        'RJ',
        'Diversi',
    ];

    /**
     * Active statuses (cases still in progress).
     */
    private const ACTIVE_STATUSES = ['Penyelidikan', 'Penyidikan', 'Tahap I', 'Tahap II'];

    /**
     * Closed/completed statuses.
     */
    private const CLOSED_STATUSES = ['SP3', 'RJ', 'Diversi'];

    // =========================================================================
    // MAIN INDEX
    // =========================================================================

    public function index(Request $request): InertiaResponse
    {
        // ── Collect filter bag ─────────────────────────────────────
        $filters = [
            'tahun'        => $request->input('tahun') ? (int) $request->input('tahun') : null,
            'bulan'        => $request->input('bulan') ? (int) $request->input('bulan') : null,
            'kategori_id'  => $request->input('kategori_id') ? (int) $request->input('kategori_id') : null,
            'wilayah_kode' => $request->input('wilayah_kode') ?: null,
            'status'       => $request->input('status') ?: null,
            'subdit'       => $request->input('subdit') ? (int) $request->input('subdit') : null,
        ];

        return Inertia::render('Pimpinan/CasePipeline', [
            'keyStats'       => $this->getKeyStats($filters),
            'statusRing'     => $this->getStatusRing($filters),
            'monthlyTrend'   => $this->getMonthlyTrend($filters),
            'subditMatrix'   => $this->getSubditMatrix($filters),
            'filterOptions'  => $this->getFilterOptions(),
            'appliedFilters' => $filters,
        ]);
    }

    // =========================================================================
    // GLOBAL FILTER HELPER
    // =========================================================================

    /**
     * Apply shared cross-filters to a laporan query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @param  array   $filters   The shared filter bag
     * @param  string  $exclude   Filter key to skip (self-exclusion for grouping axis)
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    private function applyGlobalFilters($query, array $filters, ?string $exclude = null)
    {
        if ($exclude !== 'tahun' && !empty($filters['tahun'])) {
            $query->whereRaw('YEAR(laporan.tanggal_laporan) = ?', [$filters['tahun']]);
        }

        if ($exclude !== 'bulan' && !empty($filters['bulan'])) {
            $query->whereRaw('MONTH(laporan.tanggal_laporan) = ?', [$filters['bulan']]);
        }

        if ($exclude !== 'kategori_id' && !empty($filters['kategori_id'])) {
            $query->where('laporan.kategori_kejahatan_id', $filters['kategori_id']);
        }

        if ($exclude !== 'wilayah_kode' && !empty($filters['wilayah_kode'])) {
            $query->where('laporan.kode_kabupaten_kejadian', $filters['wilayah_kode']);
        }

        if ($exclude !== 'status' && !empty($filters['status'])) {
            $query->where('laporan.status', $filters['status']);
        }

        if ($exclude !== 'subdit' && !empty($filters['subdit'])) {
            $query->where('laporan.assigned_subdit', $filters['subdit']);
        }

        return $query;
    }

    // =========================================================================
    // DATASET 1: KEY STATS
    // =========================================================================

    /**
     * Total Kasus, Kasus Aktif, Kasus Selesai, Clearance Rate.
     */
    private function getKeyStats(array $filters): array
    {
        $query = Laporan::query()->from('laporan');
        $this->applyGlobalFilters($query, $filters);

        $total = (clone $query)->count();

        $aktif = (clone $query)
            ->whereIn('laporan.status', self::ACTIVE_STATUSES)
            ->count();

        $selesai = (clone $query)
            ->whereIn('laporan.status', self::CLOSED_STATUSES)
            ->count();

        $clearanceRate = $total > 0
            ? round(($selesai / $total) * 100, 1)
            : 0;

        return [
            'total_kasus'    => $total,
            'kasus_aktif'    => $aktif,
            'kasus_selesai'  => $selesai,
            'clearance_rate' => $clearanceRate,
        ];
    }

    // =========================================================================
    // DATASET 2: STATUS RING (Doughnut Chart)
    // =========================================================================

    /**
     * Count per status. All 7 statuses guaranteed even if count = 0.
     * Returns: [{ status: 'Penyelidikan', total: 42, active: true|false }, ...]
     */
    private function getStatusRing(array $filters): array
    {
        $query = Laporan::query()->from('laporan');
        $this->applyGlobalFilters($query, $filters, 'status');

        $counts = $query
            ->select('laporan.status', DB::raw('COUNT(*) as total'))
            ->whereIn('laporan.status', self::STATUSES)
            ->groupBy('laporan.status')
            ->pluck('total', 'status')
            ->toArray();

        $activeStatus = $filters['status'] ?? null;

        return collect(self::STATUSES)->map(function ($status) use ($counts, $activeStatus) {
            return [
                'status' => $status,
                'total'  => $counts[$status] ?? 0,
                'active' => $activeStatus === $status,
            ];
        })->values()->toArray();
    }

    // =========================================================================
    // DATASET 3: MONTHLY TREND (Multi-Wave Line Chart)
    // =========================================================================

    /**
     * 12-month × 7 statuses grid.
     * Returns: [
     *   { month: 1, month_name: 'Januari', Penyelidikan: 10, Penyidikan: 5, ... },
     *   ...
     * ]
     */
    private function getMonthlyTrend(array $filters): array
    {
        $query = Laporan::query()->from('laporan');
        $this->applyGlobalFilters($query, $filters, 'bulan');

        $rows = $query
            ->select(
                DB::raw('MONTH(laporan.tanggal_laporan) as bulan'),
                'laporan.status',
                DB::raw('COUNT(*) as total')
            )
            ->whereIn('laporan.status', self::STATUSES)
            ->whereNotNull('laporan.tanggal_laporan')
            ->groupBy(DB::raw('MONTH(laporan.tanggal_laporan)'), 'laporan.status')
            ->get();

        // Build lookup: month → status → count
        $lookup = [];
        foreach ($rows as $row) {
            $lookup[$row->bulan][$row->status] = $row->total;
        }

        // Build 12-month array with all 7 statuses guaranteed
        $result = [];
        for ($m = 1; $m <= 12; $m++) {
            $entry = [
                'month'      => $m,
                'month_name' => Carbon::create(null, $m, 1)->translatedFormat('F'),
            ];
            foreach (self::STATUSES as $status) {
                $entry[$status] = $lookup[$m][$status] ?? 0;
            }
            $result[] = $entry;
        }

        return $result;
    }

    // =========================================================================
    // DATASET 4: SUBDIT MATRIX (Stacked Horizontal Bar)
    // =========================================================================

    /**
     * Subdit 1-3 × 7 statuses.
     * Returns: [
     *   { subdit: 1, label: 'Subdit 1', Penyelidikan: 10, Penyidikan: 5, ..., total: 42 },
     *   ...
     * ]
     */
    private function getSubditMatrix(array $filters): array
    {
        $query = Laporan::query()->from('laporan');
        $this->applyGlobalFilters($query, $filters, 'subdit');

        $rows = $query
            ->select(
                'laporan.assigned_subdit',
                'laporan.status',
                DB::raw('COUNT(*) as total')
            )
            ->whereIn('laporan.assigned_subdit', [1, 2, 3])
            ->whereIn('laporan.status', self::STATUSES)
            ->groupBy('laporan.assigned_subdit', 'laporan.status')
            ->get();

        // Build lookup: subdit → status → count
        $lookup = [];
        foreach ($rows as $row) {
            $lookup[$row->assigned_subdit][$row->status] = $row->total;
        }

        // Build 3-subdit array with all 7 statuses guaranteed
        $result = [];
        for ($s = 1; $s <= 3; $s++) {
            $entry = [
                'subdit' => $s,
                'label'  => 'Subdit ' . $s,
            ];
            $entryTotal = 0;
            foreach (self::STATUSES as $status) {
                $count = $lookup[$s][$status] ?? 0;
                $entry[$status] = $count;
                $entryTotal += $count;
            }
            $entry['total'] = $entryTotal;
            $result[] = $entry;
        }

        return $result;
    }

    // =========================================================================
    // FILTER OPTIONS
    // =========================================================================

    /**
     * Dropdown data for the filter panel.
     */
    private function getFilterOptions(): array
    {
        return [
            'kategori' => KategoriKejahatan::active()
                ->orderBy('nama')
                ->get(['id', 'nama'])
                ->toArray(),

            'bulan' => collect(range(1, 12))->map(fn ($m) => [
                'value' => $m,
                'label' => Carbon::create(null, $m, 1)->translatedFormat('F'),
            ])->toArray(),

            'tahun' => DB::table('laporan')
                ->selectRaw('YEAR(tanggal_laporan) as year')
                ->whereNotNull('tanggal_laporan')
                ->distinct()
                ->orderByDesc('year')
                ->pluck('year')
                ->toArray(),

            'wilayah' => Wilayah::where('kode', 'LIKE', '33.__')
                ->orderBy('nama')
                ->get(['kode', 'nama'])
                ->toArray(),

            'status' => self::STATUSES,

            'subdit' => [
                ['value' => 1, 'label' => 'Subdit 1'],
                ['value' => 2, 'label' => 'Subdit 2'],
                ['value' => 3, 'label' => 'Subdit 3'],
            ],
        ];
    }
}
