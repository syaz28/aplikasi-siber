<?php

namespace Database\Seeders;

use App\Models\Wilayah;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

/**
 * Seeder untuk Wilayah Indonesia
 * 
 * If wilayah.sql exists, imports full data (83,000+ records)
 * Otherwise, uses sample data for development
 * 
 * Full data source: https://github.com/cahyadsn/wilayah
 */
class WilayahSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sqlPath = database_path('wilayah.sql');

        // Check if full data SQL file exists
        if (File::exists($sqlPath)) {
            $this->command->info('📂 Found wilayah.sql - importing full Indonesia data...');
            
            // Call the import artisan command
            Artisan::call('wilayah:import', [
                '--fresh' => true,
            ], $this->command->getOutput());

            return;
        }

        // Fallback to sample data
        $this->command->warn('⚠️  wilayah.sql not found - using sample data');
        $this->command->info('   Download full data from: https://github.com/cahyadsn/wilayah');
        
        $this->seedSampleData();
    }

    /**
     * Seed sample data for development/testing
     */
    private function seedSampleData(): void
    {
        $wilayah = [
            // ========================================
            // PROVINSI
            // ========================================
            ['kode' => '33', 'nama' => 'JAWA TENGAH'],

            // ========================================
            // KABUPATEN/KOTA di Jawa Tengah
            // ========================================
            ['kode' => '33.01', 'nama' => 'KABUPATEN CILACAP'],
            ['kode' => '33.02', 'nama' => 'KABUPATEN BANYUMAS'],
            ['kode' => '33.03', 'nama' => 'KABUPATEN PURBALINGGA'],
            ['kode' => '33.04', 'nama' => 'KABUPATEN BANJARNEGARA'],
            ['kode' => '33.05', 'nama' => 'KABUPATEN KEBUMEN'],
            ['kode' => '33.06', 'nama' => 'KABUPATEN PURWOREJO'],
            ['kode' => '33.07', 'nama' => 'KABUPATEN WONOSOBO'],
            ['kode' => '33.08', 'nama' => 'KABUPATEN MAGELANG'],
            ['kode' => '33.09', 'nama' => 'KABUPATEN BOYOLALI'],
            ['kode' => '33.10', 'nama' => 'KABUPATEN KLATEN'],
            ['kode' => '33.11', 'nama' => 'KABUPATEN SUKOHARJO'],
            ['kode' => '33.12', 'nama' => 'KABUPATEN WONOGIRI'],
            ['kode' => '33.13', 'nama' => 'KABUPATEN KARANGANYAR'],
            ['kode' => '33.14', 'nama' => 'KABUPATEN SRAGEN'],
            ['kode' => '33.15', 'nama' => 'KABUPATEN GROBOGAN'],
            ['kode' => '33.16', 'nama' => 'KABUPATEN BLORA'],
            ['kode' => '33.17', 'nama' => 'KABUPATEN REMBANG'],
            ['kode' => '33.18', 'nama' => 'KABUPATEN PATI'],
            ['kode' => '33.19', 'nama' => 'KABUPATEN KUDUS'],
            ['kode' => '33.20', 'nama' => 'KABUPATEN JEPARA'],
            ['kode' => '33.21', 'nama' => 'KABUPATEN DEMAK'],
            ['kode' => '33.22', 'nama' => 'KABUPATEN SEMARANG'],
            ['kode' => '33.23', 'nama' => 'KABUPATEN TEMANGGUNG'],
            ['kode' => '33.24', 'nama' => 'KABUPATEN KENDAL'],
            ['kode' => '33.25', 'nama' => 'KABUPATEN BATANG'],
            ['kode' => '33.26', 'nama' => 'KABUPATEN PEKALONGAN'],
            ['kode' => '33.27', 'nama' => 'KABUPATEN PEMALANG'],
            ['kode' => '33.28', 'nama' => 'KABUPATEN TEGAL'],
            ['kode' => '33.29', 'nama' => 'KABUPATEN BREBES'],
            ['kode' => '33.71', 'nama' => 'KOTA MAGELANG'],
            ['kode' => '33.72', 'nama' => 'KOTA SURAKARTA'],
            ['kode' => '33.73', 'nama' => 'KOTA SALATIGA'],
            ['kode' => '33.74', 'nama' => 'KOTA SEMARANG'],
            ['kode' => '33.75', 'nama' => 'KOTA PEKALONGAN'],
            ['kode' => '33.76', 'nama' => 'KOTA TEGAL'],

            // ========================================
            // KECAMATAN di Kota Semarang (sample)
            // ========================================
            ['kode' => '33.74.01', 'nama' => 'SEMARANG TENGAH'],
            ['kode' => '33.74.02', 'nama' => 'SEMARANG UTARA'],
            ['kode' => '33.74.03', 'nama' => 'SEMARANG TIMUR'],
            ['kode' => '33.74.04', 'nama' => 'GAYAMSARI'],
            ['kode' => '33.74.05', 'nama' => 'GENUK'],
            ['kode' => '33.74.06', 'nama' => 'PEDURUNGAN'],
            ['kode' => '33.74.07', 'nama' => 'SEMARANG SELATAN'],
            ['kode' => '33.74.08', 'nama' => 'CANDISARI'],
            ['kode' => '33.74.09', 'nama' => 'GAJAHMUNGKUR'],
            ['kode' => '33.74.10', 'nama' => 'TEMBALANG'],
            ['kode' => '33.74.11', 'nama' => 'BANYUMANIK'],
            ['kode' => '33.74.12', 'nama' => 'GUNUNGPATI'],
            ['kode' => '33.74.13', 'nama' => 'SEMARANG BARAT'],
            ['kode' => '33.74.14', 'nama' => 'MIJEN'],
            ['kode' => '33.74.15', 'nama' => 'NGALIYAN'],
            ['kode' => '33.74.16', 'nama' => 'TUGU'],

            // ========================================
            // KELURAHAN di Kec. Semarang Tengah (sample)
            // ========================================
            ['kode' => '33.74.01.1001', 'nama' => 'MIROTO'],
            ['kode' => '33.74.01.1002', 'nama' => 'BRUMBUNGAN'],
            ['kode' => '33.74.01.1003', 'nama' => 'JAGALAN'],
            ['kode' => '33.74.01.1004', 'nama' => 'KRANGGAN'],
            ['kode' => '33.74.01.1005', 'nama' => 'GABAHAN'],
            ['kode' => '33.74.01.1006', 'nama' => 'KEMBANGSARI'],
            ['kode' => '33.74.01.1007', 'nama' => 'SEKAYU'],
            ['kode' => '33.74.01.1008', 'nama' => 'PANDANSARI'],
            ['kode' => '33.74.01.1009', 'nama' => 'BANGUNHARJO'],
            ['kode' => '33.74.01.1010', 'nama' => 'KAUMAN'],
            ['kode' => '33.74.01.1011', 'nama' => 'KEMBANG JERUK'],
            ['kode' => '33.74.01.1012', 'nama' => 'PEKUNDEN'],
            ['kode' => '33.74.01.1013', 'nama' => 'PENDRIKAN KIDUL'],
            ['kode' => '33.74.01.1014', 'nama' => 'PENDRIKAN LOR'],
            ['kode' => '33.74.01.1015', 'nama' => 'PURWODINATAN'],
        ];

        foreach ($wilayah as $data) {
            Wilayah::updateOrCreate(
                ['kode' => $data['kode']],
                $data
            );
        }

        $this->command->info('✅ Seeded: ' . count($wilayah) . ' Wilayah (Jawa Tengah sample)');
    }
}
