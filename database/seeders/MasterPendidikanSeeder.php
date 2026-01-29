<?php

namespace Database\Seeders;

use App\Models\MasterPendidikan;
use Illuminate\Database\Seeder;

/**
 * Seeder: Master Pendidikan
 * 
 * Populates the master_pendidikan table with standardized education levels.
 */
class MasterPendidikanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pendidikanList = [
            'Tidak Sekolah',
            'SD Sederajat',
            'SMP Sederajat',
            'SMA Sederajat',
            'D3',
            'S1 / D4',
            'S2',
            'S3',
            'Lainnya',
        ];

        foreach ($pendidikanList as $pendidikan) {
            MasterPendidikan::firstOrCreate(['nama' => $pendidikan]);
        }

        $this->command->info('Seeded ' . count($pendidikanList) . ' pendidikan records.');
    }
}
