<?php

namespace App\Http\Controllers\Pimpinan;

use App\Http\Controllers\Controller;
use App\Models\Korban;
use App\Models\Laporan;
use App\Models\KategoriKejahatan;
use App\Models\MasterPendidikan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * PimpinanDashboardController
 * 
 * Executive Dashboard: Profiling & Demographics
 * - KPI Stats (Total Laporan, Total Kerugian, Penyelesaian %)
 * - Crime Category Chart (Horizontal Bar - ALL categories)
 * - Demographics (Gender, Age Groups, Education)
 * - Advanced Filtering (Date Range, Multi-select filters)
 */
class PimpinanDashboardController extends Controller
{
    /**
     * Age group definitions (Police Standard Categories)
     * Calculated dynamically from tanggal_lahir vs tanggal_laporan
     */
    private const AGE_GROUPS = [
        '< 17' => ['min' => 0, 'max' => 16, 'label' => '< 17 Th (Anak-anak)'],
        '17-25' => ['min' => 17, 'max' => 25, 'label' => '17-25 Th (Remaja)'],
        '26-45' => ['min' => 26, 'max' => 45, 'label' => '26-45 Th (Dewasa)'],
        '46-60' => ['min' => 46, 'max' => 60, 'label' => '46-60 Th (Orang Tua)'],
        '> 60' => ['min' => 61, 'max' => 200, 'label' => '> 60 Th (Lansia)'],
    ];

    /**
     * Executive Dashboard - Main View
     */
    public function index(Request $request): InertiaResponse
    {
        // =============================================
        // PARSE FILTERS FROM REQUEST
        // =============================================
        $dateRange = $request->input('date_range', []);
        $filters = $request->input('filters', []);
        $kategoriFokus = $request->input('kategori_fokus');

        $startDate = !empty($dateRange['start']) ? Carbon::parse($dateRange['start'])->startOfDay() : null;
        $endDate = !empty($dateRange['end']) ? Carbon::parse($dateRange['end'])->endOfDay() : null;

        $filterPendidikan = $filters['pendidikan'] ?? [];
        $filterGender = $filters['gender'] ?? [];
        $filterAgeGroup = $filters['age_group'] ?? [];

        // =============================================
        // BUILD BASE QUERY WITH FILTERS
        // =============================================
        $baseQuery = Laporan::query()
            ->join('orang', 'laporan.pelapor_id', '=', 'orang.id');

        // Apply date range filter
        if ($startDate && $endDate) {
            $baseQuery->whereBetween('laporan.tanggal_laporan', [$startDate, $endDate]);
        }

        // Apply pendidikan filter
        if (!empty($filterPendidikan)) {
            $baseQuery->whereIn('orang.pendidikan', $filterPendidikan);
        }

        // Apply gender filter
        if (!empty($filterGender)) {
            $baseQuery->whereIn('orang.jenis_kelamin', $filterGender);
        }

        // Apply age group filter using TIMESTAMPDIFF (based on tanggal_laporan)
        if (!empty($filterAgeGroup)) {
            $baseQuery->where(function ($query) use ($filterAgeGroup) {
                foreach ($filterAgeGroup as $group) {
                    if (isset(self::AGE_GROUPS[$group])) {
                        $min = self::AGE_GROUPS[$group]['min'];
                        $max = self::AGE_GROUPS[$group]['max'];
                        $query->orWhereRaw(
                            'TIMESTAMPDIFF(YEAR, orang.tanggal_lahir, laporan.tanggal_laporan) BETWEEN ? AND ?',
                            [$min, $max]
                        );
                    }
                }
            });
        }

        // Apply kategori fokus filter
        if (!empty($kategoriFokus)) {
            $baseQuery->where('laporan.kategori_kejahatan_id', $kategoriFokus);
        }

        // =============================================
        // A. KPI STATS
        // =============================================
        $statsQuery = clone $baseQuery;
        $totalLaporan = $statsQuery->count('laporan.id');

        // Total Kerugian - need to join korban
        $kerugianQuery = Laporan::query()
            ->join('orang', 'laporan.pelapor_id', '=', 'orang.id')
            ->join('korban', 'korban.laporan_id', '=', 'laporan.id');

        // Reapply filters for kerugian query
        if ($startDate && $endDate) {
            $kerugianQuery->whereBetween('laporan.tanggal_laporan', [$startDate, $endDate]);
        }
        if (!empty($filterPendidikan)) {
            $kerugianQuery->whereIn('orang.pendidikan', $filterPendidikan);
        }
        if (!empty($filterGender)) {
            $kerugianQuery->whereIn('orang.jenis_kelamin', $filterGender);
        }
        if (!empty($filterAgeGroup)) {
            $kerugianQuery->where(function ($query) use ($filterAgeGroup) {
                foreach ($filterAgeGroup as $group) {
                    if (isset(self::AGE_GROUPS[$group])) {
                        $min = self::AGE_GROUPS[$group]['min'];
                        $max = self::AGE_GROUPS[$group]['max'];
                        $query->orWhereRaw(
                            'TIMESTAMPDIFF(YEAR, orang.tanggal_lahir, laporan.tanggal_laporan) BETWEEN ? AND ?',
                            [$min, $max]
                        );
                    }
                }
            });
        }
        if (!empty($kategoriFokus)) {
            $kerugianQuery->where('laporan.kategori_kejahatan_id', $kategoriFokus);
        }

        $totalKerugian = $kerugianQuery->sum('korban.kerugian_nominal');

        // Total Selesai (SP3, RJ, Diversi)
        $selesaiQuery = clone $baseQuery;
        $totalSelesai = $selesaiQuery->whereIn('laporan.status', ['SP3', 'RJ', 'Diversi'])->count('laporan.id');

        // Persentase penyelesaian
        $persentaseSelesai = $totalLaporan > 0 ? round(($totalSelesai / $totalLaporan) * 100, 1) : 0;

        $stats = [
            'total_laporan' => $totalLaporan,
            'total_kerugian' => $totalKerugian,
            'total_selesai' => $totalSelesai,
            'persentase_selesai' => $persentaseSelesai,
        ];

        // =============================================
        // B. CHART: GENDER DISTRIBUTION
        // =============================================
        $genderQuery = Laporan::query()
            ->join('orang', 'laporan.pelapor_id', '=', 'orang.id')
            ->select('orang.jenis_kelamin', DB::raw('COUNT(laporan.id) as total'))
            ->groupBy('orang.jenis_kelamin');

        // Apply all filters
        $this->applyFiltersToQuery($genderQuery, $startDate, $endDate, $filterPendidikan, $filterGender, $filterAgeGroup, $kategoriFokus);

        $genderData = $genderQuery->get();

        $chartGender = [
            'labels' => $genderData->pluck('jenis_kelamin')->map(fn($g) => $g === 'Laki-laki' ? 'Laki-laki' : 'Perempuan')->toArray(),
            'data' => $genderData->pluck('total')->toArray(),
        ];

        // =============================================
        // C. CHART: EDUCATION DISTRIBUTION
        // =============================================
        $pendidikanQuery = Laporan::query()
            ->join('orang', 'laporan.pelapor_id', '=', 'orang.id')
            ->select('orang.pendidikan', DB::raw('COUNT(laporan.id) as total'))
            ->whereNotNull('orang.pendidikan')
            ->groupBy('orang.pendidikan')
            ->orderByDesc('total');

        $this->applyFiltersToQuery($pendidikanQuery, $startDate, $endDate, $filterPendidikan, $filterGender, $filterAgeGroup, $kategoriFokus);

        $pendidikanData = $pendidikanQuery->get();

        $chartPendidikan = [
            'labels' => $pendidikanData->pluck('pendidikan')->toArray(),
            'data' => $pendidikanData->pluck('total')->toArray(),
        ];

        // =============================================
        // D. CHART: AGE GROUP DISTRIBUTION (based on tanggal_laporan)
        // =============================================
        $usiaData = [];
        foreach (self::AGE_GROUPS as $key => $range) {
            $usiaQuery = Laporan::query()
                ->join('orang', 'laporan.pelapor_id', '=', 'orang.id')
                ->whereRaw(
                    'TIMESTAMPDIFF(YEAR, orang.tanggal_lahir, laporan.tanggal_laporan) BETWEEN ? AND ?',
                    [$range['min'], $range['max']]
                );

            $this->applyFiltersToQuery($usiaQuery, $startDate, $endDate, $filterPendidikan, $filterGender, $filterAgeGroup, $kategoriFokus);

            $usiaData[] = [
                'group' => $key,
                'label' => $range['label'],
                'total' => $usiaQuery->count('laporan.id'),
            ];
        }

        $chartUsia = [
            'labels' => collect($usiaData)->pluck('label')->toArray(),
            'data' => collect($usiaData)->pluck('total')->toArray(),
        ];

        // =============================================
        // E. CHART: KATEGORI KEJAHATAN (ALL - Horizontal Bar)
        // =============================================
        $kategoriQuery = Laporan::query()
            ->join('orang', 'laporan.pelapor_id', '=', 'orang.id')
            ->join('kategori_kejahatan', 'laporan.kategori_kejahatan_id', '=', 'kategori_kejahatan.id')
            ->select('kategori_kejahatan.nama', DB::raw('COUNT(laporan.id) as total'))
            ->groupBy('kategori_kejahatan.id', 'kategori_kejahatan.nama')
            ->orderByDesc('total');

        $this->applyFiltersToQuery($kategoriQuery, $startDate, $endDate, $filterPendidikan, $filterGender, $filterAgeGroup, $kategoriFokus);

        $kategoriData = $kategoriQuery->get();

        $chartKategori = [
            'labels' => $kategoriData->pluck('nama')->toArray(),
            'data' => $kategoriData->pluck('total')->toArray(),
        ];

        // =============================================
        // F. FILTER OPTIONS (for dropdown/checkboxes)
        // =============================================
        $filterOptions = [
            'pendidikan' => MasterPendidikan::orderBy('nama')->pluck('nama')->toArray(),
            'gender' => ['Laki-laki', 'Perempuan'],
            'age_group' => array_keys(self::AGE_GROUPS),
            'kategori' => KategoriKejahatan::active()->orderBy('nama')->get(['id', 'nama'])->toArray(),
        ];

        // =============================================
        // RETURN INERTIA RESPONSE
        // =============================================
        return Inertia::render('Pimpinan/Dashboard', [
            'stats' => $stats,
            'chartGender' => $chartGender,
            'chartPendidikan' => $chartPendidikan,
            'chartUsia' => $chartUsia,
            'chartKategori' => $chartKategori,
            'filterOptions' => $filterOptions,
            'appliedFilters' => [
                'date_range' => $dateRange,
                'filters' => $filters,
                'kategori_fokus' => $kategoriFokus,
            ],
        ]);
    }

    /**
     * Apply common filters to a query
     */
    private function applyFiltersToQuery($query, $startDate, $endDate, $filterPendidikan, $filterGender, $filterAgeGroup, $kategoriFokus): void
    {
        if ($startDate && $endDate) {
            $query->whereBetween('laporan.tanggal_laporan', [$startDate, $endDate]);
        }

        if (!empty($filterPendidikan)) {
            $query->whereIn('orang.pendidikan', $filterPendidikan);
        }

        if (!empty($filterGender)) {
            $query->whereIn('orang.jenis_kelamin', $filterGender);
        }

        if (!empty($filterAgeGroup)) {
            $query->where(function ($q) use ($filterAgeGroup) {
                foreach ($filterAgeGroup as $group) {
                    if (isset(self::AGE_GROUPS[$group])) {
                        $min = self::AGE_GROUPS[$group]['min'];
                        $max = self::AGE_GROUPS[$group]['max'];
                        $q->orWhereRaw(
                            'TIMESTAMPDIFF(YEAR, orang.tanggal_lahir, laporan.tanggal_laporan) BETWEEN ? AND ?',
                            [$min, $max]
                        );
                    }
                }
            });
        }

        if (!empty($kategoriFokus)) {
            $query->where('laporan.kategori_kejahatan_id', $kategoriFokus);
        }
    }
}
