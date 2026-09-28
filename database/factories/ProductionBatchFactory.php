<?php

namespace Database\Factories;

use App\Decimal;
use App\Models\ProductionBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductionBatch>
 */
class ProductionBatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'batch_number' => fake()->unique()->bothify('B-######'),
            'start_date' => Decimal::today(),
            'type' => 'Beras',
            'status' => 'Proses',
            'current_stage' => 'Belum Diproses',
            'raw_material_weight' => '100.000',
            'raw_material_cogs' => '0.00',
        ];
    }
}
