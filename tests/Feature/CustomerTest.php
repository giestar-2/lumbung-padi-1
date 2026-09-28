<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use App\Livewire\Customers\Index;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_customer_index_renders()
    {
        $this->get('/customers')->assertStatus(200);
    }

    public function test_can_create_customer()
    {
        Livewire::test(Index::class)
            ->set('name', 'Budi Santoso')
            ->set('phone', '08123456789')
            ->set('address', 'Jl. Merdeka')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('customers', [
            'name' => 'Budi Santoso',
            'phone' => '08123456789',
        ]);
    }
}