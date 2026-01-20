<?php

namespace Database\Seeders;

use App\Models\Jabatan;
use Illuminate\Database\Seeder;

/**
 * Seeder untuk Jabatan Kepolisian
 * 
 * 7 jabatan di Ditresiber POLDA Jateng
 */
class JabatanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jabatan = [
            ['nama' => 'Kanit', 'deskripsi' => 'Kepala Unit'],
            ['nama' => 'Kasubnit', 'deskripsi' => 'Kepala Sub Unit'],
            ['nama' => 'Penyidik', 'deskripsi' => 'Penyidik'],
            ['nama' => 'Penyidik Pembantu', 'deskripsi' => 'Penyidik Pembantu'],
            ['nama' => 'Analis', 'deskripsi' => 'Analis'],
            ['nama' => 'Operator', 'deskripsi' => 'Operator'],
            ['nama' => 'BA PIKET', 'deskripsi' => 'Bintara Piket'],
            ['nama' => 'PAWAS PIKET', 'deskripsi' => 'Perwira Pengawas Piket'],
        ];

        foreach ($jabatan as $data) {
            Jabatan::updateOrCreate(
                ['nama' => $data['nama']],
                $data
            );
        }

        $this->command->info('✅ Seeded: 8 Jabatan Kepolisian');
    }
}
