<?php

namespace Database\Seeders;

use App\Models\Pangkat;
use Illuminate\Database\Seeder;

/**
 * Seeder untuk Pangkat Kepolisian
 * 
 * 16 pangkat dari tertinggi (Jenderal) hingga terendah (Bripda)
 * Urutan 1 = tertinggi
 */
class PangkatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pangkat = [
            ['kode' => 'JENDERAL', 'nama' => 'Jenderal Polisi', 'urutan' => 1],
            ['kode' => 'KOMJEN', 'nama' => 'Komisaris Jenderal Polisi', 'urutan' => 2],
            ['kode' => 'IRJEN', 'nama' => 'Inspektur Jenderal Polisi', 'urutan' => 3],
            ['kode' => 'BRIGJEN', 'nama' => 'Brigadir Jenderal Polisi', 'urutan' => 4],
            ['kode' => 'KOMBES', 'nama' => 'Komisaris Besar Polisi', 'urutan' => 5],
            ['kode' => 'AKBP', 'nama' => 'Ajun Komisaris Besar Polisi', 'urutan' => 6],
            ['kode' => 'KOMPOL', 'nama' => 'Komisaris Polisi', 'urutan' => 7],
            ['kode' => 'AKP', 'nama' => 'Ajun Komisaris Polisi', 'urutan' => 8],
            ['kode' => 'IPTU', 'nama' => 'Inspektur Polisi Satu', 'urutan' => 9],
            ['kode' => 'IPDA', 'nama' => 'Inspektur Polisi Dua', 'urutan' => 10],
            ['kode' => 'AIPTU', 'nama' => 'Ajun Inspektur Polisi Satu', 'urutan' => 11],
            ['kode' => 'AIPDA', 'nama' => 'Ajun Inspektur Polisi Dua', 'urutan' => 12],
            ['kode' => 'BRIPKA', 'nama' => 'Brigadir Polisi Kepala', 'urutan' => 13],
            ['kode' => 'BRIGADIR', 'nama' => 'Brigadir Polisi', 'urutan' => 14],
            ['kode' => 'BRIPTU', 'nama' => 'Brigadir Polisi Satu', 'urutan' => 15],
            ['kode' => 'BRIPDA', 'nama' => 'Brigadir Polisi Dua', 'urutan' => 16],
        ];

        foreach ($pangkat as $data) {
            Pangkat::updateOrCreate(
                ['kode' => $data['kode']],
                $data
            );
        }

        $this->command->info('✅ Seeded: 16 Pangkat Kepolisian');
    }
}
