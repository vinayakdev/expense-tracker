<?php

use Illuminate\Support\Facades\Route;

test('starter authentication routes have been removed', function () {
    expect(Route::has('login'))->toBeFalse()
        ->and(Route::has('login.store'))->toBeFalse()
        ->and(Route::has('logout'))->toBeFalse();

    expect(Route::has('filament.app.auth.login'))->toBeTrue()
        ->and(Route::has('filament.app.auth.logout'))->toBeTrue();

    $this->get('/login')->assertSuccessful();
});
