<?php

use App\Livewire\Auth\AuthPage;
use App\Livewire\Auth\CreateAccount;
use App\Livewire\CreateTransaction;
use Illuminate\Support\Facades\Route;

Route::get('/', AuthPage::class)->name('login')->middleware('guest');

Route::get('/setup', CreateAccount::class)->name('account.setup')->middleware('auth');

Route::get('/accounts/{account}/transactions/create', CreateTransaction::class)
    ->name('transactions.create')
    ->middleware('auth');
