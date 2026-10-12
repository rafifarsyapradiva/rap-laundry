<?php

namespace Database\Factories;

use App\Enums\BillingUnit;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'service_id' => Service::factory(),
            'service_name_snapshot' => 'Cuci Setrika',
            'billing_unit_snapshot' => BillingUnit::Kg,
            'quantity' => '3.00',
            'unit_price' => '8000.00',
            'subtotal' => '24000.00',
            'notes' => null,
        ];
    }
}
