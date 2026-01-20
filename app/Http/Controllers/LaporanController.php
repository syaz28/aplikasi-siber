<?php

namespace App\Http\Controllers;

use App\Models\Alamat;
use App\Models\IdentitasTersangka;
use App\Models\Korban;
use App\Models\Lampiran;
use App\Models\Laporan;
use App\Models\Orang;
use App\Models\Tersangka;
use App\Services\StpaFpdiService;
use App\Services\TerbilangService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * LaporanController
 * 
 * Handles CRUD operations for Laporan Kejahatan Siber
 * Supports multi-step form submission with related entities
 */
class LaporanController extends Controller
{
    /**
     * Eager load relationships for laporan queries
     */
    protected array $eagerLoads = [
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
        // Jenis kejahatan dengan kategori
        'jenisKejahatan.kategori',
        // Petugas dengan pangkat dan jabatan
        'petugas.pangkat',
        'petugas.jabatan',
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
    ];

    /**
     * Display a listing of laporan with pagination.
     */
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $query = Laporan::with([
            'pelapor',
            'korban.orang',
            'tersangka',
            'jenisKejahatan.kategori',
            'petugas.pangkat',
        ]);

        // Search filters
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nomor_stpa', 'like', "%{$search}%")
                    ->orWhereHas('pelapor', fn($q) => $q->where('nama', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('jenis_kejahatan_id')) {
            $query->where('jenis_kejahatan_id', $request->input('jenis_kejahatan_id'));
        }

        if ($request->filled('tanggal_dari') && $request->filled('tanggal_sampai')) {
            $query->whereBetween('tanggal_laporan', [
                $request->input('tanggal_dari'),
                $request->input('tanggal_sampai'),
            ]);
        }

        if ($request->filled('kode_provinsi')) {
            $query->where('kode_provinsi_kejadian', $request->input('kode_provinsi'));
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'tanggal_laporan');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        // Pagination
        $perPage = $request->input('per_page', 15);
        $laporan = $query->paginate($perPage)->withQueryString();

        // Return JSON for API calls
        if ($request->wantsJson()) {
            return response()->json($laporan);
        }

        // Return Inertia response for page render
        return Inertia::render('Laporan/Index', [
            'laporan' => $laporan,
            'filters' => $request->only(['search', 'status', 'jenis_kejahatan_id', 'tanggal_dari', 'tanggal_sampai']),
            'statusOptions' => Laporan::getStatusOptions(),
        ]);
    }

    /**
     * Show the form for creating a new laporan.
     */
    public function create(): InertiaResponse
    {
        return Inertia::render('Laporan/Create', [
            'statusOptions' => Laporan::getStatusOptions(),
            'hubunganPelaporOptions' => Laporan::getHubunganPelaporOptions(),
            'jenisIdentitasOptions' => IdentitasTersangka::getJenisOptions(),
            'jenisFileOptions' => Lampiran::getJenisFileOptions(),
        ]);
    }

    /**
     * Store a newly created laporan.
     * 
     * Handles multi-step form data with transaction:
     * - Step 1: Administrasi (STPA, petugas)
     * - Step 2: Pelapor + Alamat
     * - Step 3: Laporan + Korban (1:N)
     * - Step 4: Tersangka (1:N) + Identitas (1:N) + Lampiran
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            // Step 1: Administrasi
            'nomor_stpa' => 'nullable|string|max:50|unique:laporan,nomor_stpa',
            'tanggal_laporan' => 'required|date',
            'petugas_id' => 'required|exists:anggota,id',
            
            // Step 2: Pelapor
            'pelapor' => 'required|array',
            'pelapor.nik' => 'required|string|size:16',
            'pelapor.nama' => 'required|string|max:100',
            'pelapor.tempat_lahir' => 'required|string|max:100',
            'pelapor.tanggal_lahir' => 'required|date',
            'pelapor.jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'pelapor.pekerjaan' => 'required|string|max:100',
            'pelapor.telepon' => 'required|string|max:20',
            'pelapor.email' => 'nullable|email|max:100',
            
            // Alamat pelapor
            'pelapor.alamat_ktp' => 'required|array',
            'pelapor.alamat_ktp.kode_provinsi' => 'required|exists:wilayah,kode',
            'pelapor.alamat_ktp.kode_kabupaten' => 'required|exists:wilayah,kode',
            'pelapor.alamat_ktp.kode_kecamatan' => 'required|exists:wilayah,kode',
            'pelapor.alamat_ktp.kode_kelurahan' => 'required|exists:wilayah,kode',
            'pelapor.alamat_ktp.detail_alamat' => 'required|string',
            
            'hubungan_pelapor' => 'required|in:diri_sendiri,keluarga,kuasa_hukum,teman,rekan_kerja,lainnya',
            
            // Step 3: Laporan + Korban
            'jenis_kejahatan_id' => 'required|exists:jenis_kejahatan,id',
            'waktu_kejadian' => 'required|date',
            'modus' => 'required|string',
            'catatan' => 'nullable|string',
            
            // Lokasi kejadian (optional)
            'kode_provinsi_kejadian' => 'nullable|exists:wilayah,kode',
            'kode_kabupaten_kejadian' => 'nullable|exists:wilayah,kode',
            'kode_kecamatan_kejadian' => 'nullable|exists:wilayah,kode',
            'kode_kelurahan_kejadian' => 'nullable|exists:wilayah,kode',
            'alamat_kejadian' => 'nullable|string',
            
            // Korban (array of victims)
            'korban' => 'required|array|min:1',
            'korban.*.orang' => 'required|array',
            'korban.*.orang.nik' => 'required|string|size:16',
            'korban.*.orang.nama' => 'required|string|max:100',
            'korban.*.orang.tempat_lahir' => 'required|string|max:100',
            'korban.*.orang.tanggal_lahir' => 'required|date',
            'korban.*.orang.jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'korban.*.orang.pekerjaan' => 'required|string|max:100',
            'korban.*.orang.telepon' => 'required|string|max:20',
            'korban.*.kerugian_nominal' => 'required|numeric|min:0',
            'korban.*.keterangan' => 'nullable|string',
            
            // Step 4: Tersangka
            'tersangka' => 'nullable|array',
            'tersangka.*.orang' => 'nullable|array',
            'tersangka.*.catatan' => 'nullable|string',
            'tersangka.*.identitas' => 'nullable|array',
            'tersangka.*.identitas.*.jenis' => 'required|string',
            'tersangka.*.identitas.*.nilai' => 'required|string|max:255',
            'tersangka.*.identitas.*.platform' => 'nullable|string|max:100',
            'tersangka.*.identitas.*.nama_akun' => 'nullable|string|max:100',
        ]);

        try {
            DB::beginTransaction();

            // 1. Create or find Pelapor (Orang)
            $pelapor = Orang::updateOrCreate(
                ['nik' => $validated['pelapor']['nik']],
                [
                    'nama' => $validated['pelapor']['nama'],
                    'tempat_lahir' => $validated['pelapor']['tempat_lahir'],
                    'tanggal_lahir' => $validated['pelapor']['tanggal_lahir'],
                    'jenis_kelamin' => $validated['pelapor']['jenis_kelamin'],
                    'pekerjaan' => $validated['pelapor']['pekerjaan'],
                    'telepon' => $validated['pelapor']['telepon'],
                    'email' => $validated['pelapor']['email'] ?? null,
                ]
            );

            // 2. Create/Update Alamat KTP for Pelapor
            Alamat::updateOrCreate(
                ['orang_id' => $pelapor->id, 'jenis_alamat' => 'ktp'],
                [
                    'kode_provinsi' => $validated['pelapor']['alamat_ktp']['kode_provinsi'],
                    'kode_kabupaten' => $validated['pelapor']['alamat_ktp']['kode_kabupaten'],
                    'kode_kecamatan' => $validated['pelapor']['alamat_ktp']['kode_kecamatan'],
                    'kode_kelurahan' => $validated['pelapor']['alamat_ktp']['kode_kelurahan'],
                    'detail_alamat' => $validated['pelapor']['alamat_ktp']['detail_alamat'],
                ]
            );

            // 3. Create Laporan
            $laporan = Laporan::create([
                'nomor_stpa' => $validated['nomor_stpa'] ?? null,
                'tanggal_laporan' => $validated['tanggal_laporan'],
                'pelapor_id' => $pelapor->id,
                'hubungan_pelapor' => $validated['hubungan_pelapor'],
                'petugas_id' => $validated['petugas_id'],
                'jenis_kejahatan_id' => $validated['jenis_kejahatan_id'],
                'kode_provinsi_kejadian' => $validated['kode_provinsi_kejadian'] ?? null,
                'kode_kabupaten_kejadian' => $validated['kode_kabupaten_kejadian'] ?? null,
                'kode_kecamatan_kejadian' => $validated['kode_kecamatan_kejadian'] ?? null,
                'kode_kelurahan_kejadian' => $validated['kode_kelurahan_kejadian'] ?? null,
                'alamat_kejadian' => $validated['alamat_kejadian'] ?? null,
                'waktu_kejadian' => $validated['waktu_kejadian'],
                'modus' => $validated['modus'],
                'status' => Laporan::STATUS_DRAFT,
                'catatan' => $validated['catatan'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // 4. Create Korban records
            foreach ($validated['korban'] as $korbanData) {
                // Create or find Orang for korban
                $orangKorban = Orang::updateOrCreate(
                    ['nik' => $korbanData['orang']['nik']],
                    [
                        'nama' => $korbanData['orang']['nama'],
                        'tempat_lahir' => $korbanData['orang']['tempat_lahir'],
                        'tanggal_lahir' => $korbanData['orang']['tanggal_lahir'],
                        'jenis_kelamin' => $korbanData['orang']['jenis_kelamin'],
                        'pekerjaan' => $korbanData['orang']['pekerjaan'],
                        'telepon' => $korbanData['orang']['telepon'],
                        'email' => $korbanData['orang']['email'] ?? null,
                    ]
                );

                // Create Korban record
                Korban::create([
                    'laporan_id' => $laporan->id,
                    'orang_id' => $orangKorban->id,
                    'kerugian_nominal' => $korbanData['kerugian_nominal'],
                    'kerugian_terbilang' => TerbilangService::convert($korbanData['kerugian_nominal']),
                    'keterangan' => $korbanData['keterangan'] ?? null,
                ]);
            }

            // 5. Create Tersangka records (if provided)
            if (!empty($validated['tersangka'])) {
                foreach ($validated['tersangka'] as $tersangkaData) {
                    $orangTersangka = null;

                    // Create Orang for tersangka if data provided
                    if (!empty($tersangkaData['orang']) && !empty($tersangkaData['orang']['nik'])) {
                        $orangTersangka = Orang::updateOrCreate(
                            ['nik' => $tersangkaData['orang']['nik']],
                            [
                                'nama' => $tersangkaData['orang']['nama'],
                                'tempat_lahir' => $tersangkaData['orang']['tempat_lahir'],
                                'tanggal_lahir' => $tersangkaData['orang']['tanggal_lahir'],
                                'jenis_kelamin' => $tersangkaData['orang']['jenis_kelamin'],
                                'pekerjaan' => $tersangkaData['orang']['pekerjaan'],
                                'telepon' => $tersangkaData['orang']['telepon'],
                                'email' => $tersangkaData['orang']['email'] ?? null,
                            ]
                        );
                    }

                    // Create Tersangka record
                    $tersangka = Tersangka::create([
                        'laporan_id' => $laporan->id,
                        'orang_id' => $orangTersangka?->id,
                        'catatan' => $tersangkaData['catatan'] ?? null,
                    ]);

                    // Create IdentitasTersangka records
                    if (!empty($tersangkaData['identitas'])) {
                        foreach ($tersangkaData['identitas'] as $identitasData) {
                            IdentitasTersangka::create([
                                'tersangka_id' => $tersangka->id,
                                'jenis' => $identitasData['jenis'],
                                'nilai' => $identitasData['nilai'],
                                'platform' => $identitasData['platform'] ?? null,
                                'nama_akun' => $identitasData['nama_akun'] ?? null,
                                'catatan' => $identitasData['catatan'] ?? null,
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            // Load relationships for response
            $laporan->load($this->eagerLoads);

            return response()->json([
                'success' => true,
                'message' => 'Laporan berhasil disimpan',
                'data' => $laporan,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating laporan: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan laporan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified laporan.
     */
    public function show(Request $request, int $id): InertiaResponse|JsonResponse
    {
        $laporan = Laporan::with($this->eagerLoads)->findOrFail($id);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $laporan,
            ]);
        }

        return Inertia::render('Laporan/Show', [
            'laporan' => $laporan,
        ]);
    }

    /**
     * Show the form for editing the specified laporan.
     */
    public function edit(int $id): InertiaResponse
    {
        $laporan = Laporan::with($this->eagerLoads)->findOrFail($id);

        return Inertia::render('Laporan/Edit', [
            'laporan' => $laporan,
            'statusOptions' => Laporan::getStatusOptions(),
            'hubunganPelaporOptions' => Laporan::getHubunganPelaporOptions(),
            'jenisIdentitasOptions' => IdentitasTersangka::getJenisOptions(),
            'jenisFileOptions' => Lampiran::getJenisFileOptions(),
        ]);
    }

    /**
     * Update the specified laporan.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $laporan = Laporan::findOrFail($id);

        $validated = $request->validate([
            'nomor_stpa' => 'nullable|string|max:50|unique:laporan,nomor_stpa,' . $id,
            'tanggal_laporan' => 'sometimes|date',
            'hubungan_pelapor' => 'sometimes|in:diri_sendiri,keluarga,kuasa_hukum,teman,rekan_kerja,lainnya',
            'petugas_id' => 'sometimes|exists:anggota,id',
            'jenis_kejahatan_id' => 'sometimes|exists:jenis_kejahatan,id',
            'kode_provinsi_kejadian' => 'nullable|exists:wilayah,kode',
            'kode_kabupaten_kejadian' => 'nullable|exists:wilayah,kode',
            'kode_kecamatan_kejadian' => 'nullable|exists:wilayah,kode',
            'kode_kelurahan_kejadian' => 'nullable|exists:wilayah,kode',
            'alamat_kejadian' => 'nullable|string',
            'waktu_kejadian' => 'sometimes|date',
            'modus' => 'sometimes|string',
            'status' => 'sometimes|in:draft,submitted,verified,investigating,closed,rejected',
            'catatan' => 'nullable|string',
        ]);

        try {
            $validated['updated_by'] = auth()->id();
            $laporan->update($validated);

            $laporan->load($this->eagerLoads);

            return response()->json([
                'success' => true,
                'message' => 'Laporan berhasil diperbarui',
                'data' => $laporan,
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating laporan: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui laporan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified laporan.
     */
    public function destroy(int $id): JsonResponse
    {
        $laporan = Laporan::findOrFail($id);

        try {
            DB::beginTransaction();

            // Delete attachments from storage
            foreach ($laporan->lampiran as $lampiran) {
                Storage::delete($lampiran->path_file);
            }

            // Delete laporan (cascades to korban, tersangka, identitas, lampiran)
            $laporan->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Laporan berhasil dihapus',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting laporan: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus laporan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate STPA PDF for the specified laporan.
     */
    public function cetakPdf(int $id)
    {
        $laporan = Laporan::with($this->eagerLoads)->findOrFail($id);

        try {
            $pdfService = new StpaFpdiService();
            $pdfContent = $pdfService->generate($laporan);
            
            $filename = 'STPA-' . ($laporan->nomor_stpa ?? $laporan->id) . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
                
        } catch (\Exception $e) {
            Log::error('Error generating PDF: ' . $e->getMessage(), [
                'laporan_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal generate PDF: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search for suspect linkage across reports.
     * 
     * Finds other reports that have the same suspect identity
     * (phone number, bank account, social media, etc.)
     */
    public function searchSuspect(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'jenis' => 'required|string',
            'nilai' => 'required|string',
        ]);

        $linkedReports = IdentitasTersangka::where('jenis', $validated['jenis'])
            ->where('nilai', $validated['nilai'])
            ->with([
                'tersangka.laporan' => fn($q) => $q->with([
                    'jenisKejahatan.kategori',
                    'pelapor',
                    'korban',
                ]),
                'tersangka.orang',
            ])
            ->get();

        // Format response
        $results = $linkedReports->map(function ($identitas) {
            $laporan = $identitas->tersangka->laporan;
            return [
                'identitas_id' => $identitas->id,
                'tersangka_id' => $identitas->tersangka_id,
                'laporan_id' => $laporan->id,
                'nomor_stpa' => $laporan->nomor_stpa,
                'tanggal_laporan' => $laporan->tanggal_laporan->format('Y-m-d'),
                'jenis_kejahatan' => $laporan->jenisKejahatan->nama ?? '-',
                'kategori' => $laporan->jenisKejahatan->kategori->nama ?? '-',
                'nama_pelapor' => $laporan->pelapor->nama ?? '-',
                'jumlah_korban' => $laporan->korban->count(),
                'total_kerugian' => $laporan->korban->sum('kerugian_nominal'),
                'tersangka_nama' => $identitas->tersangka->orang?->nama ?? 'Belum Teridentifikasi',
                'identitas' => [
                    'jenis' => $identitas->jenis,
                    'nilai' => $identitas->nilai,
                    'platform' => $identitas->platform,
                    'nama_akun' => $identitas->nama_akun,
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'count' => $results->count(),
            'data' => $results,
            'message' => $results->count() > 0
                ? "Ditemukan {$results->count()} laporan terkait dengan identitas ini"
                : 'Tidak ditemukan laporan terkait',
        ]);
    }

    /**
     * Upload attachment for a laporan.
     */
    public function uploadLampiran(Request $request, int $id): JsonResponse
    {
        $laporan = Laporan::findOrFail($id);

        $validated = $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'jenis_file' => 'required|in:gambar,dokumen,screenshot,video,audio,lainnya',
            'deskripsi' => 'nullable|string|max:500',
        ]);

        try {
            $file = $request->file('file');
            $path = $file->store('lampiran/' . $laporan->id, 'public');

            $lampiran = Lampiran::create([
                'laporan_id' => $laporan->id,
                'nama_file' => $file->getClientOriginalName(),
                'path_file' => $path,
                'jenis_file' => $validated['jenis_file'],
                'ukuran_file' => $file->getSize(),
                'deskripsi' => $validated['deskripsi'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Lampiran berhasil diupload',
                'data' => $lampiran,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error uploading lampiran: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal upload lampiran: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete attachment from a laporan.
     */
    public function deleteLampiran(int $id, int $lampiranId): JsonResponse
    {
        $lampiran = Lampiran::where('laporan_id', $id)
            ->where('id', $lampiranId)
            ->firstOrFail();

        try {
            Storage::disk('public')->delete($lampiran->path_file);
            $lampiran->delete();

            return response()->json([
                'success' => true,
                'message' => 'Lampiran berhasil dihapus',
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting lampiran: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus lampiran: ' . $e->getMessage(),
            ], 500);
        }
    }
}
