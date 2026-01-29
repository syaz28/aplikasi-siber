<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterPlatformSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Seed master platforms for identitas tersangka
     * Hierarchical by kategori (jenis identitas)
     */
    public function run(): void
    {
        $platforms = [
            // Media Sosial
            ['kategori' => 'Media Sosial', 'nama_platform' => 'Facebook', 'urutan' => 1],
            ['kategori' => 'Media Sosial', 'nama_platform' => 'Instagram', 'urutan' => 2],
            ['kategori' => 'Media Sosial', 'nama_platform' => 'TikTok', 'urutan' => 3],
            ['kategori' => 'Media Sosial', 'nama_platform' => 'Twitter/X', 'urutan' => 4],
            ['kategori' => 'Media Sosial', 'nama_platform' => 'YouTube', 'urutan' => 5],
            ['kategori' => 'Media Sosial', 'nama_platform' => 'LinkedIn', 'urutan' => 6],
            ['kategori' => 'Media Sosial', 'nama_platform' => 'MiChat', 'urutan' => 7],
            ['kategori' => 'Media Sosial', 'nama_platform' => 'SnackVideo', 'urutan' => 8],
            ['kategori' => 'Media Sosial', 'nama_platform' => 'Telegram', 'urutan' => 9],
            ['kategori' => 'Media Sosial', 'nama_platform' => 'WeChat', 'urutan' => 10],
            ['kategori' => 'Media Sosial', 'nama_platform' => 'Line', 'urutan' => 11],
            ['kategori' => 'Media Sosial', 'nama_platform' => 'Discord', 'urutan' => 12],
            
            // Nomor Telepon / Messaging
            ['kategori' => 'Nomor Telepon', 'nama_platform' => 'WhatsApp', 'urutan' => 1],
            ['kategori' => 'Nomor Telepon', 'nama_platform' => 'Telegram', 'urutan' => 2],
            ['kategori' => 'Nomor Telepon', 'nama_platform' => 'Signal', 'urutan' => 3],
            ['kategori' => 'Nomor Telepon', 'nama_platform' => 'GetContact', 'urutan' => 4],
            ['kategori' => 'Nomor Telepon', 'nama_platform' => 'Seluler Biasa (SMS/Telp)', 'urutan' => 5],
            ['kategori' => 'Nomor Telepon', 'nama_platform' => 'Truecaller', 'urutan' => 6],
            
            // Rekening Bank
            ['kategori' => 'Rekening Bank', 'nama_platform' => 'BRI', 'urutan' => 1],
            ['kategori' => 'Rekening Bank', 'nama_platform' => 'BCA', 'urutan' => 2],
            ['kategori' => 'Rekening Bank', 'nama_platform' => 'Mandiri', 'urutan' => 3],
            ['kategori' => 'Rekening Bank', 'nama_platform' => 'BNI', 'urutan' => 4],
            ['kategori' => 'Rekening Bank', 'nama_platform' => 'BSI', 'urutan' => 5],
            ['kategori' => 'Rekening Bank', 'nama_platform' => 'CIMB Niaga', 'urutan' => 6],
            ['kategori' => 'Rekening Bank', 'nama_platform' => 'Jenius', 'urutan' => 7],
            ['kategori' => 'Rekening Bank', 'nama_platform' => 'Bank Jago', 'urutan' => 8],
            ['kategori' => 'Rekening Bank', 'nama_platform' => 'SeaBank', 'urutan' => 9],
            ['kategori' => 'Rekening Bank', 'nama_platform' => 'Danamon', 'urutan' => 10],
            ['kategori' => 'Rekening Bank', 'nama_platform' => 'Permata', 'urutan' => 11],
            ['kategori' => 'Rekening Bank', 'nama_platform' => 'BTPN', 'urutan' => 12],
            ['kategori' => 'Rekening Bank', 'nama_platform' => 'Mega', 'urutan' => 13],
            ['kategori' => 'Rekening Bank', 'nama_platform' => 'BRI Syariah', 'urutan' => 14],
            
            // E-Wallet
            ['kategori' => 'E-Wallet', 'nama_platform' => 'DANA', 'urutan' => 1],
            ['kategori' => 'E-Wallet', 'nama_platform' => 'OVO', 'urutan' => 2],
            ['kategori' => 'E-Wallet', 'nama_platform' => 'GoPay', 'urutan' => 3],
            ['kategori' => 'E-Wallet', 'nama_platform' => 'ShopeePay', 'urutan' => 4],
            ['kategori' => 'E-Wallet', 'nama_platform' => 'LinkAja', 'urutan' => 5],
            ['kategori' => 'E-Wallet', 'nama_platform' => 'iSaku', 'urutan' => 6],
            ['kategori' => 'E-Wallet', 'nama_platform' => 'Sakuku', 'urutan' => 7],
            ['kategori' => 'E-Wallet', 'nama_platform' => 'Jenius Pay', 'urutan' => 8],
            
            // Marketplace
            ['kategori' => 'Marketplace', 'nama_platform' => 'Shopee', 'urutan' => 1],
            ['kategori' => 'Marketplace', 'nama_platform' => 'Tokopedia', 'urutan' => 2],
            ['kategori' => 'Marketplace', 'nama_platform' => 'Lazada', 'urutan' => 3],
            ['kategori' => 'Marketplace', 'nama_platform' => 'Bukalapak', 'urutan' => 4],
            ['kategori' => 'Marketplace', 'nama_platform' => 'TikTok Shop', 'urutan' => 5],
            ['kategori' => 'Marketplace', 'nama_platform' => 'OLX', 'urutan' => 6],
            ['kategori' => 'Marketplace', 'nama_platform' => 'Facebook Marketplace', 'urutan' => 7],
            ['kategori' => 'Marketplace', 'nama_platform' => 'Blibli', 'urutan' => 8],
            ['kategori' => 'Marketplace', 'nama_platform' => 'JD.ID', 'urutan' => 9],
            
            // Email
            ['kategori' => 'Email', 'nama_platform' => 'Gmail', 'urutan' => 1],
            ['kategori' => 'Email', 'nama_platform' => 'Yahoo', 'urutan' => 2],
            ['kategori' => 'Email', 'nama_platform' => 'Outlook', 'urutan' => 3],
            ['kategori' => 'Email', 'nama_platform' => 'iCloud', 'urutan' => 4],
            ['kategori' => 'Email', 'nama_platform' => 'ProtonMail', 'urutan' => 5],
            ['kategori' => 'Email', 'nama_platform' => 'Lainnya', 'urutan' => 99],
            
            // Lainnya (catch-all)
            ['kategori' => 'Lainnya', 'nama_platform' => 'Website/Link', 'urutan' => 1],
            ['kategori' => 'Lainnya', 'nama_platform' => 'Aplikasi Lain', 'urutan' => 2],
            ['kategori' => 'Lainnya', 'nama_platform' => 'Tidak Diketahui', 'urutan' => 99],
        ];

        foreach ($platforms as $platform) {
            DB::table('master_platforms')->insert([
                'kategori' => $platform['kategori'],
                'nama_platform' => $platform['nama_platform'],
                'urutan' => $platform['urutan'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
