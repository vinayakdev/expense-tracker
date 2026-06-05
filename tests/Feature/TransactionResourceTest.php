<?php

use App\Filament\Resources\Transactions\Pages\CreateTransaction;
use App\Filament\Resources\Transactions\Pages\EditTransaction;
use App\Filament\Resources\Transactions\Pages\ListTransactions;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->account = Account::factory()->create(['user_id' => $this->user->id]);
    $this->category = Category::factory()->expense()->create(['user_id' => $this->user->id]);
    actingAs($this->user);
    Filament::setTenant($this->account);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    Filament::bootCurrentPanel();
});

test('can list transactions', function () {
    $transactions = Transaction::factory(3)->create([
        'account_id' => $this->account->id,
        'category_id' => $this->category->id,
    ]);

    Livewire::test(ListTransactions::class)
        ->assertCanSeeTableRecords($transactions);
});

test('cannot see other users transactions', function () {
    $otherUser = User::factory()->create();
    $otherAccount = Account::factory()->create(['user_id' => $otherUser->id]);
    $otherCategory = Category::factory()->create(['user_id' => $otherUser->id]);

    $otherTransaction = Transaction::withoutEvents(fn () => Transaction::factory()->create([
        'account_id' => $otherAccount->id,
        'category_id' => $otherCategory->id,
    ]));
    $myTransaction = Transaction::factory()->create([
        'account_id' => $this->account->id,
        'category_id' => $this->category->id,
    ]);

    Livewire::test(ListTransactions::class)
        ->assertCanSeeTableRecords([$myTransaction])
        ->assertCanNotSeeTableRecords([$otherTransaction]);
});

test('can create a transaction', function () {
    Livewire::test(CreateTransaction::class)
        ->fillForm([
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => 500,
            'transacted_at' => now()->toDateString(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('transactions', [
        'account_id' => $this->account->id,
        'type' => 'expense',
    ]);
});

test('can edit a transaction', function () {
    $transaction = Transaction::factory()->create([
        'account_id' => $this->account->id,
        'category_id' => $this->category->id,
        'type' => 'expense',
    ]);

    Livewire::test(EditTransaction::class, ['record' => $transaction->id])
        ->fillForm(['description' => 'Updated description'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($transaction->refresh()->description)->toBe('Updated description');
});

test('can delete a transaction', function () {
    $transaction = Transaction::factory()->create([
        'account_id' => $this->account->id,
        'category_id' => $this->category->id,
    ]);

    Livewire::test(EditTransaction::class, ['record' => $transaction->id])
        ->callAction(DeleteAction::class);

    $this->assertModelMissing($transaction);
});
