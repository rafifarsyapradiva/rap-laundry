<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBusinessSettingRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna berwenang membuat permintaan ini.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Dapatkan aturan validasi yang berlaku untuk permintaan ini.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:100'],
            'business_address' => ['required', 'string', 'max:255'],
            'business_phone' => ['required', 'string', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'operating_hours' => ['nullable', 'string', 'max:100'],
            'order_number_prefix' => ['required', 'string', 'alpha_num', 'min:2', 'max:5'],
            'default_estimated_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'cashier_max_discount_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'receipt_footer_note' => ['nullable', 'string', 'max:500'],
            'print_format' => ['required', 'in:a4,thermal'],
            'logo' => ['nullable', 'file', 'image', 'mimes:jpeg,png,webp', 'max:1024'],
            'remove_logo' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Dapatkan pesan kesalahan khusus untuk aturan yang ditentukan.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'business_name.required' => 'Nama usaha laundry wajib diisi.',
            'business_name.max' => 'Nama usaha maksimal 100 karakter.',
            'business_address.required' => 'Alamat usaha laundry wajib diisi.',
            'business_phone.required' => 'Nomor telepon usaha wajib diisi.',
            'business_phone.regex' => 'Format nomor telepon tidak valid (hanya angka, tanda +, spasi, dan strip, 8-20 karakter).',
            'order_number_prefix.required' => 'Format awalan nomor transaksi wajib diisi.',
            'order_number_prefix.alpha_num' => 'Awalan nomor transaksi hanya boleh berisi huruf dan angka.',
            'order_number_prefix.min' => 'Awalan nomor transaksi minimal 2 karakter.',
            'order_number_prefix.max' => 'Awalan nomor transaksi maksimal 5 karakter.',
            'default_estimated_hours.required' => 'Estimasi durasi pengerjaan bawaan wajib ditentukan.',
            'default_estimated_hours.integer' => 'Estimasi durasi harus berupa bilangan bulat dalam jam.',
            'default_estimated_hours.min' => 'Estimasi durasi minimal 1 jam.',
            'default_estimated_hours.max' => 'Estimasi durasi maksimal 720 jam (30 hari).',
            'cashier_max_discount_percent.required' => 'Batas diskon kasir wajib ditentukan.',
            'cashier_max_discount_percent.integer' => 'Batas diskon kasir harus berupa persentase 0-100.',
            'cashier_max_discount_percent.min' => 'Batas diskon kasir tidak boleh kurang dari 0%.',
            'cashier_max_discount_percent.max' => 'Batas diskon kasir tidak boleh melebihi 100%.',
            'print_format.required' => 'Format cetak bawaan wajib dipilih.',
            'print_format.in' => 'Format cetak hanya boleh "a4" (Faktur Standar) atau "thermal" (Struk Thermal).',
            'logo.image' => 'Berkas logo harus berupa gambar valid.',
            'logo.mimes' => 'Format berkas logo harus berupa PNG, JPG, JPEG, atau WEBP.',
            'logo.max' => 'Ukuran berkas logo maksimal 1.024 KB (1 MB).',
        ];
    }

    /**
     * Siapkan data sebelum divalidasi (normalisasi input).
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('order_number_prefix')) {
            $this->merge([
                'order_number_prefix' => strtoupper(trim((string) $this->input('order_number_prefix'))),
            ]);
        }

        if ($this->has('remove_logo')) {
            $this->merge([
                'remove_logo' => $this->boolean('remove_logo'),
            ]);
        }
    }
}
