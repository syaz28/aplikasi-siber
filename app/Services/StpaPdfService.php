<?php

namespace App\Services;

use App\Models\Laporan;
use App\Models\Wilayah;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

// FPDF is a global class from setasign/fpdf package
require_once base_path('vendor/setasign/fpdf/fpdf.php');

/**
 * StpaPdfService - STPA PDF Generator
 * 
 * Generates STPA (Surat Tanda Penerimaan Aduan) PDF documents.
 * Format sesuai standar Ditressiber POLDA Jateng.
 * 
 * ============================================================================
 * SPESIFIKASI DOKUMEN:
 * ============================================================================
 * - Font: Arial, Size 12
 * - Line Spacing: 1.0 (single)
 * - Margins: Top 1cm, Bottom 1cm, Left 2.5cm, Right 2cm
 * - Page: A4 Portrait
 * - Auto dashes (----) untuk filler - dihitung otomatis
 * - Logo: polri_logo.png
 * 
 * @version 4.0.0 - Sesuai Format Asli
 */
class StpaPdfService
{
    /**
     * FPDF Instance
     */
    protected $pdf;

    /**
     * Page Configuration (A4 dimensions in mm)
     */
    protected const PAGE_WIDTH = 210;
    protected const PAGE_HEIGHT = 297;
    protected const MARGIN_TOP = 10;      // 1 cm
    protected const MARGIN_BOTTOM = 10;   // 1 cm
    protected const MARGIN_LEFT = 25;     // 2.5 cm
    protected const MARGIN_RIGHT = 20;    // 2 cm
    protected const CONTENT_WIDTH = 165;  // PAGE_WIDTH - MARGIN_LEFT - MARGIN_RIGHT

    /**
     * Font Configuration
     */
    protected const FONT_FAMILY = 'Arial';
    protected const FONT_SIZE = 12;
    protected const LINE_HEIGHT = 5;      // 1.0 line spacing for 12pt font

    /**
     * Label width for identity rows (mm)
     */
    protected const LABEL_WIDTH = 45;

    /**
     * Roman Numeral Mapping for Months
     */
    protected const ROMAN_MONTHS = [
        1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV',
        5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII',
        9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
    ];

    /**
     * Indonesian Day Names
     */
    protected const DAY_NAMES = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu',
    ];

    /**
     * Indonesian Month Names
     */
    protected const MONTH_NAMES = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];

    /**
     * Generate STPA PDF Document
     * 
     * @param Laporan $laporan Laporan model with eager loaded relationships
     * @return string PDF content as binary string
     */
    public function generate(Laporan $laporan): string
    {
        try {
            // Initialize FPDF
            $this->pdf = new \FPDF('P', 'mm', 'A4');
            $this->pdf->SetMargins(self::MARGIN_LEFT, self::MARGIN_TOP, self::MARGIN_RIGHT);
            $this->pdf->SetAutoPageBreak(true, self::MARGIN_BOTTOM);
            $this->pdf->AddPage();

            // Configure font
            $this->pdf->SetFont(self::FONT_FAMILY, '', self::FONT_SIZE);
            $this->pdf->SetTextColor(0, 0, 0);

            // === BUILD DOCUMENT ===
            $this->writeHeader($laporan);
            $this->writeOpeningParagraph($laporan);
            $this->writeIdentitySection($laporan);
            $this->writeCrimeTypeSection($laporan);
            $this->writeIncidentSection($laporan);
            $this->writeVictimAndModusSection($laporan);
            $this->writeClosingSection($laporan);
            $this->writeSignatures($laporan);

            // Output PDF as string
            return $this->pdf->Output('S');

        } catch (\Exception $e) {
            Log::error('StpaPdfService: PDF generation failed', [
                'laporan_id' => $laporan->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    // =========================================================================
    // SECTION WRITERS
    // =========================================================================

    /**
     * Write Document Header (Kop Surat)
     */
    protected function writeHeader(Laporan $laporan): void
    {
        $startY = self::MARGIN_TOP;
        
        // Header text - centered, bold
        $this->pdf->SetFont(self::FONT_FAMILY, 'B', self::FONT_SIZE);
        
        // Line 1: KEPOLISIAN NEGARA REPUBLIK INDONESIA
        $this->pdf->SetXY(self::MARGIN_LEFT, $startY);
        $this->pdf->Cell(self::CONTENT_WIDTH, self::LINE_HEIGHT, 'KEPOLISIAN NEGARA REPUBLIK INDONESIA', 0, 1, 'C');
        
        // Line 2: DAERAH JAWA TENGAH
        $this->pdf->Cell(self::CONTENT_WIDTH, self::LINE_HEIGHT, 'DAERAH JAWA TENGAH', 0, 1, 'C');
        
        // Line 3: DIREKTORAT RESERSE SIBER (with underline)
        $this->pdf->SetFont(self::FONT_FAMILY, 'BU', self::FONT_SIZE);
        $this->pdf->Cell(self::CONTENT_WIDTH, self::LINE_HEIGHT, 'DIREKTORAT RESERSE SIBER', 0, 1, 'C');
        
        $currentY = $this->pdf->GetY();
        
        // Logo Polri - centered
        $logoPath = public_path('images/polri_logo.png');
        if (file_exists($logoPath)) {
            $logoWidth = 20;
            $logoHeight = 25;
            $logoX = self::MARGIN_LEFT + (self::CONTENT_WIDTH - $logoWidth) / 2;
            $this->pdf->Image($logoPath, $logoX, $currentY + 2, $logoWidth, $logoHeight);
            $currentY += $logoHeight + 5;
        } else {
            $currentY += 10;
        }
        
        // Title: SURAT TANDA PENERIMAAN ADUAN (Bold + Underline)
        $this->pdf->SetFont(self::FONT_FAMILY, 'BU', self::FONT_SIZE);
        $this->pdf->SetXY(self::MARGIN_LEFT, $currentY);
        $this->pdf->Cell(self::CONTENT_WIDTH, self::LINE_HEIGHT, 'SURAT TANDA PENERIMAAN ADUAN', 0, 1, 'C');
        
        // Nomor Surat
        $this->pdf->SetFont(self::FONT_FAMILY, '', self::FONT_SIZE);
        $nomorStpa = $this->formatNomorStpa($laporan);
        $this->pdf->Cell(self::CONTENT_WIDTH, self::LINE_HEIGHT, "Nomor : {$nomorStpa}", 0, 1, 'C');
        
        $this->pdf->Ln(3);
    }

    /**
     * Write Opening Paragraph (Naratif Pembuka)
     * Format: ----- Pada hari ini ... dengan identitas sebagai berikut : ------
     */
    protected function writeOpeningParagraph(Laporan $laporan): void
    {
        $pelapor = $laporan->pelapor;
        $tanggal = $laporan->tanggal_laporan ?? now();
        
        // Format date components
        $hari = self::DAY_NAMES[$tanggal->format('l')] ?? $tanggal->format('l');
        $tanggalNum = $tanggal->format('d');
        $bulan = self::MONTH_NAMES[$tanggal->month] ?? $tanggal->format('F');
        $tahun = $tanggal->format('Y');
        $jam = $tanggal->format('H.i'); // Pakai titik, bukan colon
        
        // Jenis kelamin
        $jenisKelamin = ($pelapor?->jenis_kelamin === 'LAKI-LAKI') ? 'seorang Laki-laki' : 'seorang Perempuan';
        
        // Build opening text
        $openingText = "Pada hari ini {$hari} tanggal {$tanggalNum} {$bulan} {$tahun}, sekitar pukul {$jam} WIB, telah datang {$jenisKelamin} di Kantor Direktorat Reserse Siber Polda Jawa Tengah, dengan identitas sebagai berikut :";
        
        // Add prefix dashes and write with auto trailing dashes
        $this->writeJustifiedWithDashes($openingText, 5);
        
        $this->pdf->Ln(1);
    }

    /**
     * Write Identity Section (Data Identitas)
     */
    protected function writeIdentitySection(Laporan $laporan): void
    {
        $pelapor = $laporan->pelapor;
        
        if (!$pelapor) {
            $this->writeIdentityRow('Nama', '-', ';');
            return;
        }

        // Nama
        $this->writeIdentityRow('Nama', $pelapor->nama ?? '-', ';');
        
        // Tempat / tanggal lahir
        $ttl = ($pelapor->tempat_lahir ?? '-') . ', ' . 
               $this->formatTanggalIndonesia($pelapor->tanggal_lahir);
        $this->writeIdentityRow('Tempat / tanggal lahir', $ttl, ';');
        
        // Pekerjaan
        $pekerjaan = $pelapor->pekerjaan ?? ($pelapor->masterPekerjaan?->nama ?? '-');
        $this->writeIdentityRow('Pekerjaan', $pekerjaan, ';');
        
        // Alamat (KTP)
        $alamatKtp = $this->formatAlamat($pelapor->alamatKtp);
        $this->writeIdentityRow('Alamat (KTP)', $alamatKtp, ';');
        
        // Alamat Domisili
        $alamatDomisili = $pelapor->alamatDomisili 
            ? $this->formatAlamat($pelapor->alamatDomisili) 
            : $alamatKtp;
        $this->writeIdentityRow('Alamat Domisili', $alamatDomisili, ';');
        
        // NIK
        $this->writeIdentityRow('NIK', $pelapor->nik ?? '-', ';');
        
        // Nomor HP - Diakhiri dengan titik (.)
        $this->writeIdentityRow('Nomor HP', $pelapor->telepon ?? '-', '.');
        
        $this->pdf->Ln(1);
    }

    /**
     * Write Crime Type Section (Jenis Pengaduan)
     * Format: 
     * ------ : Dengan maksud untuk mengadukan adanya dugaan tindak pidana : ------
     * ------------------------------------------"Penipuan Online"------------------------------------------
     */
    protected function writeCrimeTypeSection(Laporan $laporan): void
    {
        $kategori = $laporan->kategoriKejahatan?->nama ?? 'Penipuan Online';
        
        // Line 1: Intro dengan dashes di kiri dan kanan
        $introText = ": Dengan maksud untuk mengadukan adanya dugaan tindak pidana :";
        $this->writeCenteredTextWithDashes($introText);
        
        $this->pdf->Ln(1);
        
        // Line 2: Jenis kejahatan dalam tanda kutip, centered dengan dashes
        $crimeTitle = '"' . $kategori . '"';
        $this->writeCenteredTextWithDashes($crimeTitle);
        
        $this->pdf->Ln(2);
    }

    /**
     * Write Incident Section (Detail Kejadian)
     */
    protected function writeIncidentSection(Laporan $laporan): void
    {
        // Intro line
        $introText = "Bahwa telah terjadi adanya dugaan tindak pidana atau peristiwa, sebagai berikut :";
        $this->writeJustifiedWithDashes($introText, 0, true);
        
        // Tempat Kejadian
        $lokasiKejadian = $this->formatLokasiKejadian($laporan);
        $this->writeIdentityRow('Tempat Kejadian', $lokasiKejadian, ';');
        
        // Waktu Kejadian
        $waktuKejadian = $this->formatTanggalIndonesia($laporan->waktu_kejadian);
        $this->writeIdentityRow('Waktu Kejadian', $waktuKejadian, ';');
        
        // Kerugian
        $kerugian = $this->formatKerugian($laporan);
        $this->writeIdentityRow('Kerugian', $kerugian, ';');
        
        $this->pdf->Ln(1);
    }

    /**
     * Write Victim and Modus Section
     */
    protected function writeVictimAndModusSection(Laporan $laporan): void
    {
        // Korban
        $korbanText = $this->formatKorban($laporan);
        $this->writeIdentityRow('Korban', $korbanText, ';');
        
        // Modus - bisa multiline
        $modus = $laporan->modus ?? '-';
        $this->writeIdentityRowMultiline('Modus', $modus, '.');
        
        $this->pdf->Ln(2);
    }

    /**
     * Write Closing Section (Penutup)
     * Format: ----- Demikian Surat Tanda Terima Pengaduan ini ... seperlunya.------
     */
    protected function writeClosingSection(Laporan $laporan): void
    {
        $closingText = "Demikian Surat Tanda Terima Pengaduan ini dibuat dengan sebenarnya untuk digunakan seperlunya.";
        
        $this->writeJustifiedWithDashes($closingText, 5);
        
        $this->pdf->Ln(8);
    }

    /**
     * Write Signatures Section (Tanda Tangan)
     */
    protected function writeSignatures(Laporan $laporan): void
    {
        $pelapor = $laporan->pelapor;
        $petugas = $laporan->petugas;
        
        // Column configuration
        $leftColX = self::MARGIN_LEFT;
        $rightColX = self::MARGIN_LEFT + 90;
        $leftColWidth = 70;
        $rightColWidth = 75;
        
        $currentY = $this->pdf->GetY();
        
        // Row 1: "Yang Menerima" on right only
        $this->pdf->SetFont(self::FONT_FAMILY, '', self::FONT_SIZE);
        $this->pdf->SetXY($rightColX, $currentY);
        $this->pdf->Cell($rightColWidth, self::LINE_HEIGHT, 'Yang Menerima', 0, 0, 'C');
        $currentY += self::LINE_HEIGHT;
        
        // Row 2: "Pengadu" on left, "PAWAS PIKET DITRESSIBER" on right
        $this->pdf->SetXY($leftColX, $currentY);
        $this->pdf->Cell($leftColWidth, self::LINE_HEIGHT, 'Pengadu', 0, 0, 'C');
        
        $this->pdf->SetXY($rightColX, $currentY);
        $this->pdf->Cell($rightColWidth, self::LINE_HEIGHT, 'PAWAS PIKET DITRESSIBER', 0, 0, 'C');
        $currentY += self::LINE_HEIGHT;
        
        // Signature space (approximately 4 lines)
        $currentY += 20;
        
        // Row 3: Names
        $namaPelapor = strtoupper($pelapor?->nama ?? '-');
        $namaPetugas = strtoupper($petugas?->nama_lengkap ?? $petugas?->nama ?? '-');
        
        // Pelapor name - NO underline
        $this->pdf->SetFont(self::FONT_FAMILY, 'B', self::FONT_SIZE);
        $this->pdf->SetXY($leftColX, $currentY);
        $this->pdf->Cell($leftColWidth, self::LINE_HEIGHT, $namaPelapor, 0, 0, 'C');
        
        // Petugas name - WITH underline
        $this->pdf->SetFont(self::FONT_FAMILY, 'BU', self::FONT_SIZE);
        $this->pdf->SetXY($rightColX, $currentY);
        $this->pdf->Cell($rightColWidth, self::LINE_HEIGHT, $namaPetugas, 0, 0, 'C');
        $currentY += self::LINE_HEIGHT;
        
        // Row 4: Pangkat & NRP (right column only)
        $this->pdf->SetFont(self::FONT_FAMILY, '', self::FONT_SIZE);
        $pangkat = $petugas?->pangkat?->nama ?? $petugas?->pangkat ?? '';
        $nrp = $petugas?->nrp ?? '-';
        $pangkatNrp = trim("{$pangkat} NRP {$nrp}");
        
        $this->pdf->SetXY($rightColX, $currentY);
        $this->pdf->Cell($rightColWidth, self::LINE_HEIGHT, $pangkatNrp, 0, 0, 'C');
    }

    // =========================================================================
    // HELPER METHODS - AUTO DASH CALCULATION
    // =========================================================================

    /**
     * Write justified text with auto dashes at beginning and end
     * 
     * @param string $text Text content
     * @param int $prefixDashes Number of dashes before text (0 = no prefix)
     * @param bool $onlyTrailing Only add trailing dashes, no prefix
     */
    protected function writeJustifiedWithDashes(string $text, int $prefixDashes = 5, bool $onlyTrailing = false): void
    {
        $prefix = $prefixDashes > 0 ? str_repeat('-', $prefixDashes) . ' ' : '';
        if ($onlyTrailing) {
            $prefix = '';
        }
        
        $fullText = $prefix . $text . ' ';
        
        // Calculate how many dashes needed to fill the line
        $textWidth = $this->pdf->GetStringWidth($fullText);
        $dashWidth = $this->pdf->GetStringWidth('-');
        $availableWidth = self::CONTENT_WIDTH - $textWidth;
        
        // Calculate number of dashes
        $dashCount = max(0, (int)($availableWidth / $dashWidth));
        $trailingDashes = str_repeat('-', $dashCount);
        
        $finalText = $fullText . $trailingDashes;
        
        $this->pdf->SetFont(self::FONT_FAMILY, '', self::FONT_SIZE);
        $this->pdf->MultiCell(self::CONTENT_WIDTH, self::LINE_HEIGHT, $finalText, 0, 'J');
    }

    /**
     * Write centered text with dashes on both sides
     */
    protected function writeCenteredTextWithDashes(string $text): void
    {
        $textWidth = $this->pdf->GetStringWidth($text);
        $dashWidth = $this->pdf->GetStringWidth('-');
        
        // Calculate available space for dashes on each side
        $availableWidth = self::CONTENT_WIDTH - $textWidth - 4; // 4mm padding
        $dashesPerSide = max(5, (int)(($availableWidth / 2) / $dashWidth));
        
        $leftDashes = str_repeat('-', $dashesPerSide);
        $rightDashes = str_repeat('-', $dashesPerSide);
        
        $fullLine = $leftDashes . $text . $rightDashes;
        
        $this->pdf->SetFont(self::FONT_FAMILY, '', self::FONT_SIZE);
        $this->pdf->Cell(self::CONTENT_WIDTH, self::LINE_HEIGHT, $fullLine, 0, 1, 'C');
    }

    /**
     * Write identity row (label : value with auto dashes)
     * 
     * @param string $label Label text
     * @param string $value Value text
     * @param string $terminator Character at end of value (';' or '.')
     */
    protected function writeIdentityRow(string $label, string $value, string $terminator = ';'): void
    {
        $labelWidth = self::LABEL_WIDTH;
        $colonWidth = 3;
        $valueStartX = self::MARGIN_LEFT + $labelWidth + $colonWidth;
        $valueWidth = self::CONTENT_WIDTH - $labelWidth - $colonWidth;
        
        // Check if value is too long for single line
        $valueWithTerminator = $value . $terminator;
        $valueTextWidth = $this->pdf->GetStringWidth($valueWithTerminator);
        
        if ($valueTextWidth > $valueWidth - 10) {
            // Multi-line handling
            $this->writeIdentityRowMultiline($label, $value, $terminator);
            return;
        }
        
        // Single line
        $currentY = $this->pdf->GetY();
        
        // Label
        $this->pdf->SetFont(self::FONT_FAMILY, '', self::FONT_SIZE);
        $this->pdf->SetXY(self::MARGIN_LEFT, $currentY);
        $this->pdf->Cell($labelWidth, self::LINE_HEIGHT, $label, 0, 0, 'L');
        
        // Colon
        $this->pdf->Cell($colonWidth, self::LINE_HEIGHT, ':', 0, 0, 'L');
        
        // Value with auto trailing dashes
        $dashWidth = $this->pdf->GetStringWidth('-');
        $remainingWidth = $valueWidth - $valueTextWidth - 2;
        $dashCount = max(0, (int)($remainingWidth / $dashWidth));
        $trailingDashes = str_repeat('-', $dashCount);
        
        $finalValue = $valueWithTerminator . $trailingDashes;
        
        $this->pdf->Cell($valueWidth, self::LINE_HEIGHT, $finalValue, 0, 1, 'L');
    }

    /**
     * Write identity row with multiline value
     */
    protected function writeIdentityRowMultiline(string $label, string $value, string $terminator = ';'): void
    {
        $labelWidth = self::LABEL_WIDTH;
        $colonWidth = 3;
        $valueStartX = self::MARGIN_LEFT + $labelWidth + $colonWidth;
        $valueWidth = self::CONTENT_WIDTH - $labelWidth - $colonWidth;
        
        $currentY = $this->pdf->GetY();
        
        // Label
        $this->pdf->SetFont(self::FONT_FAMILY, '', self::FONT_SIZE);
        $this->pdf->SetXY(self::MARGIN_LEFT, $currentY);
        $this->pdf->Cell($labelWidth, self::LINE_HEIGHT, $label, 0, 0, 'L');
        
        // Colon
        $this->pdf->Cell($colonWidth, self::LINE_HEIGHT, ':', 0, 0, 'L');
        
        // Value - wrap text manually
        $words = explode(' ', $value);
        $lines = [];
        $currentLine = '';
        
        foreach ($words as $word) {
            $testLine = $currentLine === '' ? $word : $currentLine . ' ' . $word;
            $testWidth = $this->pdf->GetStringWidth($testLine);
            
            if ($testWidth > $valueWidth - 20) {
                // Line is full, start new line
                $lines[] = $currentLine;
                $currentLine = $word;
            } else {
                $currentLine = $testLine;
            }
        }
        
        // Add last line with terminator
        if ($currentLine !== '') {
            $lines[] = $currentLine;
        }
        
        // Write lines
        $isFirstLine = true;
        foreach ($lines as $index => $line) {
            $isLastLine = ($index === count($lines) - 1);
            
            if (!$isFirstLine) {
                // Indent continuation lines
                $this->pdf->SetXY($valueStartX, $this->pdf->GetY());
            }
            
            // Add terminator and dashes to last line
            if ($isLastLine) {
                $lineWithTerminator = $line . $terminator;
                $lineWidth = $this->pdf->GetStringWidth($lineWithTerminator);
                $dashWidth = $this->pdf->GetStringWidth('-');
                $remainingWidth = $valueWidth - $lineWidth - 2;
                $dashCount = max(0, (int)($remainingWidth / $dashWidth));
                $trailingDashes = str_repeat('-', $dashCount);
                
                $this->pdf->Cell($valueWidth, self::LINE_HEIGHT, $lineWithTerminator . $trailingDashes, 0, 1, 'L');
            } else {
                $this->pdf->Cell($valueWidth, self::LINE_HEIGHT, $line, 0, 1, 'L');
            }
            
            $isFirstLine = false;
        }
    }

    // =========================================================================
    // FORMAT HELPERS
    // =========================================================================

    /**
     * Format STPA Number
     * Format: STPA/ [nomor] /[bulan_romawi]/[tahun]/Ditressiber
     */
    protected function formatNomorStpa(Laporan $laporan): string
    {
        $tanggal = $laporan->tanggal_laporan ?? now();
        $romanMonth = self::ROMAN_MONTHS[$tanggal->month] ?? 'I';
        $year = $tanggal->format('Y');
        
        // Extract just the number from nomor_stpa
        $nomorStpa = $laporan->nomor_stpa ?? '';
        
        if (preg_match('/STPA\/\s*(\d+)\s*\//', $nomorStpa, $matches)) {
            $number = $matches[1];
        } elseif (preg_match('/(\d+)/', $nomorStpa, $matches)) {
            $number = $matches[1];
        } else {
            $number = '...';
        }
        
        return "STPA/ {$number} /{$romanMonth}/{$year}/Ditressiber";
    }

    /**
     * Format Tanggal Indonesia
     */
    protected function formatTanggalIndonesia($date): string
    {
        if (!$date) return '-';
        
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);
        
        return $carbon->format('d') . ' ' . 
               self::MONTH_NAMES[$carbon->month] . ' ' . 
               $carbon->format('Y');
    }

    /**
     * Format Alamat
     */
    protected function formatAlamat($alamat): string
    {
        if (!$alamat) return '-';
        
        $parts = [];
        
        if ($alamat->detail_alamat) {
            $parts[] = $alamat->detail_alamat;
        }
        
        // RT/RW
        $rtRw = [];
        if ($alamat->rt) $rtRw[] = "RT {$alamat->rt}";
        if ($alamat->rw) $rtRw[] = "RW {$alamat->rw}";
        if (!empty($rtRw)) {
            $parts[] = implode(' ', $rtRw);
        }
        
        // Kelurahan
        if ($alamat->relationLoaded('kelurahan') && $alamat->kelurahan) {
            $parts[] = $alamat->kelurahan->nama;
        } elseif ($alamat->kode_kelurahan) {
            $wilayah = Wilayah::where('kode', $alamat->kode_kelurahan)->first();
            if ($wilayah) $parts[] = $wilayah->nama;
        }
        
        // Kecamatan
        if ($alamat->relationLoaded('kecamatan') && $alamat->kecamatan) {
            $parts[] = $alamat->kecamatan->nama;
        } elseif ($alamat->kode_kecamatan) {
            $wilayah = Wilayah::where('kode', $alamat->kode_kecamatan)->first();
            if ($wilayah) $parts[] = $wilayah->nama;
        }
        
        // Kabupaten
        if ($alamat->relationLoaded('kabupaten') && $alamat->kabupaten) {
            $parts[] = $alamat->kabupaten->nama;
        } elseif ($alamat->kode_kabupaten) {
            $wilayah = Wilayah::where('kode', $alamat->kode_kabupaten)->first();
            if ($wilayah) $parts[] = $wilayah->nama;
        }
        
        return implode(', ', $parts) ?: '-';
    }

    /**
     * Format Lokasi Kejadian
     */
    protected function formatLokasiKejadian(Laporan $laporan): string
    {
        $parts = [];
        
        if ($laporan->kode_kabupaten_kejadian) {
            $kabupaten = Wilayah::where('kode', $laporan->kode_kabupaten_kejadian)->first();
            if ($kabupaten) {
                $parts[] = $kabupaten->nama;
            }
        }
        
        return implode(', ', $parts) ?: '-';
    }

    /**
     * Format Kerugian
     * Format: Rp. xxx.xxx,- (Terbilang Rupiah)
     */
    protected function formatKerugian(Laporan $laporan): string
    {
        $totalKerugian = 0;
        
        if ($laporan->korban) {
            foreach ($laporan->korban as $korban) {
                $totalKerugian += $korban->kerugian_nominal ?? 0;
            }
        }
        
        if ($totalKerugian <= 0) {
            return '- (tidak ada kerugian material)';
        }
        
        $formatted = 'Rp. ' . number_format($totalKerugian, 0, ',', '.');
        
        // Get terbilang
        $terbilang = $laporan->korban->first()?->kerugian_terbilang;
        if (!$terbilang) {
            $terbilang = TerbilangService::convert($totalKerugian);
        }
        
        return "{$formatted},- ({$terbilang})";
    }

    /**
     * Format Korban
     * Format: Sdr./Sdri. [Nama]
     */
    protected function formatKorban(Laporan $laporan): string
    {
        if (!$laporan->korban || $laporan->korban->isEmpty()) {
            return '- (tidak ada korban tercatat)';
        }
        
        $korbanList = $laporan->korban->map(function($korban) {
            $nama = $korban->orang?->nama ?? 'Tidak diketahui';
            $jenisKelamin = $korban->orang?->jenis_kelamin ?? 'LAKI-LAKI';
            $prefix = $jenisKelamin === 'PEREMPUAN' ? 'Sdri.' : 'Sdr.';
            return "{$prefix} {$nama}";
        })->implode(', ');
        
        return $korbanList;
    }
}
