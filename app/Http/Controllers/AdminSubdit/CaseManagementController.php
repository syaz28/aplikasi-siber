<?php

namespace App\Http\Controllers\AdminSubdit;

use App\Http\Controllers\Controller;
use App\Models\Alamat;
use App\Models\IdentitasTersangka;
use App\Models\Korban;
use App\Models\Laporan;
use App\Models\Orang;
use App\Models\Tersangka;
use App\Services\TerbilangService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
            'tersangka.identitas', // Load untuk residivis check
        ])
        ->where('assigned_subdit', $user->subdit);

        // Year filter (default: current year)
        $tahun = $request->input('tahun', date('Y'));
        if ($tahun) {
            $query->whereYear('tanggal_laporan', $tahun);
        }

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

        $laporan = $query->orderByRaw("CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(nomor_stpa, '/', 2), '/', -1) AS UNSIGNED) DESC")
            ->paginate(15)
            ->withQueryString();

        // Add residivis count for each laporan
        $laporan->getCollection()->transform(function ($item) {
            $item->residivis_count = $this->countResidivis($item);
            return $item;
        });

        return Inertia::render('AdminSubdit/Index', [
            'laporan' => $laporan,
            'filters' => $request->only(['status', 'unit', 'search', 'date_from', 'date_to', 'tahun']),
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
            'tahunOptions' => [2024, 2025, 2026],
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
     * Count how many other cases share the same suspect identities (for index page).
     */
    private function countResidivis(Laporan $laporan): int
    {
        $matchedLaporanIds = [];
        $needsPlatformMatch = ['sosmed', 'ewallet', 'rekening', 'marketplace', 'kripto'];

        foreach ($laporan->tersangka as $tersangka) {
            foreach ($tersangka->identitas as $identitas) {
                if (empty($identitas->nilai)) {
                    continue;
                }

                $query = \App\Models\IdentitasTersangka::where('nilai', $identitas->nilai)
                    ->where('id', '!=', $identitas->id)
                    ->whereHas('tersangka', function ($q) use ($laporan) {
                        $q->where('laporan_id', '!=', $laporan->id);
                    });

                if (in_array($identitas->jenis, $needsPlatformMatch) && !empty($identitas->platform)) {
                    $query->where('platform', $identitas->platform);
                }

                $duplicates = $query->with(['tersangka'])->get();

                foreach ($duplicates as $duplicate) {
                    $matchedLaporanIds[] = $duplicate->tersangka->laporan_id;
                }
            }
        }

        return count(array_unique($matchedLaporanIds));
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
                        'unit' => $relatedLaporan->disposisi_unit ? 'Unit ' . $relatedLaporan->disposisi_unit : 'Menunggu Unit',
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

    /**
     * Update the keterangan for the case.
     */
    public function updateKeterangan(Request $request, $id)
    {
        $user = Auth::user();
        
        $laporan = Laporan::findOrFail($id);

        // Security check
        if ($laporan->assigned_subdit !== $user->subdit) {
            abort(403, 'Anda tidak memiliki akses ke laporan ini.');
        }

        $validated = $request->validate([
            'keterangan' => ['required', Rule::in(['Pengaduan LI', 'Limpahan'])],
        ]);

        $laporan->update([
            'keterangan' => $validated['keterangan'],
            'updated_by' => $user->id,
        ]);

        return back()->with('success', 'Keterangan berhasil diperbarui.');
    }

    /**
     * Show the form for editing the specified case.
     */
    public function edit($id)
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
            'updatedBy',
        ])->findOrFail($id);

        // Security check: ensure the case belongs to user's subdit
        if ($laporan->assigned_subdit !== $user->subdit) {
            abort(403, 'Anda tidak memiliki akses ke laporan ini.');
        }

        return Inertia::render('AdminSubdit/Edit', [
            'laporan' => $laporan,
            'statusOptions' => [
                Laporan::STATUS_PENYELIDIKAN => 'Penyelidikan',
                Laporan::STATUS_PENYIDIKAN => 'Penyidikan',
                Laporan::STATUS_TAHAP_I => 'Tahap I',
                Laporan::STATUS_TAHAP_II => 'Tahap II',
                Laporan::STATUS_SP3 => 'SP3',
                Laporan::STATUS_RJ => 'RJ',
                Laporan::STATUS_DIVERSI => 'Diversi',
            ],
            'hubunganPelaporOptions' => Laporan::getHubunganPelaporOptions(),
            'unitOptions' => [1, 2, 3, 4, 5],
        ]);
    }

    /**
     * Update the specified case.
     * 
     * Handles both simple updates (status, unit) and comprehensive updates (with nested pelapor data).
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        
        $laporan = Laporan::findOrFail($id);

        // Security check
        if ($laporan->assigned_subdit !== $user->subdit) {
            abort(403, 'Anda tidak memiliki akses ke laporan ini.');
        }

        // Check if this is a comprehensive update (with nested pelapor data) or simple update
        $isComprehensiveUpdate = $request->has('pelapor');

        if ($isComprehensiveUpdate) {
            // Comprehensive update with nested data
            $validated = $request->validate([
                // Admin fields
                'nomor_stpa' => 'nullable|string|max:50|unique:laporan,nomor_stpa,' . $id,
                'tanggal_laporan' => 'sometimes|date',
                'petugas_id' => 'sometimes|exists:users,id',
                'status' => ['sometimes', Rule::in([
                    Laporan::STATUS_PENYELIDIKAN,
                    Laporan::STATUS_PENYIDIKAN,
                    Laporan::STATUS_TAHAP_I,
                    Laporan::STATUS_TAHAP_II,
                    Laporan::STATUS_SP3,
                    Laporan::STATUS_RJ,
                    Laporan::STATUS_DIVERSI,
                ])],
                'disposisi_unit' => 'nullable|integer|min:1|max:5',
                
                // Pelapor data
                'pelapor' => 'required|array',
                'pelapor.kewarganegaraan' => 'required|in:WNI,WNA',
                'pelapor.negara_asal' => 'required_if:pelapor.kewarganegaraan,WNA|nullable|string|max:50',
                'pelapor.nik' => $request->input('pelapor.kewarganegaraan') === 'WNI' 
                    ? 'required|string|size:16' 
                    : 'required|string|max:50',
                'pelapor.nama' => 'required|string|max:100',
                'pelapor.tempat_lahir' => $request->input('pelapor.kewarganegaraan') === 'WNI' 
                    ? 'required|string|max:100' 
                    : 'nullable|string|max:100',
                'pelapor.tanggal_lahir' => 'required|date',
                'pelapor.jenis_kelamin' => 'required|in:LAKI-LAKI,PEREMPUAN',
                'pelapor.pekerjaan' => 'required|string|max:100',
                'pelapor.pendidikan' => 'required|string|max:50',
                'pelapor.telepon' => 'required|string|max:30',
                
                // Alamat KTP (WNI only)
                'pelapor.alamat_ktp' => $request->input('pelapor.kewarganegaraan') === 'WNI' ? 'required|array' : 'nullable|array',
                'pelapor.alamat_ktp.kode_provinsi' => $request->input('pelapor.kewarganegaraan') === 'WNI' 
                    ? 'required|exists:wilayah,kode' 
                    : 'nullable',
                'pelapor.alamat_ktp.kode_kabupaten' => $request->input('pelapor.kewarganegaraan') === 'WNI' 
                    ? 'required|exists:wilayah,kode' 
                    : 'nullable',
                'pelapor.alamat_ktp.kode_kecamatan' => $request->input('pelapor.kewarganegaraan') === 'WNI' 
                    ? 'required|exists:wilayah,kode' 
                    : 'nullable',
                'pelapor.alamat_ktp.kode_kelurahan' => $request->input('pelapor.kewarganegaraan') === 'WNI' 
                    ? 'required|exists:wilayah,kode' 
                    : 'nullable',
                'pelapor.alamat_ktp.detail_alamat' => $request->input('pelapor.kewarganegaraan') === 'WNI' 
                    ? 'required|string' 
                    : 'nullable|string',
                
                // Alamat Domisili (required for all)
                'pelapor.alamat_domisili' => 'required|array',
                'pelapor.alamat_domisili.kode_provinsi' => 'required|exists:wilayah,kode',
                'pelapor.alamat_domisili.kode_kabupaten' => 'required|exists:wilayah,kode',
                'pelapor.alamat_domisili.kode_kecamatan' => 'required|exists:wilayah,kode',
                'pelapor.alamat_domisili.kode_kelurahan' => 'required|exists:wilayah,kode',
                'pelapor.alamat_domisili.detail_alamat' => 'required|string',
                
                'hubungan_pelapor' => 'required|in:diri_sendiri,keluarga,kuasa_hukum,teman,rekan_kerja,lainnya',
                
                // Kejadian
                'kategori_kejahatan_id' => 'required|exists:kategori_kejahatan,id',
                'waktu_kejadian' => 'required|date',
                'modus' => 'required|string',
                'catatan' => 'nullable|string',
                'kode_kabupaten_kejadian' => 'required|exists:wilayah,kode',
                'alamat_kejadian' => 'nullable|string',
                
                // Korban array
                'korban' => 'required|array|min:1',
                'korban.*.orang' => 'required|array',
                'korban.*.orang.nik' => 'required|string|max:50',
                'korban.*.orang.nama' => 'required|string|max:100',
                'korban.*.orang.tempat_lahir' => 'nullable|string|max:100',
                'korban.*.orang.tanggal_lahir' => 'nullable|date',
                'korban.*.orang.jenis_kelamin' => 'nullable|in:LAKI-LAKI,PEREMPUAN',
                'korban.*.orang.pekerjaan' => 'nullable|string|max:100',
                'korban.*.orang.pendidikan' => 'nullable|string|max:50',
                'korban.*.orang.telepon' => 'nullable|string|max:30',
                'korban.*.kerugian_nominal' => 'required|numeric|min:0',
                'korban.*.keterangan' => 'nullable|string',
                
                // Tersangka array
                'tersangka' => 'nullable|array',
                'tersangka.*.catatan' => 'nullable|string',
                'tersangka.*.identitas' => 'nullable|array',
                'tersangka.*.identitas.*.jenis' => 'required|string',
                'tersangka.*.identitas.*.nilai' => 'required|string|max:255',
                'tersangka.*.identitas.*.platform' => 'nullable|string|max:100',
            ], [
                'nomor_stpa.unique' => 'Nomor STPA sudah digunakan',
            ]);

            try {
                DB::beginTransaction();

                // 1. Update Pelapor (Orang)
                $pelapor = $laporan->pelapor;
                $pelapor->update([
                    'nik' => $validated['pelapor']['nik'],
                    'nama' => $validated['pelapor']['nama'],
                    'kewarganegaraan' => $validated['pelapor']['kewarganegaraan'],
                    'negara_asal' => $validated['pelapor']['negara_asal'] ?? null,
                    'tempat_lahir' => $validated['pelapor']['tempat_lahir'] ?? null,
                    'tanggal_lahir' => $validated['pelapor']['tanggal_lahir'],
                    'jenis_kelamin' => $validated['pelapor']['jenis_kelamin'],
                    'pekerjaan' => $validated['pelapor']['pekerjaan'],
                    'pendidikan' => $validated['pelapor']['pendidikan'],
                    'telepon' => $validated['pelapor']['telepon'],
                ]);

                // 2. Update Alamat for Pelapor
                if ($validated['pelapor']['kewarganegaraan'] === 'WNI') {
                    // Update Alamat KTP
                    Alamat::updateOrCreate(
                        ['orang_id' => $pelapor->id, 'jenis_alamat' => 'ktp'],
                        [
                            'negara' => 'Indonesia',
                            'kode_provinsi' => $validated['pelapor']['alamat_ktp']['kode_provinsi'] ?? null,
                            'kode_kabupaten' => $validated['pelapor']['alamat_ktp']['kode_kabupaten'] ?? null,
                            'kode_kecamatan' => $validated['pelapor']['alamat_ktp']['kode_kecamatan'] ?? null,
                            'kode_kelurahan' => $validated['pelapor']['alamat_ktp']['kode_kelurahan'] ?? null,
                            'detail_alamat' => $validated['pelapor']['alamat_ktp']['detail_alamat'] ?? '',
                        ]
                    );
                    
                    // Update Alamat Domisili
                    Alamat::updateOrCreate(
                        ['orang_id' => $pelapor->id, 'jenis_alamat' => 'domisili'],
                        [
                            'negara' => 'Indonesia',
                            'kode_provinsi' => $validated['pelapor']['alamat_domisili']['kode_provinsi'] ?? null,
                            'kode_kabupaten' => $validated['pelapor']['alamat_domisili']['kode_kabupaten'] ?? null,
                            'kode_kecamatan' => $validated['pelapor']['alamat_domisili']['kode_kecamatan'] ?? null,
                            'kode_kelurahan' => $validated['pelapor']['alamat_domisili']['kode_kelurahan'] ?? null,
                            'detail_alamat' => $validated['pelapor']['alamat_domisili']['detail_alamat'] ?? '',
                        ]
                    );
                } else {
                    // For WNA: Update Alamat Domisili only
                    Alamat::updateOrCreate(
                        ['orang_id' => $pelapor->id, 'jenis_alamat' => 'domisili'],
                        [
                            'negara' => 'Indonesia',
                            'kode_provinsi' => $validated['pelapor']['alamat_domisili']['kode_provinsi'] ?? null,
                            'kode_kabupaten' => $validated['pelapor']['alamat_domisili']['kode_kabupaten'] ?? null,
                            'kode_kecamatan' => $validated['pelapor']['alamat_domisili']['kode_kecamatan'] ?? null,
                            'kode_kelurahan' => $validated['pelapor']['alamat_domisili']['kode_kelurahan'] ?? null,
                            'detail_alamat' => $validated['pelapor']['alamat_domisili']['detail_alamat'] ?? '',
                        ]
                    );
                }

                // 3. Update Laporan basic fields (including admin fields)
                $laporanUpdate = [
                    'hubungan_pelapor' => $validated['hubungan_pelapor'],
                    'kategori_kejahatan_id' => $validated['kategori_kejahatan_id'],
                    'kode_kabupaten_kejadian' => $validated['kode_kabupaten_kejadian'],
                    'alamat_kejadian' => $validated['alamat_kejadian'] ?? null,
                    'waktu_kejadian' => $validated['waktu_kejadian'],
                    'modus' => $validated['modus'],
                    'catatan' => $validated['catatan'] ?? null,
                    'updated_by' => $user->id,
                ];

                // Admin-specific fields
                if (isset($validated['nomor_stpa'])) {
                    $laporanUpdate['nomor_stpa'] = $validated['nomor_stpa'];
                }
                if (isset($validated['tanggal_laporan'])) {
                    $laporanUpdate['tanggal_laporan'] = $validated['tanggal_laporan'];
                }
                if (isset($validated['petugas_id'])) {
                    $laporanUpdate['petugas_id'] = $validated['petugas_id'];
                }
                if (isset($validated['status'])) {
                    $laporanUpdate['status'] = $validated['status'];
                }
                if (isset($validated['disposisi_unit'])) {
                    $laporanUpdate['disposisi_unit'] = $validated['disposisi_unit'];
                }

                $laporan->update($laporanUpdate);

                // 4. Delete and recreate Korban records
                $laporan->korban()->delete();
                
                foreach ($validated['korban'] as $korbanData) {
                    // Create or find Orang for korban
                    $orangKorban = Orang::updateOrCreate(
                        ['nik' => $korbanData['orang']['nik']],
                        [
                            'nama' => $korbanData['orang']['nama'],
                            'tempat_lahir' => $korbanData['orang']['tempat_lahir'] ?? null,
                            'tanggal_lahir' => $korbanData['orang']['tanggal_lahir'] ?? null,
                            'jenis_kelamin' => $korbanData['orang']['jenis_kelamin'] ?? null,
                            'pekerjaan' => $korbanData['orang']['pekerjaan'] ?? null,
                            'pendidikan' => $korbanData['orang']['pendidikan'] ?? null,
                            'telepon' => $korbanData['orang']['telepon'] ?? null,
                        ]
                    );

                    Korban::create([
                        'laporan_id' => $laporan->id,
                        'orang_id' => $orangKorban->id,
                        'kerugian_nominal' => $korbanData['kerugian_nominal'],
                        'kerugian_terbilang' => TerbilangService::convert($korbanData['kerugian_nominal']),
                        'keterangan' => $korbanData['keterangan'] ?? null,
                    ]);
                }

                // 5. Delete and recreate Tersangka records
                foreach ($laporan->tersangka as $tersangka) {
                    $tersangka->identitas()->delete();
                }
                $laporan->tersangka()->delete();
                
                if (!empty($validated['tersangka'])) {
                    foreach ($validated['tersangka'] as $tersangkaData) {
                        $tersangka = Tersangka::create([
                            'laporan_id' => $laporan->id,
                            'orang_id' => null,
                            'catatan' => $tersangkaData['catatan'] ?? null,
                        ]);

                        if (!empty($tersangkaData['identitas'])) {
                            foreach ($tersangkaData['identitas'] as $identitasData) {
                                IdentitasTersangka::create([
                                    'tersangka_id' => $tersangka->id,
                                    'jenis' => $identitasData['jenis'],
                                    'nilai' => $identitasData['nilai'],
                                    'platform' => $identitasData['platform'] ?? null,
                                ]);
                            }
                        }
                    }
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Laporan berhasil diperbarui',
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error updating laporan (AdminSubdit): ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memperbarui laporan: ' . $e->getMessage(),
                ], 500);
            }
        } else {
            // Simple update (original behavior for status/unit changes only)
            $validated = $request->validate([
                'nomor_stpa' => 'nullable|string|max:50|unique:laporan,nomor_stpa,' . $id,
                'tanggal_laporan' => 'sometimes|date',
                'petugas_id' => 'sometimes|exists:users,id',
                'kategori_kejahatan_id' => 'sometimes|exists:kategori_kejahatan,id',
                'kode_provinsi_kejadian' => 'nullable|exists:wilayah,kode',
                'kode_kabupaten_kejadian' => 'nullable|exists:wilayah,kode',
                'kode_kecamatan_kejadian' => 'nullable|exists:wilayah,kode',
                'kode_kelurahan_kejadian' => 'nullable|exists:wilayah,kode',
                'alamat_kejadian' => 'nullable|string',
                'waktu_kejadian' => 'nullable|date',
                'modus' => 'nullable|string',
                'status' => ['sometimes', Rule::in([
                    Laporan::STATUS_PENYELIDIKAN,
                    Laporan::STATUS_PENYIDIKAN,
                    Laporan::STATUS_TAHAP_I,
                    Laporan::STATUS_TAHAP_II,
                    Laporan::STATUS_SP3,
                    Laporan::STATUS_RJ,
                    Laporan::STATUS_DIVERSI,
                ])],
                'disposisi_unit' => 'nullable|integer|min:1|max:5',
                'catatan' => 'nullable|string',
            ], [
                'nomor_stpa.unique' => 'Nomor STPA sudah digunakan',
            ]);

            try {
                $validated['updated_by'] = $user->id;
                $laporan->update($validated);

                return response()->json([
                    'success' => true,
                    'message' => 'Laporan berhasil diperbarui',
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memperbarui laporan: ' . $e->getMessage(),
                ], 500);
            }
        }
    }
}
