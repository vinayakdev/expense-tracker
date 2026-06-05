<?php

use Illuminate\Support\Facades\Route;

test('starter security settings routes and components have been removed', function () {
    expect(Route::has('security.edit'))->toBeFalse()
        ->and(Route::has('appearance.edit'))->toBeFalse()
        ->and(file_exists(app_path('Livewire/Settings/Security.php')))->toBeFalse()
        ->and(file_exists(app_path('Livewire/Settings/Appearance.php')))->toBeFalse();

    $this->get('/settings/security')->assertNotFound();
    $this->get('/settings/appearance')->assertNotFound();
});
