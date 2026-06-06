<?php

use App\Livewire\Auth\AuthPage;
use Illuminate\Support\Facades\Route;

Route::get('/', AuthPage::class)->name('login');
