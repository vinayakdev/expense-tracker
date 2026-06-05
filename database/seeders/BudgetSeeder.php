<?php

namespace Database\Seeders;

use App\Models\Budget;
use App\Models\User;
use Illuminate\Database\Seeder;

class BudgetSeeder extends Seeder
{
    public function run(): void
    {
        User::with(['accounts', 'categories'])->each(function (User $user): void {
            $expenseCategories = $user->categories->where('type', 'expense')->values();

            if ($expenseCategories->isEmpty()) {
                return;
            }

            $rows = [];
            $now = now()->toDateTimeString();

            foreach ($user->accounts as $account) {
                $budgets = self::budgetsByCurrency($account->currency);

                foreach ($expenseCategories as $category) {
                    $rows[] = [
                        'user_id' => $user->id,
                        'account_id' => $account->id,
                        'category_id' => $category->id,
                        'amount' => $this->budgetForCategory($category->name, $budgets),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                Budget::insert($chunk);
            }
        });
    }

    /** @return array<string, float> */
    private static function budgetsByCurrency(string $currency): array
    {
        return match ($currency) {
            'USD' => [
                'Rent' => 1800,
                'Groceries' => 600,
                'Food' => 500,
                'Coffee' => 150,
                'Transport' => 200,
                'Fuel' => 250,
                'Electricity' => 120,
                'Internet' => 80,
                'Shopping' => 400,
                'Clothing' => 200,
                'Healthcare' => 300,
                'Medicine' => 100,
                'Gym' => 80,
                'Entertainment' => 150,
                'Movies' => 60,
                'Travel' => 800,
                'Hotel' => 500,
                'Education' => 400,
                'Books' => 60,
                'Subscriptions' => 50,
                'Personal' => 100,
                'Salon' => 80,
                'Gifts' => 150,
                'Repairs' => 200,
                'Pets' => 120,
                'default' => 150,
            ],
            'GBP' => [
                'Rent' => 1500,
                'Groceries' => 450,
                'Food' => 400,
                'Coffee' => 100,
                'Transport' => 150,
                'Fuel' => 200,
                'Electricity' => 100,
                'Internet' => 60,
                'Shopping' => 300,
                'Clothing' => 150,
                'Healthcare' => 250,
                'Medicine' => 80,
                'Gym' => 60,
                'Entertainment' => 120,
                'Movies' => 50,
                'Travel' => 650,
                'Hotel' => 400,
                'Education' => 350,
                'Books' => 50,
                'Subscriptions' => 40,
                'Personal' => 80,
                'Salon' => 60,
                'Gifts' => 120,
                'Repairs' => 150,
                'Pets' => 100,
                'default' => 120,
            ],
            'AUD' => [
                'Rent' => 2400,
                'Groceries' => 800,
                'Food' => 650,
                'Coffee' => 200,
                'Transport' => 280,
                'Fuel' => 320,
                'Electricity' => 160,
                'Internet' => 100,
                'Shopping' => 550,
                'Clothing' => 280,
                'Healthcare' => 400,
                'Medicine' => 130,
                'Gym' => 100,
                'Entertainment' => 200,
                'Movies' => 80,
                'Travel' => 1000,
                'Hotel' => 650,
                'Education' => 500,
                'Books' => 80,
                'Subscriptions' => 70,
                'Personal' => 130,
                'Salon' => 100,
                'Gifts' => 200,
                'Repairs' => 280,
                'Pets' => 160,
                'default' => 200,
            ],
            default => [ // INR
                'Rent' => 25000,
                'Groceries' => 12000,
                'Food' => 10000,
                'Coffee' => 3000,
                'Transport' => 4000,
                'Fuel' => 5000,
                'Electricity' => 3000,
                'Internet' => 1500,
                'Shopping' => 8000,
                'Clothing' => 5000,
                'Healthcare' => 5000,
                'Medicine' => 2000,
                'Gym' => 2000,
                'Entertainment' => 3000,
                'Movies' => 1500,
                'Travel' => 15000,
                'Hotel' => 10000,
                'Education' => 8000,
                'Books' => 1500,
                'Subscriptions' => 1000,
                'Personal' => 2000,
                'Salon' => 1500,
                'Gifts' => 3000,
                'Repairs' => 5000,
                'Pets' => 2000,
                'default' => 3000,
            ],
        };
    }

    /** @param array<string, float> $budgets */
    private function budgetForCategory(string $name, array $budgets): float
    {
        foreach ($budgets as $keyword => $amount) {
            if ($keyword !== 'default' && str_contains($name, $keyword)) {
                return $amount;
            }
        }

        return $budgets['default'];
    }
}
