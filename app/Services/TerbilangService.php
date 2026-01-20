<?php

namespace App\Services;

/**
 * Terbilang Service
 * 
 * Converts numeric values to Indonesian words (Rupiah).
 * Example: 50000 -> "Lima Puluh Ribu Rupiah"
 */
class TerbilangService
{
    /**
     * Number words in Indonesian.
     */
    private static array $words = [
        '',
        'Satu',
        'Dua',
        'Tiga',
        'Empat',
        'Lima',
        'Enam',
        'Tujuh',
        'Delapan',
        'Sembilan',
        'Sepuluh',
        'Sebelas',
    ];

    /**
     * Convert a number to Indonesian words with "Rupiah" suffix.
     *
     * @param float|int|string $number
     * @return string
     */
    public static function convert($number): string
    {
        $number = abs((float) $number);
        
        if ($number < 1) {
            return 'Nol Rupiah';
        }

        $result = self::spell((int) $number);
        
        return trim($result) . ' Rupiah';
    }

    /**
     * Convert a number to Indonesian words without "Rupiah" suffix.
     *
     * @param float|int|string $number
     * @return string
     */
    public static function convertWithoutSuffix($number): string
    {
        $number = abs((float) $number);
        
        if ($number < 1) {
            return 'Nol';
        }

        return trim(self::spell((int) $number));
    }

    /**
     * Recursive function to spell out numbers in Indonesian.
     *
     * @param int $number
     * @return string
     */
    private static function spell(int $number): string
    {
        if ($number < 12) {
            return self::$words[$number];
        }

        if ($number < 20) {
            return self::$words[$number - 10] . ' Belas';
        }

        if ($number < 100) {
            $tens = (int) ($number / 10);
            $units = $number % 10;
            return self::$words[$tens] . ' Puluh' . ($units > 0 ? ' ' . self::$words[$units] : '');
        }

        if ($number < 200) {
            return 'Seratus' . ($number > 100 ? ' ' . self::spell($number - 100) : '');
        }

        if ($number < 1000) {
            $hundreds = (int) ($number / 100);
            $remainder = $number % 100;
            return self::$words[$hundreds] . ' Ratus' . ($remainder > 0 ? ' ' . self::spell($remainder) : '');
        }

        if ($number < 2000) {
            return 'Seribu' . ($number > 1000 ? ' ' . self::spell($number - 1000) : '');
        }

        if ($number < 1000000) {
            $thousands = (int) ($number / 1000);
            $remainder = $number % 1000;
            return self::spell($thousands) . ' Ribu' . ($remainder > 0 ? ' ' . self::spell($remainder) : '');
        }

        if ($number < 1000000000) {
            $millions = (int) ($number / 1000000);
            $remainder = $number % 1000000;
            return self::spell($millions) . ' Juta' . ($remainder > 0 ? ' ' . self::spell($remainder) : '');
        }

        if ($number < 1000000000000) {
            $billions = (int) ($number / 1000000000);
            $remainder = $number % 1000000000;
            return self::spell($billions) . ' Miliar' . ($remainder > 0 ? ' ' . self::spell($remainder) : '');
        }

        $trillions = (int) ($number / 1000000000000);
        $remainder = $number % 1000000000000;
        return self::spell($trillions) . ' Triliun' . ($remainder > 0 ? ' ' . self::spell($remainder) : '');
    }
}
