<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use App\Livewire\Settings;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_settings_renders()
    {
        $this->get('/settings')->assertStatus(200);
    }

    public function test_can_update_profile_settings()
    {
        $user = User::first();

        Livewire::test(Settings::class)
            ->set('name', 'Admin Baru')
            ->set('email', 'adminbaru@example.com')
            ->call('updateProfile')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Admin Baru',
            'email' => 'adminbaru@example.com',
        ]);
    }
}