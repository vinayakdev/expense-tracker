<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'category_id' => Category::factory(),
            'type' => fake()->randomElement(['expense', 'income']),
            'amount' => fake()->randomFloat(2, 1, 50000),
            'description' => fake()->optional()->sentence(),
            'transacted_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'recurrence' => null,
        ];
    }
}
