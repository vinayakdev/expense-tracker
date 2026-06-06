<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
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

    public function quickLogin(int $userId): void
    {
        abort_if(app()->isProduction(), 403);

        $user = User::findOrFail($userId);

        Auth::login($user);
        session()->regenerate();

        $this->redirect($this->panelUrl(), navigate: false);
    }

    public function login(): void
    {
        $this->validate([
            'loginEmail' => ['required', 'email'],
            'loginPassword' => ['required'],
        ]);

        if (! Auth::attempt(['email' => $this->loginEmail, 'password' => $this->loginPassword], $this->rememberMe)) {
            $this->addError('loginEmail', 'These credentials do not match our records.');

            return;
        }

        session()->regenerate();

        $this->redirect($this->panelUrl(), navigate: false);
    }

    public function register(): void
    {
        $this->validate([
            'registerName' => ['required', 'string', 'max:255'],
            'registerEmail' => ['required', 'email', 'unique:users,email'],
            'registerPassword' => ['required', 'same:registerPasswordConfirmation', Password::defaults()],
        ], attributes: [
            'registerName' => 'name',
            'registerEmail' => 'email',
            'registerPassword' => 'password',
            'registerPasswordConfirmation' => 'password confirmation',
        ]);

        $user = User::create([
            'name' => $this->registerName,
            'email' => $this->registerEmail,
            'password' => Hash::make($this->registerPassword),
        ]);

        Auth::login($user);

        session()->regenerate();

        $this->redirect($this->panelUrl(), navigate: false);
    }

    protected function panelUrl(): string
    {
        $panel = Filament::getPanel('app');
        $tenant = Auth::user()->getDefaultTenant($panel);

        if ($tenant) {
            return route('filament.app.pages.dashboard', ['tenant' => $tenant]);
        }

        return url('/new');
    }

    public function render()
    {
        $quickLoginUsers = app()->isProduction() ? collect() : User::orderBy('id')->limit(5)->get(['id', 'name', 'email']);

        return view('livewire.auth.auth-page', compact('quickLoginUsers'))->layout('layouts.guest');
    }
}
