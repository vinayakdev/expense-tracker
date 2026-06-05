<?php

namespace Database\Seeders;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $start = Carbon::create(2021, 1, 1);
        $end = Carbon::create(2026, 6, 5);

        User::with(['accounts', 'categories'])->each(function (User $user) use ($start, $end): void {
            if ($user->accounts->isEmpty()) {
                return;
            }

            $expenseCategories = $user->categories->where('type', 'expense')->values();
            $incomeCategories = $user->categories->where('type', 'income')->values();
            $salaryCategory = $incomeCategories->firstWhere('name', 'Salary') ?? $incomeCategories->first();
            $freelanceCategory = $incomeCategories->firstWhere('name', 'Freelance');
            $investmentCategory = $incomeCategories->firstWhere('name', 'Investment Returns');

            if ($expenseCategories->isEmpty() || ! $salaryCategory) {
                return;
            }

            $now = now()->toDateTimeString();

            foreach ($user->accounts as $account) {
                $currency = $account->currency;
                $salary = $this->baseSalary($currency);
                $rows = [];

                $current = $start->copy();

                while ($current->lte($end)) {
                    $month = $current->month;
                    $year = $current->year;
                    $maxDay = $current->isSameMonth($end) ? $end->day : $current->daysInMonth;

                    // ── Monthly salary ────────────────────────────────────
                    $rows[] = $this->txRow(
                        $account->id,
                        $salaryCategory->id,
                        'income',
                        $this->jitter($salary, 0.02),
                        Carbon::create($year, $month, 1),
                        'Monthly salary',
                        $now,
                    );

                    // ── Occasional freelance (60% of months) ──────────────
                    if ($freelanceCategory && fake()->boolean(60) && $maxDay >= 5) {
                        $rows[] = $this->txRow(
                            $account->id,
                            $freelanceCategory->id,
                            'income',
                            $this->jitter($salary * fake()->randomFloat(2, 0.1, 0.4), 0.05),
                            Carbon::create($year, $month, fake()->numberBetween(5, min(25, $maxDay))),
                            'Freelance project',
                            $now,
                        );
                    }

                    // ── Quarterly investment returns ───────────────────────
                    if ($investmentCategory && in_array($month, [3, 6, 9, 12]) && $maxDay >= 10) {
                        $rows[] = $this->txRow(
                            $account->id,
                            $investmentCategory->id,
                            'income',
                            $this->jitter($salary * fake()->randomFloat(2, 0.05, 0.15), 0.1),
                            Carbon::create($year, $month, fake()->numberBetween(10, min(28, $maxDay))),
                            'Investment returns',
                            $now,
                        );
                    }

                    // ── Fixed monthly expenses ────────────────────────────
                    $fixedExpenses = $this->fixedMonthlyExpenses($user, $currency, $year, $month, $now, $account->id, $maxDay);
                    $rows = array_merge($rows, $fixedExpenses);

                    // ── Variable daily expenses (18–28 per month, scaled for partial months) ─────────
                    $fullCount = fake()->numberBetween(18, 28);
                    $expenseCount = $maxDay < $current->daysInMonth
                        ? max(1, (int) round($fullCount * $maxDay / $current->daysInMonth))
                        : $fullCount;

                    for ($i = 0; $i < $expenseCount; $i++) {
                        $category = $expenseCategories->random();
                        $rows[] = $this->txRow(
                            $account->id,
                            $category->id,
                            'expense',
                            $this->randomExpenseAmount($category->name, $currency),
                            Carbon::create($year, $month, fake()->numberBetween(1, $maxDay)),
                            fake()->optional(0.6)->sentence(3),
                            $now,
                        );
                    }

                    $current->addMonth();
                }

                // Bulk insert in chunks to stay memory-efficient
                foreach (array_chunk($rows, 500) as $chunk) {
                    Transaction::insert($chunk);
                }
            } // end foreach account
        });
    }

    /** @return array<string, mixed> */
    private function fixedMonthlyExpenses(User $user, string $currency, int $year, int $month, string $now, string $accountId, int $maxDay): array
    {
        $rows = [];
        $getCategory = fn (string $name) => $user->categories->firstWhere('name', $name);

        $fixed = [
            ['Rent & Housing',    [7, 8],   0.35, 'Monthly rent'],
            ['Electricity',       [5, 6],   0.04, 'Electricity bill'],
            ['Internet & Phone',  [3, 4],   0.015, 'Internet & mobile bill'],
            ['Subscriptions',     [10, 15], 0.008, 'Netflix / Spotify'],
            ['Gym & Fitness',     [1, 5],   0.015, 'Gym membership'],
        ];

        foreach ($fixed as [$name, [$minDay, $maxDayRange], $fraction, $desc]) {
            if ($minDay > $maxDay) {
                continue;
            }

            $category = $getCategory($name);
            if (! $category) {
                continue;
            }

            $salary = $this->baseSalary($currency);
            $rows[] = $this->txRow(
                $accountId,
                $category->id,
                'expense',
                $this->jitter($salary * $fraction, 0.05),
                Carbon::create($year, $month, fake()->numberBetween($minDay, min($maxDayRange, $maxDay))),
                $desc,
                $now,
            );
        }

        return $rows;
    }

    /** @return array<string, mixed> */
    private function txRow(
        string $accountId,
        int $categoryId,
        string $type,
        float $amount,
        Carbon $date,
        ?string $description,
        string $now,
    ): array {
        return [
            'account_id' => $accountId,
            'category_id' => $categoryId,
            'type' => $type,
            'amount' => round($amount, 2),
            'description' => $description,
            'transacted_at' => $date->toDateString(),
            'recurrence' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function baseSalary(string $currency): float
    {
        return match ($currency) {
            'INR' => 75000,
            'USD' => 5500,
            'GBP' => 4200,
            'EUR' => 4800,
            'AUD' => 7000,
            default => 5000,
        };
    }

    private function jitter(float $value, float $pct): float
    {
        $delta = $value * $pct;

        return $value + fake()->randomFloat(2, -$delta, $delta);
    }

    private function randomExpenseAmount(string $categoryName, string $currency): float
    {
        $multiplier = match ($currency) {
            'INR' => 1,
            'USD' => 0.075,
            'GBP' => 0.06,
            'EUR' => 0.067,
            'AUD' => 0.11,
            default => 0.075,
        };

        [$min, $max] = match (true) {
            str_contains($categoryName, 'Food') || str_contains($categoryName, 'Dining') => [200, 1500],
            str_contains($categoryName, 'Groceries') => [500, 3000],
            str_contains($categoryName, 'Coffee') => [50, 300],
            str_contains($categoryName, 'Transport') => [50, 800],
            str_contains($categoryName, 'Fuel') => [500, 2500],
            str_contains($categoryName, 'Shopping') => [500, 8000],
            str_contains($categoryName, 'Clothing') => [500, 5000],
            str_contains($categoryName, 'Healthcare') => [200, 5000],
            str_contains($categoryName, 'Medicine') => [100, 2000],
            str_contains($categoryName, 'Entertainment') => [100, 1500],
            str_contains($categoryName, 'Movies') => [100, 800],
            str_contains($categoryName, 'Travel') => [2000, 20000],
            str_contains($categoryName, 'Hotel') => [2000, 15000],
            str_contains($categoryName, 'Education') => [500, 10000],
            str_contains($categoryName, 'Books') => [100, 1000],
            str_contains($categoryName, 'Personal') => [100, 1000],
            str_contains($categoryName, 'Salon') => [200, 1500],
            str_contains($categoryName, 'Gift') => [200, 3000],
            str_contains($categoryName, 'Repairs') => [500, 10000],
            str_contains($categoryName, 'Pets') => [200, 3000],
            default => [100, 2000],
        };

        return round(fake()->randomFloat(2, $min, $max) * $multiplier, 2);
    }
}
