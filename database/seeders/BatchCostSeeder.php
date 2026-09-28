<?php

namespace Database\Seeders;

use App\Models\BatchCost;
use Illuminate\Database\Seeder;

class BatchCostSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->isProduction()) {
            BatchCost::factory()->create();
        }
    }
}
