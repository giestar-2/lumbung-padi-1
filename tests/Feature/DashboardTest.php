<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\CashEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use App\Livewire\Dashboard;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_dashboard_renders()
    {
        $this->get('/')->assertStatus(200);
    }

    public function test_dashboard_calculates_6_indicators_correctly()
    {
        // 1. Penjualan
        Sale::factory()->create(['invoice_number' => 'A', 'sale_date' => now(), 'subtotal' => 1000000, 'discount' => 0, 'total' => 1000000, 'paid_amount' => 600000, 'payment_status' => 'Belum Lunas']);
        Sale::factory()->create(['invoice_number' => 'B', 'sale_date' => now(), 'subtotal' => 500000, 'discount' => 0, 'total' => 500000, 'paid_amount' => 500000, 'payment_status' => 'Lunas']);

        // 2. Kas
        CashEntry::factory()->create(['entry_date' => now(), 'type' => 'Pemasukan', 'category' => 'X', 'amount' => 1500000]);
        CashEntry::factory()->create(['entry_date' => now(), 'type' => 'Pengeluaran', 'category' => 'Y', 'amount' => 300000]);

        Livewire::test(Dashboard::class)
            ->assertViewHas('penjualanBersih', 1500000)
            ->assertViewHas('pemasukanKas', 1500000)
            ->assertViewHas('pengeluaranKas', 300000)
            ->assertViewHas('labaUsaha', 1200000) // 1500000 - 300000
            ->assertViewHas('sisaPiutang', 400000) // 1500000 - 1100000
            ->assertViewHas('arusKasBersih', 1200000); // 1500000 - 300000
    }
}
