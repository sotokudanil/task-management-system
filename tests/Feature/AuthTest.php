<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_with_valid_role_can_login()
    {
        $user = User::factory()->staff()->create([
            'email' => 'staff@example.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'staff@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user']);
    }

    /** @test */
    public function inactive_user_cannot_login()
    {
        $user = User::factory()->staff()->inactive()->create([
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123'
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function invalid_credentials_are_rejected()
    {
        User::factory()->create([
            'email' => 'staff@example.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'staff@example.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(401);
    }
}