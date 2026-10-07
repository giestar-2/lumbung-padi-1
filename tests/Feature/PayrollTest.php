<?php

namespace Tests\Feature;

use App\Livewire\Payrolls\Index;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\ProductionBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PayrollTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_payroll_index_renders()
    {
        $this->get('/payrolls')->assertStatus(200);
    }

    public function test_payroll_cards_calculate_correctly()
    {
        $employee = Employee::factory()->create(['name' => 'John', 'phone' => '123', 'role' => 'Karyawan Harian']);

        Payroll::factory()->create([
            'employee_id' => $employee->id,
            'payroll_date' => now()->format('Y-m-d'),
            'period_start' => now()->subDays(6)->format('Y-m-d'), // 7 days (Harian)
            'period_end' => now()->format('Y-m-d'),
            'total_amount' => 700000,
            'payment_status' => 'Sudah Dibayar',
        ]);

        Payroll::factory()->create([
            'employee_id' => $employee->id,
            'payroll_date' => now()->format('Y-m-d'),
            'period_start' => now()->subDays(29)->format('Y-m-d'), // 30 days (Bulanan)
            'period_end' => now()->format('Y-m-d'),
            'total_amount' => 3000000,
            'salary_type' => 'Bulanan',
            'payment_status' => 'Sudah Dibayar',
        ]);

        Livewire::test(Index::class)
            ->assertViewHas('totalGajiDibayar', 3700000)
            ->assertViewHas('totalGajiHarian', 700000)
            ->assertViewHas('totalGajiBulanan', 3000000)
            ->assertViewHas('jumlahKaryawanDibayar', 1); // Only 1 distinct employee
    }

    public function test_can_pay_payroll()
    {
        $employee = Employee::factory()->create(['name' => 'Budi', 'phone' => '123', 'role' => 'Karyawan Harian']);
        $payroll = Payroll::factory()->create([
            'employee_id' => $employee->id,
            'payroll_date' => now(),
            'period_start' => now(),
            'period_end' => now(),
            'total_amount' => 500000,
            'payment_status' => 'Belum Dibayar',
        ]);

        Livewire::test(Index::class)
            ->call('pay', $payroll->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('payrolls', [
            'id' => $payroll->id,
            'payment_status' => 'Sudah Dibayar',
        ]);

        $this->assertDatabaseHas('cash_entries', [
            'type' => 'Pengeluaran',
            'amount' => 500000,
            'reference_type' => Payroll::class,
            'reference_id' => $payroll->id,
        ]);
    }

    public function test_cart_pays_multiple_daily_employees_and_records_each_expense(): void
    {
        $budi = Employee::factory()->create(['name' => 'Budi', 'default_rate' => 75000]);
        $andi = Employee::factory()->create(['name' => 'Andi', 'default_rate' => 90000]);
        $form = Livewire::test(Index::class)
            ->set('period_start', '2026-10-05')->set('period_end', '2026-10-06')->set('payroll_date', '2026-10-06')
            ->call('addToCart', $budi->id)->call('addToCart', $andi->id)->call('addToCart', $budi->id)
            ->set('cart.'.$budi->id.'.days', 2);

        $form->assertDontSee('Di keranjang')->assertDontSee('Tambahkan ke keranjang')
            ->assertSee('is-selected')->assertViewHas('cartTotal', '240000.00');
        $form->call('checkout')->assertHasNoErrors()->assertSet('cart', []);
        $form->call('checkout')->assertHasErrors(['cart']);

        $this->assertDatabaseCount('payrolls', 2);
        $this->assertDatabaseCount('cash_entries', 2);
        $this->assertDatabaseHas('payrolls', ['employee_id' => $budi->id, 'salary_type' => 'Harian', 'daily_rate' => 75000, 'work_days' => 2, 'total_amount' => 150000, 'payment_status' => 'Sudah Dibayar']);
        $this->assertDatabaseHas('cash_entries', ['name' => 'Gaji Budi', 'type' => 'Pengeluaran', 'amount' => 150000, 'entry_date' => '2026-10-06']);
        $this->assertDatabaseHas('cash_entries', ['name' => 'Gaji Andi', 'type' => 'Pengeluaran', 'amount' => 90000]);
    }

    public function test_checkout_uses_employee_rate_instead_of_client_amount(): void
    {
        $employee = Employee::factory()->create(['name' => 'Budi', 'default_rate' => 50000]);
        $form = Livewire::test(Index::class)->call('addToCart', $employee->id)
            ->set('cart.'.$employee->id.'.price', 1)->set('total_amount', '1');
        $employee->update(['default_rate' => 55000]);

        $form->call('checkout')->assertHasNoErrors();

        $this->assertDatabaseHas('payrolls', ['employee_id' => $employee->id, 'daily_rate' => 55000, 'total_amount' => 55000]);
        $this->assertDatabaseHas('cash_entries', ['amount' => 55000]);
    }

    public function test_inactive_employee_rolls_back_payment_for_entire_cart(): void
    {
        $first = Employee::factory()->create(['name' => 'Andi', 'default_rate' => 50000]);
        $second = Employee::factory()->create(['name' => 'Budi', 'default_rate' => 75000]);
        $form = Livewire::test(Index::class)->call('addToCart', $first->id)->call('addToCart', $second->id);
        $second->update(['is_active' => false]);

        $form->call('checkout')->assertHasErrors(['cart']);

        $this->assertDatabaseCount('payrolls', 0);
        $this->assertDatabaseCount('cash_entries', 0);
    }

    public function test_invalid_days_and_deleted_employee_create_no_payment(): void
    {
        $employee = Employee::factory()->create(['name' => 'Budi', 'default_rate' => 50000]);
        $form = Livewire::test(Index::class)->call('addToCart', $employee->id);

        $form->set('cart.'.$employee->id.'.days', 0)->call('checkout')->assertHasErrors(['cart.'.$employee->id.'.days']);
        $form->set('cart.'.$employee->id.'.days', 2)->call('checkout')->assertHasErrors(['cart']);
        $employee->delete();
        $form->set('cart.'.$employee->id.'.days', 1)->call('checkout')->assertHasErrors(['cart']);

        $this->assertDatabaseCount('payrolls', 0);
        $this->assertDatabaseCount('cash_entries', 0);
    }

    public function test_overlapping_payment_requires_explicit_confirmation_and_is_atomic(): void
    {
        $first = Employee::factory()->create(['name' => 'Andi', 'default_rate' => 50000]);
        $second = Employee::factory()->create(['name' => 'Budi', 'default_rate' => 75000]);
        Livewire::test(Index::class)->call('addToCart', $second->id)->call('checkout')->assertHasNoErrors();
        $form = Livewire::test(Index::class)->call('addToCart', $first->id)->call('addToCart', $second->id);

        $form->call('checkout')->assertHasErrors(['confirmSimilar']);
        $this->assertDatabaseCount('payrolls', 1);
        $this->assertDatabaseCount('cash_entries', 1);
        $form->set('confirmSimilar', true)->call('checkout')->assertHasNoErrors();

        $this->assertDatabaseCount('payrolls', 3);
        $this->assertDatabaseCount('cash_entries', 3);
    }

    public function test_remove_and_clear_cart_do_not_create_expenses(): void
    {
        $employee = Employee::factory()->create(['name' => 'Budi', 'default_rate' => 50000]);

        $form = Livewire::test(Index::class)->call('addToCart', $employee->id)
            ->call('removeItem', $employee->id)->assertSet('cart', [])->assertViewHas('cartTotal', '0.00');
        $form->call('addToCart', $employee->id)->call('clearCart')->assertSet('cart', []);

        $this->assertDatabaseCount('payrolls', 0);
        $this->assertDatabaseCount('cash_entries', 0);
    }

    public function test_production_payroll_records_cost_and_rejects_locked_production(): void
    {
        $employee = Employee::factory()->create(['name' => 'Budi', 'default_rate' => 50000]);
        $batch = ProductionBatch::factory()->create(['cost_finalized_at' => now()]);
        $form = Livewire::test(Index::class)->call('addToCart', $employee->id)
            ->set('classification', 'Produksi')->set('production_batch_id', $batch->id);

        $form->call('checkout')->assertHasErrors(['production_batch_id']);
        $this->assertDatabaseCount('payrolls', 0);
        $this->assertDatabaseCount('cash_entries', 0);
        $batch->update(['cost_finalized_at' => null]);
        $form->call('checkout')->assertHasNoErrors();

        $this->assertDatabaseHas('cash_entries', ['classification' => 'Produksi', 'production_batch_id' => $batch->id, 'amount' => 50000]);
        $this->assertDatabaseHas('batch_costs', ['production_batch_id' => $batch->id, 'amount' => 50000, 'category' => 'Gaji']);
    }

    public function test_cards_only_offer_active_employees_with_daily_salary(): void
    {
        $daily = Employee::factory()->create(['name' => 'Budi harian', 'default_rate' => 50000]);
        Employee::factory()->create(['name' => 'Nonaktif', 'default_rate' => 50000, 'is_active' => false]);
        Employee::factory()->create(['name' => 'Bulanan lama', 'default_rate' => 3000000, 'salary_type' => 'Bulanan']);
        Employee::factory()->create(['name' => 'Belum ada gaji', 'default_rate' => 0]);

        Livewire::test(Index::class)->assertViewHas('availableEmployees', fn ($employees) => $employees->total() === 1 && $employees->first()->id === $daily->id);
    }

    public function test_replayed_checkout_does_not_pay_again_and_rejects_changed_payload(): void
    {
        $employee = Employee::factory()->create(['name' => 'Budi', 'default_rate' => 50000]);
        $form = app(Index::class);
        $form->mount();
        $form->cart = [$employee->id => ['days' => 1]];
        $form->period_start = '2026-10-05';
        $form->period_end = '2026-10-06';
        $replay = clone $form;
        $changed = clone $form;
        $changed->cart[$employee->id]['days'] = 2;

        $form->checkout();
        $replay->checkout();
        try {
            $changed->checkout();
            $this->fail('A used checkout must reject a different payment.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('cart', $exception->errors());
        }

        $this->assertDatabaseCount('payrolls', 1);
        $this->assertDatabaseCount('cash_entries', 1);
        $this->assertDatabaseHas('cash_entries', ['amount' => 50000]);
    }
}
