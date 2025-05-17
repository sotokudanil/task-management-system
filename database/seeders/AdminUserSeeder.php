<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buat user admin
        User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'), // Password default
            'role' => 'admin',
            'status' => true,
        ]);

        // Buat user manager contoh
        User::create([
            'id' =>(string) Str::uuid(),
            'name' => 'Manager',
            'email' => 'manager@example.com',
            'password' => Hash::make('password123'),
            'role' => 'manager',
            'status' => true,
        ]);

        // Buat user staff contoh
        User::create([
            'id' =>(string) Str::uuid(),
            'name' => 'Staff',
            'email' => 'staff@example.com',
            'password' => Hash::make('password123'),
            'role' => 'staff',
            'status' => true,
        ]);

        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: admin@example.com');
        $this->command->info('Password: password123');
    }
}