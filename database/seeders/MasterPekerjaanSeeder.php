<?php

namespace Database\Seeders;

use App\Models\MasterPekerjaan;
use Illuminate\Database\Seeder;

/**
 * Seeder: Master Pekerjaan
 * 
 * Populates the master_pekerjaan table with standardized occupation list.
 * Based on Indonesian government standard occupation categories.
 */
class MasterPekerjaanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pekerjaanList = [
            'Belum/Tidak Bekerja',
            'Mengurus Rumah Tangga',
            'Pelajar/Mahasiswa',
            'Pensiunan',
            'Wiraswasta',
            'Swasta',
            'PNS (Pegawai Negeri Sipil)',
            'TNI (Tentara Nasional Indonesia)',
            'POLRI',
            'Karyawan Swasta',
            'Karyawan BUMN',
            'Karyawan BUMD',
            'Karyawan Honorer',
            'Buruh Harian Lepas',
            'Buruh Tani/Perkebunan',
            'Buruh Nelayan/Perikanan',
            'Buruh Peternakan',
            'Pembantu Rumah Tangga',
            'Tukang Cukur',
            'Tukang Listrik',
            'Tukang Batu',
            'Tukang Kayu',
            'Tukang Sol Sepatu',
            'Tukang Las/Pandai Besi',
            'Tukang Jahit',
            'Tukang Gigi',
            'Penata Rambut',
            'Penata Rias',
            'Mekanik',
            'Sopir',
            'Montir',
            'Pedagang',
            'Petani',
            'Peternak',
            'Nelayan',
            'Industri Rumah Tangga',
            'Pengrajin',
            'Seniman',
            'Wartawan',
            'Ustadz/Mubaligh',
            'Juru Masak',
            'Penjaga Toko',
            'Penjaga Warung',
            'Pendeta',
            'Pastur',
            'Perawat',
            'Bidan',
            'Dokter',
            'Apoteker',
            'Psikiater',
            'Psikolog',
            'Guru',
            'Dosen',
            'Tenaga Pengajar',
            'Konsultan',
            'Notaris',
            'Pengacara',
            'Hakim',
            'Jaksa',
            'Arsitek',
            'Akuntan',
            'Desainer',
            'Manajer',
            'Direktur',
            'Programmer',
            'Analis Sistem',
            'Teknisi',
            'Wakil Gubernur',
            'Gubernur',
            'Wakil Bupati',
            'Bupati',
            'Anggota DPRD Provinsi',
            'Anggota DPRD Kabupaten',
            'Tenaga Tata Usaha',
            'Editor',
            'Penulis',
            'Penyiar',
            'Presenter',
            'Peneliti',
            'Fotografer',
            'Videografer',
            'Duta Besar',
            'Waiter/Waitress',
            'Biarawati',
            'Satpam',
            'Petugas Kebersihan',
            'Pramugari/Pramugara',
            'Masinis',
            'Pilot',
            'Nahkoda',
            'ABK (Anak Buah Kapal)',
            'Penerjemah',
            'Pemandu Wisata',
            'Travel Agent',
            'Pegawai Bank',
            'Pegawai Asuransi',
            'Pegawai Pajak',
            'Pegawai Bea Cukai',
            'Pegawai Imigrasi',
            'Pegawai Perusahaan Startup',
            'Asisten Ahli',
            'Promotor Acara',
            'Anggota Lembaga Tinggi',
            'Investor',
            'Lainnya',
        ];

        foreach ($pekerjaanList as $pekerjaan) {
            MasterPekerjaan::firstOrCreate(['nama' => $pekerjaan]);
        }

        $this->command->info('Seeded ' . count($pekerjaanList) . ' pekerjaan records.');
    }
}
