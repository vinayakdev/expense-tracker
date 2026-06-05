<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->word(),
            'type' => fake()->randomElement(['expense', 'income']),
            'color' => fake()->hexColor(),
            'icon' => null,
        ];
    }

    public function expense(): static
    {
        return $this->state(['type' => 'expense']);
    }

    public function income(): static
    {
        return $this->state(['type' => 'income']);
    }
}
