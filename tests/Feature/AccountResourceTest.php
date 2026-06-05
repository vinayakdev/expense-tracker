<?php

use App\Filament\Pages\EditAccountProfile;
use App\Filament\Pages\RegisterAccount;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create(['reporting_currency' => 'INR']);
    $this->account = Account::factory()->create(['user_id' => $this->user->id, 'currency' => 'INR']);
    actingAs($this->user);
    Filament::setTenant($this->account);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    Filament::bootCurrentPanel();
});

test('can register a new account with opening balance', function () {
    Livewire::test(RegisterAccount::class)
        ->fillForm([
            'name' => 'Savings',
            'currency' => 'USD',
            'opening_balance' => 5000,
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    $account = Account::where('user_id', $this->user->id)
        ->where('currency', 'USD')
        ->first();

    expect($account)->not->toBeNull()
        ->and($account->name)->toBe('Savings')
        ->and($account->balance)->toBe('5000.0000');

    $this->assertDatabaseHas('transactions', [
        'account_id' => $account->id,
        'type' => 'income',
        'description' => 'Balance rollover',
    ]);
});

test('no opening balance transaction when balance is zero', function () {
    Livewire::test(RegisterAccount::class)
        ->fillForm([
            'name' => 'Empty Account',
            'currency' => 'EUR',
            'opening_balance' => 0,
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    $account = Account::where('user_id', $this->user->id)
        ->where('currency', 'EUR')
        ->first();

    expect($account)->not->toBeNull();

    expect(Transaction::where('account_id', $account->id)->count())->toBe(0);
});

test('can edit current account name via tenant profile', function () {
    Livewire::test(EditAccountProfile::class)
        ->fillForm(['name' => 'Renamed Account'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->account->refresh()->name)->toBe('Renamed Account');
});

test('default tenant is the INR account', function () {
    $usdAccount = Account::factory()->create(['user_id' => $this->user->id, 'currency' => 'USD']);

    $defaultTenant = $this->user->getDefaultTenant(Filament::getPanel('app'));

    expect($defaultTenant->currency)->toBe('INR');
});
