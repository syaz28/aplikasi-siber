<?php

namespace App\Http\Controllers;

use App\Models\Korban;
use App\Models\Laporan;
use App\Models\Wilayah;
use App\Models\KategoriKejahatan;
use App\Models\IdentitasTersangka;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * DashboardController
 * 
 * Provides operational dashboard with cyber crime metrics,
 * analytics charts, and recent activity
 */
class DashboardController extends Controller
{
    /**
     * Main dashboard view - Operational Dashboard for Police Officers
     */
    public function index(): InertiaResponse
    {
        // =============================================
        // A. KEY METRICS (Stats Cards)
        // =============================================
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $startOfWeek = Carbon::now()->startOfWeek();
        
        // 1. Today's reports
        $todayReports = Laporan::whereDate('created_at', $today)->count();
        
        // 2. Month's reports
        $monthReports = Laporan::where('created_at', '>=', $startOfMonth)->count();
        
        // 3. Reports in active process (Penyelidikan, Penyidikan, Tahap I, Tahap II)
        $processReports = Laporan::whereIn('status', ['Penyelidikan', 'Penyidikan', 'Tahap I', 'Tahap II'])->count();
        
        // 4. Top crime category today (or this week if no reports today)
        $topCrime = $this->getTopCrimeCategory($today);
        if (!$topCrime) {
            $topCrime = $this->getTopCrimeCategory($startOfWeek, $today);
        }
        
        // 5. Total kerugian bulan ini
        $monthLoss = Korban::whereHas('laporan', function ($q) use ($startOfMonth) {
            $q->where('created_at', '>=', $startOfMonth);
        })->sum('kerugian_nominal');
        
        $metrics = [
            'today_reports' => $todayReports,
            'month_reports' => $monthReports,
            'process_reports' => $processReports,
            'top_crime' => $topCrime ?? 'Belum ada data',
            'month_loss' => $monthLoss,
        ];
        
        // =============================================
        // B. CHART DATA 1: Weekly Trend (Line/Area Chart)
        // =============================================
        $weeklyTrend = $this->getWeeklyTrend();
        
        // =============================================
        // C. CHART DATA 2: Platform Distribution (Pie/Donut Chart)
        // =============================================
        $platformDistribution = $this->getPlatformDistribution();
        
        // =============================================
        // D. CHART DATA 3: Category Distribution (Bar Chart)
        // =============================================
        $categoryDistribution = $this->getCategoryDistribution();
        
        // =============================================
        // E. RECENT ACTIVITY: Latest 5 Reports
        // =============================================
        $recentReports = Laporan::with([
            'pelapor:id,nama,telepon',
            'kategoriKejahatan:id,nama',
            'korban',
        ])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn($l) => [
                'id' => $l->id,
                'nomor_stpa' => $l->nomor_stpa ?? '-',
                'created_at' => $l->created_at->format('d M Y H:i'),
                'created_at_diff' => $l->created_at->diffForHumans(),
                'pelapor_nama' => $l->pelapor?->nama ?? '-',
                'pelapor_telepon' => $l->pelapor?->telepon ?? '-',
                'kategori' => $l->kategoriKejahatan?->nama ?? '-',
                'status' => $l->status,
                'status_label' => $l->status_label,
                'total_kerugian' => $l->korban->sum('kerugian_nominal'),
            ]);
        
        return Inertia::render('Dashboard', [
            'metrics' => $metrics,
            'weeklyTrend' => $weeklyTrend,
            'platformDistribution' => $platformDistribution,
            'categoryDistribution' => $categoryDistribution,
            'recentReports' => $recentReports,
        ]);
    }
    
    /**
     * Get top crime category for a date range
     */
    private function getTopCrimeCategory($startDate, $endDate = null): ?string
    {
        $query = Laporan::select('kategori_kejahatan_id')
            ->selectRaw('COUNT(*) as total')
            ->whereNotNull('kategori_kejahatan_id');
            
        if ($endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        } else {
            $query->whereDate('created_at', $startDate);
        }
        
        $result = $query->groupBy('kategori_kejahatan_id')
            ->orderByDesc('total')
            ->first();
            
        if ($result) {
            $kategori = KategoriKejahatan::find($result->kategori_kejahatan_id);
            return $kategori?->nama;
        }
        
        return null;
    }
    
    /**
     * Get weekly trend data for the last 7 days
     */
    private function getWeeklyTrend(): array
    {
        $labels = [];
        $series = [];
        $dayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $labels[] = $dayNames[$date->dayOfWeek];
            $series[] = Laporan::whereDate('created_at', $date)->count();
        }
        
        return [
            'labels' => $labels,
            'series' => $series,
        ];
    }
    
    /**
     * Get platform distribution from identitas_tersangka
     * Groups by platform (WhatsApp, Instagram, etc.)
     */
    private function getPlatformDistribution(): array
    {
        $data = IdentitasTersangka::select('platform')
            ->selectRaw('COUNT(*) as total')
            ->whereNotNull('platform')
            ->where('platform', '!=', '')
            ->groupBy('platform')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
        
        // If no data, return sample data structure
        if ($data->isEmpty()) {
            return [
                'labels' => ['Belum ada data'],
                'series' => [1],
            ];
        }
        
        return [
            'labels' => $data->pluck('platform')->toArray(),
            'series' => $data->pluck('total')->toArray(),
        ];
    }
    
    /**
     * Get crime category distribution
     */
    private function getCategoryDistribution(): array
    {
        $data = KategoriKejahatan::withCount('laporan')
            ->having('laporan_count', '>', 0)
            ->orderByDesc('laporan_count')
            ->limit(6)
            ->get();
        
        if ($data->isEmpty()) {
            return [
                'labels' => ['Belum ada data'],
                'series' => [1],
            ];
        }
        
        return [
            'labels' => $data->pluck('nama')->toArray(),
            'series' => $data->pluck('laporan_count')->toArray(),
        ];
    }

    /**
     * Get summary statistics
     */
    public function getSummary(): array
    {
        $today = now()->startOfDay();
        $thisMonth = now()->startOfMonth();
        
        return [
            'total_laporan' => Laporan::count(),
            'laporan_hari_ini' => Laporan::whereDate('tanggal_laporan', $today)->count(),
            'laporan_bulan_ini' => Laporan::where('tanggal_laporan', '>=', $thisMonth)->count(),
            'total_korban' => Korban::count(),
            'total_kerugian' => Korban::sum('kerugian_nominal'),
            'total_kerugian_bulan_ini' => Korban::whereHas('laporan', function ($q) use ($thisMonth) {
                $q->where('tanggal_laporan', '>=', $thisMonth);
            })->sum('kerugian_nominal'),
            'laporan_per_status' => Laporan::selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
        ];
    }

    /**
     * Get laporan statistics per provinsi
     * Uses denormalized kode_provinsi_kejadian for fast queries
     */
    public function getLaporanPerProvinsi(): array
    {
        return Laporan::select('kode_provinsi_kejadian')
            ->selectRaw('COUNT(*) as total_laporan')
            ->selectRaw('SUM(COALESCE((SELECT SUM(kerugian_nominal) FROM korban WHERE korban.laporan_id = laporan.id), 0)) as total_kerugian')
            ->whereNotNull('kode_provinsi_kejadian')
            ->groupBy('kode_provinsi_kejadian')
            ->with(['provinsiKejadian:kode,nama'])
            ->orderByDesc('total_laporan')
            ->get()
            ->map(fn($row) => [
                'kode' => $row->kode_provinsi_kejadian,
                'nama' => $row->provinsiKejadian?->nama ?? 'Tidak Diketahui',
                'total_laporan' => $row->total_laporan,
                'total_kerugian' => $row->total_kerugian,
            ])
            ->toArray();
    }

    /**
     * Get laporan statistics per kabupaten in a specific provinsi
     * Uses denormalized kode_kabupaten_kejadian
     */
    public function getLaporanPerKabupaten(Request $request): JsonResponse
    {
        $kodeProvinsi = $request->input('kode_provinsi');
        
        $query = Laporan::select('kode_kabupaten_kejadian')
            ->selectRaw('COUNT(*) as total_laporan')
            ->whereNotNull('kode_kabupaten_kejadian')
            ->groupBy('kode_kabupaten_kejadian')
            ->orderByDesc('total_laporan');
            
        if ($kodeProvinsi) {
            $query->where('kode_provinsi_kejadian', $kodeProvinsi);
        }
        
        $data = $query->get()->map(function ($row) {
            $wilayah = Wilayah::where('kode', $row->kode_kabupaten_kejadian)->first();
            return [
                'kode' => $row->kode_kabupaten_kejadian,
                'nama' => $wilayah?->nama ?? 'Tidak Diketahui',
                'total_laporan' => $row->total_laporan,
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get laporan statistics per kecamatan
     */
    public function getLaporanPerKecamatan(Request $request): JsonResponse
    {
        $kodeKabupaten = $request->input('kode_kabupaten');
        
        $query = Laporan::select('kode_kecamatan_kejadian')
            ->selectRaw('COUNT(*) as total_laporan')
            ->whereNotNull('kode_kecamatan_kejadian')
            ->groupBy('kode_kecamatan_kejadian')
            ->orderByDesc('total_laporan');
            
        if ($kodeKabupaten) {
            $query->where('kode_kabupaten_kejadian', $kodeKabupaten);
        }
        
        $data = $query->get()->map(function ($row) {
            $wilayah = Wilayah::where('kode', $row->kode_kecamatan_kejadian)->first();
            return [
                'kode' => $row->kode_kecamatan_kejadian,
                'nama' => $wilayah?->nama ?? 'Tidak Diketahui',
                'total_laporan' => $row->total_laporan,
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get laporan statistics per kategori kejahatan
     */
    public function getLaporanPerKategori(): array
    {
        return KategoriKejahatan::withCount('laporan')
            ->having('laporan_count', '>', 0)
            ->orderByDesc('laporan_count')
            ->get()
            ->map(fn($kategori) => [
                'kategori_id' => $kategori->id,
                'kategori_nama' => $kategori->nama,
                'total_laporan' => $kategori->laporan_count,
            ])
            ->toArray();
    }

    /**
     * Get top reported bank accounts (rekening)
     * Uses identitas_tersangka table
     */
    public function getTopRekening(int $limit = 10): array
    {
        return IdentitasTersangka::where('jenis', 'rekening')
            ->select('nilai', 'platform')
            ->selectRaw('COUNT(DISTINCT tersangka_id) as total_tersangka')
            ->selectRaw('COUNT(DISTINCT (SELECT laporan_id FROM tersangka WHERE tersangka.id = identitas_tersangka.tersangka_id)) as total_laporan')
            ->groupBy('nilai', 'platform')
            ->orderByDesc('total_laporan')
            ->limit($limit)
            ->get()
            ->map(fn($row) => [
                'nomor_rekening' => $row->nilai,
                'bank' => $row->platform ?? '-',
                'total_tersangka' => $row->total_tersangka,
                'total_laporan' => $row->total_laporan,
            ])
            ->toArray();
    }

    /**
     * Get top reported phone numbers
     */
    public function getTopTelepon(int $limit = 10): array
    {
        return IdentitasTersangka::where('jenis', 'telepon')
            ->select('nilai')
            ->selectRaw('COUNT(DISTINCT tersangka_id) as total_tersangka')
            ->groupBy('nilai')
            ->orderByDesc('total_tersangka')
            ->limit($limit)
            ->get()
            ->map(fn($row) => [
                'nomor_telepon' => $row->nilai,
                'total_tersangka' => $row->total_tersangka,
            ])
            ->toArray();
    }

    /**
     * Get recent laporan
     */
    public function getRecentLaporan(int $limit = 5): array
    {
        return Laporan::with([
            'pelapor:id,nama',
            'kategoriKejahatan:id,nama',
            'provinsiKejadian:kode,nama',
        ])
            ->orderByDesc('tanggal_laporan')
            ->limit($limit)
            ->get()
            ->map(fn($l) => [
                'id' => $l->id,
                'nomor_stpa' => $l->nomor_stpa ?? '-',
                'tanggal_laporan' => $l->tanggal_laporan->format('d/m/Y'),
                'pelapor' => $l->pelapor?->nama ?? '-',
                'kategori_kejahatan' => $l->kategoriKejahatan?->nama ?? '-',
                'provinsi' => $l->provinsiKejadian?->nama ?? '-',
                'status' => $l->status,
                'status_label' => $l->status_label,
            ])
            ->toArray();
    }
      
    /**
     * Get kerugian statistics per periode
     */
    public function getKerugianPerBulan(Request $request): JsonResponse
    {
        $year = $request->input('year', now()->year);
        
        $data = Korban::join('laporan', 'korban.laporan_id', '=', 'laporan.id')
            ->selectRaw('MONTH(laporan.tanggal_laporan) as bulan')
            ->selectRaw('SUM(korban.kerugian_nominal) as total_kerugian')
            ->selectRaw('COUNT(DISTINCT laporan.id) as total_laporan')
            ->whereYear('laporan.tanggal_laporan', $year)
            ->groupByRaw('MONTH(laporan.tanggal_laporan)')
            ->orderBy('bulan')
            ->get();
            
        $months = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Ags',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];
        
        $result = [];
        foreach ($months as $num => $name) {
            $row = $data->firstWhere('bulan', $num);
            $result[] = [
                'bulan' => $num,
                'nama_bulan' => $name,
                'total_kerugian' => $row?->total_kerugian ?? 0,
                'total_laporan' => $row?->total_laporan ?? 0,
            ];
        }
        
        return response()->json([
            'success' => true,
            'year' => $year,
            'data' => $result,
        ]);
    }

    /**
     * Export statistics as JSON (for further processing)
     */
    public function exportStatistics(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'generated_at' => now()->toISOString(),
            'data' => [
                'summary' => $this->getSummary(),
                'per_provinsi' => $this->getLaporanPerProvinsi(),
                'per_kategori' => $this->getLaporanPerKategori(),
                'top_rekening' => $this->getTopRekening(20),
                'top_telepon' => $this->getTopTelepon(20),
            ],
        ]);
    }
}







