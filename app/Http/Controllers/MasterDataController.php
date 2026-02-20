<?php

namespace App\Http\Controllers;

use App\Models\KategoriKejahatan;
use App\Models\MasterCountry;
use App\Models\MasterPekerjaan;
use App\Models\MasterPendidikan;
use App\Models\MasterPlatform;
use App\Models\Orang;
use App\Models\Personel;
use App\Models\User;
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
     * Get ALL kabupaten/kota in Indonesia (approx. 514 records)
     * Used for Step 2 location selection (nation-wide)
     */
    public function kabupatenAll(): JsonResponse
    {
        $kabupaten = Wilayah::whereRaw('LENGTH(kode) = 5')
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
     * Pangkat options (static list since Pangkat table is removed)
     */
    private const PANGKAT_OPTIONS = [
        'AKBP', 'Kompol', 'AKP', 'Iptu', 'Ipda', 'Aiptu', 'Aipda',
        'Bripka', 'Brigadir', 'Briptu', 'Bripda', 'PNSD'
    ];

    /**
     * Get all pangkat (police ranks) - now static
     */
    public function pangkat(): JsonResponse
    {
        $pangkat = collect(self::PANGKAT_OPTIONS)->map(fn($p, $i) => [
            'id' => $i + 1,
            'kode' => $p,
            'nama' => $p,
            'urutan' => $i + 1,
        ]);

        return response()->json([
            'success' => true,
            'data' => $pangkat,
        ]);
    }

    /**
     * Get all jabatan (police positions) - deprecated, returns empty
     */
    public function jabatan(): JsonResponse
    {
        // Jabatan is now a free text field in users table
        return response()->json([
            'success' => true,
            'data' => [],
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
     * Get all active anggota (police officers) for dropdown
     * Now fetches from personels table (refactored from users)
     */
    public function anggota(Request $request): JsonResponse
    {
        $query = Personel::query();

        // Optional filter by subdit
        if ($request->filled('subdit')) {
            $query->where('subdit_id', $request->input('subdit'));
        }

        // Optional search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nrp', 'like', "%{$search}%");
            });
        }

        $anggota = $query->orderBy('nama_lengkap')->get();

        // Format for dropdown display
        $formatted = $anggota->map(fn($a) => [
            'id' => $a->id,
            'nrp' => $a->nrp,
            'nama' => $a->nama_lengkap,
            'pangkat' => $a->pangkat,
            'jabatan' => null,
            'display_name' => ($a->pangkat ? $a->pangkat . ' ' : '') . $a->nama_lengkap,
        ]);

        return response()->json([
            'success' => true,
            'data' => $formatted,
        ]);
    }

    /**
     * Get single anggota by ID (now from personels table)
     */
    public function anggotaShow(int $id): JsonResponse
    {
        $personel = Personel::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $personel->id,
                'nrp' => $personel->nrp,
                'nama' => $personel->nama_lengkap,
                'pangkat' => $personel->pangkat,
                'jabatan' => null,
                'display_name' => ($personel->pangkat ? $personel->pangkat . ' ' : '') . $personel->nama_lengkap,
            ],
        ]);
    }

    /**
     * Get all pekerjaan (occupations) for dropdown
     */
    public function pekerjaan(): JsonResponse
    {
        $pekerjaan = MasterPekerjaan::orderBy('nama')
            ->get(['id', 'nama']);

        return response()->json([
            'success' => true,
            'data' => $pekerjaan,
        ]);
    }

    /**
     * Get all pendidikan (education levels) for dropdown
     */
    public function pendidikan(): JsonResponse
    {
        $pendidikan = MasterPendidikan::orderBy('id')
            ->get(['id', 'nama']);

        return response()->json([
            'success' => true,
            'data' => $pendidikan,
        ]);
    }

    /**
     * Get all platforms (flat list for frontend filtering)
     * Used for identitas tersangka dependent dropdown
     */
    public function getPlatforms(Request $request): JsonResponse
    {
        $query = MasterPlatform::active()
            ->orderBy('kategori')
            ->orderBy('urutan');

        // Optional: Filter by kategori if provided
        if ($request->filled('kategori')) {
            $query->byKategori($request->input('kategori'));
        }

        $platforms = $query->get(['id', 'kategori', 'nama_platform', 'urutan']);

        return response()->json([
            'success' => true,
            'data' => $platforms,
        ]);
    }

    /**
     * Get all countries for WNA dropdown
     */
    public function getCountries(): JsonResponse
    {
        $countries = MasterCountry::orderBy('name')
            ->get(['id', 'alpha_2', 'name', 'phone_code']);

        return response()->json([
            'success' => true,
            'data' => $countries,
        ]);
    }

    /**
     * Get phone codes for phone input dropdown
     * Returns countries with phone codes, ordered alphabetically by code
     */
    public function getPhoneCodes(): JsonResponse
    {
        $countries = MasterCountry::whereNotNull('phone_code')
            ->orderBy('alpha_2', 'asc')
            ->get(['phone_code', 'alpha_2', 'name']);

        return response()->json([
            'success' => true,
            'data' => $countries,
        ]);
    }

    /**
     * Search orang by NIK for auto-fill functionality
     * Returns person data with addresses if found
     */
    public function searchOrangByNik(Request $request): JsonResponse
    {
        $nik = $request->input('nik');
        
        if (!$nik || strlen($nik) < 10) {
            return response()->json([
                'success' => false,
                'message' => 'NIK harus minimal 10 karakter',
            ]);
        }

        $orang = Orang::with([
            'alamatKtp',
            'alamatDomisili',
        ])->where('nik', $nik)->first();

        if (!$orang) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan',
                'found' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'found' => true,
            'data' => [
                'id' => $orang->id,
                'nik' => $orang->nik,
                'nama' => $orang->nama,
                'kewarganegaraan' => $orang->kewarganegaraan ?? 'WNI',
                'negara_asal' => $orang->negara_asal,
                'tempat_lahir' => $orang->tempat_lahir,
                'tanggal_lahir' => $orang->tanggal_lahir?->format('Y-m-d'),
                'jenis_kelamin' => $orang->jenis_kelamin,
                'pekerjaan' => $orang->pekerjaan,
                'pendidikan' => $orang->pendidikan,
                'telepon' => $orang->telepon,
                'email' => $orang->email,
                'alamat_ktp' => $orang->alamatKtp ? [
                    'kode_provinsi' => $orang->alamatKtp->kode_provinsi,
                    'kode_kabupaten' => $orang->alamatKtp->kode_kabupaten,
                    'kode_kecamatan' => $orang->alamatKtp->kode_kecamatan,
                    'kode_kelurahan' => $orang->alamatKtp->kode_kelurahan,
                    'detail_alamat' => $orang->alamatKtp->detail_alamat,
                ] : null,
                'alamat_domisili' => $orang->alamatDomisili ? [
                    'kode_provinsi' => $orang->alamatDomisili->kode_provinsi,
                    'kode_kabupaten' => $orang->alamatDomisili->kode_kabupaten,
                    'kode_kecamatan' => $orang->alamatDomisili->kode_kecamatan,
                    'kode_kelurahan' => $orang->alamatDomisili->kode_kelurahan,
                    'detail_alamat' => $orang->alamatDomisili->detail_alamat,
                ] : null,
                // Info untuk user
                'is_pelapor' => $orang->laporanSebagaiPelapor()->count() > 0,
                'is_korban' => $orang->sebagaiKorban()->count() > 0,
                'is_tersangka' => $orang->sebagaiTersangka()->count() > 0,
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
                'pangkat' => collect(self::PANGKAT_OPTIONS)->map(fn($p, $i) => [
                    'id' => $i + 1,
                    'kode' => $p,
                    'nama' => $p,
                ]),
                'kategori_kejahatan' => KategoriKejahatan::active()
                    ->orderBy('id') // Order by ID for consistent ordering
                    ->get(['id', 'nama']),
                'anggota' => Personel::orderBy('nama_lengkap')
                    ->get()
                    ->map(fn($a) => [
                        'id' => $a->id,
                        'nama' => $a->nama_lengkap,
                        'nrp' => $a->nrp,
                        'pangkat' => $a->pangkat,
                        'jabatan' => null,
                    ]),
                'pekerjaan' => MasterPekerjaan::orderBy('nama')
                    ->get(['id', 'nama']),
                'pendidikan' => MasterPendidikan::orderBy('id')
                    ->get(['id', 'nama']),
                // ALL Kabupaten/Kota in Indonesia (514 records) for Step 2 location
                'kabupaten_all' => Wilayah::whereRaw('LENGTH(kode) = 5')
                    ->orderBy('nama')
                    ->get(['kode', 'nama']),
                // Platforms for identitas tersangka (dependent dropdown)
                'platforms' => MasterPlatform::active()
                    ->orderBy('kategori')
                    ->orderBy('urutan')
                    ->get(['id', 'kategori', 'nama_platform']),
                // Countries for WNA dropdown
                'countries' => MasterCountry::orderBy('name')
                    ->get(['id', 'alpha_2', 'name', 'phone_code']),
            ],
        ]);
    }
}
