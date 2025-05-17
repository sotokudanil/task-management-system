<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    public function definition()
    {
        return [
            'id' => Str::uuid(),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('password123'),
            'role' => $this->faker->randomElement(['admin', 'manager', 'staff']),
            'status' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin()
    {
        return $this->state([
            'role' => 'admin',
        ]);
    }

    public function manager()
    {
        return $this->state([
            'role' => 'manager',
        ]);
    }

    public function staff()
    {
        return $this->state([
            'role' => 'staff',
        ]);
    }

    public function inactive()
    {
        return $this->state([
            'status' => false,
        ]);
    }
}