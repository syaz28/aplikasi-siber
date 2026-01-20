<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\Jabatan;
use App\Models\JenisKejahatan;
use App\Models\KategoriKejahatan;
use App\Models\Pangkat;
use App\Models\Wilayah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * MasterDataController
 * 
 * Provides JSON endpoints for dropdown/select data
 * Used by frontend forms for dynamic data loading
 */
class MasterDataController extends Controller
{
    /**
     * Get wilayah list by parent code (hierarchical)
     * 
     * - No parent: returns provinsi list
     * - Parent 2 chars (provinsi): returns kabupaten/kota list
     * - Parent 5 chars (kabupaten): returns kecamatan list
     * - Parent 8 chars (kecamatan): returns kelurahan list
     */
    public function wilayah(Request $request, ?string $parentKode = null): JsonResponse
    {
        $query = Wilayah::query();

        if ($parentKode === null) {
            // Return all provinsi (2 char codes)
            $query->whereRaw('LENGTH(kode) = 2');
        } else {
            // Determine expected child length based on parent
            $childLength = match (strlen($parentKode)) {
                2 => 5,   // Provinsi → Kabupaten
                5 => 8,   // Kabupaten → Kecamatan
                8 => 13,  // Kecamatan → Kelurahan
                default => 0,
            };

            if ($childLength === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid parent code length',
                    'data' => [],
                ]);
            }

            $query->where('kode', 'like', $parentKode . '%')
                ->whereRaw('LENGTH(kode) = ?', [$childLength]);
        }

        $wilayah = $query->orderBy('nama')->get(['kode', 'nama']);

        return response()->json([
            'success' => true,
            'data' => $wilayah,
        ]);
    }

    /**
     * Get all provinsi
     */
    public function provinsi(): JsonResponse
    {
        $provinsi = Wilayah::whereRaw('LENGTH(kode) = 2')
            ->orderBy('nama')
            ->get(['kode', 'nama']);

        return response()->json([
            'success' => true,
            'data' => $provinsi,
        ]);
    }

    /**
     * Get kabupaten/kota by provinsi code
     */
    public function kabupaten(string $kodeProvinsi): JsonResponse
    {
        $kabupaten = Wilayah::where('kode', 'like', $kodeProvinsi . '%')
            ->whereRaw('LENGTH(kode) = 5')
            ->orderBy('nama')
            ->get(['kode', 'nama']);

        return response()->json([
            'success' => true,
            'data' => $kabupaten,
        ]);
    }

    /**
     * Get kecamatan by kabupaten code
     */
    public function kecamatan(string $kodeKabupaten): JsonResponse
    {
        $kecamatan = Wilayah::where('kode', 'like', $kodeKabupaten . '%')
            ->whereRaw('LENGTH(kode) = 8')
            ->orderBy('nama')
            ->get(['kode', 'nama']);

        return response()->json([
            'success' => true,
            'data' => $kecamatan,
        ]);
    }

    /**
     * Get kelurahan by kecamatan code
     */
    public function kelurahan(string $kodeKecamatan): JsonResponse
    {
        $kelurahan = Wilayah::where('kode', 'like', $kodeKecamatan . '%')
            ->whereRaw('LENGTH(kode) = 13')
            ->orderBy('nama')
            ->get(['kode', 'nama']);

        return response()->json([
            'success' => true,
            'data' => $kelurahan,
        ]);
    }

    /**
     * Get all pangkat (police ranks)
     */
    public function pangkat(): JsonResponse
    {
        $pangkat = Pangkat::orderBy('urutan')
            ->get(['id', 'kode', 'nama', 'urutan']);

        return response()->json([
            'success' => true,
            'data' => $pangkat,
        ]);
    }

    /**
     * Get all jabatan (police positions)
     */
    public function jabatan(): JsonResponse
    {
        $jabatan = Jabatan::orderBy('nama')
            ->get(['id', 'nama', 'deskripsi']);

        return response()->json([
            'success' => true,
            'data' => $jabatan,
        ]);
    }

    /**
     * Get all kategori kejahatan (crime categories)
     */
    public function kategoriKejahatan(): JsonResponse
    {
        $kategori = KategoriKejahatan::active()
            ->orderBy('nama')
            ->get(['id', 'nama', 'deskripsi']);

        return response()->json([
            'success' => true,
            'data' => $kategori,
        ]);
    }

    /**
     * Get jenis kejahatan by kategori ID
     */
    public function jenisKejahatan(int $kategoriId): JsonResponse
    {
        $jenis = JenisKejahatan::active()
            ->where('kategori_kejahatan_id', $kategoriId)
            ->orderBy('nama')
            ->get(['id', 'kategori_kejahatan_id', 'nama', 'deskripsi']);

        return response()->json([
            'success' => true,
            'data' => $jenis,
        ]);
    }

    /**
     * Get all jenis kejahatan with kategori
     */
    public function jenisKejahatanAll(): JsonResponse
    {
        $jenis = JenisKejahatan::active()
            ->with('kategori:id,nama')
            ->orderBy('kategori_kejahatan_id')
            ->orderBy('nama')
            ->get(['id', 'kategori_kejahatan_id', 'nama', 'deskripsi']);

        return response()->json([
            'success' => true,
            'data' => $jenis,
        ]);
    }

    /**
     * Get all active anggota (police officers) for dropdown
     */
    public function anggota(Request $request): JsonResponse
    {
        $query = Anggota::active()
            ->with(['pangkat:id,kode,nama', 'jabatan:id,nama']);

        // Optional filter by jabatan
        if ($request->filled('jabatan_id')) {
            $query->where('jabatan_id', $request->input('jabatan_id'));
        }

        // Optional search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('nrp', 'like', "%{$search}%");
            });
        }

        $anggota = $query->orderBy('nama')->get();

        // Format for dropdown display
        $formatted = $anggota->map(fn($a) => [
            'id' => $a->id,
            'nrp' => $a->nrp,
            'nama' => $a->nama,
            'pangkat_kode' => $a->pangkat?->kode,
            'pangkat_nama' => $a->pangkat?->nama,
            'jabatan' => $a->jabatan?->nama,
            'display_name' => $a->display_name,
        ]);

        return response()->json([
            'success' => true,
            'data' => $formatted,
        ]);
    }

    /**
     * Get single anggota by ID
     */
    public function anggotaShow(int $id): JsonResponse
    {
        $anggota = Anggota::with(['pangkat', 'jabatan'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $anggota->id,
                'nrp' => $anggota->nrp,
                'nama' => $anggota->nama,
                'pangkat' => $anggota->pangkat,
                'jabatan' => $anggota->jabatan,
                'display_name' => $anggota->display_name,
            ],
        ]);
    }

    /**
     * Get all master data for form initialization
     * Returns all dropdown data in single request
     */
    public function formInit(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'provinsi' => Wilayah::whereRaw('LENGTH(kode) = 2')
                    ->orderBy('nama')
                    ->get(['kode', 'nama']),
                'pangkat' => Pangkat::orderBy('urutan')
                    ->get(['id', 'kode', 'nama']),
                'jabatan' => Jabatan::orderBy('nama')
                    ->get(['id', 'nama']),
                'kategori_kejahatan' => KategoriKejahatan::active()
                    ->orderBy('nama')
                    ->get(['id', 'nama']),
                'anggota' => Anggota::active()
                    ->with(['pangkat:id,kode,nama', 'jabatan:id,nama'])
                    ->orderBy('nama')
                    ->get()
                    ->map(fn($a) => [
                        'id' => $a->id,
                        'nama' => $a->nama,
                        'nrp' => $a->nrp,
                        'pangkat' => $a->pangkat ? [
                            'id' => $a->pangkat->id,
                            'kode' => $a->pangkat->kode,
                            'nama' => $a->pangkat->nama,
                        ] : null,
                        'jabatan' => $a->jabatan ? [
                            'id' => $a->jabatan->id,
                            'nama' => $a->jabatan->nama,
                        ] : null,
                    ]),
            ],
        ]);
    }
}
