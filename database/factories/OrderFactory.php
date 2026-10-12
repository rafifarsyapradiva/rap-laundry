<?php

namespace Database\Factories;

use App\Enums\LaundryStatus;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    protected static int $sequenceNumber = 1;

    public function definition(): array
    {
        $today = now()->format('Ymd');
        $orderNumber = sprintf('LD-%s-%04d', $today, static::$sequenceNumber++);

        return [
            'order_number' => $orderNumber,
            'customer_id' => Customer::factory(),
            'created_by' => User::factory(),
            'received_at' => now(),
            'estimated_completion_at' => now()->addDays(2),
            'completed_at' => null,
            'subtotal' => '24000.00',
            'discount_amount' => '0.00',
            'extra_charge' => '0.00',
            'total_amount' => '24000.00',
            'notes' => fake()->optional()->sentence(),
            'laundry_status' => LaundryStatus::Diterima,
            'payment_status' => PaymentStatus::BelumDibayar,
            'cancelled_at' => null,
            'cancelled_by' => null,
            'cancellation_reason' => null,
        ];
    }

    public function lunas(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => PaymentStatus::Lunas,
        ]);
    }

    public function selesai(): static
    {
        return $this->state(fn (array $attributes) => [
            'laundry_status' => LaundryStatus::Selesai,
            'completed_at' => now(),
        ]);
    }

    public function dibatalkan(): static
    {
        return $this->state(fn (array $attributes) => [
            'laundry_status' => LaundryStatus::Dibatalkan,
            'cancelled_at' => now(),
            'cancelled_by' => User::factory(),
            'cancellation_reason' => 'Dibatalkan oleh pelanggan',
        ]);
    }
}
