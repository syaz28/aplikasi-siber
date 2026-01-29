<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PangkatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pangkat = [
            ['kode' => 'JENDERAL', 'urutan' => 1],
            ['kode' => 'KOMJEN', 'urutan' => 2],
            ['kode' => 'IRJEN', 'urutan' => 3],
            ['kode' => 'BRIGJEN', 'urutan' => 4],
            ['kode' => 'KOMBES', 'urutan' => 5],
            ['kode' => 'AKBP', 'urutan' => 6],
            ['kode' => 'KOMPOL', 'urutan' => 7],
            ['kode' => 'AKP', 'urutan' => 8],
            ['kode' => 'IPTU', 'urutan' => 9],
            ['kode' => 'IPDA', 'urutan' => 10],
            ['kode' => 'AIPTU', 'urutan' => 11],
            ['kode' => 'AIPDA', 'urutan' => 12],
            ['kode' => 'BRIPKA', 'urutan' => 13],
            ['kode' => 'BRIGADIR', 'urutan' => 14],
            ['kode' => 'BRIPTU', 'urutan' => 15],
            ['kode' => 'BRIPDA', 'urutan' => 16],
        ];

        $now = now();
        foreach ($pangkat as &$p) {
            $p['created_at'] = $now;
            $p['updated_at'] = $now;
        }

        DB::table('pangkat')->insertOrIgnore($pangkat);
    }
}
