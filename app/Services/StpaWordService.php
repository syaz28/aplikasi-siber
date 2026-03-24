<?php

namespace App\Services;

use App\Models\Laporan;
use Carbon\Carbon;
use PhpOffice\PhpWord\TemplateProcessor;
use Illuminate\Support\Facades\Log;

/**
 * StpaWordService
 * * Generates STPA (Surat Tanda Penerimaan Aduan) Word documents
 * using PhpWord TemplateProcessor approach.
 */
class StpaWordService
{
    public function generate(Laporan $laporan): string
    {
        $templatePath = app_path('Templates/stpa_template.docx');

        if (!file_exists($templatePath)) {
            throw new \RuntimeException(
                'Template STPA tidak ditemukan di: ' . $templatePath .
                '. Silakan letakkan file stpa_template.docx di storage/app/templates/'
            );
        }

        $templateProcessor = new TemplateProcessor($templatePath);

        // Map all data to template variables
        $data = $this->prepareTemplateData($laporan);

        foreach ($data as $key => $value) {
            $safeValue = $this->sanitize($value);
            $templateProcessor->setValue($key, $safeValue);
        }

        // Save to temp file (gunakan tempnam agar aman dari karakter slash di nomor_stpa)
        $tempFile = tempnam(sys_get_temp_dir(), 'stpa_') . '.docx';
        
        $templateProcessor->saveAs($tempFile);

        return $tempFile;
    }

    private function prepareTemplateData(Laporan $laporan): array
    {
        Carbon::setLocale('id');

        $pelapor = $laporan->pelapor;
        $alamatKtp = $pelapor?->alamatKtp;
        $alamatDomisili = $pelapor?->alamatDomisili;
        $korban = $laporan->korban->first();
        $orangKorban = $korban?->orang;
        $tersangka = $laporan->tersangka->first();
        $petugas = $laporan->petugas;
        $kategori = $laporan->kategoriKejahatan;

        // 1. FORMAT WAKTU LAPORAN (pastikan timezone WIB)
        $parsedTanggalLaporan = $laporan->tanggal_laporan
            ? Carbon::parse($laporan->tanggal_laporan)->setTimezone('Asia/Jakarta')
            : Carbon::now('Asia/Jakarta');
        $hariLaporan = $parsedTanggalLaporan->translatedFormat('l'); // Senin, Selasa
        $tanggalLaporan = $parsedTanggalLaporan->translatedFormat('d F Y'); // 30 Januari 2026
        $jamLaporan = $parsedTanggalLaporan->translatedFormat('H.i'); // 11.30

        // 2. FORMAT WAKTU KEJADIAN
        $waktuKejadian = $laporan->waktu_kejadian
            ? Carbon::parse($laporan->waktu_kejadian)->setTimezone('Asia/Jakarta')->translatedFormat('d F Y') // Sesuai contoh STPA: 23 Januari 2026
            : '-';

        // 3. FORMAT TANGGAL LAHIR
        $tglLahirPelapor = $pelapor?->tanggal_lahir
            ? Carbon::parse($pelapor->tanggal_lahir)->setTimezone('Asia/Jakarta')->translatedFormat('d F Y')
            : '-';

        // 4. KERUGIAN
        $kerugianNominal = $korban?->kerugian_nominal ?? 0;
        $kerugianRupiah = 'Rp ' . number_format((float) $kerugianNominal, 0, ',', '.');
        $kerugianTerbilang = TerbilangService::convert($kerugianNominal);

        // 5. PETUGAS (relasi ke model Personel via petugas_id)
        $namaPetugas = $petugas?->nama_lengkap ?? '-';
        $pangkatPetugas = $petugas?->pangkat ?? '';
        $nrpPetugas = $petugas?->nrp ?? '-';

        return [
            // === META SURAT ===
            'nomor_stpa'              => $laporan->nomor_stpa ?? '-',
            'hari_laporan'            => $hariLaporan,
            'tanggal_laporan'         => $tanggalLaporan,
            'jam_laporan'             => $jamLaporan,

            // === BIODATA PENGADU ===
            'nama_pengadu'            => strtoupper($pelapor?->nama ?? '-'),
            'tempat_lahir_pengadu'    => $pelapor?->tempat_lahir ?? '-',
            'tanggal_lahir_pengadu'   => $tglLahirPelapor,
            'pekerjaan_pengadu'       => $pelapor?->pekerjaan ?? '-',
            'alamat_ktp_pengadu'      => $alamatKtp ? $alamatKtp->alamat_lengkap : '-',
            'alamat_domisili_pengadu' => $alamatDomisili ? $alamatDomisili->alamat_lengkap : '-',
            'telepon_pengadu'         => $pelapor?->telepon ?? '-',
            'nik_pengadu'             => $pelapor?->nik ?? '-',
            'jenis_kelamin_pengadu'   => strtolower($pelapor?->jenis_kelamin ?? 'laki-laki'),

            // === SUBSTANSI PERKARA ===
            'kategori_kejahatan'      => strtoupper($kategori?->nama ?? '-'),
            'tempat_kejadian'         => $this->buildLokasiKejadian($laporan),
            'waktu_kejadian'          => $waktuKejadian,
            'kerugian_rupiah'         => $kerugianRupiah,
            'kerugian_terbilang'      => $kerugianTerbilang,
            
            // === PIHAK TERKAIT ===
            'identitas_teradu'        => $this->buildIdentitasDigital($tersangka),
            'nama_korban'             => strtoupper($orangKorban?->nama ?? $pelapor?->nama ?? '-'),
            'modus'                   => $laporan->modus ?? '-',

            // === PETUGAS ===
            'nama_petugas'            => strtoupper($namaPetugas),
            'pangkat_petugas'         => strtoupper($pangkatPetugas),
            'nrp_petugas'             => $nrpPetugas,
        ];
    }


    private function buildLokasiKejadian(Laporan $laporan): string
    {
        $parts = [];
        if ($laporan->alamat_kejadian) $parts[] = $laporan->alamat_kejadian;
        if ($laporan->kelurahanKejadian) $parts[] = 'Kel. ' . $laporan->kelurahanKejadian->nama;
        if ($laporan->kecamatanKejadian) $parts[] = 'Kec. ' . $laporan->kecamatanKejadian->nama;
        if ($laporan->kabupatenKejadian) $parts[] = $laporan->kabupatenKejadian->nama;
        if ($laporan->provinsiKejadian) $parts[] = $laporan->provinsiKejadian->nama;
        return !empty($parts) ? implode(', ', $parts) : '-';
    }

    private function buildIdentitasDigital($tersangka): string
    {
        if (!$tersangka || !$tersangka->identitas || $tersangka->identitas->isEmpty()) {
            return '-';
        }
        $lines = [];
        foreach ($tersangka->identitas as $identitas) {
            $jenis = match ($identitas->jenis) {
                'telepon'  => 'No. Telepon',
                'rekening' => 'No. Rekening',
                'sosmed'   => 'Media Sosial',
                'email'    => 'Email',
                'ewallet'  => 'E-Wallet',
                default    => ucfirst($identitas->jenis),
            };
            $nilai = $identitas->nilai ?? '-';
            $platform = $identitas->platform ? " ({$identitas->platform})" : '';
            $lines[] = "{$jenis}: {$nilai}{$platform}";
        }
        return implode('; ', $lines);
    }

    private function sanitize($value): string
    {
        $str = (string) ($value ?? '');
        if (trim($str) === '') return '-';

        // Handle enter/newline pada teks panjang seperti Modus
        $str = str_replace(["\r\n", "\r", "\n"], '</w:t><w:br/><w:t>', $str);

        // Escape XML agar file Word tidak corrupt
        $str = preg_replace_callback('/[&<>]/', function ($match) {
            return match ($match[0]) {
                '&' => '&amp;',
                '<' => '&lt;',
                '>' => '&gt;',
                default => $match[0],
            };
        }, $str);

        // Kembalikan tag <w:br/> yang sempat ter-escape
        $str = str_replace(['&lt;/w:t&gt;&lt;w:br/&gt;&lt;w:t&gt;'], ['</w:t><w:br/><w:t>'], $str);

        return $str;
    }
}