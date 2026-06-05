<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(2, true),
            'currency' => fake()->randomElement(['INR', 'USD', 'EUR', 'GBP']),
            'balance' => fake()->randomFloat(2, 0, 100000),
        ];
    }
}
