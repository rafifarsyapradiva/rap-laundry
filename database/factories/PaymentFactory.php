<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentRecordStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'recorded_by' => User::factory(),
            'payment_date' => now(),
            'amount' => '24000.00',
            'method' => PaymentMethod::Tunai,
            'cash_tendered' => null,
            'change_amount' => null,
            'reference' => null,
            'notes' => null,
            'status' => PaymentRecordStatus::Valid,
            'voided_by' => null,
            'voided_at' => null,
            'void_reason' => null,
        ];
    }

    public function voided(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentRecordStatus::Voided,
            'voided_by' => User::factory(),
            'voided_at' => now(),
            'void_reason' => 'Koreksi kesalahan input kasir',
        ]);
    }

    public function tunaiWithChange(string $amount = '24000.00', string $tendered = '30000.00'): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => PaymentMethod::Tunai,
            'amount' => $amount,
            'cash_tendered' => $tendered,
            'change_amount' => number_format((float) $tendered - (float) $amount, 2, '.', ''),
        ]);
    }
}
