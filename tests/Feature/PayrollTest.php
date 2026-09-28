<?php

namespace Tests\Feature;

use App\Livewire\Payrolls\Index;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
