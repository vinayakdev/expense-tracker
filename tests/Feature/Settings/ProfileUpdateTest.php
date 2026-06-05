<?php

use Illuminate\Support\Facades\Route;

test('starter profile settings routes and components have been removed', function () {
    expect(Route::has('profile.edit'))->toBeFalse()
        ->and(file_exists(app_path('Livewire/Settings/Profile.php')))->toBeFalse()
        ->and(file_exists(app_path('Livewire/Settings/DeleteUserForm.php')))->toBeFalse();

    $this->get('/settings')->assertNotFound();
    $this->get('/settings/profile')->assertNotFound();
});
