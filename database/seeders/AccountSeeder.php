<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        User::all()->each(static function (User $user): void {
            // INR is always first so it becomes the default tenant
            Account::updateOrCreate(['user_id' => $user->id, 'currency' => 'INR'], [
                'user_id' => $user->id,
                'name' => 'Primary',
                'currency' => 'INR',
                'balance' => 75_000,
            ]);

            Account::updateOrCreate(['user_id' => $user->id, 'currency' => 'USD'], [
                'user_id' => $user->id,
                'name' => 'USD Account',
                'currency' => 'USD',
                'balance' => 2500,
            ]);
        });
    }
}
