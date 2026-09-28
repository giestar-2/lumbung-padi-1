<?php

namespace Database\Factories;

use App\Decimal;
use App\Models\BatchCost;
use App\Models\ProductionBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BatchCost> */
class BatchCostFactory extends Factory
{
    public function definition(): array
    {
        return ['production_batch_id' => ProductionBatch::factory(), 'name' => 'Biaya pengeringan', 'category' => 'Biaya Pengolahan',
            'amount' => '75000.00', 'cost_date' => Decimal::today()];
    }
}
