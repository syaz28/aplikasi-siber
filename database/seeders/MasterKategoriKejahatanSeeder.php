<?php

namespace Database\Seeders;

use App\Models\KategoriKejahatan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder: Master Kategori Kejahatan
 * 
 * Populates kategori_kejahatan table with standardized cyber crime categories
 * based on Indonesian cyber law (UU ITE).
 */
class MasterKategoriKejahatanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Truncate existing data (disable foreign key checks temporarily)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        KategoriKejahatan::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $kategoriList = [
            'Kesusilaan/Pornografi',
            'Perjudian online',
            'Penghinaan dan pencemaran nama baik digital',
            'Pemerasan digital',
            'Pengancaman digital',
            'Penyebaran berita bohong (hoaks)',
            'Penyebaran ujaran kebencian / permusuhan',
            'Ancaman kekerasan melalui sistem elektronik',
            'Ilegal Akses (peretasan)',
            'Intersepsi',
            'Perusakan, perubahan, atau penghilangan data elektronik',
            'Gangguan terhadap sistem elektronik',
            'Penyediaan atau penggunaan alat kejahatan siber',
            'Penipuan online',
            'Pemalsuan informasi atau dokumen elektronik',
            'Pelanggaran atau penyalahgunaan data pribadi',
        ];

        foreach ($kategoriList as $index => $kategori) {
            KategoriKejahatan::create([
                'nama' => $kategori,
                'is_active' => true,
            ]);
        }

        $this->command->info('Seeded ' . count($kategoriList) . ' kategori kejahatan records.');
    }
}
