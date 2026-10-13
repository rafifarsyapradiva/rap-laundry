<?php

namespace App\Http\Requests\Payment;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;

class VoidPaymentRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna berwenang membatalkan (void) pembayaran ini.
     */
    public function authorize(): bool
    {
        /** @var Payment|null $payment */
        $payment = $this->route('payment');

        if (! $payment instanceof Payment) {
            return false;
        }

        return $this->user() !== null && $this->user()->can('void', $payment);
    }

    /**
     * Aturan validasi pembatalan pembayaran (void).
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:500'],
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
            'reason.required' => 'Alasan pembatalan (void) pembayaran wajib diisi.',
            'reason.string' => 'Alasan pembatalan pembayaran harus berupa teks valid.',
            'reason.min' => 'Alasan pembatalan pembayaran wajib diisi minimal 10 karakter.',
            'reason.max' => 'Alasan pembatalan pembayaran maksimal 500 karakter.',
        ];
    }
}
