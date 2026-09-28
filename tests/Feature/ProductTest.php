<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use App\Livewire\Products\Index;
use App\Livewire\Products\Form;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_products_page_is_rendered()
    {
        $this->get('/products')->assertStatus(200);
    }

    public function test_can_create_new_product()
    {
        Livewire::test(Form::class)
            ->set('code', 'PRD-001')
            ->set('name', 'Beras Premium')
            ->set('type', 'Beras')
            ->set('stock_kg', 100)
            ->set('cogs_per_kg', 10000)
            ->set('selling_price', 12000)
            ->call('save')
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'code' => 'PRD-001',
            'name' => 'Beras Premium',
        ]);
    }

    public function test_product_index_calculates_cards_correctly()
    {
        Product::factory()->create(['code'=>'P1', 'name'=>'N1', 'type'=>'Beras', 'stock_kg' => 100,
            'cogs_per_kg' => 10000,
            'selling_price' => 12000,
            'is_active' => true,
        ]);
        
        Product::factory()->create(['code'=>'P2', 'name'=>'N2', 'type'=>'Beras', 'stock_kg' => 0,
            'cogs_per_kg' => 5000,
            'selling_price' => 6000,
            'is_active' => true,
        ]);

        Livewire::test(Index::class)
            ->assertViewHas('totalHargaProduk', (100 * 12000) + (0 * 6000))
            ->assertViewHas('totalHargaModal', (100 * 10000) + (0 * 5000))
            ->assertViewHas('jumlahProduk', 2)
            ->assertViewHas('produkStokMenipis', 1);
    }

    public function test_can_delete_product()
    {
        $product = Product::factory()->create(['code'=>'DEL-01','name'=>'Del','type'=>'Beras','stock_kg'=>10,'cogs_per_kg'=>1000,'selling_price'=>2000]);

        Livewire::test(Index::class)
            ->call('delete', $product->id);

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
