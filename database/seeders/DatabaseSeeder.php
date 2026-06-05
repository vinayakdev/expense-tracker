<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        collect([
            ['name' => 'Test User', 'email' => 'test@example.com'],
            ['name' => 'Avery Stone', 'email' => 'avery@example.com'],
            ['name' => 'Blake Carter', 'email' => 'blake@example.com'],
            ['name' => 'Casey Morgan', 'email' => 'casey@example.com'],
            ['name' => 'Drew Parker', 'email' => 'drew@example.com'],
        ])->each(fn (array $user): User => User::query()->updateOrCreate(
            ['email' => $user['email']],
            [
                'name' => $user['name'],
                'password' => 'password',
            ],
        ));
    }
}
