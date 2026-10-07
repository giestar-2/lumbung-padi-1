<?php

namespace Tests\Feature;

use App\Livewire\Cash\Index as CashIndex;
use App\Livewire\Dashboard;
use App\Livewire\Products\Form;
use App\Livewire\Products\Index;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

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
            ->set('total_cost', 1000000)
            ->set('selling_price', 12000)
            ->call('save')
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'code' => 'PRD-001',
            'name' => 'Beras Premium',
        ]);
        $product = Product::where('code', 'PRD-001')->firstOrFail();
        $this->assertDatabaseHas('cash_entries', [
            'type' => 'Pengeluaran', 'category' => 'Pembelian Produk',
            'classification' => 'Persediaan', 'amount' => 1000000,
            'reference_type' => Product::class, 'reference_id' => $product->id,
            'entry_date' => now('Asia/Jakarta')->toDateString(),
        ]);

        Livewire::test(CashIndex::class, ['direction' => 'Pengeluaran'])
            ->set('source', 'product')
            ->assertSee('Pembelian Beras Premium')
            ->assertSee('Buka produk')
            ->assertViewHas('totalPengeluaran', 1000000)
            ->assertViewHas('pembelianBahan', 1000000)
            ->assertViewHas('gajiOperasional', '0.00');
        Livewire::test(Dashboard::class)
            ->assertViewHas('pengeluaranKas', 1000000)
            ->assertViewHas('labaUsaha', '0.00');
    }

    public function test_zero_stock_or_zero_total_does_not_create_an_expense(): void
    {
        foreach (['EMPTY' => ['0', '0'], 'FREE' => ['100', '0']] as $code => [$stock, $cost]) {
            Livewire::test(Form::class)
                ->set('code', $code)->set('name', $code)
                ->set('stock_kg', $stock)->set('total_cost', $cost)
                ->call('save')->assertHasNoErrors()->assertRedirect(route('products.index'));
        }

        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseCount('cash_entries', 0);
    }

    public function test_fractional_purchase_is_rounded_and_repeated_submission_does_not_duplicate_expense(): void
    {
        $form = Livewire::test(Form::class)
            ->set('code', 'DECIMAL')->set('name', 'Beras pecahan')
            ->set('stock_kg', '1,125')->set('total_cost', '1388,89');

        $form->call('save')->assertHasNoErrors();
        $this->assertDatabaseHas('cash_entries', ['amount' => 1388.89]);
        $form->call('save')->assertHasErrors(['code']);

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseCount('cash_entries', 1);
    }

    public function test_editing_product_does_not_change_original_purchase_expense(): void
    {
        Livewire::test(Form::class)
            ->set('code', 'EDIT')->set('name', 'Beras')
            ->set('stock_kg', '10')->set('total_cost', '100000')->call('save');
        $product = Product::where('code', 'EDIT')->firstOrFail();

        Livewire::test(Form::class, ['id' => $product->id])
            ->set('name', 'Beras baru')->set('total_cost', '120000')->set('stock_kg', '99')
            ->call('save')->assertHasNoErrors();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'inventory_value' => 100000, 'cogs_per_kg' => 10000, 'stock_kg' => 10]);
        $this->assertDatabaseHas('cash_entries', ['name' => 'Pembelian Beras', 'amount' => 100000]);
        $this->assertDatabaseCount('cash_entries', 1);
    }

    public function test_product_and_automatic_expense_history_cannot_be_deleted(): void
    {
        Livewire::test(Form::class)
            ->set('code', 'HISTORY')->set('name', 'Beras')
            ->set('stock_kg', '10')->set('total_cost', '100000')->call('save');
        $product = Product::where('code', 'HISTORY')->firstOrFail();
        $entry = $product->cashEntries()->firstOrFail();

        Livewire::test(Index::class)->call('delete', $product->id)->assertHasErrors(['delete']);
        Livewire::test(CashIndex::class)->call('delete', $entry->id)->assertForbidden();
        Livewire::test(CashIndex::class)->call('edit', $entry->id)->assertForbidden();

        $this->assertModelExists($product);
        $this->assertModelExists($entry);
    }

    public function test_invalid_product_does_not_create_stock_or_expense(): void
    {
        Livewire::test(Form::class)
            ->set('name', 'Beras')->set('stock_kg', '-1')->set('total_cost', '100000')
            ->call('save')->assertHasErrors(['stock_kg']);

        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('cash_entries', 0);
    }

    public function test_total_purchase_requires_stock_and_preview_handles_incomplete_input(): void
    {
        $form = Livewire::test(Form::class)->set('total_cost', '100000');
        $form->call('save')->assertHasErrors(['stock_kg']);
        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('cash_entries', 0);

        $form->set('stock_kg', 'abc')->assertViewHas('initialCostPerKg', null);
        $form->set('stock_kg', '20')->assertViewHas('initialCostPerKg', '5000.000000');
    }

    public function test_stock_purchase_uses_weighted_average_for_cheaper_and_more_expensive_stock(): void
    {
        foreach ([['400000', '1400000', '9333.333333'], ['700000', '1700000', '11333.333333']] as [$total, $value, $average]) {
            $product = Product::factory()->create(['stock_kg' => 100, 'cogs_per_kg' => 10000]);

            $form = Livewire::test(Index::class)->call('adjust', $product->id)
                ->set('incomingStock', '50')->set('purchaseTotal', $total);
            $form->assertViewHas('purchasePreview', ['total' => $total.'.00', 'stock' => '150.000', 'average' => $average]);
            $form->call('saveAdjustment')->assertHasNoErrors()->assertSet('adjustId', null);
            $form->call('saveAdjustment')->assertHasNoErrors();

            $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_kg' => 150, 'inventory_value' => $value, 'cogs_per_kg' => $average]);
            $this->assertDatabaseHas('cash_entries', ['reference_type' => Product::class, 'reference_id' => $product->id, 'amount' => $total, 'classification' => 'Persediaan']);
        }
        $this->assertDatabaseCount('cash_entries', 2);
    }

    public function test_stock_purchase_without_total_uses_current_cost(): void
    {
        $product = Product::factory()->create(['stock_kg' => 100, 'cogs_per_kg' => 10000]);

        Livewire::test(Index::class)->call('adjust', $product->id)
            ->set('incomingStock', '12,500')->call('saveAdjustment')->assertHasNoErrors();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_kg' => 112.5, 'inventory_value' => 1125000, 'cogs_per_kg' => 10000]);
        $this->assertDatabaseHas('cash_entries', ['reference_id' => $product->id, 'amount' => 125000]);
    }

    public function test_zero_cost_stock_dilutes_average_without_creating_cash(): void
    {
        $product = Product::factory()->create(['stock_kg' => 100, 'cogs_per_kg' => 10000]);

        Livewire::test(Index::class)->call('adjust', $product->id)
            ->set('incomingStock', '100')->set('purchaseTotal', '0')
            ->call('saveAdjustment')->assertHasNoErrors();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_kg' => 200, 'inventory_value' => 1000000, 'cogs_per_kg' => 5000]);
        $this->assertDatabaseCount('cash_entries', 0);
    }

    public function test_restocking_empty_product_uses_exact_total_as_inventory_value(): void
    {
        $product = Product::factory()->create(['stock_kg' => 0, 'cogs_per_kg' => 9000]);

        Livewire::test(Index::class)->call('adjust', $product->id)
            ->set('incomingStock', '3')->set('purchaseTotal', '100,01')
            ->call('saveAdjustment')->assertHasNoErrors();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_kg' => 3, 'inventory_value' => 100.01, 'cogs_per_kg' => '33.336666']);
        $this->assertDatabaseHas('cash_entries', ['amount' => 100.01]);
    }

    public function test_invalid_stock_purchase_and_cancel_do_not_change_stock_or_cash(): void
    {
        $product = Product::factory()->create(['stock_kg' => 100, 'cogs_per_kg' => 10000]);
        $form = Livewire::test(Index::class)->call('adjust', $product->id);

        $form->set('incomingStock', '0')->call('saveAdjustment')->assertHasErrors(['incomingStock']);
        $form->set('incomingStock', '10')->set('purchaseTotal', '-1')->call('saveAdjustment')->assertHasErrors(['purchaseTotal']);
        $form->set('purchaseTotal', '1.000.000')->assertViewHas('purchasePreview', null);
        $form->call('cancelAdjustment')->call('saveAdjustment');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_kg' => 100, 'inventory_value' => 1000000]);
        $this->assertDatabaseCount('cash_entries', 0);
    }

    public function test_stock_purchase_rejects_changed_inventory_value(): void
    {
        $product = Product::factory()->create(['stock_kg' => 100, 'cogs_per_kg' => 10000]);
        $form = Livewire::test(Index::class)->call('adjust', $product->id)->set('incomingStock', '10')->set('purchaseTotal', '120000');
        $product->update(['inventory_value' => 1100000, 'cogs_per_kg' => 11000]);

        $form->call('saveAdjustment')->assertHasErrors(['incomingStock']);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_kg' => 100, 'inventory_value' => 1100000]);
        $this->assertDatabaseCount('cash_entries', 0);
    }

    public function test_product_index_calculates_cards_correctly()
    {
        Product::factory()->create(['code' => 'P1', 'name' => 'N1', 'type' => 'Beras', 'stock_kg' => 100,
            'cogs_per_kg' => 10000,
            'selling_price' => 12000,
            'is_active' => true,
        ]);

        Product::factory()->create(['code' => 'P2', 'name' => 'N2', 'type' => 'Beras', 'stock_kg' => 0,
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
        $product = Product::factory()->create(['code' => 'DEL-01', 'name' => 'Del', 'type' => 'Beras', 'stock_kg' => 10, 'cogs_per_kg' => 1000, 'selling_price' => 2000]);

        Livewire::test(Index::class)
            ->call('delete', $product->id);

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
