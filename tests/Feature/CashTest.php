<?php

namespace Tests\Feature;

use App\Livewire\Cash\Index;
use App\Models\CashEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CashTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_cash_index_renders()
    {
        $this->get('/cash')->assertStatus(200);
    }

    public function test_cash_cards_calculate_correctly()
    {
        CashEntry::factory()->create([
            'entry_date' => now()->format('Y-m-d'),
            'type' => 'Pemasukan',
            'category' => 'Penjualan Langsung',
            'amount' => 500000,
        ]);

        CashEntry::factory()->create([
            'entry_date' => now()->format('Y-m-d'),
            'type' => 'Pemasukan',
            'category' => 'Penerimaan Piutang',
            'amount' => 200000,
        ]);

        CashEntry::factory()->create([
            'entry_date' => now()->format('Y-m-d'),
            'type' => 'Pengeluaran',
            'category' => 'Pembelian Bahan',
            'amount' => 300000,
        ]);

        Livewire::test(Index::class)
            ->assertViewHas('totalPemasukan', 700000)
            ->assertViewHas('penjualanLangsung', 500000)
            ->assertViewHas('penerimaanPiutang', 200000)
            ->assertViewHas('totalPengeluaran', 300000)
            ->assertViewHas('pembelianBahan', 300000)
            ->assertViewHas('saldo', 400000); // 700k - 300k
    }

    public function test_can_create_cash_entry()
    {
        Livewire::test(Index::class)
            ->set('entry_date', now()->format('Y-m-d'))
            ->set('type', 'Pemasukan')
            ->set('name', 'Setoran pemilik')
            ->set('category', 'Lain-lain')
            ->set('amount', 100000)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('cash_entries', [
            'type' => 'Pemasukan',
            'amount' => 100000,
        ]);
    }
}
