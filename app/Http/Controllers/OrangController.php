<?php

namespace App\Http\Controllers;

use App\Models\Orang;
use App\Models\Korban;
use App\Models\Tersangka;
use App\Models\Laporan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * OrangController
 * 
 * Handles listing of all persons in the system
 * Accessible by all roles: petugas, admin, admin_subdit, pimpinan
 * 
 * Provides 4 views:
 * - Semua Orang (all persons)
 * - Daftar Tersangka (suspects)
 * - Daftar Korban (victims)
 * - Daftar Pelapor (reporters)
 */
class OrangController extends Controller
{
    /**
     * Display a listing of persons with tab filtering.
     */
    public function index(Request $request): InertiaResponse
    {
        $tab = $request->input('tab', 'semua');
        $search = $request->input('search', '');
        $perPage = 15;

        // Get counts for each tab
        $counts = [
            'semua' => Orang::count(),
            'tersangka' => Orang::whereHas('sebagaiTersangka')->count(),
            'korban' => Orang::whereHas('sebagaiKorban')->count(),
            'pelapor' => Orang::whereHas('laporanSebagaiPelapor')->count(),
        ];

        // Build query based on tab
        $query = match ($tab) {
            'tersangka' => $this->getTersangkaQuery(),
            'korban' => $this->getKorbanQuery(),
            'pelapor' => $this->getPelaporQuery(),
            default => $this->getSemuaQuery(),
        };

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhere('telepon', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Get paginated results
        $orang = $query->orderBy('nama')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Orang/Index', [
            'orang' => $orang,
            'filters' => [
                'tab' => $tab,
                'search' => $search,
            ],
            'counts' => $counts,
        ]);
    }

    /**
     * Display the specified person.
     */
    public function show($id): InertiaResponse
    {
        $orang = Orang::with([
            'alamatKtp.provinsi',
            'alamatKtp.kabupaten',
            'alamatKtp.kecamatan',
            'alamatKtp.kelurahan',
            'alamatDomisili.provinsi',
            'alamatDomisili.kabupaten',
            'alamatDomisili.kecamatan',
            'alamatDomisili.kelurahan',
            'laporanSebagaiPelapor.kategoriKejahatan',
            'sebagaiKorban.laporan.kategoriKejahatan',
            'sebagaiTersangka.laporan.kategoriKejahatan',
            'sebagaiTersangka.identitas',
        ])->findOrFail($id);

        return Inertia::render('Orang/Show', [
            'orang' => $orang,
        ]);
    }

    /**
     * Get query for all persons (Semua Orang)
     */
    private function getSemuaQuery()
    {
        return Orang::query()
            ->withCount(['laporanSebagaiPelapor', 'sebagaiKorban', 'sebagaiTersangka'])
            ->with(['alamatKtp.kabupaten']);
    }

    /**
     * Get query for suspects (Tersangka)
     */
    private function getTersangkaQuery()
    {
        return Orang::query()
            ->whereHas('sebagaiTersangka')
            ->withCount('sebagaiTersangka')
            ->with([
                'alamatKtp.kabupaten',
                'sebagaiTersangka' => function ($q) {
                    $q->with(['laporan:id,nomor_stpa,tanggal_laporan,status', 'identitas']);
                    $q->latest()->limit(3);
                }
            ]);
    }

    /**
     * Get query for victims (Korban)
     */
    private function getKorbanQuery()
    {
        return Orang::query()
            ->whereHas('sebagaiKorban')
            ->withCount('sebagaiKorban')
            ->withSum('sebagaiKorban', 'kerugian_nominal')
            ->with([
                'alamatKtp.kabupaten',
                'sebagaiKorban' => function ($q) {
                    $q->with('laporan:id,nomor_stpa,tanggal_laporan,status');
                    $q->latest()->limit(3);
                }
            ]);
    }

    /**
     * Get query for reporters (Pelapor)
     */
    private function getPelaporQuery()
    {
        return Orang::query()
            ->whereHas('laporanSebagaiPelapor')
            ->withCount('laporanSebagaiPelapor')
            ->with([
                'alamatKtp.kabupaten',
                'laporanSebagaiPelapor' => function ($q) {
                    $q->select('id', 'nomor_stpa', 'tanggal_laporan', 'status', 'pelapor_id', 'hubungan_pelapor');
                    $q->latest()->limit(3);
                }
            ]);
    }
}
