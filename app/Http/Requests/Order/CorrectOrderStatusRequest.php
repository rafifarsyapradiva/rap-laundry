<?php

namespace App\Http\Requests\Order;

use App\Enums\LaundryStatus;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CorrectOrderStatusRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna berhak melakukan koreksi status pada pesanan ini.
     */
    public function authorize(): bool
    {
        /** @var Order|null $order */
        $order = $this->route('order');

        return $order instanceof Order && (bool) $this->user()?->can('correctStatus', $order);
    }

    /**
     * Sanitasi dan normalisasi input sebelum validasi.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => is_string($this->input('status')) ? trim($this->input('status')) : $this->input('status'),
            'reason' => is_string($this->input('reason')) ? trim($this->input('reason')) : null,
        ]);
    }

    /**
     * Aturan validasi koreksi status di server.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Order $order */
        $order = $this->route('order');

        $currentStatusValue = $order instanceof Order ? $order->laundry_status->value : null;

        return [
            'status' => [
                'required',
                'string',
                Rule::enum(LaundryStatus::class),
                // Status koreksi tidak boleh sama dengan status saat ini
                Rule::notIn(array_filter([$currentStatusValue])),
                // Status koreksi tidak boleh Dibatalkan (harus melalui alur pembatalan resmi)
                Rule::notIn([LaundryStatus::Dibatalkan->value]),
            ],
            'reason' => [
                'required',
                'string',
                'min:5',
                'max:1000',
            ],
        ];
    }

    /**
     * Pesan kesalahan validasi berbahasa Indonesia yang informatif.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Status target koreksi wajib dipilih.',
            'status.enum' => 'Status koreksi yang dipilih tidak valid di dalam sistem.',
            'status.not_in' => 'Status target koreksi tidak boleh sama dengan status saat ini atau status dibatalkan.',
            'reason.required' => 'Alasan koreksi status wajib diisi untuk keperluan jejak audit.',
            'reason.min' => 'Alasan koreksi status minimal 5 karakter agar informatif bagi audit operasional.',
            'reason.max' => 'Alasan koreksi status tidak boleh melebihi 1000 karakter.',
        ];
    }
}
