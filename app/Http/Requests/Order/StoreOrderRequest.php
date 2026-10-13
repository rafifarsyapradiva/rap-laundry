<?php

namespace App\Http\Requests\Order;

use App\Enums\BillingUnit;
use App\Models\BusinessSetting;
use App\Models\Order;
use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreOrderRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna memiliki izin untuk membuat pesanan.
     */
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('create', Order::class);
    }

    /**
     * Sanitasi dan normalisasi input sebelum divalidasi.
     * Mengubah koma (,) menjadi titik (.) untuk input kuantitas desimal dan nominal uang.
     */
    protected function prepareForValidation(): void
    {
        $items = $this->input('items');

        if (is_array($items)) {
            foreach ($items as $index => $item) {
                if (isset($item['quantity'])) {
                    $cleanedQty = str_replace(',', '.', trim((string) $item['quantity']));
                    $items[$index]['quantity'] = is_numeric($cleanedQty) ? (float) $cleanedQty : $item['quantity'];
                }

                if (isset($item['notes'])) {
                    $items[$index]['notes'] = trim((string) $item['notes']);
                }
            }

            $this->merge(['items' => $items]);
        }

        if ($this->has('discount_amount')) {
            $rawDiscount = str_replace(',', '.', trim((string) $this->input('discount_amount')));
            $this->merge(['discount_amount' => $rawDiscount === '' ? 0 : (is_numeric($rawDiscount) ? (float) $rawDiscount : $this->input('discount_amount'))]);
        } else {
            $this->merge(['discount_amount' => 0]);
        }

        if ($this->has('extra_charge')) {
            $rawExtra = str_replace(',', '.', trim((string) $this->input('extra_charge')));
            $this->merge(['extra_charge' => $rawExtra === '' ? 0 : (is_numeric($rawExtra) ? (float) $rawExtra : $this->input('extra_charge'))]);
        } else {
            $this->merge(['extra_charge' => 0]);
        }

        if ($this->has('notes')) {
            $this->merge(['notes' => trim((string) $this->input('notes'))]);
        }
    }

    /**
     * Aturan validasi untuk pembuatan pesanan laundry baru.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id')->whereNull('deleted_at')->where('is_active', true),
            ],
            'received_at' => ['required', 'date'],
            'estimated_completion_at' => ['nullable', 'date', 'after_or_equal:received_at'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.service_id' => [
                'required',
                'integer',
                Rule::exists('services', 'id')->whereNull('deleted_at')->where('is_active', true),
            ],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'extra_charge' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Pesan validasi berbahasa Indonesia yang jelas.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'Pelanggan wajib dipilih.',
            'customer_id.exists' => 'Pelanggan yang dipilih tidak valid atau sedang dinonaktifkan.',
            'received_at.required' => 'Waktu penerimaan cucian wajib diisi.',
            'received_at.date' => 'Format waktu penerimaan cucian tidak valid.',
            'estimated_completion_at.after_or_equal' => 'Estimasi waktu selesai tidak boleh mendahului waktu penerimaan cucian.',
            'items.required' => 'Minimal satu item layanan harus ditambahkan ke pesanan.',
            'items.min' => 'Minimal satu item layanan harus ditambahkan ke pesanan.',
            'items.*.service_id.required' => 'Jenis layanan wajib dipilih pada setiap baris item.',
            'items.*.service_id.exists' => 'Layanan yang dipilih tidak valid atau sudah tidak aktif.',
            'items.*.quantity.required' => 'Kuantitas atau berat wajib diisi pada setiap item.',
            'items.*.quantity.numeric' => 'Kuantitas harus berupa angka valid.',
            'items.*.quantity.gt' => 'Kuantitas cucian harus lebih besar dari nol.',
            'items.*.notes.max' => 'Catatan item maksimal 255 karakter.',
            'discount_amount.numeric' => 'Nominal diskon harus berupa angka.',
            'discount_amount.min' => 'Nominal diskon tidak boleh bernilai negatif.',
            'extra_charge.numeric' => 'Biaya tambahan harus berupa angka.',
            'extra_charge.min' => 'Biaya tambahan tidak boleh bernilai negatif.',
            'notes.max' => 'Catatan pesanan maksimal 1000 karakter.',
        ];
    }

    /**
     * Validasi lanjutan:
     * 1. Layanan selain kilogram (item/paket) wajib berupa bilangan bulat.
     * 2. Nominal diskon tidak boleh melebihi total subtotal pesanan.
     * 3. Nominal diskon oleh kasir tidak boleh melampaui batas izin (default 20%).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $items = $this->input('items', []);

            if (! is_array($items) || empty($items)) {
                return;
            }

            // Kumpulkan ID layanan yang valid
            $serviceIds = collect($items)->pluck('service_id')->filter()->unique()->toArray();
            $services = Service::query()->whereIn('id', $serviceIds)->get()->keyBy('id');

            $serverSubtotal = 0.0;

            foreach ($items as $index => $item) {
                $serviceId = $item['service_id'] ?? null;
                $quantity = $item['quantity'] ?? null;

                if (! $serviceId || ! isset($services[$serviceId])) {
                    continue;
                }

                /** @var Service $service */
                $service = $services[$serviceId];

                // Jika bukan layanan KG (misal: Item atau Paket), kuantitas harus bulat
                if ($service->billing_unit !== BillingUnit::Kg && is_numeric($quantity)) {
                    if (floor((float) $quantity) != (float) $quantity) {
                        $unitLabel = $service->billing_unit?->label() ?? 'satuan';
                        $validator->errors()->add(
                            "items.{$index}.quantity",
                            "Kuantitas untuk layanan '{$service->name}' berpenagihan per {$unitLabel} harus berupa bilangan bulat."
                        );
                    }
                }

                if (is_numeric($quantity) && (float) $quantity > 0) {
                    $itemSubtotal = round(((float) $quantity) * ((float) $service->price), 0);
                    $serverSubtotal += $itemSubtotal;
                }
            }

            $discountAmount = (float) $this->input('discount_amount', 0);

            // Validasi: Diskon tidak boleh melebihi subtotal
            if ($discountAmount > $serverSubtotal) {
                $validator->errors()->add(
                    'discount_amount',
                    'Diskon tidak boleh melebihi total subtotal pesanan (Rp' . number_format($serverSubtotal, 0, ',', '.') . ').'
                );
            }

            // Validasi: Otorisasi diskon berdasarkan peran pengguna
            $user = $this->user();
            if ($user && $discountAmount > 0) {
                if (! $user->can('applyDiscount', [Order::class, $discountAmount, $serverSubtotal])) {
                    $maxPercent = (float) BusinessSetting::get(BusinessSetting::KEY_CASHIER_MAX_DISCOUNT_PERCENT, '20');
                    $maxAllowed = round(($serverSubtotal * $maxPercent) / 100, 2);

                    $validator->errors()->add(
                        'discount_amount',
                        "Kasir hanya diizinkan memberikan diskon maksimal {$maxPercent}% (maksimal Rp" . number_format($maxAllowed, 0, ',', '.') . "). Diskon lebih besar membutuhkan otorisasi Admin."
                    );
                }
            }
        });
    }
}
