<?php

namespace App\Http\Requests\Order;

use App\Enums\LaundryStatus;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna memiliki izin untuk memperbarui status pesanan ini.
     */
    public function authorize(): bool
    {
        /** @var Order|null $order */
        $order = $this->route('order');

        if (! $order instanceof Order) {
            return false;
        }

        $user = $this->user();
        if (! $user) {
            return false;
        }

        $targetStatus = $this->input('status');

        if ($targetStatus === LaundryStatus::Dibatalkan->value) {
            return $user->can('cancel', $order);
        }

        return $user->can('updateStatus', $order);
    }

    /**
     * Sanitasi dan normalisasi input sebelum validasi.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => is_string($this->input('status')) ? trim($this->input('status')) : $this->input('status'),
            'note' => is_string($this->input('note')) ? trim($this->input('note')) : null,
            'cancellation_reason' => is_string($this->input('cancellation_reason')) ? trim($this->input('cancellation_reason')) : null,
        ]);
    }

    /**
     * Aturan validasi pembaruan status di server.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::enum(LaundryStatus::class),
            ],
            'note' => [
                'nullable',
                'string',
                'max:500',
            ],
            'cancellation_reason' => [
                'nullable',
                'string',
                'max:1000',
                'required_if:status,' . LaundryStatus::Dibatalkan->value,
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
            'status.required' => 'Status cucian target wajib dipilih.',
            'status.enum' => 'Status cucian yang dipilih tidak valid di dalam sistem.',
            'note.max' => 'Catatan status tidak boleh melebihi 500 karakter.',
            'cancellation_reason.required_if' => 'Alasan pembatalan pesanan wajib diisi ketika membatalkan pesanan.',
            'cancellation_reason.max' => 'Alasan pembatalan tidak boleh melebihi 1000 karakter.',
        ];
    }
}
