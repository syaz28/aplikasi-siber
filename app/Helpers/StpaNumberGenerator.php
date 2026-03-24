<?php

namespace App\Helpers;

use App\Models\Laporan;
use Carbon\Carbon;

class StpaNumberGenerator
{
    /**
     * Generate nomor STPA dengan format: STPA/XXX/MM/YYYY/Ditressiber
     * 
     * @return string
     */
    public static function generate(): string
    {
        $now = Carbon::now();
        $year = $now->format('Y');
        $month = self::getRomanMonth($now->month);
        
        // Get sequence number for current month
        $sequence = self::getNextSequence($now->year, $now->month);
        
        // Format: STPA/601/IV/2025/Ditressiber
        return sprintf(
            'STPA/%03d/%s/%s/Ditressiber',
            $sequence,
            $month,
            $year
        );
    }
    
    /**
     * Get next sequence number for current month
     * 
     * @param int $year
     * @param int $month
     * @return int
     */
    private static function getNextSequence(int $year, int $month): int
    {
        // Nomor urut STPA reset per TAHUN (bukan per bulan)
        // Cari nomor urut tertinggi dari semua nomor_stpa di tahun ini
        $maxSequence = Laporan::where('nomor_stpa', 'like', "STPA/%/%/{$year}/Ditressiber")
            ->get()
            ->map(function ($laporan) {
                // Extract sequence number dari STPA/XXX/...
                if (preg_match('/^STPA\/(\d+)\//', $laporan->nomor_stpa, $matches)) {
                    return (int) $matches[1];
                }
                return 0;
            })
            ->max() ?? 0;

        return $maxSequence + 1;
    }
    
    /**
     * Convert month number to Roman numeral
     * 
     * @param int $month
     * @return string
     */
    private static function getRomanMonth(int $month): string
    {
        $romans = [
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII',
        ];
        
        return $romans[$month] ?? 'I';
    }
}
