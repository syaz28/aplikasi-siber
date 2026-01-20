<?php

namespace Database\Seeders;

use App\Models\JenisKejahatan;
use App\Models\KategoriKejahatan;
use Illuminate\Database\Seeder;

/**
 * Seeder untuk Jenis Kejahatan Siber
 * 
 * 30+ jenis kejahatan spesifik berdasarkan kategori
 */
class JenisKejahatanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get kategori IDs by name
        $kategoriMap = KategoriKejahatan::pluck('id', 'nama')->toArray();

        $jenisKejahatan = [
            // Penipuan Online (8 jenis)
            [
                'kategori' => 'Penipuan Online',
                'nama' => 'Penipuan Investasi Bodong',
                'deskripsi' => 'Penipuan berkedok investasi dengan iming-iming keuntungan besar',
            ],
            [
                'kategori' => 'Penipuan Online',
                'nama' => 'Penipuan Jual Beli Online',
                'deskripsi' => 'Penipuan dalam transaksi jual beli online (barang tidak dikirim/tidak sesuai)',
            ],
            [
                'kategori' => 'Penipuan Online',
                'nama' => 'Penipuan Berkedok Undian',
                'deskripsi' => 'Penipuan dengan modus pemenang undian palsu',
            ],
            [
                'kategori' => 'Penipuan Online',
                'nama' => 'Penipuan Romance Scam',
                'deskripsi' => 'Penipuan dengan modus hubungan romantis/asmara palsu',
            ],
            [
                'kategori' => 'Penipuan Online',
                'nama' => 'Penipuan Berkedok Lowongan Kerja',
                'deskripsi' => 'Penipuan dengan modus tawaran pekerjaan palsu',
            ],
            [
                'kategori' => 'Penipuan Online',
                'nama' => 'Penipuan Berkedok Pinjaman Online',
                'deskripsi' => 'Penipuan mengatasnamakan pinjaman online ilegal',
            ],
            [
                'kategori' => 'Penipuan Online',
                'nama' => 'Penipuan Kripto/Aset Digital',
                'deskripsi' => 'Penipuan terkait cryptocurrency atau aset digital lainnya',
            ],
            [
                'kategori' => 'Penipuan Online',
                'nama' => 'Penipuan Phishing',
                'deskripsi' => 'Penipuan untuk mendapatkan data sensitif melalui link/website palsu',
            ],

            // Pemerasan Digital (4 jenis)
            [
                'kategori' => 'Pemerasan Digital',
                'nama' => 'Sextortion',
                'deskripsi' => 'Pemerasan menggunakan konten intim korban',
            ],
            [
                'kategori' => 'Pemerasan Digital',
                'nama' => 'Ransomware',
                'deskripsi' => 'Pemerasan dengan mengenkripsi data korban',
            ],
            [
                'kategori' => 'Pemerasan Digital',
                'nama' => 'Ancaman Penyebaran Data',
                'deskripsi' => 'Pemerasan dengan ancaman menyebarkan data pribadi',
            ],
            [
                'kategori' => 'Pemerasan Digital',
                'nama' => 'Pemerasan via Sosial Media',
                'deskripsi' => 'Pemerasan melalui platform media sosial',
            ],

            // Pencurian Data (4 jenis)
            [
                'kategori' => 'Pencurian Data',
                'nama' => 'Pencurian Identitas',
                'deskripsi' => 'Pencurian data identitas untuk disalahgunakan',
            ],
            [
                'kategori' => 'Pencurian Data',
                'nama' => 'Pencurian Data Kartu Kredit',
                'deskripsi' => 'Pencurian data kartu kredit/debit',
            ],
            [
                'kategori' => 'Pencurian Data',
                'nama' => 'Pembobolan Akun',
                'deskripsi' => 'Pengambilalihan akun digital korban',
            ],
            [
                'kategori' => 'Pencurian Data',
                'nama' => 'Social Engineering',
                'deskripsi' => 'Manipulasi psikologis untuk mendapatkan data sensitif',
            ],

            // Akses Ilegal (3 jenis)
            [
                'kategori' => 'Akses Ilegal',
                'nama' => 'Hacking',
                'deskripsi' => 'Akses ilegal ke sistem komputer',
            ],
            [
                'kategori' => 'Akses Ilegal',
                'nama' => 'Defacing Website',
                'deskripsi' => 'Mengubah tampilan website tanpa izin',
            ],
            [
                'kategori' => 'Akses Ilegal',
                'nama' => 'DDoS Attack',
                'deskripsi' => 'Serangan untuk melumpuhkan layanan',
            ],

            // Konten Ilegal (5 jenis)
            [
                'kategori' => 'Konten Ilegal',
                'nama' => 'Penyebaran Konten Pornografi',
                'deskripsi' => 'Penyebaran konten pornografi termasuk revenge porn',
            ],
            [
                'kategori' => 'Konten Ilegal',
                'nama' => 'Cyberbullying',
                'deskripsi' => 'Perundungan melalui media digital',
            ],
            [
                'kategori' => 'Konten Ilegal',
                'nama' => 'Hoax/Berita Bohong',
                'deskripsi' => 'Penyebaran berita bohong/disinformasi',
            ],
            [
                'kategori' => 'Konten Ilegal',
                'nama' => 'SARA',
                'deskripsi' => 'Penyebaran konten berbau SARA',
            ],
            [
                'kategori' => 'Konten Ilegal',
                'nama' => 'Penghinaan/Pencemaran Nama Baik',
                'deskripsi' => 'Penghinaan atau pencemaran nama baik via digital',
            ],

            // Perjudian Online (2 jenis)
            [
                'kategori' => 'Perjudian Online',
                'nama' => 'Judi Online',
                'deskripsi' => 'Perjudian melalui platform online/website',
            ],
            [
                'kategori' => 'Perjudian Online',
                'nama' => 'Slot Online Ilegal',
                'deskripsi' => 'Permainan slot online ilegal',
            ],

            // Kejahatan Perbankan Digital (4 jenis)
            [
                'kategori' => 'Kejahatan Perbankan Digital',
                'nama' => 'Skimming',
                'deskripsi' => 'Pencurian data kartu melalui alat skimmer',
            ],
            [
                'kategori' => 'Kejahatan Perbankan Digital',
                'nama' => 'SIM Swap',
                'deskripsi' => 'Pengambilalihan nomor HP untuk akses perbankan',
            ],
            [
                'kategori' => 'Kejahatan Perbankan Digital',
                'nama' => 'Pembobolan Mobile Banking',
                'deskripsi' => 'Pembobolan aplikasi mobile banking',
            ],
            [
                'kategori' => 'Kejahatan Perbankan Digital',
                'nama' => 'Pembobolan Internet Banking',
                'deskripsi' => 'Pembobolan akses internet banking',
            ],

            // Lainnya (1 jenis)
            [
                'kategori' => 'Lainnya',
                'nama' => 'Kejahatan Siber Lainnya',
                'deskripsi' => 'Jenis kejahatan siber lain yang tidak terkategori',
            ],
        ];

        $count = 0;
        foreach ($jenisKejahatan as $data) {
            $kategoriId = $kategoriMap[$data['kategori']] ?? null;
            
            if (!$kategoriId) {
                $this->command->warn("Kategori tidak ditemukan: {$data['kategori']}");
                continue;
            }

            JenisKejahatan::updateOrCreate(
                [
                    'kategori_kejahatan_id' => $kategoriId,
                    'nama' => $data['nama'],
                ],
                [
                    'kategori_kejahatan_id' => $kategoriId,
                    'nama' => $data['nama'],
                    'deskripsi' => $data['deskripsi'],
                    'is_active' => true,
                ]
            );
            $count++;
        }

        $this->command->info("✅ Seeded: {$count} Jenis Kejahatan Siber");
    }
}
