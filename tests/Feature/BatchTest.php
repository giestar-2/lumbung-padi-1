<?php

namespace Tests\Feature;

use App\Livewire\Batches\Form;
use App\Livewire\Batches\Show;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_batch_index_renders()
    {
        $this->get('/batches')->assertStatus(200);
    }

    public function test_can_create_new_production_batch()
    {
        $gabah = Product::factory()->create(['code' => 'G01', 'name' => 'Gabah', 'type' => 'Bahan Baku', 'stock_kg' => 2000, 'cogs_per_kg' => 5000, 'selling_price' => 6000]);

        Livewire::test(Form::class)
            ->set('raw_material_id', $gabah->id)
            ->set('start_date', now()->format('Y-m-d'))
            ->set('raw_material_weight', 1000)
            ->set('notes', 'Test Batch')
            ->call('save')
            ->assertRedirect();

        $this->assertDatabaseHas('production_batches', [
            'raw_material_id' => $gabah->id,
            'raw_material_weight' => 1000,
            'status' => 'Proses',
        ]);
    }

    public function test_can_add_output_to_batch()
    {
        $bahanBaku = Product::factory()->create(['code' => 'G02', 'name' => 'Bahan Baku', 'type' => 'Bahan Baku', 'stock_kg' => 2000, 'cogs_per_kg' => 5000, 'selling_price' => 6000]);
        $batch = ProductionBatch::factory()->create([
            'batch_number' => 'B-TEST',
            'start_date' => now(),
            'status' => 'Proses',
            'raw_material_id' => $bahanBaku->id,
            'raw_material_weight' => 1000,
            'raw_material_cogs' => 5000000,
            'result_weight' => 600,
            'current_stage' => 'Selesai',
            'status' => 'Selesai',
            'cost_finalized_at' => now(),
            'cost_allocation' => ['Beras' => '3000000.00', 'Dedek' => '1000000.00', 'Pupuk' => '1000000.00'],
        ]);

        $beras = Product::factory()->create(['code' => 'B01', 'name' => 'Beras Test', 'type' => 'Beras', 'stock_kg' => 0, 'cogs_per_kg' => 0, 'selling_price' => 10000]);

        Livewire::test(Show::class, ['id' => $batch->id])
            ->set('output_product_id', $beras->id)
            ->call('addOutput')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('production_outputs', [
            'production_batch_id' => $batch->id,
            'product_id' => $beras->id,
            'weight' => 600,
            'allocated_cost' => 3000000, // 60% of 5M
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $beras->id,
            'stock_kg' => 600,
        ]);
    }
}
