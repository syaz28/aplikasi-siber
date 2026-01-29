<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateLaporanRequest
 * 
 * Validation for updating existing Laporan
 * Supports partial updates (only validate provided fields)
 */
class UpdateLaporanRequest extends FormRequest
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
        $laporanId = $this->route('laporan') ?? $this->route('id');

        return [
            // Administrasi
            'nomor_stpa' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
                Rule::unique('laporan', 'nomor_stpa')->ignore($laporanId),
            ],
            'tanggal_laporan' => 'sometimes|date',
            'petugas_id' => 'sometimes|integer|exists:anggota,id',
            'hubungan_pelapor' => 'sometimes|in:diri_sendiri,keluarga,kuasa_hukum,teman,rekan_kerja,lainnya',

            // Kejadian
            'kategori_kejahatan_id' => 'sometimes|integer|exists:kategori_kejahatan,id',
            'waktu_kejadian' => 'sometimes|date|before_or_equal:now',
            'modus' => 'sometimes|string|min:10|max:5000',
            'catatan' => 'nullable|string|max:2000',

            // Lokasi Kejadian
            'kode_provinsi_kejadian' => 'nullable|string|size:2|exists:wilayah,kode',
            'kode_kabupaten_kejadian' => 'nullable|string|size:5|exists:wilayah,kode',
            'kode_kecamatan_kejadian' => 'nullable|string|size:8|exists:wilayah,kode',
            'kode_kelurahan_kejadian' => 'nullable|string|size:13|exists:wilayah,kode',
            'alamat_kejadian' => 'nullable|string|max:500',

            // Status (for workflow)
            'status' => 'sometimes|in:draft,submitted,verified,investigating,closed,rejected',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'nomor_stpa.unique' => 'Nomor STPA sudah digunakan oleh laporan lain',
            'petugas_id.exists' => 'Petugas tidak ditemukan',
            'kategori_kejahatan_id.exists' => 'Kategori kejahatan tidak ditemukan',
            'waktu_kejadian.before_or_equal' => 'Waktu kejadian tidak boleh di masa depan',
            'modus.min' => 'Modus operandi minimal 10 karakter',
            'status.in' => 'Status tidak valid',
        ];
    }
}
