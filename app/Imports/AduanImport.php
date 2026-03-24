<?php

namespace App\Imports;

use App\Models\Alamat;
use App\Models\KategoriKejahatan;
use App\Models\Korban;
use App\Models\Laporan;
use App\Models\Orang;
use App\Models\Tersangka;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AduanImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    /**
     * Counters for import summary
     */
    public int $imported = 0;
    public int $skipped = 0;
    public array $errors = [];

    /**
     * Cached kategori kejahatan map (nama => id)
     */
    private ?array $kategoriCache = null;

    /**
     * Chunk size for memory efficiency
     */
    public function chunkSize(): int
    {
        return 500;
    }

    /**
     * Process each chunk of rows from the Excel file.
     */
    public function collection(Collection $rows)
    {

        // Pre-load kategori cache once
        if ($this->kategoriCache === null) {
            $this->kategoriCache = KategoriKejahatan::pluck('id', 'nama')->toArray();
        }

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; 

            // --- MESIN PENCARI KODE WILAYAH DUMMY (SESUAI UKURAN KEMENDAGRI) ---
            static $dummyProv = null;
            static $dummyKab = null;
            static $dummyKec = null;
            static $dummyKel = null;

            if ($dummyProv === null) {
                // Sesuai standar: 2, 5, 8, 13 karakter
                $dummyProv = \Illuminate\Support\Facades\DB::table('wilayah')->whereRaw('LENGTH(kode) = 2')->value('kode');
                $dummyKab  = \Illuminate\Support\Facades\DB::table('wilayah')->whereRaw('LENGTH(kode) = 5')->value('kode');
                $dummyKec  = \Illuminate\Support\Facades\DB::table('wilayah')->whereRaw('LENGTH(kode) = 8')->value('kode');
                $dummyKel  = \Illuminate\Support\Facades\DB::table('wilayah')->whereRaw('LENGTH(kode) = 13')->value('kode');
            }
            // ----------------------------------------

            try {
                // ==========================================
                // RULE 1: SKIP GHOST ROWS
                // ==========================================
                $namaPelapor = $this->cleanString($row['nama_pelapor'] ?? $row['nama'] ?? $row['pelapor'] ?? $row['nama_pelapor_'] ?? null);

                if (empty($namaPelapor)) {
                    $this->skipped++;
                    continue;
                }

                // --- 1. AMBIL LOCUS DARI EXCEL ---
                $locusRaw = $this->cleanString($row['locus_pelapor'] ?? $row['alamat_pelapor'] ?? $row['alamat'] ?? null);
                
                // --- 2. JALANKAN MESIN PINTAR WILAYAH ---
                [$kodeProv, $kodeKab, $kodeKec, $kodeKel] = $this->resolveWilayah($locusRaw, $dummyProv, $dummyKab, $dummyKec, $dummyKel);

                // --- 3. GERBANG TRANSAKSI (Pastikan variabel wilayah masuk) ---
                DB::transaction(function () use ($row, $rowNumber, $namaPelapor, $locusRaw, $kodeProv, $kodeKab, $kodeKec, $kodeKel) {
                    // ==========================================
                    // STEP 1: Create/Find Orang (Pelapor)
                    // ==========================================
                    $rawNik = $this->cleanString($row['nik'] ?? null);
                    $nik = $rawNik ?? 'IMP-' . now()->format('YmdHis') . '-' . $rowNumber;
                    
                    $telepon = $this->cleanString($row['no_hp'] ?? $row['no_telp'] ?? $row['hp'] ?? null) ?? '-';
                    $jenisKelamin = $this->normalizeGender($row['jenis_kelamin'] ?? $row['jk'] ?? null) ?? 'LAKI-LAKI';

                    $pelapor = null;
                    if (!empty($rawNik)) {
                        $pelapor = Orang::where('nik', $rawNik)->first();
                    }

                    if (!$pelapor) {
                        $pelapor = Orang::create([
                            'nik'             => $nik,
                            'nama'            => $namaPelapor,
                            'jenis_kelamin'   => $jenisKelamin,
                            'telepon'         => $telepon,
                            // --- TAMBAHAN DATA DUMMY WAJIB DATABASE ---
                            'tempat_lahir'    => '-',
                            'tanggal_lahir'   => '1970-01-01',
                            'pekerjaan'       => 'TIDAK DIKETAHUI',
                            'pendidikan'      => 'TIDAK DIKETAHUI',
                            'kewarganegaraan' => 'WNI',
                        ]);
                    }

                    // ==========================================
                    // STEP 2: Create Alamat Pelapor (if present)
                    // ==========================================
                    $alamatDetail = $this->cleanString(
                        $row['alamat_pelapor'] ?? $row['locus_pelapor'] ?? $row['alamat'] ?? null
                    );

                    if (!empty($alamatDetail)) {
                        Alamat::create([
                            'orang_id'       => $pelapor->id,
                            'jenis_alamat'   => 'domisili',
                            'detail_alamat'  => $alamatDetail,
                            'negara'         => 'Indonesia',
                            'kode_provinsi'  => $kodeProv,
                            'kode_kabupaten' => $kodeKab,
                            'kode_kecamatan' => $kodeKec,
                            'kode_kelurahan' => $kodeKel,
                        ]);
                    }

                    // ==========================================
                    // STEP 3: Resolve Kategori Kejahatan (STRICT 16)
                    // ==========================================
                    $jenisAduanRaw = $this->cleanString(
                        $row['jenis_aduan'] ?? $row['jenis_kejahatan'] ?? $row['kategori_kejahatan'] ?? $row['kategori'] ?? null
                    );

                    $kategoriId = $this->resolveKategoriKejahatan($jenisAduanRaw);

                    // ==========================================
                    // STEP 4: Parse Tanggal
                    // ==========================================
                    $tanggalLaporan = $this->parseDate(
                        $row['tgl_aduan'] ?? $row['tanggal_aduan'] ?? $row['tgl_laporan'] ?? $row['tanggal'] ?? null
                    );

                    // ── STEP 4b: Validate/override using STPA number ──
                    // The STPA contains the authoritative month/year.
                    // If parseDate produced a date whose month/year conflicts
                    // with the STPA, trust the STPA.
                    $stpaRaw = $this->cleanString($row['no_surat'] ?? $row['nomor_surat'] ?? $row['no_stpa'] ?? null);
                    $stpaDate = $this->extractDateFromStpa($stpaRaw);

                    if ($stpaDate) {
                        // If parseDate failed or its month/year doesn't match STPA → use STPA date
                        if (!$tanggalLaporan
                            || $tanggalLaporan->month !== $stpaDate->month
                            || $tanggalLaporan->year !== $stpaDate->year
                        ) {
                            $tanggalLaporan = $stpaDate;
                        }
                    }

                    // ==========================================
                    // STEP 5: Clean Kerugian (Money)
                    // ==========================================
                    $kerugian = $this->cleanMoney(
                        $row['kerugian'] ?? $row['jumlah_kerugian'] ?? $row['total_kerugian'] ?? null
                    );

                    // ==========================================
                    // STEP 6: Read SUBDIT assignment
                    // ==========================================
                    $subdit = $this->cleanString($row['subdit'] ?? $row['assigned_subdit'] ?? null);
                    $assignedSubdit = null;
                    if (!empty($subdit)) {
                        if (preg_match('/(\d+)/', $subdit, $m)) {
                            $val = (int) $m[1];
                            if ($val >= 1 && $val <= 3) {
                                $assignedSubdit = $val;
                            }
                        }
                    }

                    // ==========================================
                    // STEP 7: Read Modus/Uraian/Kronologis
                    // ==========================================
                    $modus = $this->cleanString(
                        $row['uraian'] ?? $row['kronologis'] ?? $row['modus'] ?? $row['keterangan_kejadian'] ?? null
                    );

                    // ==========================================
                    // STEP 8: Read NO SURAT & Deteksi Limpahan
                    // ==========================================
                    $nomorStpa = $this->cleanString($row['no_surat'] ?? $row['nomor_surat'] ?? $row['no_stpa'] ?? null);

                    // Deteksi apakah ini Aduan Limpahan
                    $isLimpahan = false;
                    if (str_contains(strtoupper($nomorStpa ?? ''), 'LIMPAHAN')) {
                        $isLimpahan = true;
                    }

                    // JURUS ANTI-DUPLIKAT: Jika kembar atau limpahan, tambahkan nomor baris agar unik di Database
                    if (!empty($nomorStpa)) {
                        $cekDuplikat = \App\Models\Laporan::where('nomor_stpa', $nomorStpa)->exists();
                        if ($cekDuplikat || $isLimpahan) {
                            $nomorStpa = $nomorStpa . ' - Baris ' . $rowNumber;
                        }
                    }

                    // ==========================================
                    // STEP 9: Create Laporan
                    // ==========================================
                    $laporan = Laporan::create([
                        'nomor_stpa'            => $nomorStpa,
                        'tanggal_laporan'       => $tanggalLaporan ?? now(),
                        'pelapor_id'            => $pelapor->id,
                        'kategori_kejahatan_id' => $kategoriId,
                        'modus'                 => $modus,
                        'status'                => 'Penyelidikan',
                        'assigned_subdit'       => $assignedSubdit,
                        'assigned_by'           => $assignedSubdit ? Auth::id() : null,
                        'assigned_at'           => $assignedSubdit ? now() : null,
                        'created_by'            => Auth::id(),
                        'waktu_kejadian'        => $tanggalLaporan ?? now(),
                        'hubungan_pelapor'      => 'diri_sendiri',
                        'petugas_id'            => Auth::id() ?? 1,
                        'kode_kabupaten_kejadian' => $kodeKab,
                        'alamat_kejadian'         => $alamatDetail,
                    ]);

                    // ==========================================
                    // STEP 10: Create Korban (use pelapor data + kerugian)
                    // ==========================================
                    if ($kerugian > 0) {
                        Korban::create([
                            'laporan_id'       => $laporan->id,
                            'orang_id'         => $pelapor->id,
                            'kerugian_nominal' => $kerugian,
                        ]);
                    }

                    // ==========================================
                    // STEP 11: Create Tersangka (TERADU) if present
                    // ==========================================
                    $namaTeraduRaw = $this->cleanString($row['teradu'] ?? $row['tersangka'] ?? $row['nama_teradu'] ?? null);

                    if (!empty($namaTeraduRaw) && $namaTeraduRaw !== '-') {
                        $orangTeradu = Orang::create([
                            'nama'            => $namaTeraduRaw,
                            // --- TAMBAHAN DATA DUMMY WAJIB DATABASE ---
                            'nik'             => 'TRD-' . now()->format('YmdHis') . '-' . $rowNumber,
                            'jenis_kelamin'   => 'LAKI-LAKI',
                            'telepon'         => '-',
                            'tempat_lahir'    => '-',
                            'tanggal_lahir'   => '1970-01-01',
                            'pekerjaan'       => 'TIDAK DIKETAHUI',
                            'pendidikan'      => 'TIDAK DIKETAHUI',
                            'kewarganegaraan' => 'WNI',
                        ]);

                        $tersangka = Tersangka::create([
                            'laporan_id' => $laporan->id,
                            'orang_id'   => $orangTeradu->id,
                        ]);

                        // ==========================================
                        // STEP 12: Create IdentitasTersangka (PLATFORM) if present
                        // ==========================================
                        $platformRaw = $this->cleanString($row['platform'] ?? $row['media'] ?? null);

                        if (!empty($platformRaw)) {
                            $platformNormalized = $this->normalizePlatform($platformRaw);

                            $tersangka->identitas()->create([
                                'jenis'    => 'sosmed',
                                // BUG FIXED: Nilai diisi nama/nomor teradu asli, bukan nama aplikasinya!
                                'nilai'    => $namaTeraduRaw ?? '-', 
                                'platform' => $platformNormalized,
                            ]);
                        }
                    }

                    $this->imported++;
                });
            } catch (\Throwable $e) {
                $this->errors[] = "Baris {$rowNumber}: {$e->getMessage()}";
                Log::warning("AduanImport error row {$rowNumber}", [
                    'error' => $e->getMessage(),
                    'row'   => $row->toArray(),
                ]);
            }
        }
    }

    // ========================================
    // CLEANING HELPERS
    // ========================================

    /**
     * Clean and trim a string value.
     * Returns null if empty after cleaning.
     */
    private function cleanString($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $cleaned = trim((string) $value);
        return $cleaned === '' ? null : $cleaned;
    }

    /**
     * Clean money value from messy Excel format.
     * Examples: "Rp.5.730.000", "Rp 0", "Rp." → integer
     */
    private function cleanMoney($value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        // Remove everything except digits
        $digits = preg_replace('/[^0-9]/', '', (string) $value);

        return $digits === '' ? 0 : (int) $digits;
    }

    /**
     * Parse date from various messy formats.
     * Handles: "2025-01-09", "20/12/2024", Excel serial numbers, etc.
     */
    private function parseDate($value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        $val = trim((string) $value);

        try {
            $date = null;

            // Check if it's an Excel serial date number (e.g., 45678)
            if (is_numeric($val) && (int) $val > 40000) {
                $date = Carbon::createFromFormat('Y-m-d', gmdate('Y-m-d', ($val - 25569) * 86400));
            }
            // Detect d/m/Y or d-m-Y format (day first)
            elseif (preg_match('#^(\d{1,2})[/\-](\d{1,2})[/\-](\d{4})$#', $val, $m)) {
                $date = Carbon::createFromFormat('d/m/Y', "{$m[1]}/{$m[2]}/{$m[3]}");
            }
            // Detect Y-m-d or Y/m/d (ISO format)
            elseif (preg_match('#^(\d{4})[/\-](\d{1,2})[/\-](\d{1,2})$#', $val, $m)) {
                $date = Carbon::createFromFormat('Y-m-d', "{$m[1]}-{$m[2]}-{$m[3]}");
            }
            // Fallback: let Carbon try to parse it
            else {
                $date = Carbon::parse($val);
            }

            // SANITY CHECK: reject future dates → return null so STPA fallback can take over
            if ($date && $date->isAfter(now()->endOfDay())) {
                return null;
            }

            return $date;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Roman numeral to integer map for STPA date extraction.
     */
    private const ROMAN_MONTHS = [
        'I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4,
        'V' => 5, 'VI' => 6, 'VII' => 7, 'VIII' => 8,
        'IX' => 9, 'X' => 10, 'XI' => 11, 'XII' => 12,
    ];

    /**
     * Extract a Carbon date from an STPA number.
     * Format: STPA/{number}/{ROMAN_MONTH}/{YEAR}/Ditressiber
     * Returns Carbon set to the 1st of that month, or null if unparseable.
     */
    private function extractDateFromStpa(?string $stpa): ?Carbon
    {
        if (!$stpa) {
            return null;
        }

        if (!preg_match('#STPA\s*/\s*\d+\s*/\s*(I{1,3}|IV|VI{0,3}|IX|X{0,3}I{0,3}|XI{0,2})\s*/\s*(\d{4})#i', $stpa, $m)) {
            return null;
        }

        $romanStr = strtoupper(trim($m[1]));
        $year     = (int) $m[2];
        $month    = self::ROMAN_MONTHS[$romanStr] ?? null;

        if (!$month || $year < 2000 || $year > 2100) {
            return null;
        }

        return Carbon::create($year, $month, 1, 0, 0, 0);
    }

    /**
     * Normalize gender value.
     * "L", "Laki", "LAKI-LAKI" → "LAKI-LAKI"
     * "P", "Perem", "PEREMPUAN" → "PEREMPUAN"
     */
    private function normalizeGender($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $val = strtoupper(trim((string) $value));

        // Jika mengandung kata LAKI atau huruf L saja, jadikan LAKI-LAKI utuh
        if (str_contains($val, 'LAKI') || $val === 'L') {
            return 'LAKI-LAKI';
        }

        // Jika mengandung kata PEREM atau huruf P saja, jadikan PEREMPUAN utuh
        if (str_contains($val, 'PEREM') || $val === 'P') {
            return 'PEREMPUAN';
        }

        return null;
    }

    // ========================================
    // PLATFORM NORMALIZATION (DICTIONARY)
    // ========================================

    /**
     * Normalize platform names from messy Excel input.
     * "wa", "WA" → "WhatsApp"
     * "ig", "IG" → "Instagram"
     * "fb", "FB" → "Facebook"
     * Etc.
     */
    private function normalizePlatform(string $raw): string
    {
        $lower = strtolower(trim($raw));

        // Order matters: check more specific patterns first
        $dictionary = [
            // WhatsApp
            ['patterns' => ['whatsapp', 'wa'], 'result' => 'WhatsApp'],
            // Instagram
            ['patterns' => ['instagram', 'ig'], 'result' => 'Instagram'],
            // Facebook
            ['patterns' => ['facebook', 'fb'], 'result' => 'Facebook'],
            // Telegram
            ['patterns' => ['telegram', 'tele'], 'result' => 'Telegram'],
            // TikTok (check before "x" to avoid false positives with "tiktok" containing letters)
            ['patterns' => ['tiktok', 'tik tok'], 'result' => 'TikTok'],
            // X/Twitter
            ['patterns' => ['twitter'], 'result' => 'X (Twitter)'],
            // YouTube
            ['patterns' => ['youtube', 'yt'], 'result' => 'YouTube'],
            // Shopee
            ['patterns' => ['shopee'], 'result' => 'Shopee'],
            // Tokopedia
            ['patterns' => ['tokopedia', 'tokped'], 'result' => 'Tokopedia'],
            // Bukalapak
            ['patterns' => ['bukalapak'], 'result' => 'Bukalapak'],
        ];

        foreach ($dictionary as $entry) {
            foreach ($entry['patterns'] as $pattern) {
                if (str_contains($lower, $pattern)) {
                    return $entry['result'];
                }
            }
        }

        // Special case: standalone "x" (exact match or "x.com")
        if ($lower === 'x' || str_contains($lower, 'x.com')) {
            return 'X (Twitter)';
        }

        // Fallback: Title Case the original text
        return Str::title($raw);
    }

    // ========================================
    // KATEGORI KEJAHATAN MATCHING (STRICT 16)
    // ========================================

    /**
     * The 16 official crime categories with their matching keywords.
     * Each entry: [db_name => array of keyword triggers]
     */
    private const KATEGORI_MAP = [
        'Penipuan online' => ['tipu', 'penipuan', 'scam', 'fraud'],
        'Pemerasan digital' => ['peras', 'pemerasan', 'extort'],
        'Pengancaman digital' => ['ancam', 'pengancaman', 'threat'],
        'Ilegal Akses (peretasan)' => ['ilegal akses', 'hack', 'retas', 'peretasan', 'hacking', 'ilegal'],
        'Perjudian online' => ['judi', 'gambling', 'slot', 'togel'],
        'Kesusilaan/Pornografi' => ['susila', 'porno', 'pornografi', 'asusila', 'kesusilaan'],
        'Penghinaan dan pencemaran nama baik digital' => ['hina', 'pencemaran', 'defamasi', 'nama baik'],
        'Penyebaran berita bohong (hoaks)' => ['hoaks', 'hoax', 'bohong', 'fake news', 'berita palsu'],
        'Penyebaran ujaran kebencian / permusuhan' => ['kebencian', 'ujaran', 'hate', 'sara', 'permusuhan'],
        'Ancaman kekerasan melalui sistem elektronik' => ['kekerasan', 'violence'],
        'Intersepsi' => ['intersepsi', 'sadap', 'penyadapan', 'intercept'],
        'Perusakan, perubahan, atau penghilangan data elektronik' => ['rusak data', 'hapus data', 'perusakan', 'deface'],
        'Gangguan terhadap sistem elektronik' => ['gangguan sistem', 'ddos', 'dos', 'disruption'],
        'Penyediaan atau penggunaan alat kejahatan siber' => ['alat kejahatan', 'malware', 'ransomware', 'virus'],
        'Pemalsuan informasi atau dokumen elektronik' => ['palsu', 'pemalsuan', 'forgery', 'fake', 'manipulasi'],
        'Pelanggaran atau penyalahgunaan data pribadi' => ['data pribadi', 'privasi', 'privacy', 'penyalahgunaan data', 'pdp'],
    ];

    /**
     * Resolve kategori kejahatan from messy Excel text to database ID.
     * Uses keyword matching against the 16 official categories.
     */
    private function resolveKategoriKejahatan(?string $raw): ?int
    {
        if (empty($raw)) {
            return null;
        }

        $lower = strtolower($raw);

        // Try exact match first (fast path)
        if (isset($this->kategoriCache[$raw])) {
            return $this->kategoriCache[$raw];
        }

        // Fuzzy match using keyword dictionary
        foreach (self::KATEGORI_MAP as $kategoriName => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($lower, $keyword)) {
                    // Found a match — return cached ID or look up from DB
                    if (isset($this->kategoriCache[$kategoriName])) {
                        return $this->kategoriCache[$kategoriName];
                    }

                    // If somehow not cached, find from DB
                    $kategori = KategoriKejahatan::where('nama', $kategoriName)->first();
                    if ($kategori) {
                        $this->kategoriCache[$kategoriName] = $kategori->id;
                        return $kategori->id;
                    }
                }
            }
        }

        // Fallback SAPU JAGAT: 
        // Jika ketikan Excel sangat aneh (misal: "Pencurian Tas"), 
        // paksa masuk ke kategori pertama di DB agar tidak error, lalu Admin bisa edit di Dashboard.
        $defaultKategori = \App\Models\KategoriKejahatan::first();
        return $defaultKategori ? $defaultKategori->id : 1;
    }

    // ========================================
    // WILAYAH NORMALIZATION (SMART MAPPING - KEMENDAGRI)
    // ========================================
    private function resolveWilayah(?string $locusRaw, $fallbackProv, $fallbackKab, $fallbackKec, $fallbackKel): array
    {
        if (empty($locusRaw) || $locusRaw === '-') {
            return [$fallbackProv, $fallbackKab, $fallbackKec, $fallbackKel];
        }

        // 1. Bersihkan dan jadikan HURUF BESAR (Sesuai format DB Kemendagri)
        $search = strtoupper(trim($locusRaw));

        // 2. Coba cari spesifik dulu (menghindari ambigu Semarang Kota vs Kab)
        $kabupaten = null;
        
        // Skenario A: Input sudah pakai "KOTA" atau "KABUPATEN" (ex: "KOTA SEMARANG", "KAB DEMAK")
        if (str_starts_with($search, 'KOTA ') || str_starts_with($search, 'KABUPATEN ') || str_starts_with($search, 'KAB ')) {
            $cleanSearch = str_replace('KAB ', 'KABUPATEN ', $search);
            $kabupaten = \Illuminate\Support\Facades\DB::table('wilayah')
                ->whereRaw('LENGTH(kode) = 5')
                ->where('nama', $cleanSearch)
                ->first();
        }

        // Skenario B: Coba paksa tambah "KOTA " (Biasanya kalau nulis "Semarang", maksudnya Kota)
        if (!$kabupaten) {
            $kabupaten = \Illuminate\Support\Facades\DB::table('wilayah')
                ->whereRaw('LENGTH(kode) = 5')
                ->where('nama', 'KOTA ' . $search)
                ->first();
        }

        // Skenario C: Coba paksa tambah "KABUPATEN " (Misal input "Demak" -> "KABUPATEN DEMAK")
        if (!$kabupaten) {
            $kabupaten = \Illuminate\Support\Facades\DB::table('wilayah')
                ->whereRaw('LENGTH(kode) = 5')
                ->where('nama', 'KABUPATEN ' . $search)
                ->first();
        }

        // Skenario D: Pencarian Fuzzy (LIKE) terakhir jika masih gagal
        if (!$kabupaten) {
            $kabupaten = \Illuminate\Support\Facades\DB::table('wilayah')
                ->whereRaw('LENGTH(kode) = 5')
                ->where('nama', 'LIKE', '%' . $search . '%')
                ->first();
        }

        // 3. Jika Kabupaten Ketemu, Turunkan ke Provinsi, Kecamatan, Kelurahan
        if ($kabupaten) {
            $kodeKab = $kabupaten->kode; // cth: "33.74"
            $kodeProv = substr($kodeKab, 0, 2); // cth: "33"
            
            // Cari 1 kecamatan acak di kabupaten ini (panjang 8)
            $kecamatan = \Illuminate\Support\Facades\DB::table('wilayah')
                ->whereRaw('LENGTH(kode) = 8')
                ->where('kode', 'LIKE', $kodeKab . '.%')
                ->first();
            $kodeKec = $kecamatan ? $kecamatan->kode : $fallbackKec;
            
            // Cari 1 kelurahan acak di kecamatan ini (panjang 13)
            $kelurahan = \Illuminate\Support\Facades\DB::table('wilayah')
                ->whereRaw('LENGTH(kode) = 13')
                ->where('kode', 'LIKE', $kodeKec . '.%')
                ->first();
            $kodeKel = $kelurahan ? $kelurahan->kode : $fallbackKel;

            return [$kodeProv, $kodeKab, $kodeKec, $kodeKel];
        }

        // Kalau benar-benar tidak ketemu di DB, pakai Dummy aman
        return [$fallbackProv, $fallbackKab, $fallbackKec, $fallbackKel];
    }
}
