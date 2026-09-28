<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use App\Livewire\Sales\Index;
use App\Livewire\Sales\Pos;

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
            'payment_status' => 'Belum Lunas'
        ]);
        
        Livewire::test(Index::class)
            ->assertViewHas('totalPenjualan', 100000)
            ->assertViewHas('jumlahTransaksi', 1)
            ->assertViewHas('totalDibayar', 50000)
            ->assertViewHas('sisaPiutang', 50000);
    }

    public function test_can_checkout_pos()
    {
        $beras = Product::factory()->create(['code'=>'B02', 'name'=>'Beras B', 'type'=>'Beras', 'stock_kg'=>100, 'selling_price'=>15000]);
        $customer = Customer::factory()->create(['name' => 'Pak Haji']);

        Livewire::test(Pos::class)
            ->set('customer_id', $customer->id)
            ->call('addToCart', $beras->id)
            ->set('paid_amount', 15000) // Lunas for 1 item (15000)
            ->call('checkout')
            ->assertRedirect(route('sales.index'));

        $this->assertDatabaseHas('sales', [
            'customer_id' => $customer->id,
            'total' => 15000,
            'payment_status' => 'Lunas'
        ]);

        // Stock reduced
        $this->assertDatabaseHas('products', [
            'id' => $beras->id,
            'stock_kg' => 99
        ]);
    }
}