<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IdentitasTersangka;
use App\Models\Laporan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * AdminLaporanController
 * 
 * Mengelola laporan masuk untuk Admin - assign ke subdit
 */
class AdminLaporanController extends Controller
{
    /**
     * Display a listing of laporan for admin to assign.
     */
    public function index(Request $request): InertiaResponse
    {
        $query = Laporan::with([
            'pelapor',
            'korban',
            'kategoriKejahatan',
            'assignedBy',
        ]);

        // Search filters
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nomor_stpa', 'like', "%{$search}%")
                    ->orWhereHas('pelapor', fn($q) => $q->where('nama', 'like', "%{$search}%"));
            });
        }

        // Filter by assignment status
        if ($request->filled('assigned')) {
            if ($request->input('assigned') === 'yes') {
                $query->whereNotNull('assigned_subdit');
            } else {
                $query->whereNull('assigned_subdit');
            }
        }

        // Filter by subdit
        if ($request->filled('subdit')) {
            $query->where('assigned_subdit', $request->input('subdit'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Sorting - newest first
        $query->orderBy('created_at', 'desc');

        $laporan = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/Laporan/Index', [
            'laporan' => $laporan,
            'filters' => $request->only(['search', 'assigned', 'subdit', 'status']),
            'subditOptions' => [
                ['value' => 1, 'label' => 'Subdit 1'],
                ['value' => 2, 'label' => 'Subdit 2'],
                ['value' => 3, 'label' => 'Subdit 3'],
            ],
            'statusOptions' => Laporan::getStatusOptions(),
        ]);
    }

    /**
     * Display the specified laporan detail.
     */
    public function show(Laporan $laporan): InertiaResponse
    {
        $laporan->load([
            'pelapor.alamatKtp.provinsi',
            'pelapor.alamatKtp.kabupaten',
            'pelapor.alamatKtp.kecamatan',
            'pelapor.alamatKtp.kelurahan',
            'korban.orang',
            'tersangka.identitas',
            'kategoriKejahatan',
            'petugas', // pangkat and jabatan are string fields on users table
            'lampiran',
            'provinsiKejadian',
            'kabupatenKejadian',
            'kecamatanKejadian',
            'kelurahanKejadian',
            'assignedBy',
        ]);

        // Detect recidivist suspects
        $trackRecord = $this->detectRecidivist($laporan);

        return Inertia::render('Admin/Laporan/Show', [
            'laporan' => $laporan,
            'trackRecord' => $trackRecord,
            'subditOptions' => [
                ['value' => 1, 'label' => 'Subdit 1'],
                ['value' => 2, 'label' => 'Subdit 2'],
                ['value' => 3, 'label' => 'Subdit 3'],
            ],
        ]);
    }

    /**
     * Detect recidivist suspects by matching digital identities across cases.
     */
    private function detectRecidivist(Laporan $laporan): array
    {
        $trackRecord = [];
        $currentLaporanId = $laporan->id;

        // Jenis identitas yang perlu cocokkan platform juga
        $needsPlatformMatch = ['sosmed', 'ewallet', 'rekening', 'marketplace', 'kripto'];

        foreach ($laporan->tersangka as $tersangka) {
            $matches = [];

            foreach ($tersangka->identitas as $identitas) {
                if (empty($identitas->nilai)) {
                    continue;
                }

                // Build query for matching identities
                $query = IdentitasTersangka::where('nilai', $identitas->nilai)
                    ->where('id', '!=', $identitas->id)
                    ->whereHas('tersangka', function ($q) use ($currentLaporanId) {
                        $q->where('laporan_id', '!=', $currentLaporanId);
                    });

                // Untuk sosmed, ewallet, rekening, dll: harus cocok platform juga
                if (in_array($identitas->jenis, $needsPlatformMatch) && !empty($identitas->platform)) {
                    $query->where('platform', $identitas->platform);
                }

                $duplicates = $query->with(['tersangka.laporan' => function ($q) {
                        $q->select('id', 'nomor_stpa', 'status', 'assigned_subdit', 'tanggal_laporan');
                    }])
                    ->get();

                foreach ($duplicates as $duplicate) {
                    $relatedLaporan = $duplicate->tersangka->laporan;
                    
                    if (!$relatedLaporan) continue;

                    $matches[] = [
                        'jenis_label' => $this->getJenisLabel($identitas->jenis),
                        'nilai' => $identitas->nilai,
                        'platform' => $identitas->platform,
                        'laporan_id' => $relatedLaporan->id,
                        'nomor_stpa' => $relatedLaporan->nomor_stpa ?: 'Belum ada STPA',
                        'status' => $relatedLaporan->status,
                        'subdit' => $relatedLaporan->assigned_subdit ? 'Subdit ' . $relatedLaporan->assigned_subdit : '-',
                        'tanggal_laporan' => $relatedLaporan->tanggal_laporan?->format('d M Y'),
                    ];
                }
            }

            if (!empty($matches)) {
                $uniqueMatches = collect($matches)->unique(function ($item) {
                    return $item['nilai'] . '-' . $item['laporan_id'];
                })->values()->toArray();

                $trackRecord[$tersangka->id] = $uniqueMatches;
            }
        }

        return $trackRecord;
    }

    /**
     * Get human-readable label for identity type.
     */
    private function getJenisLabel(string $jenis): string
    {
        $labels = [
            'telepon' => 'Telepon',
            'rekening' => 'Rekening Bank',
            'sosmed' => 'Media Sosial',
            'email' => 'Email',
            'ewallet' => 'E-Wallet',
            'kripto' => 'Kripto',
            'marketplace' => 'Marketplace',
            'website' => 'Website',
            'lainnya' => 'Lainnya',
        ];

        return $labels[$jenis] ?? $jenis;
    }

    /**
     * Assign laporan to a subdit.
     */
    public function assignSubdit(Request $request, Laporan $laporan)
    {
        $request->validate([
            'subdit' => 'required|integer|between:1,3',
        ], [
            'subdit.required' => 'Subdit wajib dipilih',
            'subdit.between' => 'Subdit harus antara 1-3',
        ]);

        $laporan->update([
            'assigned_subdit' => $request->subdit,
            'assigned_by' => auth()->id(),
            'assigned_at' => now(),
        ]);

        return back()->with('success', "Laporan berhasil ditugaskan ke Subdit {$request->subdit}");
    }
}
