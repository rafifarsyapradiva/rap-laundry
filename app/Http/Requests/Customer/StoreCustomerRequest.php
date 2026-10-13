<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna memiliki izin untuk membuat data pelanggan.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Sanitasi dan normalisasi input sebelum validasi dijalankan.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $rawPhone = preg_replace('/[\s\-\(\)\.]/', '', (string) $this->input('phone'));

            if (str_starts_with($rawPhone, '+62')) {
                $rawPhone = '0' . substr($rawPhone, 3);
            } elseif (str_starts_with($rawPhone, '62') && strlen($rawPhone) >= 10) {
                $rawPhone = '0' . substr($rawPhone, 2);
            }

            $this->merge(['phone' => $rawPhone]);
        }

        if ($this->has('name')) {
            $this->merge(['name' => trim((string) $this->input('name'))]);
        }
    }

    /**
     * Aturan validasi untuk data pelanggan baru.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'phone' => ['required', 'string', 'min:9', 'max:16', 'regex:/^0[2-9][0-9]{7,12}$/'],
            'address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Pesan validasi berbahasa Indonesia yang jelas dan informatif.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama pelanggan wajib diisi.',
            'name.min' => 'Nama pelanggan minimal terdiri dari 3 karakter.',
            'name.max' => 'Nama pelanggan maksimal 100 karakter.',
            'phone.required' => 'Nomor telepon wajib diisi.',
            'phone.min' => 'Nomor telepon terlalu pendek (minimal 9 digit).',
            'phone.max' => 'Nomor telepon terlalu panjang (maksimal 16 digit).',
            'phone.regex' => 'Format nomor telepon tidak valid. Gunakan format nomor telepon Indonesia (misal: 08123456789).',
            'address.max' => 'Alamat maksimal 500 karakter.',
            'notes.max' => 'Catatan maksimal 500 karakter.',
        ];
    }
}
