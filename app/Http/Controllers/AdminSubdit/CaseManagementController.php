<?php

namespace App\Http\Controllers\AdminSubdit;

use App\Http\Controllers\Controller;
use App\Models\Laporan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CaseManagementController extends Controller
{
    /**
     * Display a listing of cases assigned to the admin's subdit.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = Laporan::with([
            'pelapor',
            'kategoriKejahatan',
            'petugas',
            'provinsiKejadian',
            'kabupatenKejadian',
        ])
        ->where('assigned_subdit', $user->subdit);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by unit
        if ($request->filled('unit')) {
            $query->where('disposisi_unit', $request->unit);
        }

        // Search by nomor_stpa or pelapor name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor_stpa', 'like', "%{$search}%")
                  ->orWhereHas('pelapor', function ($q) use ($search) {
                      $q->where('nama', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('tanggal_laporan', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('tanggal_laporan', '<=', $request->date_to);
        }

        $laporan = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('AdminSubdit/Index', [
            'laporan' => $laporan,
            'filters' => $request->only(['status', 'unit', 'search', 'date_from', 'date_to']),
            'statusOptions' => [
                Laporan::STATUS_PENYELIDIKAN => 'Penyelidikan',
                Laporan::STATUS_PENYIDIKAN => 'Penyidikan',
                Laporan::STATUS_TAHAP_I => 'Tahap I',
                Laporan::STATUS_TAHAP_II => 'Tahap II',
                Laporan::STATUS_SP3 => 'SP3',
                Laporan::STATUS_RJ => 'RJ',
                Laporan::STATUS_DIVERSI => 'Diversi',
            ],
            'unitOptions' => [1, 2, 3, 4, 5],
        ]);
    }

    /**
     * Display the specified case.
     */
    public function show($id)
    {
        $user = Auth::user();
        
        $laporan = Laporan::with([
            // Pelapor dengan alamat lengkap
            'pelapor.alamatKtp.provinsi',
            'pelapor.alamatKtp.kabupaten',
            'pelapor.alamatKtp.kecamatan',
            'pelapor.alamatKtp.kelurahan',
            'pelapor.alamatDomisili.provinsi',
            'pelapor.alamatDomisili.kabupaten',
            'pelapor.alamatDomisili.kecamatan',
            'pelapor.alamatDomisili.kelurahan',
            // Korban dengan data orang
            'korban.orang',
            // Tersangka dengan identitas digital
            'tersangka.orang',
            'tersangka.identitas',
            // Kategori kejahatan
            'kategoriKejahatan',
            // Petugas
            'petugas',
            // Lampiran
            'lampiran',
            // Lokasi kejadian
            'provinsiKejadian',
            'kabupatenKejadian',
            'kecamatanKejadian',
            'kelurahanKejadian',
            // Audit
            'createdBy',
            'assignedBy',
        ])->findOrFail($id);

        // Security check: ensure the case belongs to user's subdit
        if ($laporan->assigned_subdit !== $user->subdit) {
            abort(403, 'Anda tidak memiliki akses ke laporan ini.');
        }

        // ========================================
        // DETEKSI RESIDIVIS - Digital Identity Cross-Reference
        // ========================================
        $trackRecord = $this->detectRecidivist($laporan);

        return Inertia::render('AdminSubdit/Show', [
            'laporan' => $laporan,
            'trackRecord' => $trackRecord,
            'statusOptions' => [
                Laporan::STATUS_PENYELIDIKAN => 'Penyelidikan',
                Laporan::STATUS_PENYIDIKAN => 'Penyidikan',
                Laporan::STATUS_TAHAP_I => 'Tahap I',
                Laporan::STATUS_TAHAP_II => 'Tahap II',
                Laporan::STATUS_SP3 => 'SP3',
                Laporan::STATUS_RJ => 'RJ',
                Laporan::STATUS_DIVERSI => 'Diversi',
            ],
            'unitOptions' => [1, 2, 3, 4, 5],
        ]);
    }

    /**
     * Detect recidivist suspects by matching digital identities across cases.
     * 
     * @param Laporan $laporan
     * @return array Track record grouped by suspect ID
     */
    private function detectRecidivist(Laporan $laporan): array
    {
        $trackRecord = [];
        $currentLaporanId = $laporan->id;

        // Jenis identitas yang perlu cocokkan platform juga
        $needsPlatformMatch = ['sosmed', 'ewallet', 'rekening', 'marketplace', 'kripto'];

        // Loop through each suspect
        foreach ($laporan->tersangka as $tersangka) {
            $matches = [];

            // Loop through each identity of this suspect
            foreach ($tersangka->identitas as $identitas) {
                // Skip if nilai is empty
                if (empty($identitas->nilai)) {
                    continue;
                }

                // Build query for matching identities
                $query = \App\Models\IdentitasTersangka::where('nilai', $identitas->nilai)
                    ->where('id', '!=', $identitas->id)
                    ->whereHas('tersangka', function ($q) use ($currentLaporanId) {
                        $q->where('laporan_id', '!=', $currentLaporanId);
                    });

                // Untuk sosmed, ewallet, rekening, dll: harus cocok platform juga
                if (in_array($identitas->jenis, $needsPlatformMatch) && !empty($identitas->platform)) {
                    $query->where('platform', $identitas->platform);
                }

                $duplicates = $query->with(['tersangka.laporan' => function ($q) {
                        $q->select('id', 'nomor_stpa', 'status', 'assigned_subdit', 'disposisi_unit', 'tanggal_laporan')
                            ->with('kategoriKejahatan:id,nama');
                    }])
                    ->get();

                // Add matches to the list
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

            // Only add to trackRecord if there are matches
            if (!empty($matches)) {
                // Remove duplicates (same case might match multiple times)
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
     * Update the unit assignment for the case.
     */
    public function updateUnit(Request $request, $id)
    {
        $user = Auth::user();
        
        $laporan = Laporan::findOrFail($id);

        // Security check
        if ($laporan->assigned_subdit !== $user->subdit) {
            abort(403, 'Anda tidak memiliki akses ke laporan ini.');
        }

        $validated = $request->validate([
            'disposisi_unit' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $laporan->update([
            'disposisi_unit' => $validated['disposisi_unit'],
            'updated_by' => $user->id,
        ]);

        return back()->with('success', 'Unit disposisi berhasil diperbarui.');
    }

    /**
     * Update the status for the case.
     */
    public function updateStatus(Request $request, $id)
    {
        $user = Auth::user();
        
        $laporan = Laporan::findOrFail($id);

        // Security check
        if ($laporan->assigned_subdit !== $user->subdit) {
            abort(403, 'Anda tidak memiliki akses ke laporan ini.');
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in([
                Laporan::STATUS_PENYELIDIKAN,
                Laporan::STATUS_PENYIDIKAN,
                Laporan::STATUS_TAHAP_I,
                Laporan::STATUS_TAHAP_II,
                Laporan::STATUS_SP3,
                Laporan::STATUS_RJ,
                Laporan::STATUS_DIVERSI,
            ])],
        ]);

        $laporan->update([
            'status' => $validated['status'],
            'updated_by' => $user->id,
        ]);

        return back()->with('success', 'Status berhasil diperbarui.');
    }
}
