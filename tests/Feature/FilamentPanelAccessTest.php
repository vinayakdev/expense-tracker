<?php

use App\Models\Account;
use App\Models\User;
use DutchCodingCompany\FilamentDeveloperLogins\FilamentDeveloperLoginsPlugin;
use Filament\Facades\Filament;

test('normal users can access the filament app panel', function () {
    $user = User::factory()->create();
    Account::factory()->create(['user_id' => $user->id]);

    expect($user->canAccessPanel(Filament::getPanel('app')))->toBeTrue();

    $this->actingAs($user)
        ->get('/')
        ->assertRedirectContains('/');
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

test('filament developer logins use seeded normal users', function () {
    $this->seed();

    /** @var FilamentDeveloperLoginsPlugin $plugin */
    $plugin = Filament::getPanel('app')->getPlugin('filament-developer-logins');

    expect($plugin->getEnabled())->toBeFalse()
        ->and($plugin->getUsers())->toMatchArray([
            'Avery Stone' => 'avery@example.com',
            'Test User' => 'test@example.com',
        ]);
});
