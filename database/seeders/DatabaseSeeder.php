<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $users = [
            ['name' => 'Test User',    'email' => 'test@example.com',  'reporting_currency' => 'INR'],
            ['name' => 'Avery Stone',  'email' => 'avery@example.com', 'reporting_currency' => 'USD'],
            ['name' => 'Blake Carter', 'email' => 'blake@example.com', 'reporting_currency' => 'INR'],
            ['name' => 'Casey Morgan', 'email' => 'casey@example.com', 'reporting_currency' => 'GBP'],
            ['name' => 'Drew Parker',  'email' => 'drew@example.com',  'reporting_currency' => 'AUD'],
        ];

        foreach ($users as $data) {
            User::query()->updateOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => 'password', 'reporting_currency' => $data['reporting_currency']],
            );
        }

        $this->call([
            AccountSeeder::class,
            CategorySeeder::class,
            TransactionSeeder::class,
            BudgetSeeder::class,
        ]);
    }
}
