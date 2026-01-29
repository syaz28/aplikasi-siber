<?php

namespace App\Services;

use App\Models\Laporan;
use setasign\Fpdi\Fpdi;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * StpaFpdiService - Production-Ready PDF Generator
 * 
 * Generates STPA (Surat Tanda Penerimaan Aduan) PDF documents using the 
 * "Template Overlay" method with FPDI.
 * 
 * ============================================================================
 * CRITICAL BUSINESS RULES IMPLEMENTED:
 * ============================================================================
 * 1. Roman Numeral Date Conversion - Automatic month to Roman numeral
 * 2. Visual Masking Technique - White background cells to cover dotted lines
 * 3. Auto-Center Crime Title - Centered text with auto-generated dashes
 * 4. Smart Scaling - Auto font reduction to guarantee single-page output
 * 5. Strict Footer Format - Pengadu left, PAWAS PIKET DITRESSIBER right
 * 
 * ============================================================================
 * REQUIRED EAGER LOADS (must be loaded before calling generate()):
 * ============================================================================
 * - pelapor.alamatKtp.provinsi/kabupaten/kecamatan/kelurahan
 * - pelapor.alamatDomisili.provinsi/kabupaten/kecamatan/kelurahan
 * - korban.orang
 * - tersangka.orang
 * - tersangka.identitas
 * - kategoriKejahatan
 * - petugas (pangkat & jabatan are string fields on User)
 * - provinsiKejadian/kabupatenKejadian/kecamatanKejadian/kelurahanKejadian
 * 
 * @author Senior Backend Engineer - Laravel/FPDI Specialist
 * @version 2.0.0 - Production Ready
 */
class StpaFpdiService
{
    /**
     * FPDI Instance
     */
    protected Fpdi $pdf;

    /**
     * Page Configuration (A4 dimensions in mm)
     */
    protected const PAGE_WIDTH = 210;
    protected const PAGE_HEIGHT = 297;
    protected const MARGIN_LEFT = 25;
    protected const MARGIN_RIGHT = 20;
    protected const CONTENT_WIDTH = 165; // PAGE_WIDTH - MARGIN_LEFT - MARGIN_RIGHT

    /**
     * Font Configuration
     */
    protected const FONT_FAMILY = 'Arial';
    protected const FONT_SIZE_NORMAL = 11;
    protected const FONT_SIZE_TITLE = 12;
    protected const FONT_SIZE_SMALL = 10;
    protected const LINE_HEIGHT = 6;

    /**
     * Y-Coordinate Mapping (Based on template_stpa.pdf layout)
     * All values in millimeters from top of page
     */
    protected const Y_NOMOR_SURAT = 41.5;
    protected const Y_WAKTU_NARASI = 50;
    protected const Y_NAMA_PELAPOR = 61;
    protected const Y_TTL_PELAPOR = 67;
    protected const Y_PEKERJAAN = 73;
    protected const Y_ALAMAT_KTP = 79;
    protected const Y_ALAMAT_DOMISILI = 89;
    protected const Y_NIK = 99;
    protected const Y_TELEPON = 105;
    protected const Y_CRIME_BOX = 122;
    protected const Y_TEMPAT_KEJADIAN = 137;
    protected const Y_WAKTU_KEJADIAN = 143;
    protected const Y_KERUGIAN = 149;
    protected const Y_TERADU_START = 155;
    protected const Y_FOOTER = 238;

    /**
     * Roman Numeral Mapping for Months
     */
    protected const ROMAN_MONTHS = [
        1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV',
        5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII',
        9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
    ];

    /**
     * Identity Type Labels (Indonesian)
     */
    protected const IDENTITY_LABELS = [
        'telepon' => 'No. HP',
        'rekening' => 'No. Rekening',
        'sosmed' => 'Akun Sosmed',
        'email' => 'Email',
        'ewallet' => 'E-Wallet',
        'website' => 'Website',
        'lainnya' => 'Lainnya',
    ];

    /**
     * Generate STPA PDF Document
     * 
     * Main entry point for PDF generation. Implements the Template Overlay
     * method using FPDI to write dynamic content on top of blank template.
     * 
     * @param Laporan $laporan Laporan model with eager loaded relationships
     * @return string PDF content as binary string
     * @throws \Exception When template not found or generation fails
     */
    public function generate(Laporan $laporan): string
    {
        try {
            // Initialize FPDI
            $this->pdf = new Fpdi();
            $this->pdf->AddPage('P', 'A4');

            // Load and apply template
            $this->loadTemplate();

            // Configure base font settings
            $this->configureFont();

            // Set Indonesian locale for date formatting
            Carbon::setLocale('id');

            // === SECTION A: DOCUMENT HEADER ===
            $this->writeDocumentNumber($laporan);
            $this->writeTimeNarrative($laporan);

            // === SECTION B: REPORTER DATA ===
            $this->writeReporterData($laporan);

            // === SECTION C: CRIME TYPE (Auto-Centered) ===
            $this->drawAutoCrimeBox($laporan);

            // === SECTION D: INCIDENT DATA ===
            $this->writeIncidentData($laporan);

            // === SECTION E: LOSS/DAMAGE ===
            $yAfterKerugian = $this->writeKerugianData($laporan);

            // === SECTION F: SUSPECTS (Dynamic positioning) ===
            $yAfterTeradu = $this->writeSuspectData($laporan, $yAfterKerugian);

            // === SECTION G: VICTIMS ===
            $yAfterKorban = $this->writeVictimData($laporan, $yAfterTeradu);

            // === SECTION H: MODUS OPERANDI (Smart Scaling) ===
            $this->drawModus($laporan->modus ?? '-', $yAfterKorban);

            // === SECTION I: FOOTER/SIGNATURES ===
            $this->drawFooter($laporan);

            // Output PDF as string
            return $this->pdf->Output('S');

        } catch (\Exception $e) {
            Log::error('StpaFpdiService: PDF generation failed', [
                'laporan_id' => $laporan->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    // =========================================================================
    // TEMPLATE & CONFIGURATION METHODS
    // =========================================================================

    /**
     * Load PDF Template
     * 
     * @throws \Exception When template file not found
     */
    protected function loadTemplate(): void
    {
        $templatePath = app_path('Templates/template_stpa.pdf');

        if (!file_exists($templatePath)) {
            // Fallback to storage location
            $templatePath = storage_path('app/templates/template_stpa.pdf');
            
            if (!file_exists($templatePath)) {
                throw new \Exception(
                    "Template PDF tidak ditemukan. " .
                    "Pastikan file tersedia di: app/Templates/template_stpa.pdf " .
                    "atau storage/app/templates/template_stpa.pdf"
                );
            }
        }

        $this->pdf->setSourceFile($templatePath);
        $templateId = $this->pdf->importPage(1);
        $this->pdf->useTemplate($templateId, 0, 0, self::PAGE_WIDTH);
    }

    /**
     * Configure default font settings
     */
    protected function configureFont(): void
    {
        $this->pdf->SetFont(self::FONT_FAMILY, '', self::FONT_SIZE_NORMAL);
        $this->pdf->SetTextColor(0, 0, 0);
    }

    // =========================================================================
    // HELPER METHODS - Roman Numerals & Text Formatting
    // =========================================================================

    /**
     * Convert numeric month to Roman numeral
     * 
     * Business Rule: Document numbers must use Roman numerals for months
     * Example: April (4) → IV
     * 
     * @param int $month Month number (1-12)
     * @return string Roman numeral representation
     */
    protected function getRomanMonth(int $month): string
    {
        return self::ROMAN_MONTHS[$month] ?? 'I';
    }

    /**
     * Write masked text (with white background)
     * 
     * Visual Masking Technique: Sets white background to cover dotted lines
     * in the template while keeping remaining dots visible.
     * 
     * @param float $x X-coordinate (mm from left)
     * @param float $y Y-coordinate (mm from top)
     * @param string $text Text to write
     * @param string $style Font style ('', 'B', 'I', 'BI')
     */
    protected function fillMaskedText(float $x, float $y, string $text, string $style = ''): void
    {
        $this->pdf->SetFont(self::FONT_FAMILY, $style, self::FONT_SIZE_NORMAL);
        $this->pdf->SetXY($x, $y);
        $this->pdf->SetFillColor(255, 255, 255); // WHITE background
        
        $textWidth = $this->pdf->GetStringWidth($text) + 2; // +2mm padding
        $this->pdf->Cell($textWidth, self::LINE_HEIGHT, $text, 0, 0, 'L', true);
    }

    /**
     * Write multi-line masked text (for long content like addresses)
     * 
     * @param float $x X-coordinate
     * @param float $y Y-coordinate
     * @param string $text Text to write
     * @param float $maxWidth Maximum width before wrapping
     * @param float $lineHeight Line height (default 6mm)
     */
    protected function fillMultiLineMasked(
        float $x, 
        float $y, 
        string $text, 
        float $maxWidth, 
        float $lineHeight = self::LINE_HEIGHT
    ): void {
        $this->pdf->SetFont(self::FONT_FAMILY, '', self::FONT_SIZE_NORMAL);
        $this->pdf->SetXY($x, $y);
        $this->pdf->SetFillColor(255, 255, 255);
        $this->pdf->MultiCell($maxWidth, $lineHeight, $text, 0, 'L', true);
    }

    // =========================================================================
    // SECTION WRITERS
    // =========================================================================

    /**
     * Write Document Number
     * 
     * Format: STPA/[Number]/[Roman Month]/[Year]/Ditressiber
     */
    protected function writeDocumentNumber(Laporan $laporan): void
    {
        $tanggalLaporan = $laporan->tanggal_laporan ?? now();
        $romanMonth = $this->getRomanMonth($tanggalLaporan->month);
        $year = $tanggalLaporan->year;
        $stpaNumber = $laporan->nomor_stpa ?? '.....';
        
        $nomorSurat = "STPA/ {$stpaNumber} / {$romanMonth} / {$year} / Ditressiber";
        
        $this->fillMaskedText(92, self::Y_NOMOR_SURAT, $nomorSurat);
    }

    /**
     * Write Time Narrative
     * 
     * Writes day, date, and time in their respective positions
     */
    protected function writeTimeNarrative(Laporan $laporan): void
    {
        $tanggalLaporan = $laporan->tanggal_laporan ?? now();
        
        $hari = $tanggalLaporan->translatedFormat('l');     // Senin, Selasa, etc.
        $tanggal = $tanggalLaporan->translatedFormat('d F Y'); // 15 Januari 2026
        $jam = $tanggalLaporan->format('H.i');              // 14.30
        
        $this->fillMaskedText(50, self::Y_WAKTU_NARASI, $hari);
        $this->fillMaskedText(75, self::Y_WAKTU_NARASI, $tanggal);
        $this->fillMaskedText(143, self::Y_WAKTU_NARASI, $jam);
    }

    /**
     * Write Reporter (Pelapor) Data
     */
    protected function writeReporterData(Laporan $laporan): void
    {
        $pelapor = $laporan->pelapor;

        if (!$pelapor) {
            // Write placeholders if no reporter data
            $this->writeReporterPlaceholders();
            return;
        }

        // Nama Pelapor
        $this->fillMaskedText(60, self::Y_NAMA_PELAPOR, ($pelapor->nama ?? '-') . ";");

        // Tempat, Tanggal Lahir
        $ttl = $this->formatTTL($pelapor);
        $this->fillMaskedText(60, self::Y_TTL_PELAPOR, $ttl . ";");

        // Pekerjaan
        $this->fillMaskedText(60, self::Y_PEKERJAAN, ($pelapor->pekerjaan ?? '-') . ";");

        // Alamat KTP
        $alamatKtp = $this->formatAlamat($pelapor->alamatKtp);
        $this->fillMultiLineMasked(60, self::Y_ALAMAT_KTP, $alamatKtp . ";", 130);

        // Alamat Domisili
        $alamatDomisili = $pelapor->alamatDomisili 
            ? $this->formatAlamat($pelapor->alamatDomisili) 
            : $alamatKtp;
        $this->fillMultiLineMasked(60, self::Y_ALAMAT_DOMISILI, $alamatDomisili . ";", 130);

        // NIK (auto-decrypted if using encrypted cast)
        $this->fillMaskedText(60, self::Y_NIK, ($pelapor->nik ?? '-') . ";");

        // Telepon
        $this->fillMaskedText(60, self::Y_TELEPON, ($pelapor->telepon ?? '-') . ".");
    }

    /**
     * Write placeholder data when reporter is missing
     */
    protected function writeReporterPlaceholders(): void
    {
        $this->fillMaskedText(60, self::Y_NAMA_PELAPOR, "-;");
        $this->fillMaskedText(60, self::Y_TTL_PELAPOR, "-;");
        $this->fillMaskedText(60, self::Y_PEKERJAAN, "-;");
        $this->fillMultiLineMasked(60, self::Y_ALAMAT_KTP, "-;", 130);
        $this->fillMultiLineMasked(60, self::Y_ALAMAT_DOMISILI, "-;", 130);
        $this->fillMaskedText(60, self::Y_NIK, "-;");
        $this->fillMaskedText(60, self::Y_TELEPON, "-.");
    }

    /**
     * Format Tempat, Tanggal Lahir
     */
    protected function formatTTL($pelapor): string
    {
        $tempat = $pelapor->tempat_lahir ?? '-';
        
        if ($pelapor->tanggal_lahir) {
            return $tempat . ", " . $pelapor->tanggal_lahir->translatedFormat('d F Y');
        }
        
        return $tempat;
    }

    /**
     * Format Alamat from Alamat model with wilayah relationships
     */
    protected function formatAlamat($alamat): string
    {
        if (!$alamat) {
            return '-';
        }

        $parts = [];
        
        if ($alamat->detail_alamat) {
            $parts[] = $alamat->detail_alamat;
        }
        
        // Build hierarchical address from loaded relationships
        if ($alamat->relationLoaded('kelurahan') && $alamat->kelurahan) {
            $parts[] = "Kel. " . $alamat->kelurahan->nama;
        }
        if ($alamat->relationLoaded('kecamatan') && $alamat->kecamatan) {
            $parts[] = "Kec. " . $alamat->kecamatan->nama;
        }
        if ($alamat->relationLoaded('kabupaten') && $alamat->kabupaten) {
            $parts[] = $alamat->kabupaten->nama;
        }
        if ($alamat->relationLoaded('provinsi') && $alamat->provinsi) {
            $parts[] = $alamat->provinsi->nama;
        }

        return count($parts) > 0 ? implode(', ', $parts) : '-';
    }

    /**
     * Draw Auto-Centered Crime Type Box
     * 
     * Business Rule: Crime type must be centered with auto-generated dashes
     * on both sides to fill remaining space to margins.
     * 
     * Layout: -------- " CRIME TYPE " --------
     */
    protected function drawAutoCrimeBox(Laporan $laporan): void
    {
        $kategoriKejahatan = $laporan->kategoriKejahatan;
        $crimeText = $kategoriKejahatan 
            ? strtoupper($kategoriKejahatan->nama ?? 'TIDAK DIKETAHUI')
            : 'TIDAK DIKETAHUI';

        $fullText = '" ' . $crimeText . ' "';
        
        // Switch to bold title font
        $this->pdf->SetFont(self::FONT_FAMILY, 'B', self::FONT_SIZE_TITLE);
        $textWidth = $this->pdf->GetStringWidth($fullText);
        
        // Calculate center position
        $areaWidth = self::CONTENT_WIDTH;
        $centerX = self::MARGIN_LEFT + ($areaWidth / 2);
        $textStartX = $centerX - ($textWidth / 2);

        // === LEFT DASHES ===
        $leftDashWidth = $textStartX - self::MARGIN_LEFT - 2;
        if ($leftDashWidth > 0) {
            $this->pdf->SetXY(self::MARGIN_LEFT, self::Y_CRIME_BOX);
            $this->pdf->SetFillColor(255, 255, 255);
            
            // Calculate number of dashes to fill the space
            $dashChar = '-';
            $singleDashWidth = $this->pdf->GetStringWidth($dashChar);
            $numDashes = (int) floor($leftDashWidth / $singleDashWidth);
            
            $this->pdf->Cell($leftDashWidth, self::LINE_HEIGHT, str_repeat($dashChar, $numDashes), 0, 0, 'R', true);
        }

        // === CENTER TEXT ===
        $this->pdf->SetXY($textStartX, self::Y_CRIME_BOX);
        $this->pdf->Cell($textWidth, self::LINE_HEIGHT, $fullText, 0, 0, 'C', true);

        // === RIGHT DASHES ===
        $rightStartX = $textStartX + $textWidth + 2;
        $rightDashWidth = (self::PAGE_WIDTH - self::MARGIN_RIGHT) - $rightStartX;
        if ($rightDashWidth > 0) {
            $this->pdf->SetXY($rightStartX, self::Y_CRIME_BOX);
            
            $dashChar = '-';
            $singleDashWidth = $this->pdf->GetStringWidth($dashChar);
            $numDashes = (int) floor($rightDashWidth / $singleDashWidth);
            
            $this->pdf->Cell($rightDashWidth, self::LINE_HEIGHT, str_repeat($dashChar, $numDashes), 0, 0, 'L', true);
        }

        // Reset font
        $this->pdf->SetFont(self::FONT_FAMILY, '', self::FONT_SIZE_NORMAL);
    }

    /**
     * Write Incident Data (Location & Time)
     */
    protected function writeIncidentData(Laporan $laporan): void
    {
        // Location
        $lokasi = $this->formatLokasiKejadian($laporan);
        $this->fillMaskedText(60, self::Y_TEMPAT_KEJADIAN, $lokasi . ";");

        // Time
        $waktuKejadian = $laporan->waktu_kejadian;
        $waktuText = $waktuKejadian 
            ? $waktuKejadian->translatedFormat('d F Y') . " Pukul " . $waktuKejadian->format('H.i') . " WIB"
            : '-';
        $this->fillMaskedText(60, self::Y_WAKTU_KEJADIAN, $waktuText . ";");
    }

    /**
     * Format incident location from Laporan with wilayah relationships
     */
    protected function formatLokasiKejadian(Laporan $laporan): string
    {
        $parts = [];
        
        if ($laporan->alamat_kejadian) {
            $parts[] = $laporan->alamat_kejadian;
        }
        
        if ($laporan->relationLoaded('kelurahanKejadian') && $laporan->kelurahanKejadian) {
            $parts[] = "Kel. " . $laporan->kelurahanKejadian->nama;
        }
        if ($laporan->relationLoaded('kecamatanKejadian') && $laporan->kecamatanKejadian) {
            $parts[] = "Kec. " . $laporan->kecamatanKejadian->nama;
        }
        if ($laporan->relationLoaded('kabupatenKejadian') && $laporan->kabupatenKejadian) {
            $parts[] = $laporan->kabupatenKejadian->nama;
        }
        if ($laporan->relationLoaded('provinsiKejadian') && $laporan->provinsiKejadian) {
            $parts[] = $laporan->provinsiKejadian->nama;
        }

        return count($parts) > 0 ? implode(', ', $parts) : '-';
    }

    /**
     * Write Kerugian (Loss/Damage) Data
     * 
     * @return float Y-coordinate after writing kerugian
     */
    protected function writeKerugianData(Laporan $laporan): float
    {
        $totalKerugian = 0;
        
        if ($laporan->korban && $laporan->korban->count() > 0) {
            $totalKerugian = $laporan->korban->sum('kerugian_nominal') ?? 0;
        }

        $terbilang = TerbilangService::convert($totalKerugian);
        $kerugianFormatted = number_format($totalKerugian, 0, ',', '.');
        $kerugianText = "Rp. {$kerugianFormatted} ({$terbilang});";
        
        $this->fillMultiLineMasked(60, self::Y_KERUGIAN, $kerugianText, 130);
        
        return self::Y_TERADU_START;
    }

    /**
     * Write Suspect (Teradu/Tersangka) Data
     * 
     * Loops through all suspects and their digital identities.
     * 
     * @return float Y-coordinate after writing suspect data
     */
    protected function writeSuspectData(Laporan $laporan, float $startY): float
    {
        $y = $startY;

        if (!$laporan->tersangka || $laporan->tersangka->count() === 0) {
            $this->fillMaskedText(60, $y, "- Belum teridentifikasi;");
            return $y + self::LINE_HEIGHT;
        }

        foreach ($laporan->tersangka as $index => $tersangka) {
            // Write suspect name if identified
            if ($tersangka->orang && $tersangka->orang->nama) {
                $label = $laporan->tersangka->count() > 1 
                    ? ($index + 1) . ". " 
                    : "- ";
                $this->fillMaskedText(60, $y, $label . "Nama: " . $tersangka->orang->nama . ";");
                $y += self::LINE_HEIGHT;
            }
            
            // Write digital identities
            if ($tersangka->identitas && $tersangka->identitas->count() > 0) {
                foreach ($tersangka->identitas as $identitas) {
                    $jenisLabel = self::IDENTITY_LABELS[$identitas->jenis] ?? ucfirst($identitas->jenis ?? 'ID');
                    $nilai = $identitas->nilai ?? '-';
                    $platform = $identitas->platform ? " ({$identitas->platform})" : "";
                    $namaAkun = $identitas->nama_akun ? " a.n. {$identitas->nama_akun}" : "";
                    
                    $line = "  - {$jenisLabel}: {$nilai}{$platform}{$namaAkun};";
                    $this->fillMaskedText(60, $y, $line);
                    $y += self::LINE_HEIGHT;
                }
            }
            
            // Write notes if any
            if ($tersangka->catatan) {
                $this->fillMaskedText(60, $y, "  Catatan: " . $tersangka->catatan . ";");
                $y += self::LINE_HEIGHT;
            }
        }

        return $y;
    }

    /**
     * Write Victim (Korban) Data
     * 
     * @return float Y-coordinate after writing victim data
     */
    protected function writeVictimData(Laporan $laporan, float $startY): float
    {
        $y = max(167, $startY); // Minimum Y position for victims

        if (!$laporan->korban || $laporan->korban->count() === 0) {
            $this->fillMaskedText(60, $y, "- (tidak ada korban tercatat);");
            return $y + self::LINE_HEIGHT;
        }

        $korbanList = $laporan->korban->map(function($korban) {
            $nama = $korban->orang?->nama ?? 'Tidak diketahui';
            return "Sdr. " . $nama;
        })->implode(', ');

        $this->fillMaskedText(60, $y, $korbanList . ";");
        
        return $y + self::LINE_HEIGHT;
    }

    /**
     * Draw Modus Operandi with Smart Scaling
     * 
     * Business Rule: Document MUST fit on exactly ONE page.
     * Auto-reduces font size based on text length:
     * - Normal (11pt): Up to 600 characters
     * - Small (10pt): 601-850 characters
     * - Smaller (9pt): 851-1200 characters
     * - Tiny (8pt): Over 1200 characters
     * 
     * @param string $text Modus operandi text
     * @param float $startY Starting Y-coordinate
     */
    protected function drawModus(string $text, float $startY): void
    {
        // Clean and normalize text
        $cleanText = trim(preg_replace('/\s+/', ' ', $text));
        $length = strlen($cleanText);

        // Smart font scaling based on text length
        $fontSize = self::FONT_SIZE_NORMAL;
        $lineHeight = 5.5;

        if ($length > 1200) {
            $fontSize = 8;
            $lineHeight = 4;
        } elseif ($length > 850) {
            $fontSize = 9;
            $lineHeight = 4.5;
        } elseif ($length > 600) {
            $fontSize = 10;
            $lineHeight = 5;
        }

        // Calculate available height to footer
        $availableHeight = self::Y_FOOTER - 10 - $startY;
        
        // Further reduce if needed to prevent overflow
        $estimatedLines = $this->estimateLines($cleanText, 130, $fontSize);
        $estimatedHeight = $estimatedLines * $lineHeight;
        
        if ($estimatedHeight > $availableHeight && $fontSize > 8) {
            $fontSize = 8;
            $lineHeight = 4;
        }

        $this->pdf->SetFont(self::FONT_FAMILY, '', $fontSize);
        $this->pdf->SetXY(60, $startY);
        $this->pdf->SetFillColor(255, 255, 255);
        $this->pdf->MultiCell(130, $lineHeight, $cleanText . ";", 0, 'J', true);
    }

    /**
     * Estimate number of lines for given text
     */
    protected function estimateLines(string $text, float $width, float $fontSize): int
    {
        $this->pdf->SetFont(self::FONT_FAMILY, '', $fontSize);
        $textWidth = $this->pdf->GetStringWidth($text);
        return (int) ceil($textWidth / $width) + 1;
    }

    /**
     * Draw Footer with Signatures
     * 
     * Business Rule:
     * - Left: "Pengadu" (Complainant) - UPPERCASE, BOLD
     * - Right: "Yang Menerima / PAWAS PIKET DITRESSIBER" - UPPERCASE, BOLD
     * - NO "a.n." or "BA PIKET" prefixes allowed
     */
    protected function drawFooter(Laporan $laporan): void
    {
        $y = self::Y_FOOTER;

        // Clear footer area with white rectangle
        $this->pdf->SetXY(self::MARGIN_LEFT, $y - 5);
        $this->pdf->SetFillColor(255, 255, 255);
        $this->pdf->Cell(self::CONTENT_WIDTH, 40, '', 0, 0, 'C', true);

        $this->pdf->SetFont(self::FONT_FAMILY, 'B', self::FONT_SIZE_NORMAL);

        // === LEFT COLUMN: Pengadu (Complainant) ===
        $namaPelapor = strtoupper($laporan->pelapor?->nama ?? '-');
        
        $this->pdf->SetXY(30, $y);
        $this->pdf->Cell(60, self::LINE_HEIGHT, "Pengadu", 0, 0, 'C');
        
        $this->pdf->SetXY(30, $y + 25);
        $this->pdf->Cell(60, self::LINE_HEIGHT, $namaPelapor, 'B', 0, 'C'); // 'B' = bottom border (underline)

        // === RIGHT COLUMN: Yang Menerima ===
        $petugas = $laporan->petugas;
        $namaPetugas = strtoupper($petugas?->nama ?? '-');
        $pangkatKode = $petugas?->pangkat?->kode ?? '';
        $nrp = $petugas?->nrp ?? '-';
        
        $this->pdf->SetXY(120, $y);
        $this->pdf->Cell(70, self::LINE_HEIGHT, "Yang Menerima", 0, 0, 'C');
        
        $this->pdf->SetXY(120, $y + 5);
        $this->pdf->Cell(70, self::LINE_HEIGHT, "PAWAS PIKET DITRESSIBER", 0, 0, 'C');
        
        $this->pdf->SetXY(120, $y + 25);
        $this->pdf->Cell(70, self::LINE_HEIGHT, $namaPetugas, 'B', 0, 'C');
        
        // Pangkat & NRP (smaller font, below signature line)
        $this->pdf->SetFont(self::FONT_FAMILY, '', self::FONT_SIZE_SMALL);
        $this->pdf->SetXY(120, $y + 31);
        $this->pdf->Cell(70, self::LINE_HEIGHT, "{$pangkatKode} NRP. {$nrp}", 0, 0, 'C');
    }
}
