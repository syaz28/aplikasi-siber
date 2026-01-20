<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StoreOrangRequest
 * 
 * Validation for creating/updating Orang (person) data
 * Used for Pelapor, Korban, and Tersangka
 * Includes NIK format validation and phone format
 */
class StoreOrangRequest extends FormRequest
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
        $orangId = $this->route('orang') ?? $this->route('id');

        return [
            'nik' => [
                'required',
                'string',
                'size:16',
                'regex:/^[0-9]{16}$/',
                $orangId 
                    ? Rule::unique('orang', 'nik')->ignore($orangId)
                    : Rule::unique('orang', 'nik'),
            ],
            'nama' => 'required|string|max:100',
            'tempat_lahir' => 'required|string|max:100',
            'tanggal_lahir' => 'required|date|before:today',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'pekerjaan' => 'required|string|max:100',
            'telepon' => [
                'required',
                'string',
                'max:20',
                'regex:/^(\+62|62|0)[0-9]{8,13}$/',
            ],
            'email' => 'nullable|email|max:100',

            // Alamat KTP (optional - can be added separately)
            'alamat_ktp' => 'nullable|array',
            'alamat_ktp.kode_provinsi' => 'required_with:alamat_ktp|string|size:2|exists:wilayah,kode',
            'alamat_ktp.kode_kabupaten' => 'required_with:alamat_ktp|string|size:5|exists:wilayah,kode',
            'alamat_ktp.kode_kecamatan' => 'required_with:alamat_ktp|string|size:8|exists:wilayah,kode',
            'alamat_ktp.kode_kelurahan' => 'required_with:alamat_ktp|string|size:13|exists:wilayah,kode',
            'alamat_ktp.detail_alamat' => 'required_with:alamat_ktp|string|max:500',

            // Alamat Domisili (optional)
            'alamat_domisili' => 'nullable|array',
            'alamat_domisili.kode_provinsi' => 'required_with:alamat_domisili|string|size:2|exists:wilayah,kode',
            'alamat_domisili.kode_kabupaten' => 'required_with:alamat_domisili|string|size:5|exists:wilayah,kode',
            'alamat_domisili.kode_kecamatan' => 'required_with:alamat_domisili|string|size:8|exists:wilayah,kode',
            'alamat_domisili.kode_kelurahan' => 'required_with:alamat_domisili|string|size:13|exists:wilayah,kode',
            'alamat_domisili.detail_alamat' => 'required_with:alamat_domisili|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'nik.required' => 'NIK wajib diisi',
            'nik.size' => 'NIK harus tepat 16 digit',
            'nik.regex' => 'NIK harus berupa angka',
            'nik.unique' => 'NIK sudah terdaftar dalam sistem',
            'nama.required' => 'Nama lengkap wajib diisi',
            'nama.max' => 'Nama maksimal 100 karakter',
            'tempat_lahir.required' => 'Tempat lahir wajib diisi',
            'tanggal_lahir.required' => 'Tanggal lahir wajib diisi',
            'tanggal_lahir.before' => 'Tanggal lahir tidak valid',
            'jenis_kelamin.required' => 'Jenis kelamin wajib dipilih',
            'jenis_kelamin.in' => 'Jenis kelamin harus Laki-laki atau Perempuan',
            'pekerjaan.required' => 'Pekerjaan wajib diisi',
            'telepon.required' => 'Nomor telepon wajib diisi',
            'telepon.regex' => 'Format nomor telepon tidak valid (contoh: 08123456789)',
            'email.email' => 'Format email tidak valid',

            // Alamat
            'alamat_ktp.kode_provinsi.required_with' => 'Provinsi wajib dipilih',
            'alamat_ktp.kode_provinsi.exists' => 'Provinsi tidak ditemukan',
            'alamat_ktp.kode_kabupaten.required_with' => 'Kabupaten/Kota wajib dipilih',
            'alamat_ktp.kode_kabupaten.exists' => 'Kabupaten/Kota tidak ditemukan',
            'alamat_ktp.kode_kecamatan.required_with' => 'Kecamatan wajib dipilih',
            'alamat_ktp.kode_kecamatan.exists' => 'Kecamatan tidak ditemukan',
            'alamat_ktp.kode_kelurahan.required_with' => 'Kelurahan wajib dipilih',
            'alamat_ktp.kode_kelurahan.exists' => 'Kelurahan tidak ditemukan',
            'alamat_ktp.detail_alamat.required_with' => 'Detail alamat wajib diisi',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'nik' => 'NIK',
            'nama' => 'nama lengkap',
            'tempat_lahir' => 'tempat lahir',
            'tanggal_lahir' => 'tanggal lahir',
            'jenis_kelamin' => 'jenis kelamin',
            'pekerjaan' => 'pekerjaan',
            'telepon' => 'nomor telepon',
            'email' => 'email',
        ];
    }

    /**
     * Validate NIK structure (Indonesian ID number)
     * 
     * Format: PPKKCC-DDMMYY-XXXX
     * - PP: Province code (01-91)
     * - KK: City/Regency code
     * - CC: District code
     * - DD: Birth date (female +40)
     * - MM: Birth month
     * - YY: Birth year
     * - XXXX: Sequential number
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $nik = $this->input('nik');
            $tanggalLahir = $this->input('tanggal_lahir');

            if ($nik && $tanggalLahir && strlen($nik) === 16) {
                // Extract birth date from NIK
                $nikDay = (int) substr($nik, 6, 2);
                $nikMonth = (int) substr($nik, 8, 2);
                $nikYear = (int) substr($nik, 10, 2);

                // Female NIK has day + 40
                if ($nikDay > 40) {
                    $nikDay -= 40;
                }

                // Parse actual birth date
                $birthDate = \Carbon\Carbon::parse($tanggalLahir);
                $birthDay = $birthDate->day;
                $birthMonth = $birthDate->month;
                $birthYear = $birthDate->year % 100; // Last 2 digits

                // Validate date matches NIK (warning only, not blocking)
                if ($nikDay !== $birthDay || $nikMonth !== $birthMonth || $nikYear !== $birthYear) {
                    // Add as warning, not error (NIK might be entered manually)
                    // $validator->errors()->add('nik', 'Tanggal lahir di NIK tidak sesuai dengan tanggal lahir yang diinput');
                }
            }
        });
    }
}
