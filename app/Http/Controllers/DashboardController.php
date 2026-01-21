<?php

namespace App\Http\Controllers;

use App\Models\Korban;
use App\Models\Laporan;
use App\Models\Wilayah;
use App\Models\JenisKejahatan;
use App\Models\IdentitasTersangka;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * DashboardController
 * 
 * Provides statistics and analytics using denormalized wilayah data
 * for fast queries without complex string parsing
 */
class DashboardController extends Controller
{
    /**
     * Main dashboard view with summary statistics
     */
    public function index(): InertiaResponse
    {
        return Inertia::render('Dashboard', [
            'summary' => $this->getSummary(),
            'laporanPerProvinsi' => $this->getLaporanPerProvinsi(),
            'laporanPerKategori' => $this->getLaporanPerKategori(),
            'topRekening' => $this->getTopRekening(10),
            'recentLaporan' => $this->getRecentLaporan(5),
        ]);
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
        return JenisKejahatan::withCount('laporan')
            ->with('kategori:id,nama')
            ->having('laporan_count', '>', 0)
            ->orderByDesc('laporan_count')
            ->get()
            ->map(fn($jenis) => [
                'jenis_id' => $jenis->id,
                'jenis_nama' => $jenis->nama,
                'kategori_nama' => $jenis->kategori?->nama ?? '-',
                'total_laporan' => $jenis->laporan_count,
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
            'jenisKejahatan:id,nama',
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
                'jenis_kejahatan' => $l->jenisKejahatan?->nama ?? '-',
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
