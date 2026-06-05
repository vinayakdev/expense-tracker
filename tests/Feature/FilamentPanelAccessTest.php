<?php

use App\Models\User;
use Filament\Facades\Filament;

test('normal users can access the filament app panel', function () {
    $user = User::factory()->create();

    expect($user->canAccessPanel(Filament::getPanel('app')))->toBeTrue();

    $this->actingAs($user)
        ->get('/')
        ->assertSuccessful();
});

test('guests are redirected to the filament login page', function () {
    $this->get('/')
        ->assertRedirect('/login');
});

test('database seeder creates multiple normal panel users', function () {
    $this->seed();

    expect(User::query()->count())->toBe(5)
        ->and(User::query()->where('email', 'test@example.com')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'avery@example.com')->exists())->toBeTrue();
});
