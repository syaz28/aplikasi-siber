<?php

namespace Database\Seeders;

use App\Models\Alamat;
use App\Models\IdentitasTersangka;
use App\Models\KategoriKejahatan;
use App\Models\Korban;
use App\Models\Laporan;
use App\Models\Orang;
use App\Models\Personel;
use App\Models\Tersangka;
use App\Models\Wilayah;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * LaporanDummySeeder
 * 
 * Generates dummy laporan data with:
 * - Multiple reports
 * - Some tersangka appearing in multiple reports (RESIDIVIS)
 * - Various crime categories
 * - Different statuses
 * 
 * RESIDIVIS cases created:
 * 1. Same phone number (telepon) in multiple reports
 * 2. Same bank account (rekening) in multiple reports  
 * 3. Same social media account in multiple reports
 */
class LaporanDummySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('🔄 Generating dummy laporan data...');

        // Get required master data
        $kategoris = KategoriKejahatan::all();
        $personels = Personel::all();
        // Kode wilayah format: 33.XX (kabupaten di Jateng)
        $wilayahKab = Wilayah::where('kode', 'like', '33.__')->get();
        
        if ($kategoris->isEmpty()) {
            $this->command->error('No kategori kejahatan found. Run KategoriKejahatanSeeder first.');
            return;
        }

        if ($personels->isEmpty()) {
            $this->command->error('No personel found. Run PersonelSeeder first.');
            return;
        }
        
        if ($wilayahKab->isEmpty()) {
            $this->command->warn('No wilayah kabupaten found. Using default kode.');
            $defaultKab = '33.74'; // Kota Semarang
        }

        // ================================================================
        // RESIDIVIS DATA - Identitas yang akan muncul di beberapa laporan
        // ================================================================
        
        // Residivis 1: Pelaku dengan nomor HP yang sama
        $residivisTelepon = '+6281234567890';
        
        // Residivis 2: Pelaku dengan rekening yang sama
        $residivisRekening = '1234567890123456';
        $residivisBank = 'BCA';
        
        // Residivis 3: Pelaku dengan akun Instagram yang sama
        $residivisInstagram = '@penipu_online123';
        
        // Residivis 4: Pelaku dengan nomor WhatsApp yang sama
        $residivisWhatsapp = '+6289876543210';

        // ================================================================
        // CREATE DUMMY LAPORAN
        // ================================================================

        DB::beginTransaction();
        try {
            $laporanCount = 0;

            // -----------------------------------------------------------
            // LAPORAN 1: Penipuan Online - Residivis Telepon (pertama)
            // -----------------------------------------------------------
            $this->createLaporan([
                'nomor_stpa' => 'STPA/001/I/2026/Ditressiber',
                'tanggal_laporan' => Carbon::parse('2026-01-05 09:00:00'),
                'waktu_kejadian' => Carbon::parse('2026-01-03 14:30:00'),
                'kategori' => 'Penipuan Online',
                'modus' => 'Pelaku menawarkan penjualan iPhone 15 Pro Max melalui marketplace Facebook dengan harga sangat murah. Setelah korban transfer uang sebesar Rp 15.000.000, pelaku langsung memblokir semua kontak.',
                'status' => 'Penyelidikan',
                'pelapor' => [
                    'nama' => 'Budi Santoso',
                    'nik' => '3374011234567890',
                    'tempat_lahir' => 'Semarang',
                    'tanggal_lahir' => '1990-05-15',
                    'jenis_kelamin' => 'Laki-laki',
                    'pekerjaan' => 'Karyawan Swasta',
                    'telepon' => '+6281111222333',
                    'alamat' => 'Jl. Pandanaran No. 123, RT 001 RW 002',
                    'kode_kelurahan' => '3374010001', // Semarang
                ],
                'korban' => [
                    'kerugian' => 15000000,
                ],
                'tersangka' => [
                    [
                        'identitas' => [
                            ['jenis' => 'telepon', 'nilai' => $residivisTelepon, 'platform' => 'WhatsApp', 'nama_akun' => 'Seller Trusted'],
                            ['jenis' => 'rekening', 'nilai' => '9876543210', 'platform' => 'BNI', 'nama_akun' => 'ANDI PRATAMA'],
                        ],
                    ],
                ],
                'personel' => $personels->random(),
                'kode_kabupaten' => $wilayahKab->isNotEmpty() ? $wilayahKab->random()->kode : '33.74',
                'assigned_subdit' => 1,
            ]);
            $laporanCount++;
            $this->command->info("  ✓ Laporan {$laporanCount}: Penipuan Online (Residivis Telepon #1)");

            // -----------------------------------------------------------
            // LAPORAN 2: Penipuan Online - Residivis Telepon (kedua) + Rekening (pertama)
            // -----------------------------------------------------------
            $this->createLaporan([
                'nomor_stpa' => 'STPA/002/I/2026/Ditressiber',
                'tanggal_laporan' => Carbon::parse('2026-01-08 10:30:00'),
                'waktu_kejadian' => Carbon::parse('2026-01-06 16:00:00'),
                'kategori' => 'Penipuan Online',
                'modus' => 'Korban tertipu investasi bodong dengan janji keuntungan 50% per bulan. Pelaku menghubungi via WhatsApp dan meminta transfer ke rekening BCA.',
                'status' => 'Penyidikan',
                'pelapor' => [
                    'nama' => 'Siti Rahayu',
                    'nik' => '3374021234567891',
                    'tempat_lahir' => 'Semarang',
                    'tanggal_lahir' => '1985-08-20',
                    'jenis_kelamin' => 'Perempuan',
                    'pekerjaan' => 'Ibu Rumah Tangga',
                    'telepon' => '+6282222333444',
                    'alamat' => 'Jl. Sisingamangaraja No. 45, RT 003 RW 001',
                    'kode_kelurahan' => '3374010002',
                ],
                'korban' => [
                    'kerugian' => 50000000,
                ],
                'tersangka' => [
                    [
                        'identitas' => [
                            ['jenis' => 'telepon', 'nilai' => $residivisTelepon, 'platform' => 'WhatsApp', 'nama_akun' => 'Investasi Terpercaya'],
                            ['jenis' => 'rekening', 'nilai' => $residivisRekening, 'platform' => $residivisBank, 'nama_akun' => 'PT INVESTASI JAYA'],
                        ],
                    ],
                ],
                'personel' => $personels->random(),
                'kode_kabupaten' => $wilayahKab->isNotEmpty() ? $wilayahKab->random()->kode : '33.74',
                'assigned_subdit' => 1,
                'disposisi_unit' => 1,
            ]);
            $laporanCount++;
            $this->command->info("  ✓ Laporan {$laporanCount}: Penipuan Investasi (Residivis Telepon #2 + Rekening #1)");

            // -----------------------------------------------------------
            // LAPORAN 3: Penipuan Online - Residivis Rekening (kedua)
            // -----------------------------------------------------------
            $this->createLaporan([
                'nomor_stpa' => 'STPA/003/I/2026/Ditressiber',
                'tanggal_laporan' => Carbon::parse('2026-01-10 14:00:00'),
                'waktu_kejadian' => Carbon::parse('2026-01-09 11:00:00'),
                'kategori' => 'Penipuan Online',
                'modus' => 'Korban membeli laptop bekas di OLX. Setelah transfer ke rekening BCA, barang tidak pernah dikirim.',
                'status' => 'Penyelidikan',
                'pelapor' => [
                    'nama' => 'Ahmad Wijaya',
                    'nik' => '3374031234567892',
                    'tempat_lahir' => 'Solo',
                    'tanggal_lahir' => '1995-03-10',
                    'jenis_kelamin' => 'Laki-laki',
                    'pekerjaan' => 'Mahasiswa',
                    'telepon' => '+6283333444555',
                    'alamat' => 'Jl. Gajah Mada No. 78, RT 005 RW 003',
                    'kode_kelurahan' => '3374010003',
                ],
                'korban' => [
                    'kerugian' => 8500000,
                ],
                'tersangka' => [
                    [
                        'identitas' => [
                            ['jenis' => 'rekening', 'nilai' => $residivisRekening, 'platform' => $residivisBank, 'nama_akun' => 'JOKO SUSANTO'],
                            ['jenis' => 'telepon', 'nilai' => '+6285111222333', 'platform' => 'WhatsApp', 'nama_akun' => 'Laptop Murah'],
                        ],
                    ],
                ],
                'personel' => $personels->random(),
                'kode_kabupaten' => $wilayahKab->isNotEmpty() ? $wilayahKab->random()->kode : '33.74',
                'assigned_subdit' => 2,
            ]);
            $laporanCount++;
            $this->command->info("  ✓ Laporan {$laporanCount}: Penipuan Jual Beli (Residivis Rekening #2)");

            // -----------------------------------------------------------
            // LAPORAN 4: Penipuan Online - Residivis Instagram (pertama)
            // -----------------------------------------------------------
            $this->createLaporan([
                'nomor_stpa' => 'STPA/004/I/2026/Ditressiber',
                'tanggal_laporan' => Carbon::parse('2026-01-12 09:30:00'),
                'waktu_kejadian' => Carbon::parse('2026-01-11 15:00:00'),
                'kategori' => 'Penipuan Online',
                'modus' => 'Pelaku menjual tas branded palsu melalui Instagram. Korban sudah transfer tapi barang yang datang tidak sesuai dengan yang dijanjikan (KW rendah).',
                'status' => 'Tahap I',
                'pelapor' => [
                    'nama' => 'Dewi Kusuma',
                    'nik' => '3374041234567893',
                    'tempat_lahir' => 'Semarang',
                    'tanggal_lahir' => '1992-12-25',
                    'jenis_kelamin' => 'Perempuan',
                    'pekerjaan' => 'PNS',
                    'telepon' => '+6284444555666',
                    'alamat' => 'Jl. Pemuda No. 100, RT 002 RW 004',
                    'kode_kelurahan' => '3374010001',
                ],
                'korban' => [
                    'kerugian' => 3500000,
                ],
                'tersangka' => [
                    [
                        'identitas' => [
                            ['jenis' => 'sosmed', 'nilai' => $residivisInstagram, 'platform' => 'Instagram', 'nama_akun' => 'Branded Bag Original'],
                            ['jenis' => 'rekening', 'nilai' => '5555666677778888', 'platform' => 'Mandiri', 'nama_akun' => 'LINA SARI'],
                        ],
                    ],
                ],
                'personel' => $personels->random(),
                'kode_kabupaten' => $wilayahKab->isNotEmpty() ? $wilayahKab->random()->kode : '33.74',
                'assigned_subdit' => 1,
                'disposisi_unit' => 2,
            ]);
            $laporanCount++;
            $this->command->info("  ✓ Laporan {$laporanCount}: Penipuan Instagram (Residivis Instagram #1)");

            // -----------------------------------------------------------
            // LAPORAN 5: Penipuan Online - Residivis Instagram (kedua)
            // -----------------------------------------------------------
            $this->createLaporan([
                'nomor_stpa' => 'STPA/005/I/2026/Ditressiber',
                'tanggal_laporan' => Carbon::parse('2026-01-15 11:00:00'),
                'waktu_kejadian' => Carbon::parse('2026-01-14 10:00:00'),
                'kategori' => 'Penipuan Online',
                'modus' => 'Korban tertipu pembelian sepatu Nike original via Instagram. Setelah transfer, akun penjual menghilang.',
                'status' => 'Penyelidikan',
                'pelapor' => [
                    'nama' => 'Riko Firmansyah',
                    'nik' => '3374051234567894',
                    'tempat_lahir' => 'Kendal',
                    'tanggal_lahir' => '1998-07-08',
                    'jenis_kelamin' => 'Laki-laki',
                    'pekerjaan' => 'Wiraswasta',
                    'telepon' => '+6285555666777',
                    'alamat' => 'Jl. Ahmad Yani No. 55, RT 001 RW 005',
                    'kode_kelurahan' => '3374010002',
                ],
                'korban' => [
                    'kerugian' => 2800000,
                ],
                'tersangka' => [
                    [
                        'identitas' => [
                            ['jenis' => 'sosmed', 'nilai' => $residivisInstagram, 'platform' => 'Instagram', 'nama_akun' => 'Nike Original Store'],
                            ['jenis' => 'ewallet', 'nilai' => '+6281999888777', 'platform' => 'OVO', 'nama_akun' => 'Nike Store'],
                        ],
                    ],
                ],
                'personel' => $personels->random(),
                'kode_kabupaten' => $wilayahKab->isNotEmpty() ? $wilayahKab->random()->kode : '33.74',
                'assigned_subdit' => 2,
            ]);
            $laporanCount++;
            $this->command->info("  ✓ Laporan {$laporanCount}: Penipuan Sepatu (Residivis Instagram #2)");

            // -----------------------------------------------------------
            // LAPORAN 6: Pinjol Ilegal - Residivis WhatsApp (pertama)
            // -----------------------------------------------------------
            $this->createLaporan([
                'nomor_stpa' => 'STPA/006/I/2026/Ditressiber',
                'tanggal_laporan' => Carbon::parse('2026-01-18 08:00:00'),
                'waktu_kejadian' => Carbon::parse('2026-01-15 09:00:00'),
                'kategori' => 'Pinjaman Online Ilegal',
                'modus' => 'Korban meminjam uang via aplikasi pinjol. Bunga mencapai 100% dan ditagih dengan ancaman sebar data ke kontak.',
                'status' => 'Penyidikan',
                'pelapor' => [
                    'nama' => 'Hendra Saputra',
                    'nik' => '3374061234567895',
                    'tempat_lahir' => 'Demak',
                    'tanggal_lahir' => '1988-11-30',
                    'jenis_kelamin' => 'Laki-laki',
                    'pekerjaan' => 'Buruh',
                    'telepon' => '+6286666777888',
                    'alamat' => 'Jl. Kaligawe No. 200, RT 003 RW 002',
                    'kode_kelurahan' => '3374010003',
                ],
                'korban' => [
                    'kerugian' => 5000000,
                ],
                'tersangka' => [
                    [
                        'identitas' => [
                            ['jenis' => 'telepon', 'nilai' => $residivisWhatsapp, 'platform' => 'WhatsApp', 'nama_akun' => 'DC Pinjol'],
                            ['jenis' => 'lainnya', 'nilai' => 'Dana Cepat Kilat', 'platform' => 'APK', 'nama_akun' => 'Dana Cepat'],
                        ],
                    ],
                ],
                'personel' => $personels->random(),
                'kode_kabupaten' => $wilayahKab->isNotEmpty() ? $wilayahKab->random()->kode : '33.74',
                'assigned_subdit' => 3,
            ]);
            $laporanCount++;
            $this->command->info("  ✓ Laporan {$laporanCount}: Pinjol Ilegal (Residivis WhatsApp #1)");

            // -----------------------------------------------------------
            // LAPORAN 7: Pinjol Ilegal - Residivis WhatsApp (kedua)
            // -----------------------------------------------------------
            $this->createLaporan([
                'nomor_stpa' => 'STPA/007/I/2026/Ditressiber',
                'tanggal_laporan' => Carbon::parse('2026-01-20 13:00:00'),
                'waktu_kejadian' => Carbon::parse('2026-01-18 14:00:00'),
                'kategori' => 'Pinjaman Online Ilegal',
                'modus' => 'Debt collector pinjol menghubungi semua kontak korban dan menyebarkan foto KTP serta data pribadi korban.',
                'status' => 'Penyelidikan',
                'pelapor' => [
                    'nama' => 'Nur Aini',
                    'nik' => '3374071234567896',
                    'tempat_lahir' => 'Semarang',
                    'tanggal_lahir' => '1993-04-12',
                    'jenis_kelamin' => 'Perempuan',
                    'pekerjaan' => 'Pedagang',
                    'telepon' => '+6287777888999',
                    'alamat' => 'Jl. Majapahit No. 88, RT 004 RW 001',
                    'kode_kelurahan' => '3374010001',
                ],
                'korban' => [
                    'kerugian' => 3000000,
                ],
                'tersangka' => [
                    [
                        'identitas' => [
                            ['jenis' => 'telepon', 'nilai' => $residivisWhatsapp, 'platform' => 'WhatsApp', 'nama_akun' => 'Penagihan'],
                            ['jenis' => 'lainnya', 'nilai' => 'Pinjam Tunai Express', 'platform' => 'APK', 'nama_akun' => 'Pinjam Tunai'],
                        ],
                    ],
                ],
                'personel' => $personels->random(),
                'kode_kabupaten' => $wilayahKab->isNotEmpty() ? $wilayahKab->random()->kode : '33.74',
            ]);
            $laporanCount++;
            $this->command->info("  ✓ Laporan {$laporanCount}: Pinjol Ilegal (Residivis WhatsApp #2)");

            // -----------------------------------------------------------
            // LAPORAN 8: Pornografi - Tanpa Residivis
            // -----------------------------------------------------------
            $this->createLaporan([
                'nomor_stpa' => 'STPA/008/I/2026/Ditressiber',
                'tanggal_laporan' => Carbon::parse('2026-01-22 10:00:00'),
                'waktu_kejadian' => Carbon::parse('2026-01-20 20:00:00'),
                'kategori' => 'Pornografi',
                'modus' => 'Pelaku menyebarkan foto pribadi korban tanpa izin di grup Telegram sebagai bentuk balas dendam.',
                'status' => 'Penyidikan',
                'pelapor' => [
                    'nama' => 'Maya Putri',
                    'nik' => '3374081234567897',
                    'tempat_lahir' => 'Ungaran',
                    'tanggal_lahir' => '2000-02-14',
                    'jenis_kelamin' => 'Perempuan',
                    'pekerjaan' => 'Mahasiswa',
                    'telepon' => '+6288888999000',
                    'alamat' => 'Jl. Diponegoro No. 150, RT 002 RW 003',
                    'kode_kelurahan' => '3374010002',
                ],
                'korban' => [
                    'kerugian' => 0,
                ],
                'tersangka' => [
                    [
                        'catatan' => 'Mantan pacar korban',
                        'identitas' => [
                            ['jenis' => 'sosmed', 'nilai' => '@revenge_xxx', 'platform' => 'Telegram', 'nama_akun' => 'Anonymous'],
                            ['jenis' => 'telepon', 'nilai' => '+6281222333444', 'platform' => 'WhatsApp', 'nama_akun' => 'Tersangka'],
                        ],
                    ],
                ],
                'personel' => $personels->random(),
                'kode_kabupaten' => $wilayahKab->isNotEmpty() ? $wilayahKab->random()->kode : '33.74',
                'assigned_subdit' => 2,
                'disposisi_unit' => 3,
            ]);
            $laporanCount++;
            $this->command->info("  ✓ Laporan {$laporanCount}: Revenge Porn (Tanpa Residivis)");

            // -----------------------------------------------------------
            // LAPORAN 9: Pencurian Data - Tanpa Residivis
            // -----------------------------------------------------------
            $this->createLaporan([
                'nomor_stpa' => 'STPA/009/I/2026/Ditressiber',
                'tanggal_laporan' => Carbon::parse('2026-01-25 15:00:00'),
                'waktu_kejadian' => Carbon::parse('2026-01-23 08:00:00'),
                'kategori' => 'Pencurian Data Pribadi',
                'modus' => 'Data pelanggan perusahaan dicuri oleh mantan karyawan dan dijual di dark web.',
                'status' => 'Tahap I',
                'pelapor' => [
                    'nama' => 'PT Teknologi Maju',
                    'nik' => '3374091234567898',
                    'tempat_lahir' => 'Semarang',
                    'tanggal_lahir' => '1980-01-01',
                    'jenis_kelamin' => 'Laki-laki',
                    'pekerjaan' => 'Direktur',
                    'telepon' => '+6289999000111',
                    'alamat' => 'Jl. Industri No. 99, RT 001 RW 001',
                    'kode_kelurahan' => '3374010003',
                ],
                'korban' => [
                    'kerugian' => 100000000,
                ],
                'tersangka' => [
                    [
                        'nama' => 'Eko Prasetyo',
                        'nik' => '3374991234567899',
                        'catatan' => 'Mantan karyawan IT',
                        'identitas' => [
                            ['jenis' => 'email', 'nilai' => 'eko.darkweb@proton.me', 'platform' => 'Proton Mail', 'nama_akun' => 'D4rkH4cker'],
                            ['jenis' => 'kripto', 'nilai' => 'bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh', 'platform' => 'Bitcoin', 'nama_akun' => 'Unknown'],
                        ],
                    ],
                ],
                'personel' => $personels->random(),
                'kode_kabupaten' => $wilayahKab->isNotEmpty() ? $wilayahKab->random()->kode : '33.74',
                'assigned_subdit' => 3,
                'disposisi_unit' => 1,
            ]);
            $laporanCount++;
            $this->command->info("  ✓ Laporan {$laporanCount}: Pencurian Data (Tanpa Residivis)");

            // -----------------------------------------------------------
            // LAPORAN 10: Penipuan Online - Residivis Telepon (ketiga)
            // -----------------------------------------------------------
            $this->createLaporan([
                'nomor_stpa' => 'STPA/010/I/2026/Ditressiber',
                'tanggal_laporan' => Carbon::parse('2026-01-28 09:00:00'),
                'waktu_kejadian' => Carbon::parse('2026-01-26 12:00:00'),
                'kategori' => 'Penipuan Online',
                'modus' => 'Korban tertipu undian berhadiah palsu. Diminta transfer biaya administrasi untuk mencairkan hadiah.',
                'status' => 'Penyelidikan',
                'pelapor' => [
                    'nama' => 'Surya Dharma',
                    'nik' => '3374101234567800',
                    'tempat_lahir' => 'Kudus',
                    'tanggal_lahir' => '1975-09-05',
                    'jenis_kelamin' => 'Laki-laki',
                    'pekerjaan' => 'Pensiunan',
                    'telepon' => '+6281000111222',
                    'alamat' => 'Jl. Veteran No. 25, RT 005 RW 002',
                    'kode_kelurahan' => '3374010001',
                ],
                'korban' => [
                    'kerugian' => 25000000,
                ],
                'tersangka' => [
                    [
                        'identitas' => [
                            ['jenis' => 'telepon', 'nilai' => $residivisTelepon, 'platform' => 'Telepon', 'nama_akun' => 'Call Center Undian'],
                            ['jenis' => 'rekening', 'nilai' => '1111222233334444', 'platform' => 'BRI', 'nama_akun' => 'YAYASAN UNDIAN'],
                        ],
                    ],
                ],
                'personel' => $personels->random(),
                'kode_kabupaten' => $wilayahKab->isNotEmpty() ? $wilayahKab->random()->kode : '33.74',
            ]);
            $laporanCount++;
            $this->command->info("  ✓ Laporan {$laporanCount}: Penipuan Undian (Residivis Telepon #3)");

            // -----------------------------------------------------------
            // LAPORAN 11: Penipuan Rekening (ketiga)
            // -----------------------------------------------------------
            $this->createLaporan([
                'nomor_stpa' => 'STPA/011/I/2026/Ditressiber',
                'tanggal_laporan' => Carbon::parse('2026-01-30 11:00:00'),
                'waktu_kejadian' => Carbon::parse('2026-01-29 16:00:00'),
                'kategori' => 'Penipuan Online',
                'modus' => 'Korban tertipu arisan online. Sudah setor beberapa kali tapi saat giliran dapat, bandar menghilang.',
                'status' => 'Penyidikan',
                'pelapor' => [
                    'nama' => 'Ratna Sari',
                    'nik' => '3374111234567801',
                    'tempat_lahir' => 'Pati',
                    'tanggal_lahir' => '1987-06-18',
                    'jenis_kelamin' => 'Perempuan',
                    'pekerjaan' => 'Guru',
                    'telepon' => '+6282111222333',
                    'alamat' => 'Jl. Pahlawan No. 30, RT 002 RW 004',
                    'kode_kelurahan' => '3374010002',
                ],
                'korban' => [
                    'kerugian' => 12000000,
                ],
                'tersangka' => [
                    [
                        'identitas' => [
                            ['jenis' => 'rekening', 'nilai' => $residivisRekening, 'platform' => $residivisBank, 'nama_akun' => 'ARISAN BERKAH'],
                            ['jenis' => 'sosmed', 'nilai' => '@arisan_berkah_official', 'platform' => 'Facebook', 'nama_akun' => 'Arisan Berkah Official'],
                        ],
                    ],
                ],
                'personel' => $personels->random(),
                'kode_kabupaten' => $wilayahKab->isNotEmpty() ? $wilayahKab->random()->kode : '33.74',
                'assigned_subdit' => 1,
            ]);
            $laporanCount++;
            $this->command->info("  ✓ Laporan {$laporanCount}: Penipuan Arisan (Residivis Rekening #3)");

            // -----------------------------------------------------------
            // LAPORAN 12: Instagram (ketiga)
            // -----------------------------------------------------------
            $this->createLaporan([
                'nomor_stpa' => 'STPA/012/II/2026/Ditressiber',
                'tanggal_laporan' => Carbon::parse('2026-02-01 14:00:00'),
                'waktu_kejadian' => Carbon::parse('2026-01-31 10:00:00'),
                'kategori' => 'Penipuan Online',
                'modus' => 'Pelaku menjual tiket konser palsu melalui Instagram. Setelah transfer, tiket tidak pernah dikirim.',
                'status' => 'Penyelidikan',
                'pelapor' => [
                    'nama' => 'Kevin Anggara',
                    'nik' => '3374121234567802',
                    'tempat_lahir' => 'Semarang',
                    'tanggal_lahir' => '2001-10-10',
                    'jenis_kelamin' => 'Laki-laki',
                    'pekerjaan' => 'Mahasiswa',
                    'telepon' => '+6283222333444',
                    'alamat' => 'Jl. Kelud Raya No. 15, RT 003 RW 001',
                    'kode_kelurahan' => '3374010003',
                ],
                'korban' => [
                    'kerugian' => 1500000,
                ],
                'tersangka' => [
                    [
                        'identitas' => [
                            ['jenis' => 'sosmed', 'nilai' => $residivisInstagram, 'platform' => 'Instagram', 'nama_akun' => 'Tiket Konser Murah'],
                            ['jenis' => 'ewallet', 'nilai' => '+6285666777888', 'platform' => 'GoPay', 'nama_akun' => 'Tiket Store'],
                        ],
                    ],
                ],
                'personel' => $personels->random(),
                'kode_kabupaten' => $wilayahKab->isNotEmpty() ? $wilayahKab->random()->kode : '33.74',
            ]);
            $laporanCount++;
            $this->command->info("  ✓ Laporan {$laporanCount}: Penipuan Tiket (Residivis Instagram #3)");

            DB::commit();

            $this->command->info('');
            $this->command->info("✅ Created {$laporanCount} dummy laporan");
            $this->command->info('');
            $this->command->info('📊 RESIDIVIS SUMMARY:');
            $this->command->info("   • Telepon {$residivisTelepon}: 3 laporan");
            $this->command->info("   • Rekening {$residivisBank} {$residivisRekening}: 3 laporan");
            $this->command->info("   • Instagram {$residivisInstagram}: 3 laporan");
            $this->command->info("   • WhatsApp {$residivisWhatsapp}: 2 laporan");
            $this->command->info('');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a single laporan with all related data
     */
    private function createLaporan(array $data): Laporan
    {
        // Get or find kategori
        $kategori = KategoriKejahatan::where('nama', 'like', '%' . $data['kategori'] . '%')->first()
            ?? KategoriKejahatan::first();

        // Create pelapor (Orang)
        $pelapor = Orang::create([
            'nama' => $data['pelapor']['nama'],
            'nik' => $data['pelapor']['nik'],
            'tempat_lahir' => $data['pelapor']['tempat_lahir'],
            'tanggal_lahir' => $data['pelapor']['tanggal_lahir'],
            'jenis_kelamin' => $data['pelapor']['jenis_kelamin'],
            'pekerjaan' => $data['pelapor']['pekerjaan'],
            'telepon' => $data['pelapor']['telepon'],
            'kewarganegaraan' => 'WNI',
        ]);

        // Create alamat for pelapor
        $kodeKelurahan = $data['pelapor']['kode_kelurahan'] ?? null;
        if ($kodeKelurahan) {
            // Format kode: 33.74.01.0001 -> extract parts
            // Tapi data dummy pakai format lama, skip alamat untuk simplifikasi
        }

        // Create laporan
        $laporan = Laporan::create([
            'nomor_stpa' => $data['nomor_stpa'],
            'tanggal_laporan' => $data['tanggal_laporan'],
            'waktu_kejadian' => $data['waktu_kejadian'],
            'pelapor_id' => $pelapor->id,
            'hubungan_pelapor' => 'diri_sendiri',
            'petugas_id' => $data['personel']->id,
            'kategori_kejahatan_id' => $kategori->id,
            'kode_kabupaten_kejadian' => $data['kode_kabupaten'] ?? '3374',
            'kode_provinsi_kejadian' => '33',
            'modus' => $data['modus'],
            'status' => $data['status'],
            'assigned_subdit' => $data['assigned_subdit'] ?? null,
            'disposisi_subdit' => $data['disposisi_subdit'] ?? $data['assigned_subdit'] ?? null,
            'disposisi_unit' => $data['disposisi_unit'] ?? null,
        ]);

        // Create korban (same as pelapor for diri_sendiri)
        Korban::create([
            'laporan_id' => $laporan->id,
            'orang_id' => $pelapor->id,
            'kerugian_nominal' => $data['korban']['kerugian'] ?? 0,
        ]);

        // Create tersangka(s)
        foreach ($data['tersangka'] as $tersangkaData) {
            // Create orang for tersangka if nama provided
            $tersangkaOrangId = null;
            if (!empty($tersangkaData['nama'])) {
                $tersangkaOrang = Orang::create([
                    'nama' => $tersangkaData['nama'],
                    'nik' => $tersangkaData['nik'] ?? null,
                    'kewarganegaraan' => 'WNI',
                    'tempat_lahir' => 'Tidak Diketahui',
                    'tanggal_lahir' => '1990-01-01',
                    'jenis_kelamin' => 'Laki-laki',
                    'pekerjaan' => 'Tidak Diketahui',
                    'telepon' => '-',
                ]);
                $tersangkaOrangId = $tersangkaOrang->id;
            }

            $tersangka = Tersangka::create([
                'laporan_id' => $laporan->id,
                'orang_id' => $tersangkaOrangId,
                'catatan' => $tersangkaData['catatan'] ?? null,
            ]);

            // Create identitas tersangka
            foreach ($tersangkaData['identitas'] ?? [] as $identitasData) {
                IdentitasTersangka::create([
                    'tersangka_id' => $tersangka->id,
                    'jenis' => $identitasData['jenis'],
                    'nilai' => $identitasData['nilai'],
                    'platform' => $identitasData['platform'] ?? null,
                    'nama_akun' => $identitasData['nama_akun'] ?? null,
                ]);
            }
        }

        return $laporan;
    }
}
