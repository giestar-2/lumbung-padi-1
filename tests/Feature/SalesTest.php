<?php

namespace Tests\Feature;

use App\Livewire\Sales\Index;
use App\Livewire\Sales\Pos;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\SalesLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class SalesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_sales_index_renders()
    {
        $this->get('/sales')->assertStatus(200);
    }

    public function test_sales_cards_calculate_correctly()
    {
        Sale::factory()->create([
            'invoice_number' => 'INV-001',
            'sale_date' => now()->format('Y-m-d'),
            'subtotal' => 100000,
            'discount' => 0,
            'total' => 100000,
            'paid_amount' => 50000,
            'payment_status' => 'Belum Lunas',
        ]);

        Livewire::test(Index::class)
            ->assertViewHas('totalPenjualan', 100000)
            ->assertViewHas('jumlahTransaksi', 1)
            ->assertViewHas('totalDibayar', 50000)
            ->assertViewHas('sisaPiutang', 50000);
    }

    public function test_sale_detail_opens_as_a_dialog(): void
    {
        $product = Product::factory()->create(['name' => 'Beras Premium']);
        $sale = (new SalesLedger)->confirm([
            'cart' => [$product->id => ['qty' => '1.000']], 'customer_id' => null,
            'discount' => '0', 'discount_type' => 'Rupiah', 'paid_amount' => '15000',
            'due_date' => '', 'notes' => '',
        ], (string) Str::uuid());

        Livewire::test(Index::class)->call('openDetail', $sale->id)
            ->assertSet('detail', $sale->id)
            ->assertSee('role="dialog"', false)
            ->assertSee($sale->invoice_number)
            ->assertSee('Beras Premium');
    }

    public function test_deleting_sale_restores_stock_and_removes_related_payment_records(): void
    {
        $customer = Customer::factory()->create(['name' => 'Pelanggan Uji']);
        $product = Product::factory()->create(['stock_kg' => '100', 'cogs_per_kg' => '10000']);
        $sale = (new SalesLedger)->confirm([
            'cart' => [$product->id => ['qty' => '2.000']], 'customer_id' => $customer->id,
            'discount' => '0', 'discount_type' => 'Rupiah', 'paid_amount' => '10000',
            'due_date' => '', 'notes' => '',
        ], (string) Str::uuid());
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_kg' => 98, 'inventory_value' => 980000]);
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'receivable_balance' => 20000]);
        $this->assertDatabaseCount('cash_entries', 1);

        Livewire::test(Index::class)->call('openDetail', $sale->id)->call('delete', $sale->id)
            ->assertHasNoErrors()->assertSet('detail', null);

        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
        $this->assertDatabaseMissing('sale_items', ['sale_id' => $sale->id]);
        $this->assertDatabaseMissing('payments', ['sale_id' => $sale->id]);
        $this->assertDatabaseCount('cash_entries', 0);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_kg' => 100, 'inventory_value' => 1000000, 'cogs_per_kg' => 10000]);
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'receivable_balance' => 0]);
    }

    public function test_can_checkout_pos()
    {
        $beras = Product::factory()->create(['code' => 'B02', 'name' => 'Beras B', 'type' => 'Beras', 'stock_kg' => 100, 'selling_price' => 15000]);
        $customer = Customer::factory()->create(['name' => 'Pak Haji']);

        Livewire::test(Pos::class)
            ->set('customer_id', $customer->id)
            ->call('addToCart', $beras->id)
            ->set('paid_amount', 15000) // Lunas for 1 item (15000)
            ->call('checkout')
            ->assertHasNoErrors()
            ->assertNoRedirect()
            ->assertSet('cart', [])
            ->assertSet('customer_id', null)
            ->assertSet('paid_amount', 0)
            ->assertDispatched('pos-completed');

        $this->assertDatabaseHas('sales', [
            'customer_id' => $customer->id,
            'total' => 15000,
            'payment_status' => 'Lunas',
        ]);

        // Stock reduced
        $this->assertDatabaseHas('products', [
            'id' => $beras->id,
            'stock_kg' => 99,
        ]);
    }

    public function test_pos_can_process_next_sale_without_leaking_previous_customer_or_discount(): void
    {
        $product = Product::factory()->create(['name' => 'Beras', 'selling_price' => 15000, 'stock_kg' => 100]);
        $customer = Customer::factory()->create(['name' => 'Budi']);
        $form = Livewire::test(Pos::class)->set('customer_id', $customer->id)
            ->set('notes', 'Pesanan pertama')->set('discount_type', 'Persen')->set('discount', 10)
            ->call('addToCart', $product->id)->set('paid_amount', 13500);
        $key = $form->get('idempotencyKey');

        $form->call('checkout')->assertNoRedirect()->assertHasNoErrors()
            ->assertSet('notes', '')->assertSet('discount', 0)->assertSet('discount_type', 'Rupiah')
            ->assertSet('customer_id', null)->assertViewHas('products', fn ($products) => (float) $products->first()->stock_kg === 99.0);
        $this->assertNotSame($key, $form->get('idempotencyKey'));
        $form->call('addToCart', $product->id)->set('paid_amount', 15000)->call('checkout')->assertHasNoErrors()->assertNoRedirect();

        $this->assertDatabaseCount('sales', 2);
        $this->assertDatabaseCount('cash_entries', 2);
        $this->assertDatabaseHas('sales', ['customer_id' => null, 'total' => 15000, 'notes' => '']);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_kg' => 98]);
        $form->assertSee(Sale::latest('id')->firstOrFail()->invoice_number);
    }
}
