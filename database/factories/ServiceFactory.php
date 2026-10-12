<?php

namespace Database\Factories;

use App\Enums\BillingUnit;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true).' Service',
            'description' => fake()->sentence(),
            'billing_unit' => fake()->randomElement(BillingUnit::cases()),
            'price' => fake()->randomElement(['7000.00', '8000.00', '10000.00', '15000.00', '25000.00']),
            'estimated_hours' => fake()->randomElement([24, 48, 72]),
            'is_active' => true,
        ];
    }

    public function kg(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_unit' => BillingUnit::Kg,
            'price' => '8000.00',
        ]);
    }

    public function item(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_unit' => BillingUnit::Item,
            'price' => '25000.00',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
