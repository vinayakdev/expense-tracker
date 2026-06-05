<?php

use Illuminate\Support\Facades\Route;

test('starter password confirmation routes have been removed', function () {
    expect(Route::has('password.confirm'))->toBeFalse()
        ->and(Route::has('password.confirm.store'))->toBeFalse()
        ->and(Route::has('password.confirmation'))->toBeFalse();

    $this->get('/user/confirm-password')->assertNotFound();
    $this->post('/user/confirm-password')->assertNotFound();
});
