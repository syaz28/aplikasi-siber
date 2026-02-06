<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StoreLaporanRequest
 * 
 * Validation for creating new Laporan via multi-step form
 * Handles all 4 steps: Administrasi, Pelapor, Kejadian+Korban, Tersangka
 */
class StoreLaporanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // ==========================================
            // STEP 1: ADMINISTRASI
            // ==========================================
            'nomor_stpa' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('laporan', 'nomor_stpa'),
            ],
            'tanggal_laporan' => 'required|date',
            'petugas_id' => 'required|integer|exists:anggota,id',

            // ==========================================
            // STEP 2: DATA PELAPOR
            // ==========================================
            'pelapor' => 'required|array',
            'pelapor.nik' => [
                'required',
                'string',
                'size:16',
                'regex:/^[0-9]{16}$/',
            ],
            'pelapor.nama' => 'required|string|max:100',
            'pelapor.tempat_lahir' => 'required|string|max:100',
            'pelapor.tanggal_lahir' => 'required|date|before:today',
            'pelapor.jenis_kelamin' => 'required|in:LAKI-LAKI,PEREMPUAN',
            'pelapor.pekerjaan' => 'required|string|max:100',
            'pelapor.telepon' => [
                'required',
                'string',
                'max:20',
                'regex:/^(\+62|62|0)[0-9]{8,13}$/',
            ],
            'pelapor.email' => 'nullable|email|max:100',

            // Alamat KTP Pelapor
            'pelapor.alamat_ktp' => 'required|array',
            'pelapor.alamat_ktp.kode_provinsi' => 'required|string|size:2|exists:wilayah,kode',
            'pelapor.alamat_ktp.kode_kabupaten' => 'required|string|size:5|exists:wilayah,kode',
            'pelapor.alamat_ktp.kode_kecamatan' => 'required|string|size:8|exists:wilayah,kode',
            'pelapor.alamat_ktp.kode_kelurahan' => 'required|string|size:13|exists:wilayah,kode',
            'pelapor.alamat_ktp.detail_alamat' => 'required|string|max:500',

            // Alamat Domisili (optional)
            'pelapor.alamat_domisili' => 'nullable|array',
            'pelapor.alamat_domisili.kode_provinsi' => 'nullable|string|size:2|exists:wilayah,kode',
            'pelapor.alamat_domisili.kode_kabupaten' => 'nullable|string|size:5|exists:wilayah,kode',
            'pelapor.alamat_domisili.kode_kecamatan' => 'nullable|string|size:8|exists:wilayah,kode',
            'pelapor.alamat_domisili.kode_kelurahan' => 'nullable|string|size:13|exists:wilayah,kode',
            'pelapor.alamat_domisili.detail_alamat' => 'nullable|string|max:500',

            'hubungan_pelapor' => 'required|in:diri_sendiri,keluarga,kuasa_hukum,teman,rekan_kerja,lainnya',

            // ==========================================
            // STEP 3: DATA KEJADIAN + KORBAN
            // ==========================================
            'kategori_kejahatan_id' => 'required|integer|exists:kategori_kejahatan,id',
            'waktu_kejadian' => 'required|date|before_or_equal:now',
            'modus' => 'required|string|min:10|max:5000',
            'catatan' => 'nullable|string|max:2000',

            // Lokasi Kejadian (optional - bisa online crime)
            'kode_provinsi_kejadian' => 'nullable|string|size:2|exists:wilayah,kode',
            'kode_kabupaten_kejadian' => 'nullable|string|size:5|exists:wilayah,kode',
            'kode_kecamatan_kejadian' => 'nullable|string|size:8|exists:wilayah,kode',
            'kode_kelurahan_kejadian' => 'nullable|string|size:13|exists:wilayah,kode',
            'alamat_kejadian' => 'nullable|string|max:500',

            // Korban (1:N - minimal 1 korban)
            'korban' => 'required|array|min:1',
            'korban.*.orang' => 'required|array',
            'korban.*.orang.nik' => [
                'required',
                'string',
                'size:16',
                'regex:/^[0-9]{16}$/',
            ],
            'korban.*.orang.nama' => 'required|string|max:100',
            'korban.*.orang.tempat_lahir' => 'required|string|max:100',
            'korban.*.orang.tanggal_lahir' => 'required|date|before:today',
            'korban.*.orang.jenis_kelamin' => 'required|in:LAKI-LAKI,PEREMPUAN',
            'korban.*.orang.pekerjaan' => 'required|string|max:100',
            'korban.*.orang.telepon' => [
                'required',
                'string',
                'max:20',
                'regex:/^(\+62|62|0)[0-9]{8,13}$/',
            ],
            'korban.*.orang.email' => 'nullable|email|max:100',
            'korban.*.kerugian_nominal' => 'required|numeric|min:0|max:999999999999999',
            'korban.*.keterangan' => 'nullable|string|max:1000',

            // ==========================================
            // STEP 4: DATA TERSANGKA (Optional)
            // ==========================================
            'tersangka' => 'nullable|array',
            'tersangka.*.orang' => 'nullable|array',
            'tersangka.*.orang.nik' => 'nullable|string|size:16|regex:/^[0-9]{16}$/',
            'tersangka.*.orang.nama' => 'nullable|string|max:100',
            'tersangka.*.orang.tempat_lahir' => 'nullable|string|max:100',
            'tersangka.*.orang.tanggal_lahir' => 'nullable|date|before:today',
            'tersangka.*.orang.jenis_kelamin' => 'nullable|in:LAKI-LAKI,PEREMPUAN',
            'tersangka.*.orang.pekerjaan' => 'nullable|string|max:100',
            'tersangka.*.orang.telepon' => 'nullable|string|max:20',
            'tersangka.*.catatan' => 'nullable|string|max:1000',

            // Identitas Digital Tersangka (1:N)
            'tersangka.*.identitas' => 'nullable|array',
            'tersangka.*.identitas.*.jenis' => 'required|in:telepon,rekening,sosmed,email,ewallet,kripto,marketplace,website,lainnya',
            'tersangka.*.identitas.*.nilai' => 'required|string|max:255',
            'tersangka.*.identitas.*.platform' => 'nullable|string|max:100',
            'tersangka.*.identitas.*.nama_akun' => 'nullable|string|max:100',
            'tersangka.*.identitas.*.catatan' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            // Administrasi
            'nomor_stpa.unique' => 'Nomor STPA sudah digunakan',
            'tanggal_laporan.required' => 'Tanggal laporan wajib diisi',
            'petugas_id.required' => 'Petugas penerima wajib dipilih',
            'petugas_id.exists' => 'Petugas tidak ditemukan',

            // Pelapor
            'pelapor.nik.required' => 'NIK pelapor wajib diisi',
            'pelapor.nik.size' => 'NIK harus 16 digit',
            'pelapor.nik.regex' => 'NIK harus berupa angka',
            'pelapor.nama.required' => 'Nama pelapor wajib diisi',
            'pelapor.tempat_lahir.required' => 'Tempat lahir pelapor wajib diisi',
            'pelapor.tanggal_lahir.required' => 'Tanggal lahir pelapor wajib diisi',
            'pelapor.tanggal_lahir.before' => 'Tanggal lahir tidak valid',
            'pelapor.jenis_kelamin.required' => 'Jenis kelamin pelapor wajib dipilih',
            'pelapor.pekerjaan.required' => 'Pekerjaan pelapor wajib diisi',
            'pelapor.telepon.required' => 'Nomor telepon pelapor wajib diisi',
            'pelapor.telepon.regex' => 'Format nomor telepon tidak valid',

            // Alamat
            'pelapor.alamat_ktp.kode_provinsi.required' => 'Provinsi wajib dipilih',
            'pelapor.alamat_ktp.kode_kabupaten.required' => 'Kabupaten/Kota wajib dipilih',
            'pelapor.alamat_ktp.kode_kecamatan.required' => 'Kecamatan wajib dipilih',
            'pelapor.alamat_ktp.kode_kelurahan.required' => 'Kelurahan wajib dipilih',
            'pelapor.alamat_ktp.detail_alamat.required' => 'Detail alamat wajib diisi',

            // Kejadian
            'kategori_kejahatan_id.required' => 'Kategori kejahatan wajib dipilih',
            'kategori_kejahatan_id.exists' => 'Kategori kejahatan tidak ditemukan',
            'waktu_kejadian.required' => 'Waktu kejadian wajib diisi',
            'waktu_kejadian.before_or_equal' => 'Waktu kejadian tidak boleh di masa depan',
            'modus.required' => 'Modus operandi wajib diisi',
            'modus.min' => 'Modus operandi minimal 10 karakter',

            // Korban
            'korban.required' => 'Minimal harus ada 1 korban',
            'korban.min' => 'Minimal harus ada 1 korban',
            'korban.*.orang.nik.required' => 'NIK korban wajib diisi',
            'korban.*.orang.nik.size' => 'NIK korban harus 16 digit',
            'korban.*.orang.nama.required' => 'Nama korban wajib diisi',
            'korban.*.kerugian_nominal.required' => 'Kerugian korban wajib diisi',
            'korban.*.kerugian_nominal.min' => 'Kerugian tidak boleh negatif',

            // Tersangka Identitas
            'tersangka.*.identitas.*.jenis.required' => 'Jenis identitas wajib dipilih',
            'tersangka.*.identitas.*.jenis.in' => 'Jenis identitas tidak valid',
            'tersangka.*.identitas.*.nilai.required' => 'Nilai identitas wajib diisi',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'pelapor.nik' => 'NIK pelapor',
            'pelapor.nama' => 'nama pelapor',
            'pelapor.telepon' => 'telepon pelapor',
            'korban.*.orang.nik' => 'NIK korban',
            'korban.*.orang.nama' => 'nama korban',
            'korban.*.kerugian_nominal' => 'kerugian korban',
            'tersangka.*.identitas.*.jenis' => 'jenis identitas',
            'tersangka.*.identitas.*.nilai' => 'nilai identitas',
        ];
    }
}
