<?php

namespace App\Livewire\Auth;

use App\Models\Account;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CreateAccount extends Component
{
    public string $name = '';

    public string $currency = 'USD';

    public function create(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'currency' => ['required', 'string', 'size:3'],
        ]);

        $account = Account::create([
            'user_id' => Auth::id(),
            'name' => $this->name,
            'currency' => $this->currency,
            'balance' => 0,
        ]);

        $this->redirect(
            route('filament.app.pages.dashboard', ['tenant' => $account]),
            navigate: false,
        );
    }

    public function render()
    {
        return view('livewire.auth.create-account')
            ->layout('layouts.guest');
    }
}
