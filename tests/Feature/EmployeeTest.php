<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use App\Livewire\Employees\Index;

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
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('employees', [
            'name' => 'Andi',
            'role' => 'Karyawan Harian',
        ]);
    }
}