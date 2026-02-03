<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Personel;

/**
 * PersonelSeeder - Dummy Police Personnel
 * 
 * Creates 15 sample officers for Pawas selection testing.
 */
class PersonelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('👮 Seeding personels...');

        $names = [
            'Budi Santoso',
            'Agus Pratama',
            'Andi Wijaya',
            'Dedi Kurniawan',
            'Eko Prasetyo',
            'Fajar Nugroho',
            'Gunawan Setiawan',
            'Hendra Saputra',
            'Indra Permana',
            'Joko Susilo',
            'Krisna Mahendra',
            'Lukman Hakim',
            'Muhammad Rizki',
            'Nurdin Hidayat',
            'Omar Bakri',
        ];

        $pangkats = ['IPDA', 'IPTU', 'AKP', 'KOMPOL', 'AIPDA', 'BRIPKA'];

        foreach ($names as $index => $name) {
            Personel::create([
                'nrp' => '7811' . str_pad($index + 1, 4, '0', STR_PAD_LEFT), // e.g., 78110001
                'nama_lengkap' => $name,
                'pangkat' => $pangkats[array_rand($pangkats)],
                'subdit_id' => rand(1, 3), // 1=Ekonomi, 2=Sosial, 3=Khusus
                'unit_id' => rand(1, 5),
            ]);
            
            $this->command->info("   ✓ Created personel: {$name}");
        }

        $this->command->info("   📊 Total personels created: " . count($names));
    }
}
