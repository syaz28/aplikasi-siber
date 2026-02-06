<?php

namespace App\Http\Controllers;

use App\Models\Tersangka;
use App\Models\IdentitasTersangka;
use App\Models\Orang;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * TersangkaController
 * 
 * Handles listing and display of suspects (tersangka) in the system.
 * This is separate from Orang as Tersangka may not have identified personal data.
 * 
 * Features:
 * - List all suspects with search/filter
 * - Show detailed suspect information with:
 *   - Digital identities (rekening, telepon, sosmed, etc.)
 *   - Linked reports
 *   - Related suspects (same identity across different reports)
 *   - Statistics and summary
 */
class TersangkaController extends Controller
{
    /**
     * Display a listing of suspects with filtering.
     */
    public function index(Request $request): InertiaResponse
    {
        $search = $request->input('search', '');
        $jenisFilter = $request->input('jenis', ''); // Filter by identity type
        $statusFilter = $request->input('status', ''); // identified / unidentified
        $tab = $request->input('tab', 'all'); // all / linked
        $perPage = 15;

        // Get statistics
        $stats = $this->getStatistics();

        // Get linked suspects data (for both tabs - to show stats)
        $linkedData = $this->getLinkedSuspects();

        // Build query
        $query = Tersangka::query()
            ->with([
                'laporan:id,nomor_stpa,tanggal_laporan,status,kategori_kejahatan_id',
                'laporan.kategoriKejahatan:id,nama',
                'orang:id,nik,nama,jenis_kelamin,pekerjaan',
                'identitas',
            ])
            ->withCount('identitas');

        // If tab is 'linked', only show tersangka that have linked identities
        if ($tab === 'linked') {
            $linkedTersangkaIds = collect($linkedData['groups'])->flatMap(function ($group) {
                return collect($group['tersangka'])->pluck('id');
            })->unique()->values()->toArray();
            
            $query->whereIn('id', $linkedTersangkaIds);
        }

        // Apply status filter
        if ($statusFilter === 'identified') {
            $query->whereNotNull('orang_id');
        } elseif ($statusFilter === 'unidentified') {
            $query->whereNull('orang_id');
        }

        // Apply identity type filter
        if ($jenisFilter) {
            $query->whereHas('identitas', function ($q) use ($jenisFilter) {
                $q->where('jenis', $jenisFilter);
            });
        }

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                // Search in orang nama
                $q->whereHas('orang', function ($oq) use ($search) {
                    $oq->where('nama', 'like', "%{$search}%")
                       ->orWhere('nik', 'like', "%{$search}%");
                })
                // Or search in identitas nilai
                ->orWhereHas('identitas', function ($iq) use ($search) {
                    $iq->where('nilai', 'like', "%{$search}%")
                       ->orWhere('nama_akun', 'like', "%{$search}%")
                       ->orWhere('platform', 'like', "%{$search}%");
                })
                // Or search in laporan
                ->orWhereHas('laporan', function ($lq) use ($search) {
                    $lq->where('nomor_stpa', 'like', "%{$search}%");
                })
                // Or search in catatan
                ->orWhere('catatan', 'like', "%{$search}%");
            });
        }

        // Get paginated results
        $tersangka = $query->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        // Get jenis options for filter dropdown
        $jenisOptions = IdentitasTersangka::getJenisOptions();

        return Inertia::render('Tersangka/Index', [
            'tersangka' => $tersangka,
            'filters' => [
                'search' => $search,
                'jenis' => $jenisFilter,
                'status' => $statusFilter,
                'tab' => $tab,
            ],
            'stats' => $stats,
            'linkedStats' => [
                'total_groups' => count($linkedData['groups']),
                'total_linked_tersangka' => $linkedData['total_linked_tersangka'],
                'total_shared_identities' => $linkedData['total_shared_identities'],
            ],
            'linkedGroups' => $tab === 'linked' ? $linkedData['groups'] : [],
            'jenisOptions' => $jenisOptions,
        ]);
    }

    /**
     * Get suspects that share the same digital identities.
     * Groups them by shared identity values.
     */
    private function getLinkedSuspects(): array
    {
        // Find identity values that appear more than once
        $duplicateIdentities = IdentitasTersangka::select('jenis', 'nilai', DB::raw('COUNT(*) as count'))
            ->groupBy('jenis', 'nilai')
            ->having('count', '>', 1)
            ->get();

        $groups = [];
        $linkedTersangkaIds = collect();

        foreach ($duplicateIdentities as $dup) {
            // Get all tersangka with this identity
            $matchingIdentities = IdentitasTersangka::where('jenis', $dup->jenis)
                ->where('nilai', $dup->nilai)
                ->with([
                    'tersangka:id,orang_id,laporan_id,catatan,created_at',
                    'tersangka.orang:id,nama,nik,jenis_kelamin',
                    'tersangka.laporan:id,nomor_stpa,tanggal_laporan,status',
                ])
                ->get();

            $tersangkaInGroup = $matchingIdentities->map(function ($identity) {
                return [
                    'id' => $identity->tersangka->id,
                    'nama' => $identity->tersangka->orang?->nama ?? 'Belum Teridentifikasi',
                    'nik' => $identity->tersangka->orang?->nik,
                    'is_identified' => !is_null($identity->tersangka->orang_id),
                    'laporan_id' => $identity->tersangka->laporan?->id,
                    'nomor_stpa' => $identity->tersangka->laporan?->nomor_stpa,
                    'tanggal_laporan' => $identity->tersangka->laporan?->tanggal_laporan,
                ];
            })->unique('id')->values();

            $linkedTersangkaIds = $linkedTersangkaIds->merge($tersangkaInGroup->pluck('id'));

            $groups[] = [
                'identity' => [
                    'jenis' => $dup->jenis,
                    'nilai' => $dup->nilai,
                    'count' => $dup->count,
                ],
                'tersangka' => $tersangkaInGroup->toArray(),
            ];
        }

        return [
            'groups' => $groups,
            'total_linked_tersangka' => $linkedTersangkaIds->unique()->count(),
            'total_shared_identities' => count($groups),
        ];
    }

    /**
     * Display the specified suspect with full details.
     */
    public function show($id): InertiaResponse
    {
        $tersangka = Tersangka::with([
            'laporan.kategoriKejahatan',
            'laporan.pelapor',
            'laporan.korban.orang',
            'orang.alamatKtp.provinsi',
            'orang.alamatKtp.kabupaten',
            'orang.alamatKtp.kecamatan',
            'orang.alamatKtp.kelurahan',
            'identitas',
        ])->findOrFail($id);

        // Find related suspects (same identity values across different reports)
        $relatedSuspects = $this->findRelatedSuspects($tersangka);

        // Get identity link analysis
        $identityAnalysis = $this->analyzeIdentities($tersangka);

        // Get timeline of reports
        $timeline = $this->getReportTimeline($tersangka);

        return Inertia::render('Tersangka/Show', [
            'tersangka' => $tersangka,
            'relatedSuspects' => $relatedSuspects,
            'identityAnalysis' => $identityAnalysis,
            'timeline' => $timeline,
        ]);
    }

    /**
     * Get statistics for the index page.
     */
    private function getStatistics(): array
    {
        return [
            'total' => Tersangka::count(),
            'identified' => Tersangka::whereNotNull('orang_id')->count(),
            'unidentified' => Tersangka::whereNull('orang_id')->count(),
            'total_identities' => IdentitasTersangka::count(),
            'by_identity_type' => IdentitasTersangka::select('jenis', DB::raw('COUNT(*) as count'))
                ->groupBy('jenis')
                ->pluck('count', 'jenis')
                ->toArray(),
        ];
    }

    /**
     * Find related suspects based on matching identities.
     */
    private function findRelatedSuspects(Tersangka $tersangka): array
    {
        $relatedIds = collect();

        foreach ($tersangka->identitas as $identity) {
            // Find other identities with same jenis+nilai
            $matchingIdentities = IdentitasTersangka::where('jenis', $identity->jenis)
                ->where('nilai', $identity->nilai)
                ->where('tersangka_id', '!=', $tersangka->id)
                ->with(['tersangka.laporan:id,nomor_stpa,tanggal_laporan,status'])
                ->get();

            foreach ($matchingIdentities as $match) {
                $relatedIds->push([
                    'tersangka_id' => $match->tersangka_id,
                    'matched_on' => [
                        'jenis' => $identity->jenis,
                        'nilai' => $identity->nilai,
                        'platform' => $identity->platform,
                    ],
                    'laporan' => $match->tersangka->laporan,
                ]);
            }
        }

        return $relatedIds->unique('tersangka_id')->values()->toArray();
    }

    /**
     * Analyze identities to find patterns and links.
     */
    private function analyzeIdentities(Tersangka $tersangka): array
    {
        $analysis = [];

        foreach ($tersangka->identitas as $identity) {
            // Count how many times this identity appears in other reports
            $occurrences = IdentitasTersangka::where('jenis', $identity->jenis)
                ->where('nilai', $identity->nilai)
                ->count();

            $analysis[] = [
                'id' => $identity->id,
                'jenis' => $identity->jenis,
                'jenis_label' => $identity->jenis_label,
                'nilai' => $identity->nilai,
                'platform' => $identity->platform,
                'nama_akun' => $identity->nama_akun,
                'catatan' => $identity->catatan,
                'total_occurrences' => $occurrences,
                'is_repeated' => $occurrences > 1,
            ];
        }

        return $analysis;
    }

    /**
     * Get timeline of reports involving this suspect.
     */
    private function getReportTimeline(Tersangka $tersangka): array
    {
        // Get all reports from related suspects too
        $relatedTersangkaIds = collect([$tersangka->id]);

        foreach ($tersangka->identitas as $identity) {
            $matchingIds = IdentitasTersangka::where('jenis', $identity->jenis)
                ->where('nilai', $identity->nilai)
                ->pluck('tersangka_id');
            
            $relatedTersangkaIds = $relatedTersangkaIds->merge($matchingIds);
        }

        $reports = Tersangka::whereIn('id', $relatedTersangkaIds->unique())
            ->with(['laporan:id,nomor_stpa,tanggal_laporan,status,kategori_kejahatan_id', 'laporan.kategoriKejahatan:id,nama'])
            ->get()
            ->pluck('laporan')
            ->unique('id')
            ->sortByDesc('tanggal_laporan')
            ->values()
            ->map(function ($laporan) use ($tersangka) {
                return [
                    'id' => $laporan->id,
                    'nomor_stpa' => $laporan->nomor_stpa,
                    'tanggal_laporan' => $laporan->tanggal_laporan,
                    'status' => $laporan->status,
                    'kategori' => $laporan->kategoriKejahatan?->nama,
                    'is_current' => $laporan->id === $tersangka->laporan_id,
                ];
            })
            ->toArray();

        return $reports;
    }

    /**
     * Identify a suspect - create or link to Orang record.
     * 
     * Minimal required fields: NIK, Nama, Jenis Kelamin
     * Optional fields can be filled later
     */
    public function identify(Request $request, $id): JsonResponse
    {
        $tersangka = Tersangka::findOrFail($id);

        // If already identified, return error
        if ($tersangka->orang_id) {
            return response()->json([
                'success' => false,
                'message' => 'Tersangka sudah teridentifikasi sebelumnya',
            ], 422);
        }

        // Validate minimal required fields
        $validated = $request->validate([
            'nik' => [
                'required',
                'string',
                'size:16',
                'regex:/^[0-9]{16}$/',
            ],
            'nama' => 'required|string|max:100',
            'jenis_kelamin' => 'required|in:LAKI-LAKI,PEREMPUAN',
            // Optional fields
            'tempat_lahir' => 'nullable|string|max:100',
            'tanggal_lahir' => 'nullable|date',
            'pekerjaan' => 'nullable|string|max:100',
            'telepon' => 'nullable|string|max:30',
        ], [
            'nik.required' => 'NIK wajib diisi',
            'nik.size' => 'NIK harus tepat 16 digit',
            'nik.regex' => 'NIK harus berupa angka',
            'nama.required' => 'Nama wajib diisi',
            'jenis_kelamin.required' => 'Jenis kelamin wajib dipilih',
            'jenis_kelamin.in' => 'Jenis kelamin harus LAKI-LAKI atau PEREMPUAN',
        ]);

        try {
            DB::beginTransaction();

            // Check if Orang with this NIK already exists
            $existingOrang = Orang::where('nik', $validated['nik'])->first();

            if ($existingOrang) {
                // Link to existing Orang
                $tersangka->orang_id = $existingOrang->id;
                $tersangka->save();

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Tersangka berhasil diidentifikasi (terhubung dengan data orang yang sudah ada)',
                    'orang' => $existingOrang,
                    'is_existing' => true,
                ]);
            }

            // Create new Orang record with minimal data
            $orangData = [
                'nik' => strtoupper($validated['nik']),
                'nama' => strtoupper($validated['nama']),
                'jenis_kelamin' => $validated['jenis_kelamin'],
                'tempat_lahir' => strtoupper($validated['tempat_lahir'] ?? 'TIDAK DIKETAHUI'),
                'tanggal_lahir' => $validated['tanggal_lahir'] ?? '1900-01-01', // Placeholder
                'pekerjaan' => strtoupper($validated['pekerjaan'] ?? 'TIDAK DIKETAHUI'),
                'telepon' => $validated['telepon'] ?? '0000000000', // Placeholder
            ];

            $orang = Orang::create($orangData);

            // Link tersangka to the new Orang
            $tersangka->orang_id = $orang->id;
            $tersangka->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tersangka berhasil diidentifikasi',
                'orang' => $orang,
                'is_existing' => false,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengidentifikasi tersangka: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search existing Orang by NIK for linking.
     */
    public function searchOrang(Request $request): JsonResponse
    {
        $nik = $request->input('nik', '');

        if (strlen($nik) < 6) {
            return response()->json([
                'success' => true,
                'data' => null,
            ]);
        }

        $orang = Orang::where('nik', $nik)->first();

        return response()->json([
            'success' => true,
            'data' => $orang,
        ]);
    }

    /**
     * Unlink tersangka from orang (remove identification).
     */
    public function unidentify($id): JsonResponse
    {
        $tersangka = Tersangka::findOrFail($id);

        if (!$tersangka->orang_id) {
            return response()->json([
                'success' => false,
                'message' => 'Tersangka belum teridentifikasi',
            ], 422);
        }

        $tersangka->orang_id = null;
        $tersangka->save();

        return response()->json([
            'success' => true,
            'message' => 'Identifikasi tersangka berhasil dihapus',
        ]);
    }

    /**
     * Add a new identity to tersangka.
     */
    public function addIdentity(Request $request, $id): JsonResponse
    {
        $tersangka = Tersangka::findOrFail($id);

        $validated = $request->validate([
            'jenis' => 'required|in:telepon,rekening,sosmed,email,ewallet,kripto,marketplace,website,lainnya',
            'nilai' => 'required|string|max:255',
            'platform' => 'nullable|string|max:100',
            'nama_akun' => 'nullable|string|max:100',
            'catatan' => 'nullable|string|max:500',
        ], [
            'jenis.required' => 'Jenis identitas wajib dipilih',
            'jenis.in' => 'Jenis identitas tidak valid',
            'nilai.required' => 'Nilai identitas wajib diisi',
        ]);

        try {
            $identity = IdentitasTersangka::create([
                'tersangka_id' => $tersangka->id,
                'jenis' => $validated['jenis'],
                'nilai' => strtoupper($validated['nilai']),
                'platform' => $validated['platform'] ? strtoupper($validated['platform']) : null,
                'nama_akun' => $validated['nama_akun'] ?? null,
                'catatan' => $validated['catatan'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Identitas digital berhasil ditambahkan',
                'identity' => $identity,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan identitas: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an existing identity.
     */
    public function updateIdentity(Request $request, $id, $identityId): JsonResponse
    {
        $tersangka = Tersangka::findOrFail($id);
        $identity = IdentitasTersangka::where('tersangka_id', $tersangka->id)
            ->where('id', $identityId)
            ->firstOrFail();

        $validated = $request->validate([
            'jenis' => 'required|in:telepon,rekening,sosmed,email,ewallet,kripto,marketplace,website,lainnya',
            'nilai' => 'required|string|max:255',
            'platform' => 'nullable|string|max:100',
            'nama_akun' => 'nullable|string|max:100',
            'catatan' => 'nullable|string|max:500',
        ], [
            'jenis.required' => 'Jenis identitas wajib dipilih',
            'jenis.in' => 'Jenis identitas tidak valid',
            'nilai.required' => 'Nilai identitas wajib diisi',
        ]);

        try {
            $identity->update([
                'jenis' => $validated['jenis'],
                'nilai' => strtoupper($validated['nilai']),
                'platform' => $validated['platform'] ? strtoupper($validated['platform']) : null,
                'nama_akun' => $validated['nama_akun'] ?? null,
                'catatan' => $validated['catatan'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Identitas digital berhasil diperbarui',
                'identity' => $identity->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui identitas: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete an identity.
     */
    public function deleteIdentity($id, $identityId): JsonResponse
    {
        $tersangka = Tersangka::findOrFail($id);
        $identity = IdentitasTersangka::where('tersangka_id', $tersangka->id)
            ->where('id', $identityId)
            ->firstOrFail();

        try {
            $identity->delete();

            return response()->json([
                'success' => true,
                'message' => 'Identitas digital berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus identitas: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get jenis options for identity form.
     */
    public function getJenisOptions(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => IdentitasTersangka::getJenisOptions(),
        ]);
    }
}
