<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('PRD-######'),
            'name' => 'Beras '.fake()->unique()->word(),
            'type' => 'Beras',
            'stock_kg' => '100.000',
            'cogs_per_kg' => '10000.000000',
            'selling_price' => '15000.00',
            'is_active' => true,
        ];
    }
}
