<?php

namespace App\Http\Controllers\Pimpinan;

use App\Http\Controllers\Controller;
use App\Models\IdentitasTersangka;
use App\Models\KategoriKejahatan;
use App\Models\Korban;
use App\Models\Laporan;
use App\Models\MasterPendidikan;
use App\Models\Tersangka;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * PetaPlatformController — Menu 2: Peta & Platform
 *
 * Shared $filters bag (cross-compatible with Menu 1):
 *   tahun, kategori_id, bulan, gender, pendidikan, usia_group,
 *   wilayah_kode (Polres/Kabupaten), platform_name
 *
 * Datasets:
 *   1. REGIONAL_MAP       — Reports count + losses + coordinates per Polres
 *   2. PLATFORM_CATEGORY  — Nested: Platform → Category → count (treemap)
 *   3. REGIONAL_BOARD     — Top 10 regions by kerugian_nominal
 *   4. PLATFORM_BOARD     — Top platforms by total reports
 */
class PetaPlatformController extends Controller
{
    /**
     * Age-group boundaries (shared with Dashboard controller).
     */
    private const AGE_GROUPS = [
        '< 15 Tahun'   => [0, 14],
        '15-30 Tahun'  => [15, 30],
        '31-45 Tahun'  => [31, 45],
        '46-60 Tahun'  => [46, 60],
        '> 60 Tahun'   => [61, 200],
    ];

    /**
     * Kabupaten/Kota Jawa Tengah — lat/long centroids for map plotting.
     *
     * Source: BPS / Wikipedia / OpenStreetMap centroids.
     * Key = wilayah kode (5 chars), value = [lat, lng].
     */
    private const POLRES_COORDS = [
        '33.01' => [-7.7268, 108.8606],  // Cilacap
        '33.02' => [-7.4316, 109.2497],  // Banyumas
        '33.03' => [-7.3931, 109.3640],  // Purbalingga
        '33.04' => [-7.3917, 109.6919],  // Banjarnegara
        '33.05' => [-7.6810, 109.6507],  // Kebumen
        '33.06' => [-7.7142, 110.0055],  // Purworejo
        '33.07' => [-7.3631, 109.9040],  // Wonosobo
        '33.08' => [-7.4704, 110.2177],  // Kab. Magelang
        '33.09' => [-7.5321, 110.6018],  // Boyolali
        '33.10' => [-7.7055, 110.5947],  // Klaten
        '33.11' => [-7.6818, 110.8375],  // Sukoharjo
        '33.12' => [-7.8157, 110.9153],  // Wonogiri
        '33.13' => [-7.6049, 110.9583],  // Karanganyar
        '33.14' => [-7.4287, 111.0199],  // Sragen
        '33.15' => [-7.0816, 110.8980],  // Grobogan
        '33.16' => [-6.9746, 111.4131],  // Blora
        '33.17' => [-6.7057, 111.3460],  // Rembang
        '33.18' => [-6.7456, 111.0387],  // Pati
        '33.19' => [-6.8040, 110.8424],  // Kudus
        '33.20' => [-6.5895, 110.6773],  // Jepara
        '33.21' => [-6.8943, 110.6382],  // Demak
        '33.22' => [-7.1843, 110.4209],  // Kab. Semarang
        '33.23' => [-7.3167, 110.1753],  // Temanggung
        '33.24' => [-7.0316, 110.1783],  // Kendal
        '33.25' => [-6.8958, 109.7233],  // Batang
        '33.26' => [-7.0587, 109.5977],  // Kab. Pekalongan
        '33.27' => [-6.8892, 109.3816],  // Pemalang
        '33.28' => [-7.0479, 109.1401],  // Kab. Tegal
        '33.29' => [-6.8717, 109.0401],  // Brebes
        '33.71' => [-7.4797, 110.2177],  // Kota Magelang
        '33.72' => [-7.5755, 110.8243],  // Kota Surakarta
        '33.73' => [-7.3305, 110.5084],  // Kota Salatiga
        '33.74' => [-6.9667, 110.4196],  // Kota Semarang
        '33.75' => [-6.8886, 109.6753],  // Kota Pekalongan
        '33.76' => [-6.8689, 109.1402],  // Kota Tegal
    ];

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(Request $request): InertiaResponse
    {
        $filters = [
            'tahun'         => $request->input('tahun') ? (int) $request->input('tahun') : null,
            'kategori_id'   => $request->input('kategori_id') ? (int) $request->input('kategori_id') : null,
            'bulan'         => $request->input('bulan') ? (int) $request->input('bulan') : null,
            'gender'        => $request->input('gender') ?: null,
            'pendidikan'    => $request->input('pendidikan') ?: null,
            'usia_group'    => $request->input('usia_group') ?: null,
            'wilayah_kode'  => $request->input('wilayah_kode') ?: null,
            'platform_name' => $request->input('platform_name') ?: null,
        ];

        return Inertia::render('Pimpinan/PetaPlatform', [
            'keyStats'                  => $this->getKeyStats($filters),
            'regionalMap'               => $this->getRegionalMapData($filters),
            'platformCategory'          => $this->getPlatformCategoryTreemap($filters),
            'platformKategoriTreemap'   => $this->getPlatformKategoriTreemap($filters),
            'regionalLeaderboard'       => $this->getRegionalLeaderboard($filters),
            'platformLeaderboard'       => $this->getPlatformLeaderboard($filters),
            'filterOptions'             => $this->getFilterOptions(),
            'appliedFilters'            => $filters,
        ]);
    }

    // =========================================================================
    // KEY STATS (KPI cards)
    // =========================================================================

    private function getKeyStats(array $filters): array
    {
        $laporanQuery = Laporan::query();
        $this->applyGlobalFilters($laporanQuery, $filters, 'laporan');

        $totalLaporan = (clone $laporanQuery)->count();
        $totalSelesai = (clone $laporanQuery)
            ->whereIn('status', ['SP3', 'RJ', 'Diversi'])
            ->count();

        $clearanceRate = $totalLaporan > 0
            ? round(($totalSelesai / $totalLaporan) * 100, 1)
            : 0;

        $kerugianQuery = Korban::query()
            ->join('orang', 'korban.orang_id', '=', 'orang.id');
        $this->applyGlobalFilters($kerugianQuery, $filters, 'korban');
        $totalKerugian = (float) $kerugianQuery->sum('korban.kerugian_nominal');

        return [
            'total_laporan'  => $totalLaporan,
            'total_kerugian' => $totalKerugian,
            'total_selesai'  => $totalSelesai,
            'clearance_rate' => $clearanceRate,
        ];
    }

    // =========================================================================
    // GLOBAL FILTER HELPER
    // =========================================================================

    /**
     * Apply cross-filters to any query.
     *
     * Contexts: 'laporan' | 'korban' | 'tersangka' | 'identitas_tersangka'
     */
    private function applyGlobalFilters($query, array $filters, string $context)
    {
        $hasKategori    = !empty($filters['kategori_id']);
        $hasBulan       = !empty($filters['bulan']);
        $hasTahun       = !empty($filters['tahun']);
        $hasGender      = !empty($filters['gender']);
        $hasPendidikan  = !empty($filters['pendidikan']);
        $hasUsia        = !empty($filters['usia_group']);
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
            // Platform filter: exists in identitas_tersangka → tersangka → laporan
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
     * Apply age-group filter using TIMESTAMPDIFF.
     */
    private function applyAgeFilter($query, string $group): void
    {
        $bounds = self::AGE_GROUPS[$group] ?? null;
        if (!$bounds) return;

        $query->whereRaw(
            'TIMESTAMPDIFF(YEAR, orang.tanggal_lahir, laporan.tanggal_laporan) BETWEEN ? AND ?',
            [$bounds[0], $bounds[1]]
        );
    }

    // =========================================================================
    // 1. REGIONAL MAP DATA
    // =========================================================================

    /**
     * Reports count, total losses, and coordinates grouped by Polres (kabupaten).
     * Excludes the wilayah_kode filter from the grouping axis (self-exclusion).
     */
    private function getRegionalMapData(array $filters): array
    {
        $mapFilters = $filters;
        $mapFilters['wilayah_kode'] = null; // exclude from own axis

        $query = DB::table('laporan')
            ->select('laporan.kode_kabupaten_kejadian as kode')
            ->selectRaw('COUNT(DISTINCT laporan.id) as total_reports')
            ->selectRaw('COALESCE(SUM(korban.kerugian_nominal), 0) as total_losses')
            ->leftJoin('korban', 'korban.laporan_id', '=', 'laporan.id')
            ->whereNotNull('laporan.kode_kabupaten_kejadian')
            ->where('laporan.kode_kabupaten_kejadian', '!=', '');

        // Apply non-wilayah global filters
        $this->applyMapFilters($query, $mapFilters);

        $rows = $query
            ->groupBy('laporan.kode_kabupaten_kejadian')
            ->orderByDesc('total_reports')
            ->get();

        // Resolve wilayah names and attach coordinates
        $wilayahNames = DB::table('wilayah')
            ->whereIn('kode', $rows->pluck('kode'))
            ->pluck('nama', 'kode');

        return $rows->map(function ($row) use ($wilayahNames, $filters) {
            $coords = self::POLRES_COORDS[$row->kode] ?? null;

            return [
                'kode'          => $row->kode,
                'nama'          => $wilayahNames[$row->kode] ?? $row->kode,
                'total_reports' => (int) $row->total_reports,
                'total_losses'  => (float) $row->total_losses,
                'lat'           => $coords[0] ?? null,
                'lng'           => $coords[1] ?? null,
                'active'        => $filters['wilayah_kode'] === $row->kode,
            ];
        })->toArray();
    }

    /**
     * Apply filters to the regional map query (DB::table based).
     * Similar to applyMonthlyFilters in Dashboard controller.
     */
    private function applyMapFilters($query, array $filters): void
    {
        if (!empty($filters['kategori_id'])) {
            $query->where('laporan.kategori_kejahatan_id', $filters['kategori_id']);
        }
        if (!empty($filters['bulan'])) {
            $query->whereRaw('MONTH(laporan.tanggal_laporan) = ?', [$filters['bulan']]);
        }
        if (!empty($filters['tahun'])) {
            $query->whereRaw('YEAR(laporan.tanggal_laporan) = ?', [$filters['tahun']]);
        }
        if (!empty($filters['wilayah_kode'])) {
            $query->where('laporan.kode_kabupaten_kejadian', $filters['wilayah_kode']);
        }

        // Platform filter
        if (!empty($filters['platform_name'])) {
            $query->whereExists(function ($sub) use ($filters) {
                $sub->select(DB::raw(1))
                    ->from('tersangka')
                    ->join('identitas_tersangka', 'identitas_tersangka.tersangka_id', '=', 'tersangka.id')
                    ->whereColumn('tersangka.laporan_id', 'laporan.id')
                    ->where('identitas_tersangka.platform', $filters['platform_name']);
            });
        }

        // Demographic filters
        $hasGender     = !empty($filters['gender']);
        $hasPendidikan = !empty($filters['pendidikan']);
        $hasUsia       = !empty($filters['usia_group']);

        if ($hasGender || $hasPendidikan || $hasUsia) {
            $query->whereExists(function ($sub) use ($filters, $hasGender, $hasPendidikan, $hasUsia) {
                $sub->select(DB::raw(1))
                    ->from('korban')
                    ->join('orang', 'korban.orang_id', '=', 'orang.id')
                    ->join('laporan as lap_age', 'korban.laporan_id', '=', 'lap_age.id')
                    ->whereColumn('korban.laporan_id', 'laporan.id');

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

    // =========================================================================
    // 2. PLATFORM → CATEGORY TREEMAP
    // =========================================================================

    /**
     * Nested Platform → Category → count for treemap visualization.
     * Excludes platform_name filter from the grouping axis (self-exclusion).
     */
    private function getPlatformCategoryTreemap(array $filters): array
    {
        $treeFilters = $filters;
        $treeFilters['platform_name'] = null; // self-exclusion

        $query = DB::table('identitas_tersangka')
            ->join('tersangka', 'identitas_tersangka.tersangka_id', '=', 'tersangka.id')
            ->join('laporan', 'tersangka.laporan_id', '=', 'laporan.id')
            ->join('kategori_kejahatan', 'laporan.kategori_kejahatan_id', '=', 'kategori_kejahatan.id')
            ->select(
                'identitas_tersangka.platform',
                'kategori_kejahatan.nama as kategori',
                DB::raw('COUNT(*) as total')
            )
            ->whereNotNull('identitas_tersangka.platform')
            ->where('identitas_tersangka.platform', '!=', '')
            ->where('identitas_tersangka.platform', '!=', 'Tidak Diketahui');

        // Apply non-platform filters via inline logic (raw query context)
        if (!empty($treeFilters['kategori_id'])) {
            $query->where('laporan.kategori_kejahatan_id', $treeFilters['kategori_id']);
        }
        if (!empty($treeFilters['bulan'])) {
            $query->whereRaw('MONTH(laporan.tanggal_laporan) = ?', [$treeFilters['bulan']]);
        }
        if (!empty($treeFilters['tahun'])) {
            $query->whereRaw('YEAR(laporan.tanggal_laporan) = ?', [$treeFilters['tahun']]);
        }
        if (!empty($treeFilters['wilayah_kode'])) {
            $query->where('laporan.kode_kabupaten_kejadian', $treeFilters['wilayah_kode']);
        }

        // Demographic filters
        $hasGender     = !empty($treeFilters['gender']);
        $hasPendidikan = !empty($treeFilters['pendidikan']);
        $hasUsia       = !empty($treeFilters['usia_group']);

        if ($hasGender || $hasPendidikan || $hasUsia) {
            $query->whereExists(function ($sub) use ($treeFilters, $hasGender, $hasPendidikan, $hasUsia) {
                $sub->select(DB::raw(1))
                    ->from('korban')
                    ->join('orang', 'korban.orang_id', '=', 'orang.id')
                    ->join('laporan as lap_age', 'korban.laporan_id', '=', 'lap_age.id')
                    ->whereColumn('korban.laporan_id', 'tersangka.laporan_id');

                if ($hasGender) $sub->where('orang.jenis_kelamin', $treeFilters['gender']);
                if ($hasPendidikan) $sub->where('orang.pendidikan', $treeFilters['pendidikan']);
                if ($hasUsia) {
                    $sub->whereRaw(
                        'TIMESTAMPDIFF(YEAR, orang.tanggal_lahir, lap_age.tanggal_laporan) BETWEEN ? AND ?',
                        self::AGE_GROUPS[$treeFilters['usia_group']] ?? [0, 200]
                    );
                }
            });
        }

        $rows = $query
            ->groupBy('identitas_tersangka.platform', 'kategori_kejahatan.nama')
            ->orderByDesc('total')
            ->get();

        // Group into nested structure: [ { platform, total, active, children: [ { kategori, total } ] } ]
        $grouped = $rows->groupBy('platform');

        return $grouped->map(function ($items, $platform) use ($filters) {
            return [
                'platform' => $platform,
                'total'    => $items->sum('total'),
                'active'   => $filters['platform_name'] === $platform,
                'children' => $items->map(fn ($row) => [
                    'kategori' => $row->kategori,
                    'total'    => (int) $row->total,
                ])->values()->toArray(),
            ];
        })->sortByDesc('total')->values()->toArray();
    }

    // =========================================================================
    // 3. REGIONAL LEADERBOARD
    // =========================================================================

    /**
     * Top 10 regions by total kerugian_nominal.
     */
    private function getRegionalLeaderboard(array $filters): array
    {
        $query = DB::table('laporan')
            ->join('korban', 'korban.laporan_id', '=', 'laporan.id')
            ->select('laporan.kode_kabupaten_kejadian as kode')
            ->selectRaw('COUNT(DISTINCT laporan.id) as total_reports')
            ->selectRaw('SUM(korban.kerugian_nominal) as total_losses')
            ->whereNotNull('laporan.kode_kabupaten_kejadian')
            ->where('laporan.kode_kabupaten_kejadian', '!=', '');

        // Apply all filters (including wilayah — this is a leaderboard, not a grouping axis)
        $this->applyMapFilters($query, $filters);

        $rows = $query
            ->groupBy('laporan.kode_kabupaten_kejadian')
            ->orderByDesc('total_losses')
            ->limit(10)
            ->get();

        $wilayahNames = DB::table('wilayah')
            ->whereIn('kode', $rows->pluck('kode'))
            ->pluck('nama', 'kode');

        return $rows->map(fn ($row) => [
            'kode'          => $row->kode,
            'nama'          => $wilayahNames[$row->kode] ?? $row->kode,
            'total_reports' => (int) $row->total_reports,
            'total_losses'  => (float) $row->total_losses,
        ])->toArray();
    }

    // =========================================================================
    // 4. PLATFORM LEADERBOARD
    // =========================================================================

    /**
     * Top platforms by total reports.
     */
    private function getPlatformLeaderboard(array $filters): array
    {
        $baseQuery = IdentitasTersangka::query()
            ->join('tersangka', 'identitas_tersangka.tersangka_id', '=', 'tersangka.id');
        $this->applyGlobalFilters($baseQuery, $filters, 'identitas_tersangka');

        return $baseQuery
            ->select('identitas_tersangka.platform', DB::raw('COUNT(*) as total'))
            ->whereNotNull('identitas_tersangka.platform')
            ->where('identitas_tersangka.platform', '!=', '')
            ->where('identitas_tersangka.platform', '!=', 'Tidak Diketahui')
            ->groupBy('identitas_tersangka.platform')
            ->orderByDesc('total')
            ->limit(15)
            ->get()
            ->map(fn ($row) => [
                'platform' => $row->platform,
                'total'    => (int) $row->total,
                'active'   => $filters['platform_name'] === $row->platform,
            ])
            ->toArray();
    }

    // =========================================================================
    // FILTER OPTIONS
    // =========================================================================

    /**
     * Nested Treemap: Platform → KategoriKejahatan → count.
     *
     * Output format for ECharts treemap:
     * [
     *   {
     *     "name": "Facebook",
     *     "value": 150,
     *     "children": [
     *       { "name": "Penipuan Online", "value": 100, "kategori_id": 1 },
     *       { "name": "Hina Cemar",      "value": 50,  "kategori_id": 2 }
     *     ]
     *   }
     * ]
     *
     * Excludes platform_name filter from grouping axis (self-exclusion).
     */
    private function getPlatformKategoriTreemap(array $filters): array
    {
        $treemapFilters = $filters;
        $treemapFilters['platform_name'] = null; // self-exclusion

        $query = DB::table('identitas_tersangka')
            ->join('tersangka', 'identitas_tersangka.tersangka_id', '=', 'tersangka.id')
            ->join('laporan', 'tersangka.laporan_id', '=', 'laporan.id')
            ->join('kategori_kejahatan', 'laporan.kategori_kejahatan_id', '=', 'kategori_kejahatan.id')
            ->select(
                'identitas_tersangka.platform',
                'kategori_kejahatan.id as kategori_id',
                'kategori_kejahatan.nama as kategori_nama',
                DB::raw('COUNT(*) as total')
            )
            ->whereNotNull('identitas_tersangka.platform')
            ->where('identitas_tersangka.platform', '!=', '')
            ->where('identitas_tersangka.platform', '!=', 'Tidak Diketahui');

        // ── Apply global filters (inline, raw query context) ──
        if (!empty($treemapFilters['kategori_id'])) {
            $query->where('laporan.kategori_kejahatan_id', $treemapFilters['kategori_id']);
        }
        if (!empty($treemapFilters['bulan'])) {
            $query->whereRaw('MONTH(laporan.tanggal_laporan) = ?', [$treemapFilters['bulan']]);
        }
        if (!empty($treemapFilters['tahun'])) {
            $query->whereRaw('YEAR(laporan.tanggal_laporan) = ?', [$treemapFilters['tahun']]);
        }
        if (!empty($treemapFilters['wilayah_kode'])) {
            $query->where('laporan.kode_kabupaten_kejadian', $treemapFilters['wilayah_kode']);
        }

        // Demographic filters
        $hasGender     = !empty($treemapFilters['gender']);
        $hasPendidikan = !empty($treemapFilters['pendidikan']);
        $hasUsia       = !empty($treemapFilters['usia_group']);

        if ($hasGender || $hasPendidikan || $hasUsia) {
            $query->whereExists(function ($sub) use ($treemapFilters, $hasGender, $hasPendidikan, $hasUsia) {
                $sub->select(DB::raw(1))
                    ->from('korban')
                    ->join('orang', 'korban.orang_id', '=', 'orang.id')
                    ->join('laporan as lap_age', 'korban.laporan_id', '=', 'lap_age.id')
                    ->whereColumn('korban.laporan_id', 'tersangka.laporan_id');

                if ($hasGender) $sub->where('orang.jenis_kelamin', $treemapFilters['gender']);
                if ($hasPendidikan) $sub->where('orang.pendidikan', $treemapFilters['pendidikan']);
                if ($hasUsia) {
                    $sub->whereRaw(
                        'TIMESTAMPDIFF(YEAR, orang.tanggal_lahir, lap_age.tanggal_laporan) BETWEEN ? AND ?',
                        self::AGE_GROUPS[$treemapFilters['usia_group']] ?? [0, 200]
                    );
                }
            });
        }

        $rows = $query
            ->groupBy('identitas_tersangka.platform', 'kategori_kejahatan.id', 'kategori_kejahatan.nama')
            ->orderByDesc('total')
            ->get();

        // ── Transform to nested ECharts treemap structure ──
        $grouped = $rows->groupBy('platform');

        return $grouped->map(function ($items, $platform) {
            $children = $items->map(fn ($row) => [
                'name'        => $row->kategori_nama,
                'value'       => (int) $row->total,
                'kategori_id' => (int) $row->kategori_id,
            ])->sortByDesc('value')->values()->toArray();

            return [
                'name'     => $platform,
                'value'    => $items->sum('total'),
                'children' => $children,
            ];
        })->sortByDesc('value')->values()->toArray();
    }


    private function getFilterOptions(): array
    {
        return [
            'tahun' => DB::table('laporan')
                ->selectRaw('YEAR(tanggal_laporan) as year')
                ->whereNotNull('tanggal_laporan')
                ->distinct()
                ->orderByDesc('year')
                ->pluck('year')
                ->toArray(),

            'kategori' => KategoriKejahatan::active()
                ->orderBy('nama')
                ->get(['id', 'nama'])
                ->toArray(),

            'bulan' => collect(range(1, 12))->map(fn ($m) => [
                'value' => $m,
                'label' => Carbon::create(null, $m, 1)->translatedFormat('F'),
            ])->toArray(),

            'wilayah' => DB::table('wilayah')
                ->whereRaw("LENGTH(kode) = 5")
                ->where('kode', 'LIKE', '33%')
                ->orderBy('nama')
                ->get(['kode', 'nama'])
                ->map(fn ($w) => ['kode' => $w->kode, 'nama' => $w->nama])
                ->toArray(),

            'platform' => DB::table('identitas_tersangka')
                ->select('platform')
                ->whereNotNull('platform')
                ->where('platform', '!=', '')
                ->where('platform', '!=', 'Tidak Diketahui')
                ->distinct()
                ->orderBy('platform')
                ->pluck('platform')
                ->toArray(),

            'gender' => ['LAKI-LAKI', 'PEREMPUAN'],

            'pendidikan' => MasterPendidikan::query()
                ->orderBy('nama')
                ->pluck('nama')
                ->toArray(),

            'usia_group' => array_keys(self::AGE_GROUPS),
        ];
    }
}
