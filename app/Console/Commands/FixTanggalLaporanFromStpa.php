<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-time fix: Recalculate tanggal_laporan from the authoritative
 * STPA number (format: STPA/{no}/{ROMAN_MONTH}/{YEAR}/Ditressiber).
 *
 * The Excel import stored wrong dates for many records because the
 * parseDate function mis-interpreted Excel serial numbers. The STPA
 * number is the ground truth for month & year of the report.
 */
class FixTanggalLaporanFromStpa extends Command
{
    protected $signature = 'fix:tanggal-laporan {--dry-run : Preview changes without applying}';

    protected $description = 'Fix tanggal_laporan using month/year extracted from STPA number';

    /**
     * Roman numeral to integer map.
     */
    private const ROMAN = [
        'I'    => 1,
        'II'   => 2,
        'III'  => 3,
        'IV'   => 4,
        'V'    => 5,
        'VI'   => 6,
        'VII'  => 7,
        'VIII' => 8,
        'IX'   => 9,
        'X'    => 10,
        'XI'   => 11,
        'XII'  => 12,
    ];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info($dryRun ? '=== DRY RUN — no changes will be made ===' : '=== LIVE RUN ===');
        $this->newLine();

        // ── Step 1: Fix records that have parseable STPA numbers ────
        $records = DB::table('laporan')
            ->select('id', 'nomor_stpa', 'tanggal_laporan', 'created_at')
            ->whereNotNull('nomor_stpa')
            ->where('nomor_stpa', 'LIKE', 'STPA/%')
            ->get();

        $fixedStpa    = 0;
        $skippedStpa  = 0;
        $alreadyOk    = 0;

        foreach ($records as $row) {
            $parsed = $this->parseStpa($row->nomor_stpa);

            if (!$parsed) {
                $skippedStpa++;
                continue;
            }

            [$month, $year] = $parsed;
            $correctDate = sprintf('%04d-%02d-01 00:00:00', $year, $month);

            // Check if the stored month/year already matches
            $storedDate = substr($row->tanggal_laporan, 0, 7); // "YYYY-MM"
            $correctYm  = sprintf('%04d-%02d', $year, $month);

            if ($storedDate === $correctYm) {
                $alreadyOk++;
                continue;
            }

            if ($dryRun) {
                $this->line("  [DRY] id={$row->id}  STPA: {$row->nomor_stpa}");
                $this->line("        stored: {$row->tanggal_laporan}  →  correct: {$correctDate}");
            } else {
                DB::table('laporan')
                    ->where('id', $row->id)
                    ->update(['tanggal_laporan' => $correctDate]);
            }

            $fixedStpa++;
        }

        $this->info("STPA-based fixes: {$fixedStpa} updated, {$alreadyOk} already correct, {$skippedStpa} unparseable");
        $this->newLine();

        // ── Step 2: Fix remaining future-dated records (non-STPA) ───
        $futureRecords = DB::table('laporan')
            ->select('id', 'nomor_stpa', 'tanggal_laporan', 'created_at')
            ->whereRaw('tanggal_laporan > NOW()')
            ->where(function ($q) {
                $q->whereNull('nomor_stpa')
                  ->orWhere('nomor_stpa', 'NOT LIKE', 'STPA/%');
            })
            ->get();

        $fixedFuture = 0;
        foreach ($futureRecords as $row) {
            // Fallback: use created_at as the best available date
            $fallback = $row->created_at;

            if ($dryRun) {
                $this->line("  [DRY] id={$row->id}  nomor: {$row->nomor_stpa}");
                $this->line("        stored: {$row->tanggal_laporan}  →  fallback: {$fallback}");
            } else {
                DB::table('laporan')
                    ->where('id', $row->id)
                    ->update(['tanggal_laporan' => $fallback]);
            }

            $fixedFuture++;
        }

        $this->info("Future-date fallback fixes: {$fixedFuture}");
        $this->newLine();

        $total = $fixedStpa + $fixedFuture;
        $this->info("Total: {$total} records " . ($dryRun ? 'would be fixed' : 'fixed'));

        return Command::SUCCESS;
    }

    /**
     * Extract (month, year) from an STPA number.
     *
     * Format: STPA/{number}/{ROMAN_MONTH}/{YEAR}/...
     * Example: "STPA/318/XII/2024/ Ditressiber" → [12, 2024]
     */
    private function parseStpa(string $stpa): ?array
    {
        // Match pattern: STPA / {number} / {roman} / {4-digit-year}
        if (!preg_match('#STPA\s*/\s*\d+\s*/\s*(I{1,3}|IV|VI{0,3}|IX|X{0,3}I{0,3}|XI{0,2})\s*/\s*(\d{4})#i', $stpa, $m)) {
            return null;
        }

        $romanStr = strtoupper(trim($m[1]));
        $year     = (int) $m[2];

        $month = self::ROMAN[$romanStr] ?? null;

        if (!$month || $year < 2000 || $year > 2100) {
            return null;
        }

        return [$month, $year];
    }
}
