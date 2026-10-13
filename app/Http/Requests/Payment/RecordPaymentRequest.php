<?php

namespace App\Http\Requests\Payment;

use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RecordPaymentRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna memiliki hak akses untuk mencatat pembayaran pada pesanan ini.
     */
    public function authorize(): bool
    {
        /** @var Order|null $order */
        $order = $this->route('order');

        if (! $order instanceof Order) {
            return false;
        }

        return $this->user() !== null && $this->user()->can('create', [Payment::class, $order]);
    }

    /**
     * Normalisasi dan sanitasi input nominal rupiah sebelum divalidasi.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('payment_method') && ! $this->has('method')) {
            $this->merge(['method' => $this->input('payment_method')]);
        }

        if ($this->has('amount')) {
            $cleaned = preg_replace('/[^0-9]/', '', (string) $this->input('amount'));
            $this->merge(['amount' => $cleaned !== '' ? (int) $cleaned : null]);
        }

        if ($this->has('cash_tendered') && $this->input('cash_tendered') !== null && $this->input('cash_tendered') !== '') {
            $cleanedTendered = preg_replace('/[^0-9]/', '', (string) $this->input('cash_tendered'));
            $this->merge(['cash_tendered' => $cleanedTendered !== '' ? (int) $cleanedTendered : null]);
        } else {
            $this->merge(['cash_tendered' => null]);
        }

        if ($this->has('reference')) {
            $this->merge(['reference' => trim((string) $this->input('reference')) ?: null]);
        }

        if ($this->has('notes')) {
            $this->merge(['notes' => trim((string) $this->input('notes')) ?: null]);
        }
    }

    /**
     * Aturan validasi formulir pencatatan pembayaran manual.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'cash_tendered' => ['nullable', 'integer', 'min:1'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:255'],
            'payment_date' => ['nullable', 'date', 'before_or_equal:now'],
            'confirm_received' => ['required', 'accepted'],
        ];
    }

    /**
     * Pesan validasi berbahasa Indonesia yang informatif.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Nominal pembayaran wajib diisi.',
            'amount.integer' => 'Nominal pembayaran harus berupa angka bulat Rupiah.',
            'amount.min' => 'Nominal pembayaran minimal Rp1.',
            'method.required' => 'Metode pembayaran wajib dipilih.',
            'method.Illuminate\Validation\Rules\Enum' => 'Metode pembayaran yang dipilih tidak valid.',
            'cash_tendered.integer' => 'Nominal uang fisik diterima harus berupa angka bulat Rupiah.',
            'cash_tendered.min' => 'Nominal uang fisik diterima minimal Rp1.',
            'reference.max' => 'Nomor referensi pembayaran maksimal 100 karakter.',
            'notes.max' => 'Catatan pembayaran maksimal 255 karakter.',
            'payment_date.date' => 'Format tanggal pembayaran tidak valid.',
            'payment_date.before_or_equal' => 'Tanggal pembayaran tidak boleh melebihi waktu saat ini.',
            'confirm_received.required' => 'Anda wajib mengonfirmasi bahwa dana fisik/manual telah diterima.',
            'confirm_received.accepted' => 'Anda wajib mencentang konfirmasi penerimaan dana sebelum menyimpan.',
        ];
    }

    /**
     * Validasi bisnis lanjutan:
     * 1. Nominal pembayaran tidak boleh melebihi sisa saldo tagihan.
     * 2. Uang fisik diterima (cash_tendered) tidak boleh kurang dari nominal yang dibayar.
     * 3. Metode Lainnya wajib memiliki catatan keterangan.
     * 4. Batasan backdate hanya untuk Administrator.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Order|null $order */
            $order = $this->route('order');
            if (! $order instanceof Order) {
                return;
            }

            $amount = (int) $this->input('amount', 0);
            $remaining = (int) round($order->remaining_balance);

            if ($amount > $remaining) {
                $formattedAmount = 'Rp' . number_format($amount, 0, ',', '.');
                $formattedRemaining = 'Rp' . number_format($remaining, 0, ',', '.');
                $validator->errors()->add(
                    'amount',
                    "Nominal pembayaran ({$formattedAmount}) melebihi sisa tagihan yang harus dibayar ({$formattedRemaining})."
                );
            }

            $methodValue = $this->input('method');
            if ($methodValue === PaymentMethod::Tunai->value) {
                $cashTendered = $this->input('cash_tendered');
                if ($cashTendered !== null && $cashTendered < $amount) {
                    $formattedTendered = 'Rp' . number_format($cashTendered, 0, ',', '.');
                    $formattedAmount = 'Rp' . number_format($amount, 0, ',', '.');
                    $validator->errors()->add(
                        'cash_tendered',
                        "Uang tunai yang diserahkan ({$formattedTendered}) kurang dari nominal pembayaran ({$formattedAmount})."
                    );
                }
            }

            if ($methodValue === PaymentMethod::Lainnya->value && empty($this->input('notes'))) {
                $validator->errors()->add(
                    'notes',
                    'Keterangan catatan wajib diisi jika memilih metode pembayaran Lainnya.'
                );
            }

            $paymentDate = $this->input('payment_date');
            if (! empty($paymentDate)) {
                $user = $this->user();
                if ($user && ! $user->isAdmin()) {
                    // Kasir dilarang mengubah tanggal pembayaran
                    $validator->errors()->add(
                        'payment_date',
                        'Hanya Administrator yang memiliki wewenang untuk mengatur tanggal pembayaran lampau (backdate).'
                    );
                } else {
                    $parsed = Carbon::parse($paymentDate);
                    if ($parsed->isBefore($order->received_at)) {
                        $validator->errors()->add(
                            'payment_date',
                            'Tanggal pembayaran tidak boleh mendahului tanggal penerimaan pesanan (' . $order->received_at->format('d-m-Y H:i') . ' WIB).'
                        );
                    }
                    if (! $parsed->isToday() && empty($this->input('notes'))) {
                        $validator->errors()->add(
                            'notes',
                            'Catatan wajib diisi saat mencatat pembayaran dengan tanggal lampau (backdate).'
                        );
                    }
                }
            }
        });
    }
}
