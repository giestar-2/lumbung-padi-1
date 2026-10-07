<?php

namespace Tests\Feature;

use App\Livewire\Employees\Index;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_employee_index_renders()
    {
        $this->get('/employees')->assertStatus(200);
    }

    public function test_can_create_employee()
    {
        Livewire::test(Index::class)
            ->set('name', 'Andi')
            ->set('phone', '08987654321')
            ->set('role', 'Karyawan Harian')
            ->set('default_rate', '75000')
            ->set('salary_type', 'Bulanan')
            ->set('default_rate', '75000')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('employees', [
            'name' => 'Andi',
            'role' => 'Karyawan Harian',
            'salary_type' => 'Harian',
            'default_rate' => 75000,
            'salary_type' => 'Harian',
            'default_rate' => 75000,
        ]);
    }

    public function test_monthly_employee_requires_daily_rate_before_conversion(): void
    {
        $employee = Employee::factory()->create(['name' => 'Budi', 'salary_type' => 'Bulanan', 'default_rate' => 3000000]);

        $form = Livewire::test(Index::class)->call('edit', $employee->id)->assertSet('default_rate', '');
        $form->call('save')->assertHasErrors(['default_rate']);
        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'salary_type' => 'Bulanan', 'default_rate' => 3000000]);
        $form->set('default_rate', '90000')->call('save')->assertHasNoErrors();

        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'salary_type' => 'Harian', 'default_rate' => 90000]);
    }
}
