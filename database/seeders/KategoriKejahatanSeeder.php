<?php

namespace Database\Seeders;

use App\Models\KategoriKejahatan;
use Illuminate\Database\Seeder;

/**
 * Seeder untuk Kategori Kejahatan Siber
 * 
 * 8 kategori utama kejahatan siber
 */
class KategoriKejahatanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kategori = [
            [
                'nama' => 'Penipuan Online',
                'deskripsi' => 'Kejahatan penipuan yang dilakukan melalui media digital/online',
                'is_active' => true,
            ],
            [
                'nama' => 'Pemerasan Digital',
                'deskripsi' => 'Kejahatan pemerasan dengan memanfaatkan teknologi digital',
                'is_active' => true,
            ],
            [
                'nama' => 'Pencurian Data',
                'deskripsi' => 'Kejahatan pencurian data pribadi atau data sensitif',
                'is_active' => true,
            ],
            [
                'nama' => 'Akses Ilegal',
                'deskripsi' => 'Akses tidak sah ke sistem komputer atau jaringan',
                'is_active' => true,
            ],
            [
                'nama' => 'Konten Ilegal',
                'deskripsi' => 'Penyebaran konten ilegal melalui media digital',
                'is_active' => true,
            ],
            [
                'nama' => 'Perjudian Online',
                'deskripsi' => 'Perjudian yang dilakukan melalui platform digital',
                'is_active' => true,
            ],
            [
                'nama' => 'Kejahatan Perbankan Digital',
                'deskripsi' => 'Kejahatan yang menargetkan sistem perbankan digital',
                'is_active' => true,
            ],
            [
                'nama' => 'Lainnya',
                'deskripsi' => 'Kejahatan siber lainnya yang tidak termasuk kategori di atas',
                'is_active' => true,
            ],
        ];

        foreach ($kategori as $data) {
            KategoriKejahatan::updateOrCreate(
                ['nama' => $data['nama']],
                $data
            );
        }

        $this->command->info('✅ Seeded: 8 Kategori Kejahatan Siber');
    }
}
