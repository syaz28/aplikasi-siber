<?php

namespace App\Http\Controllers\Pimpinan;

use App\Http\Controllers\Controller;
use App\Models\IdentitasTersangka;
use App\Models\KategoriKejahatan;
use App\Models\Korban;
use App\Models\Laporan;
use App\Models\MasterPekerjaan;
use App\Models\MasterPendidikan;
use App\Models\Tersangka;
use App\Models\Wilayah;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * PimpinanDashboardController — Tableau-style Cross-Filtering
 *
 * All datasets are filtered by a shared $filters bag:
 *   kategori_id, bulan, gender, pendidikan, usia_group, tahun
 *
 * Datasets:
 *   1. KEY_STATS          — KPIs (total laporan, kerugian, clearance, suspect ID)
 *   2. MONTHLY_COMBO      — 12-month bar+line (highlighted month when bulan filter)
 *   3. CATEGORIES_BAR     — All categories with total_reports & total_losses
 *   4. VICTIM_PROFILING   — Gender / Education / Occupation (stacked cross-filters)
 *   5. PLATFORM_SUNBURST  — IdentitasTersangka jenis → platform
 *   6. RECENT_REPORTS     — Latest 10 for Live Threat Ticker
 */
class PimpinanDashboardController extends Controller
{
    /**
     * Age-group boundaries used for the usia_group filter.
     * Labels must match what the frontend sends.
     */
    private const AGE_GROUPS = [
        '< 15 Tahun'   => [0, 14],
        '15-30 Tahun'  => [15, 30],
        '31-45 Tahun'  => [31, 45],
        '46-60 Tahun'  => [46, 60],
        '> 60 Tahun'   => [61, 200],
    ];

    /**
     * Labels to exclude from victim profiling (unknown / placeholder data).
     */
    private const EXCLUDED_LABELS = [
        'TIDAK DIKETAHUI',
        'Tidak Diketahui',
        'tidak diketahui',
    ];

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(Request $request): InertiaResponse
    {
        // ── Collect filters ────────────────────────────────────────
        $filters = [
            'kategori_id'   => $request->input('kategori_id') ? (int) $request->input('kategori_id') : null,
            'bulan'         => $request->input('bulan') ? (int) $request->input('bulan') : null,
            'gender'        => $request->input('gender') ?: null,
            'pendidikan'    => $request->input('pendidikan') ?: null,
            'usia_group'    => $request->input('usia_group') ?: null,
            'tahun'         => $request->input('tahun') ? (int) $request->input('tahun') : null,
            'wilayah_kode'  => $request->input('wilayah_kode') ?: null,
            'platform_name' => $request->input('platform_name') ?: null,
        ];

        return Inertia::render('Pimpinan/Dashboard', [
            'keyStats'          => $this->getKeyStats($filters),
            'monthlyCombo'      => $this->getMonthlyCombo($filters),
            'categoriesBar'     => $this->getCategoriesBar($filters),
            'victimProfiling'   => $this->getVictimProfiling($filters),
            'platformSunburst'  => $this->getPlatformSunburst($filters),
            'recentReports'     => $this->getRecentReports(),
            'filterOptions'     => $this->getFilterOptions(),
            'appliedFilters'    => $filters,
        ]);
    }

    // =========================================================================
    // GLOBAL FILTER HELPER
    // =========================================================================

    /**
     * Apply cross-filters to any query.
     *
     * The method inspects the query's base table and intelligently joins
     * whatever is needed:
     *   - 'laporan'  context → joins korban→orang when demographic filters present
     *   - 'korban'   context → joins laporan (for kategori/bulan) and orang (demographics)
     *   - 'tersangka'/'identitas_tersangka' context → joins laporan
     *
     * @param  Builder|\Illuminate\Database\Query\Builder  $query
     * @param  array   $filters  The shared filter bag
     * @param  string  $context  'laporan' | 'korban' | 'tersangka' | 'identitas_tersangka'
     * @return Builder|\Illuminate\Database\Query\Builder
     */
    private function applyGlobalFilters($query, array $filters, string $context)
    {
        $hasKategori    = !empty($filters['kategori_id']);
        $hasBulan       = !empty($filters['bulan']);
        $hasGender      = !empty($filters['gender']);
        $hasPendidikan  = !empty($filters['pendidikan']);
        $hasUsia        = !empty($filters['usia_group']);
        $hasTahun       = !empty($filters['tahun']);
        $hasWilayah     = !empty($filters['wilayah_kode']);
        $hasPlatform    = !empty($filters['platform_name']);
        $hasDemographic = $hasGender || $hasPendidikan || $hasUsia;

        // ── Context: laporan ───────────────────────────────────────
        if ($context === 'laporan') {
            if ($hasKategori) {
                $query->where('laporan.kategori_kejahatan_id', $filters['kategori_id']);
            }
            if ($hasBulan) {
                $query->whereRaw('MONTH(laporan.tanggal_laporan) = ?', [$filters['bulan']]);
            }
            if ($hasTahun) {
                $query->whereRaw('YEAR(laporan.tanggal_laporan) = ?', [$filters['tahun']]);
            }
            if ($hasWilayah) {
                $query->where('laporan.kode_kabupaten_kejadian', $filters['wilayah_kode']);
            }
            if ($hasPlatform) {
                $query->whereExists(function ($sub) use ($filters) {
                    $sub->select(DB::raw(1))
                        ->from('tersangka')
                        ->join('identitas_tersangka', 'identitas_tersangka.tersangka_id', '=', 'tersangka.id')
                        ->whereColumn('tersangka.laporan_id', 'laporan.id')
                        ->where('identitas_tersangka.platform', $filters['platform_name']);
                });
            }
            // Demographic filters require korban→orang
            if ($hasDemographic) {
                $query->whereExists(function ($sub) use ($filters, $hasGender, $hasPendidikan, $hasUsia) {
                    $sub->select(DB::raw(1))
                        ->from('korban')
                        ->join('orang', 'korban.orang_id', '=', 'orang.id')
                        ->whereColumn('korban.laporan_id', 'laporan.id');

                    if ($hasGender) {
                        $sub->where('orang.jenis_kelamin', $filters['gender']);
                    }
                    if ($hasPendidikan) {
                        $sub->where('orang.pendidikan', $filters['pendidikan']);
                    }
                    if ($hasUsia) {
                        // laporan is the outer table — join it into subquery for age calc
                        $sub->join('laporan as lap_age', 'korban.laporan_id', '=', 'lap_age.id');
                        $sub->whereRaw(
                            'TIMESTAMPDIFF(YEAR, orang.tanggal_lahir, lap_age.tanggal_laporan) BETWEEN ? AND ?',
                            self::AGE_GROUPS[$filters['usia_group']] ?? [0, 200]
                        );
                    }
                });
            }
        }

        // ── Context: korban ────────────────────────────────────────
        if ($context === 'korban') {
            // Always join laporan — needed for historical age calc (tanggal_laporan) and kategori/bulan filters
            $query->join('laporan', 'korban.laporan_id', '=', 'laporan.id');

            if ($hasKategori) {
                $query->where('laporan.kategori_kejahatan_id', $filters['kategori_id']);
            }
            if ($hasBulan) {
                $query->whereRaw('MONTH(laporan.tanggal_laporan) = ?', [$filters['bulan']]);
            }
            if ($hasTahun) {
                $query->whereRaw('YEAR(laporan.tanggal_laporan) = ?', [$filters['tahun']]);
            }
            if ($hasWilayah) {
                $query->where('laporan.kode_kabupaten_kejadian', $filters['wilayah_kode']);
            }
            if ($hasPlatform) {
                $query->whereExists(function ($sub) use ($filters) {
                    $sub->select(DB::raw(1))
                        ->from('tersangka')
                        ->join('identitas_tersangka', 'identitas_tersangka.tersangka_id', '=', 'tersangka.id')
                        ->whereColumn('tersangka.laporan_id', 'laporan.id')
                        ->where('identitas_tersangka.platform', $filters['platform_name']);
                });
            }
            // orang demographic filters (orang is expected to be joined by caller)
            if ($hasGender) {
                $query->where('orang.jenis_kelamin', $filters['gender']);
            }
            if ($hasPendidikan) {
                $query->where('orang.pendidikan', $filters['pendidikan']);
            }
            if ($hasUsia) {
                $this->applyAgeFilter($query, $filters['usia_group']);
            }
        }

        // ── Context: tersangka ─────────────────────────────────────
        if ($context === 'tersangka') {
            $needsLaporan = $hasKategori || $hasBulan || $hasTahun || $hasWilayah || $hasDemographic;
            if ($needsLaporan) {
                $query->join('laporan', 'tersangka.laporan_id', '=', 'laporan.id');

                if ($hasKategori) {
                    $query->where('laporan.kategori_kejahatan_id', $filters['kategori_id']);
                }
                if ($hasBulan) {
                    $query->whereRaw('MONTH(laporan.tanggal_laporan) = ?', [$filters['bulan']]);
                }
                if ($hasTahun) {
                    $query->whereRaw('YEAR(laporan.tanggal_laporan) = ?', [$filters['tahun']]);
                }
                if ($hasWilayah) {
                    $query->where('laporan.kode_kabupaten_kejadian', $filters['wilayah_kode']);
                }
            }
            if ($hasPlatform) {
                $query->whereExists(function ($sub) use ($filters) {
                    $sub->select(DB::raw(1))
                        ->from('identitas_tersangka')
                        ->whereColumn('identitas_tersangka.tersangka_id', 'tersangka.id')
                        ->where('identitas_tersangka.platform', $filters['platform_name']);
                });
            }
            if ($hasDemographic) {
                $query->whereExists(function ($sub) use ($filters, $hasGender, $hasPendidikan, $hasUsia) {
                    $sub->select(DB::raw(1))
                        ->from('korban')
                        ->join('orang', 'korban.orang_id', '=', 'orang.id')
                        ->join('laporan as lap_age', 'korban.laporan_id', '=', 'lap_age.id')
                        ->whereColumn('korban.laporan_id', 'tersangka.laporan_id');

                    if ($hasGender) $sub->where('orang.jenis_kelamin', $filters['gender']);
                    if ($hasPendidikan) $sub->where('orang.pendidikan', $filters['pendidikan']);
                    if ($hasUsia) {
                        $sub->whereRaw(
                            'TIMESTAMPDIFF(YEAR, orang.tanggal_lahir, lap_age.tanggal_laporan) BETWEEN ? AND ?',
                            self::AGE_GROUPS[$filters['usia_group']] ?? [0, 200]
                        );
                    }
                });
            }
        }

        // ── Context: identitas_tersangka ───────────────────────────
        if ($context === 'identitas_tersangka') {
            // tersangka is expected to be joined by caller
            $needsLaporan = $hasKategori || $hasBulan || $hasTahun || $hasWilayah;
            if ($needsLaporan) {
                $query->join('laporan', 'tersangka.laporan_id', '=', 'laporan.id');

                if ($hasKategori) {
                    $query->where('laporan.kategori_kejahatan_id', $filters['kategori_id']);
                }
                if ($hasBulan) {
                    $query->whereRaw('MONTH(laporan.tanggal_laporan) = ?', [$filters['bulan']]);
                }
                if ($hasTahun) {
                    $query->whereRaw('YEAR(laporan.tanggal_laporan) = ?', [$filters['tahun']]);
                }
                if ($hasWilayah) {
                    $query->where('laporan.kode_kabupaten_kejadian', $filters['wilayah_kode']);
                }
            }
            if ($hasPlatform) {
                $query->where('identitas_tersangka.platform', $filters['platform_name']);
            }
            if ($hasDemographic) {
                $query->whereExists(function ($sub) use ($filters, $hasGender, $hasPendidikan, $hasUsia) {
                    $sub->select(DB::raw(1))
                        ->from('korban')
                        ->join('orang', 'korban.orang_id', '=', 'orang.id')
                        ->join('laporan as lap_age', 'korban.laporan_id', '=', 'lap_age.id')
                        ->whereColumn('korban.laporan_id', 'tersangka.laporan_id');

                    if ($hasGender) $sub->where('orang.jenis_kelamin', $filters['gender']);
                    if ($hasPendidikan) $sub->where('orang.pendidikan', $filters['pendidikan']);
                    if ($hasUsia) {
                        $sub->whereRaw(
                            'TIMESTAMPDIFF(YEAR, orang.tanggal_lahir, lap_age.tanggal_laporan) BETWEEN ? AND ?',
                            self::AGE_GROUPS[$filters['usia_group']] ?? [0, 200]
                        );
                    }
                });
            }
        }

        return $query;
    }

    /**
     * Apply an age-group filter using TIMESTAMPDIFF on orang.tanggal_lahir.
     */
    private function applyAgeFilter($query, string $group): void
    {
        $bounds = self::AGE_GROUPS[$group] ?? null;
        if (!$bounds) {
            return;
        }

        $query->whereRaw(
            'TIMESTAMPDIFF(YEAR, orang.tanggal_lahir, laporan.tanggal_laporan) BETWEEN ? AND ?',
            [$bounds[0], $bounds[1]]
        );
    }

    // =========================================================================
    // 1. KEY_STATS
    // =========================================================================

    private function getKeyStats(array $filters): array
    {
        // --- Laporan counts ---
        $laporanQuery = Laporan::query();
        $this->applyGlobalFilters($laporanQuery, $filters, 'laporan');

        $totalLaporan = (clone $laporanQuery)->count();

        $totalSelesai = (clone $laporanQuery)
            ->whereIn('status', ['SP3', 'RJ', 'Diversi'])
            ->count();

        $clearanceRate = $totalLaporan > 0
            ? round(($totalSelesai / $totalLaporan) * 100, 1)
            : 0;

        // --- Total Kerugian (via korban→orang, fully filtered) ---
        $kerugianQuery = Korban::query()
            ->join('orang', 'korban.orang_id', '=', 'orang.id');
        $this->applyGlobalFilters($kerugianQuery, $filters, 'korban');

        $totalKerugian = (float) $kerugianQuery->sum('korban.kerugian_nominal');

        // --- Suspect Identification Rate ---
        $tersangkaQuery = Tersangka::query();
        $this->applyGlobalFilters($tersangkaQuery, $filters, 'tersangka');

        $totalTersangka  = (clone $tersangkaQuery)->count();
        $identifiedCount = (clone $tersangkaQuery)->whereNotNull('tersangka.orang_id')->count();

        $suspectIdRate = $totalTersangka > 0
            ? round(($identifiedCount / $totalTersangka) * 100, 1)
            : 0;

        return [
            'total_laporan'    => $totalLaporan,
            'total_kerugian'   => $totalKerugian,
            'total_selesai'    => $totalSelesai,
            'clearance_rate'   => $clearanceRate,
            'total_tersangka'  => $totalTersangka,
            'identified_count' => $identifiedCount,
            'suspect_id_rate'  => $suspectIdRate,
        ];
    }

    // =========================================================================
    // 2. MONTHLY_COMBO
    // =========================================================================

    /**
     * Full 12-month trend (Jan–Dec).
     * All filters EXCEPT bulan are applied globally.
     * The active bulan is returned separately so the frontend can highlight it.
     */
    private function getMonthlyCombo(array $filters): array
    {
        // Remove bulan from the filter set for this query — we always show all 12 months
        $monthFilters = $filters;
        $activeBulan  = $monthFilters['bulan'] ?? null;
        $monthFilters['bulan'] = null;

        $year = $filters['tahun'] ?? null;

        $raw = DB::table('laporan')
            ->selectRaw('MONTH(laporan.tanggal_laporan) as bulan')
            ->selectRaw('COUNT(DISTINCT laporan.id) as report_count')
            ->selectRaw('COALESCE(SUM(korban.kerugian_nominal), 0) as total_loss')
            ->leftJoin('korban', 'korban.laporan_id', '=', 'laporan.id');

        // Filter by year only when tahun is explicitly selected
        if ($year) {
            $raw->whereYear('laporan.tanggal_laporan', $year);
        }

        // Remove tahun from monthly filters — already handled above
        $monthFilters['tahun'] = null;

        // Apply all filters except bulan (and tahun) to the raw query
        $this->applyMonthlyFilters($raw, $monthFilters);

        $raw = $raw->groupByRaw('MONTH(laporan.tanggal_laporan)')
            ->get()
            ->keyBy('bulan');

        $displayYear = $year ?? Carbon::now()->year;
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $row = $raw->get($m);
            $months[] = [
                'month'        => Carbon::create($displayYear, $m, 1)->translatedFormat('M'),
                'bulan'        => $m,
                'report_count' => $row ? (int) $row->report_count : 0,
                'total_loss'   => $row ? (float) $row->total_loss : 0,
                'highlighted'  => $activeBulan === $m,
            ];
        }

        return $months;
    }

    /**
     * Apply non-bulan filters to the raw monthly query (DB::table context).
     * This is a specialized version because the monthly query uses DB::table
     * rather than Eloquent Builder.
     */
    private function applyMonthlyFilters($query, array $filters): void
    {
        $hasKategori   = !empty($filters['kategori_id']);
        $hasGender     = !empty($filters['gender']);
        $hasPendidikan = !empty($filters['pendidikan']);
        $hasUsia       = !empty($filters['usia_group']);

        if ($hasKategori) {
            $query->where('laporan.kategori_kejahatan_id', $filters['kategori_id']);
        }

        // Demographic filters require korban→orang existence
        if ($hasGender || $hasPendidikan || $hasUsia) {
            $query->whereExists(function ($sub) use ($filters, $hasGender, $hasPendidikan, $hasUsia) {
                $sub->select(DB::raw(1))
                    ->from('korban')
                    ->join('orang', 'korban.orang_id', '=', 'orang.id')
                    ->join('laporan as lap_age', 'korban.laporan_id', '=', 'lap_age.id')
                    ->whereColumn('korban.laporan_id', 'laporan.id');

                if ($hasGender) {
                    $sub->where('orang.jenis_kelamin', $filters['gender']);
                }
                if ($hasPendidikan) {
                    $sub->where('orang.pendidikan', $filters['pendidikan']);
                }
                if ($hasUsia) {
                    $sub->whereRaw(
                        'TIMESTAMPDIFF(YEAR, orang.tanggal_lahir, lap_age.tanggal_laporan) BETWEEN ? AND ?',
                        self::AGE_GROUPS[$filters['usia_group']] ?? [0, 200]
                    );
                }
            });
        }
    }

    // =========================================================================
    // 3. CATEGORIES_BAR (was Treemap — now filtered globally)
    // =========================================================================

    /**
     * All categories with total_reports & total_losses.
     * Cross-filtered by all global filters.
     */
    private function getCategoriesBar(array $filters): array
    {
        $query = DB::table('kategori_kejahatan')
            ->select('kategori_kejahatan.id', 'kategori_kejahatan.nama')
            ->selectRaw('COUNT(DISTINCT laporan.id) as total_reports')
            ->selectRaw('COALESCE(SUM(korban.kerugian_nominal), 0) as total_losses')
            ->leftJoin('laporan', 'laporan.kategori_kejahatan_id', '=', 'kategori_kejahatan.id')
            ->leftJoin('korban', 'korban.laporan_id', '=', 'laporan.id');

        // Apply non-kategori filters (kategori is the grouping axis itself)
        $barFilters = $filters;
        $barFilters['kategori_id'] = null;

        if (!empty($filters['bulan'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereNull('laporan.id')
                  ->orWhereRaw('MONTH(laporan.tanggal_laporan) = ?', [$filters['bulan']]);
            });
        }

        if (!empty($filters['tahun'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereNull('laporan.id')
                  ->orWhereRaw('YEAR(laporan.tanggal_laporan) = ?', [$filters['tahun']]);
            });
        }

        // Demographic filters
        $hasGender     = !empty($barFilters['gender']);
        $hasPendidikan = !empty($barFilters['pendidikan']);
        $hasUsia       = !empty($barFilters['usia_group']);

        if ($hasGender || $hasPendidikan || $hasUsia) {
            $query->whereExists(function ($sub) use ($barFilters, $hasGender, $hasPendidikan, $hasUsia) {
                $sub->select(DB::raw(1))
                    ->from('korban as k2')
                    ->join('orang', 'k2.orang_id', '=', 'orang.id')
                    ->join('laporan as lap_age', 'k2.laporan_id', '=', 'lap_age.id')
                    ->whereColumn('k2.laporan_id', 'laporan.id');

                if ($hasGender) $sub->where('orang.jenis_kelamin', $barFilters['gender']);
                if ($hasPendidikan) $sub->where('orang.pendidikan', $barFilters['pendidikan']);
                if ($hasUsia) {
                    $sub->whereRaw(
                        'TIMESTAMPDIFF(YEAR, orang.tanggal_lahir, lap_age.tanggal_laporan) BETWEEN ? AND ?',
                        self::AGE_GROUPS[$barFilters['usia_group']] ?? [0, 200]
                    );
                }
            });
        }

        return $query
            ->groupBy('kategori_kejahatan.id', 'kategori_kejahatan.nama')
            ->orderByDesc('total_reports')
            ->get()
            ->map(fn ($row) => [
                'id'            => $row->id,
                'nama'          => $row->nama,
                'total_reports' => (int) $row->total_reports,
                'total_losses'  => (float) $row->total_losses,
                'active'        => !empty($filters['kategori_id']) && $row->id == $filters['kategori_id'],
            ])
            ->toArray();
    }

    // =========================================================================
    // 4. VICTIM_PROFILING
    // =========================================================================

    /**
     * Gender, Education, Occupation breakdown.
     * Stacked cross-filters: each dimension excludes ITSELF from the filter set
     * so clicking "LAKI-LAKI" won't reduce the Gender chart to a single bar.
     *
     * Example: if kategori_id=3 AND pendidikan="S1":
     *   - Gender chart   → filtered by kategori_id + pendidikan (NOT gender)
     *   - Education chart → filtered by kategori_id + gender (NOT pendidikan)
     *   - Occupation chart → filtered by kategori_id + pendidikan + gender
     */
    private function getVictimProfiling(array $filters): array
    {
        $excluded = self::EXCLUDED_LABELS;

        // ── Gender: exclude gender filter so the chart remains full ─
        $genderFilters = $filters;
        $genderFilters['gender'] = null;

        $genderQuery = Korban::query()
            ->join('orang', 'korban.orang_id', '=', 'orang.id');
        $this->applyGlobalFilters($genderQuery, $genderFilters, 'korban');

        $gender = $genderQuery
            ->select('orang.jenis_kelamin as label', DB::raw('COUNT(*) as total'))
            ->whereNotNull('orang.jenis_kelamin')
            ->where('orang.jenis_kelamin', '!=', '')
            ->whereNotIn('orang.jenis_kelamin', $excluded)
            ->groupBy('orang.jenis_kelamin')
            ->orderByDesc('total')
            ->get();

        // ── Education: exclude pendidikan filter ───────────────────
        $eduFilters = $filters;
        $eduFilters['pendidikan'] = null;

        $eduQuery = Korban::query()
            ->join('orang', 'korban.orang_id', '=', 'orang.id');
        $this->applyGlobalFilters($eduQuery, $eduFilters, 'korban');

        $education = $eduQuery
            ->select('orang.pendidikan as label', DB::raw('COUNT(*) as total'))
            ->whereNotNull('orang.pendidikan')
            ->where('orang.pendidikan', '!=', '')
            ->whereNotIn('orang.pendidikan', $excluded)
            ->groupBy('orang.pendidikan')
            ->orderByDesc('total')
            ->get();

        // ── Occupation: all filters applied (including gender/pendidikan) ──
        $occQuery = Korban::query()
            ->join('orang', 'korban.orang_id', '=', 'orang.id');
        $this->applyGlobalFilters($occQuery, $filters, 'korban');

        $occupation = $occQuery
            ->select('orang.pekerjaan as label', DB::raw('COUNT(*) as total'))
            ->whereNotNull('orang.pekerjaan')
            ->where('orang.pekerjaan', '!=', '')
            ->whereNotIn('orang.pekerjaan', $excluded)
            ->groupBy('orang.pekerjaan')
            ->orderByDesc('total')
            ->get();

        // ── Age Groups: exclude usia_group filter ──────────────────
        $ageFilters = $filters;
        $ageFilters['usia_group'] = null;

        $ageQuery = Korban::query()
            ->join('orang', 'korban.orang_id', '=', 'orang.id')
            ->whereNotNull('orang.tanggal_lahir');
        $this->applyGlobalFilters($ageQuery, $ageFilters, 'korban');

        $ageRaw = (clone $ageQuery)
            ->selectRaw('TIMESTAMPDIFF(YEAR, orang.tanggal_lahir, laporan.tanggal_laporan) as usia')
            ->get()
            ->pluck('usia');

        // Bucket into age groups (use plain array — Collection doesn't support indirect modification in PHP 8.2+)
        $ageBuckets = array_fill_keys(array_keys(self::AGE_GROUPS), 0);
        foreach ($ageRaw as $age) {
            foreach (self::AGE_GROUPS as $label => [$min, $max]) {
                if ($age >= $min && $age <= $max) {
                    $ageBuckets[$label]++;
                    break;
                }
            }
        }
        // Remove empty buckets
        $ageBuckets = array_filter($ageBuckets, fn ($v) => $v > 0);

        return [
            'gender' => [
                'labels' => $gender->pluck('label')->toArray(),
                'data'   => $gender->pluck('total')->map(fn ($v) => (int) $v)->toArray(),
            ],
            'education' => [
                'labels' => $education->pluck('label')->toArray(),
                'data'   => $education->pluck('total')->map(fn ($v) => (int) $v)->toArray(),
            ],
            'occupation' => [
                'labels' => $occupation->pluck('label')->toArray(),
                'data'   => $occupation->pluck('total')->map(fn ($v) => (int) $v)->toArray(),
            ],
            'usia' => [
                'labels' => array_keys($ageBuckets),
                'data'   => array_values($ageBuckets),
            ],
        ];
    }

    // =========================================================================
    // 5. PLATFORM_SUNBURST
    // =========================================================================

    private function getPlatformSunburst(array $filters): array
    {
        $baseQuery = IdentitasTersangka::query()
            ->join('tersangka', 'identitas_tersangka.tersangka_id', '=', 'tersangka.id');
        $this->applyGlobalFilters($baseQuery, $filters, 'identitas_tersangka');

        // Outer ring: group by jenis
        $byJenis = (clone $baseQuery)
            ->select('identitas_tersangka.jenis', DB::raw('COUNT(*) as total'))
            ->groupBy('identitas_tersangka.jenis')
            ->orderByDesc('total')
            ->get();

        // Inner ring: group by jenis + platform (exclude unknown/empty)
        $byJenisPlatform = (clone $baseQuery)
            ->select(
                'identitas_tersangka.jenis',
                'identitas_tersangka.platform',
                DB::raw('COUNT(*) as total')
            )
            ->whereNotNull('identitas_tersangka.platform')
            ->where('identitas_tersangka.platform', '!=', '')
            ->where('identitas_tersangka.platform', '!=', 'Tidak Diketahui')
            ->groupBy('identitas_tersangka.jenis', 'identitas_tersangka.platform')
            ->orderBy('identitas_tersangka.jenis')
            ->orderByDesc('total')
            ->get()
            ->groupBy('jenis');

        $jenisOptions = IdentitasTersangka::getJenisOptions();

        return $byJenis->map(function ($row) use ($byJenisPlatform, $jenisOptions) {
            $platforms = $byJenisPlatform->get($row->jenis, collect());

            return [
                'jenis'       => $row->jenis,
                'jenis_label' => $jenisOptions[$row->jenis] ?? $row->jenis,
                'total'       => (int) $row->total,
                'platforms'   => $platforms->map(fn ($p) => [
                    'platform' => $p->platform,
                    'total'    => (int) $p->total,
                ])->values()->toArray(),
            ];
        })->values()->toArray();
    }

    // =========================================================================
    // 6. RECENT_REPORTS (Live Threat Feed)
    // =========================================================================

    private function getRecentReports(): array
    {
        return Laporan::query()
            ->with('kategoriKejahatan:id,nama')
            ->withSum('korban', 'kerugian_nominal')
            ->select('id', 'kategori_kejahatan_id', 'created_at')
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(fn ($lap) => [
                'id'             => $lap->id,
                'waktu'          => $lap->created_at->format('H:i'),
                'kategori'       => $lap->kategoriKejahatan?->nama ?? '-',
                'total_kerugian' => (float) ($lap->korban_sum_kerugian_nominal ?? 0),
            ])
            ->toArray();
    }

    // =========================================================================
    // FILTER OPTIONS (for frontend dropdowns)
    // =========================================================================

    private function getFilterOptions(): array
    {
        return [
            'kategori' => KategoriKejahatan::active()
                ->orderBy('nama')
                ->get(['id', 'nama'])
                ->toArray(),

            'gender' => ['LAKI-LAKI', 'PEREMPUAN'],

            'pendidikan' => MasterPendidikan::query()
                ->orderBy('nama')
                ->pluck('nama')
                ->toArray(),

            'usia_group' => array_keys(self::AGE_GROUPS),

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

            'platform' => DB::table('identitas_tersangka')
                ->whereNotNull('platform')
                ->where('platform', '!=', '')
                ->distinct()
                ->orderBy('platform')
                ->pluck('platform')
                ->toArray(),
        ];
    }
}
