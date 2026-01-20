<?php

namespace Database\Seeders;

use App\Models\Anggota;
use App\Models\Jabatan;
use App\Models\Pangkat;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder untuk Anggota Kepolisian
 * 
 * Beragam pangkat dan jabatan untuk pengujian
 */
class AnggotaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get pangkat and jabatan IDs
        $pangkatMap = Pangkat::pluck('id', 'kode')->toArray();
        $jabatanMap = Jabatan::pluck('id', 'nama')->toArray();

        // Daftar lengkap petugas
        $anggota = [
            // Pimpinan
            [
                'pangkat' => 'AKBP',
                'jabatan' => 'Kanit',
                'nrp' => '76010001',
                'nama' => 'BUDI SANTOSO',
            ],
            [
                'pangkat' => 'KOMPOL',
                'jabatan' => 'Kanit',
                'nrp' => '82010002',
                'nama' => 'DEWI KARTIKA',
            ],
            // Penyidik Senior
            [
                'pangkat' => 'AKP',
                'jabatan' => 'Penyidik',
                'nrp' => '85020001',
                'nama' => 'AGUS SETIAWAN',
            ],
            [
                'pangkat' => 'IPTU',
                'jabatan' => 'Penyidik',
                'nrp' => '88010003',
                'nama' => 'RINA WULANDARI',
            ],
            [
                'pangkat' => 'IPDA',
                'jabatan' => 'Penyidik',
                'nrp' => '90010004',
                'nama' => 'HENDRA WIJAYA',
            ],
            // BA PIKET (Petugas Penerima Laporan)
            [
                'pangkat' => 'AIPTU',
                'jabatan' => 'BA PIKET',
                'nrp' => '92010005',
                'nama' => 'JOKO PRASETYO',
            ],
            [
                'pangkat' => 'AIPDA',
                'jabatan' => 'BA PIKET',
                'nrp' => '95010006',
                'nama' => 'SITI RAHAYU',
            ],
            [
                'pangkat' => 'BRIPKA',
                'jabatan' => 'BA PIKET',
                'nrp' => '98010007',
                'nama' => 'DIMAS KURNIAWAN',
            ],
            [
                'pangkat' => 'BRIGADIR',
                'jabatan' => 'BA PIKET',
                'nrp' => '99010008',
                'nama' => 'AHMAD RIFAI',
            ],
            [
                'pangkat' => 'BRIPTU',
                'jabatan' => 'BA PIKET',
                'nrp' => '00010009',
                'nama' => 'SARI WULANDARI',
            ],
            // Penyidik Pembantu
            [
                'pangkat' => 'BRIPKA',
                'jabatan' => 'Penyidik Pembantu',
                'nrp' => '97010010',
                'nama' => 'EKO NUGROHO',
            ],
            [
                'pangkat' => 'BRIGADIR',
                'jabatan' => 'Penyidik Pembantu',
                'nrp' => '01010011',
                'nama' => 'MAYA SARI',
            ],
            // Operator
            [
                'pangkat' => 'BRIPTU',
                'jabatan' => 'Operator',
                'nrp' => '02010012',
                'nama' => 'RUDI HARTONO',
            ],
            [
                'pangkat' => 'BRIPDA',
                'jabatan' => 'Operator',
                'nrp' => '03010013',
                'nama' => 'LISA PERMATA',
            ],
            // Pawas Piket
            [
                'pangkat' => 'IPDA',
                'jabatan' => 'PAWAS PIKET',
                'nrp' => '91010014',
                'nama' => 'FERRY IRAWAN',
            ],
            [
                'pangkat' => 'AIPTU',
                'jabatan' => 'PAWAS PIKET',
                'nrp' => '93010015',
                'nama' => 'ANDI PRATAMA',
            ],
        ];

        // Clear existing data (disable FK check)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Anggota::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $count = 0;
        foreach ($anggota as $data) {
            $pangkatId = $pangkatMap[$data['pangkat']] ?? null;
            $jabatanId = $jabatanMap[$data['jabatan']] ?? null;

            if (!$pangkatId) {
                $this->command->warn("Skipping {$data['nama']}: Pangkat '{$data['pangkat']}' not found");
                continue;
            }
            if (!$jabatanId) {
                $this->command->warn("Skipping {$data['nama']}: Jabatan '{$data['jabatan']}' not found");
                continue;
            }

            Anggota::create([
                'pangkat_id' => $pangkatId,
                'jabatan_id' => $jabatanId,
                'nrp' => $data['nrp'],
                'nama' => $data['nama'],
                'is_active' => true,
            ]);
            $count++;
        }

        $this->command->info("✅ Seeded: {$count} Anggota Kepolisian");
        
        // Summary by jabatan
        $this->command->info("   📊 Summary:");
        $jabatanCounts = Anggota::selectRaw('jabatan_id, COUNT(*) as total')
            ->groupBy('jabatan_id')
            ->get();
        foreach ($jabatanCounts as $jc) {
            $jabatan = Jabatan::find($jc->jabatan_id);
            $this->command->info("      - {$jabatan->nama}: {$jc->total} orang");
        }
    }
}
