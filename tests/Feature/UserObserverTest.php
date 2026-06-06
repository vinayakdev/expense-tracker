<?php

use App\Models\Category;
use App\Models\User;
use Database\Seeders\CategorySeeder;

it('seeds default categories when a user is created', function (): void {
    $user = User::factory()->create();

    $expectedCount = count(CategorySeeder::definitions());

    expect(Category::where('user_id', $user->id)->count())->toBe($expectedCount);
});

it('seeds categories with correct names and types', function (): void {
    $user = User::factory()->create();

    $categories = Category::where('user_id', $user->id)->pluck('name')->toArray();

    foreach (CategorySeeder::definitions() as $definition) {
        expect($categories)->toContain($definition['name']);
    }
});
