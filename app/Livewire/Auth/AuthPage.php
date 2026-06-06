<?php

namespace App\Livewire\Auth;

use Livewire\Component;

class AuthPage extends Component
{
    public string $loginEmail = '';

    public string $loginPassword = '';

    public bool $rememberMe = false;

    public string $registerName = '';

    public string $registerEmail = '';

    public string $registerPassword = '';

    public string $registerPasswordConfirmation = '';

    public function login(): void
    {
        //
    }

    public function register(): void
    {
        //
    }

    public function render()
    {
        return view('livewire.auth.auth-page')
            ->layout('layouts.guest');
    }
}
