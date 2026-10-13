<?php

namespace App\Http\Requests\Service;

use App\Enums\BillingUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna berwenang membuat master layanan baru.
     * Hanya Pemilik / Administrator yang memiliki kewenangan ini.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Sanitasi dan normalisasi input sebelum validasi.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge(['name' => trim((string) $this->input('name'))]);
        }

        if ($this->has('price')) {
            $rawPrice = trim((string) $this->input('price'));

            // Jika sudah numerik murni (misal 10000 atau 10000.00), pertahankan
            if (! is_numeric($rawPrice)) {
                $rawPrice = str_ireplace(['Rp', ' '], '', $rawPrice);

                if (str_contains($rawPrice, ',') && str_contains($rawPrice, '.')) {
                    $rawPrice = str_replace('.', '', $rawPrice);
                    $rawPrice = str_replace(',', '.', $rawPrice);
                } elseif (str_contains($rawPrice, ',')) {
                    $rawPrice = str_replace(',', '.', $rawPrice);
                } elseif (substr_count($rawPrice, '.') > 1) {
                    $rawPrice = str_replace('.', '', $rawPrice);
                }
            }

            $this->merge(['price' => $rawPrice]);
        }
    }

    /**
     * Aturan validasi untuk data master layanan baru.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:100', 'unique:services,name'],
            'description' => ['nullable', 'string', 'max:500'],
            'billing_unit' => ['required', Rule::enum(BillingUnit::class)],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'estimated_hours' => ['nullable', 'integer', 'min:1', 'max:720'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Pesan validasi berbahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama layanan wajib diisi.',
            'name.min' => 'Nama layanan minimal 3 karakter.',
            'name.max' => 'Nama layanan maksimal 100 karakter.',
            'name.unique' => 'Nama layanan ini sudah terdaftar. Gunakan nama lain.',
            'billing_unit.required' => 'Satuan penagihan wajib dipilih.',
            'billing_unit.Illuminate\Validation\Rules\Enum' => 'Satuan penagihan tidak valid. Pilih antara Kg, Item, atau Paket.',
            'price.required' => 'Harga tarif layanan wajib diisi.',
            'price.numeric' => 'Harga tarif layanan harus berupa angka valid.',
            'price.min' => 'Harga tarif layanan tidak boleh bernilai negatif.',
            'price.max' => 'Harga tarif layanan melebihi batas maksimum sistem.',
            'estimated_hours.integer' => 'Estimasi durasi pengerjaan harus berupa bilangan bulat jam.',
            'estimated_hours.min' => 'Estimasi durasi minimal 1 jam.',
            'estimated_hours.max' => 'Estimasi durasi maksimal 720 jam (30 hari).',
            'description.max' => 'Deskripsi layanan maksimal 500 karakter.',
        ];
    }
}
