<?php

use Illuminate\Support\Facades\Route;

test('starter web routes have been removed', function () {
    expect(Route::has('home'))->toBeFalse()
        ->and(Route::has('dashboard'))->toBeFalse();

    $this->get('/dashboard')->assertNotFound();
});
