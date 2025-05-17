<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TaskFactory extends Factory
{
    public function definition()
    {
        return [
            'id' => Str::uuid(),
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'status' => 'pending',
            'due_date' => $this->faker->dateTimeBetween('now', '+1 month'),
        ];
    }

    public function pending()
    {
        return $this->state([
            'status' => 'pending',
        ]);
    }

    public function inProgress()
    {
        return $this->state([
            'status' => 'in_progress',
        ]);
    }

    public function done()
    {
        return $this->state([
            'status' => 'done',
        ]);
    }

    public function overdue()
    {
        return $this->state([
            'due_date' => $this->faker->dateTimeBetween('-1 month', '-1 day'),
            'status' => 'pending',
        ]);
    }
}