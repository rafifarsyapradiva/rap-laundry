<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    protected static int $sequenceNumber = 1;

    public function definition(): array
    {
        $code = sprintf('CUST-%04d', static::$sequenceNumber++);

        return [
            'customer_code' => $code,
            'name' => fake()->name(),
            'phone' => '08'.fake()->numerify('##########'),
            'address' => fake()->address(),
            'notes' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
