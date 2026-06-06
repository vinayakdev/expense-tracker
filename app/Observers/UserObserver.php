<?php

namespace App\Observers;

use App\Models\Category;
use App\Models\User;
use Database\Seeders\CategorySeeder;

class UserObserver
{
    public function created(User $user): void
    {
        foreach (CategorySeeder::definitions() as $definition) {
            Category::create([...$definition, 'user_id' => $user->id]);
        }
    }
}
