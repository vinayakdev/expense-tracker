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
    Transaction::factory()->create([
        'account_id' => $this->account->id,
        'category_id' => $this->category->id,
        'description' => 'Monthly groceries',
        'transacted_at' => now()->toDateString(),
    ]);

    Livewire::test(ListTransactions::class)
        ->assertSee('Monthly transactions')
        ->assertSee('Monthly groceries');
});

test('cannot see other users transactions', function () {
    $otherUser = User::factory()->create();
    $otherAccount = Account::factory()->create(['user_id' => $otherUser->id]);
    $otherCategory = Category::factory()->create(['user_id' => $otherUser->id]);

    $otherTransaction = Transaction::withoutEvents(fn () => Transaction::factory()->create([
        'account_id' => $otherAccount->id,
        'category_id' => $otherCategory->id,
        'description' => 'Other account transaction',
        'transacted_at' => now()->toDateString(),
    ]));
    $myTransaction = Transaction::factory()->create([
        'account_id' => $this->account->id,
        'category_id' => $this->category->id,
        'description' => 'My account transaction',
        'transacted_at' => now()->toDateString(),
    ]);

    Livewire::test(ListTransactions::class)
        ->assertSee($myTransaction->description)
        ->assertDontSee($otherTransaction->description);
});

test('selected month is restored from the URL query string', function () {
    Transaction::factory()->create([
        'account_id' => $this->account->id,
        'category_id' => $this->category->id,
        'description' => 'May subscription',
        'transacted_at' => '2026-05-12',
    ]);
    Transaction::factory()->create([
        'account_id' => $this->account->id,
        'category_id' => $this->category->id,
        'description' => 'June subscription',
        'transacted_at' => '2026-06-12',
    ]);

    Livewire::withQueryParams(['month' => '2026-05'])
        ->test(ListTransactions::class)
        ->assertSet('selectedMonthKey', '2026-05')
        ->assertSee('May subscription')
        ->assertDontSee('June subscription');
});

test('custom transaction list loads fifteen records before infinite scroll loads more', function () {
    foreach (range(1, 16) as $index) {
        Transaction::factory()->create([
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'description' => sprintf('Infinite item %03d', $index),
            'transacted_at' => now()->toDateString(),
        ]);
    }

    Livewire::test(ListTransactions::class)
        ->assertSet('visibleTransactionsCount', 15)
        ->assertSee('Infinite item 016')
        ->assertDontSee('Infinite item 001')
        ->assertSee('Loading more transactions')
        ->call('loadMoreTransactions')
        ->assertSet('visibleTransactionsCount', 30)
        ->assertSee('Infinite item 001')
        ->assertDontSee('Loading more transactions');
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

    $this->assertSoftDeleted($transaction);
});

test('can edit a transaction from the custom transaction list', function () {
    $transaction = Transaction::factory()->create([
        'account_id' => $this->account->id,
        'category_id' => $this->category->id,
        'type' => 'expense',
        'transacted_at' => now()->toDateString(),
    ]);

    Livewire::test(ListTransactions::class)
        ->assertActionExists('editTransaction', arguments: ['transaction' => $transaction->id])
        ->callAction('editTransaction', data: ['description' => 'Edited from feed'], arguments: ['transaction' => $transaction->id]);

    expect($transaction->refresh()->description)->toBe('Edited from feed');
});

test('can delete a transaction from the custom transaction list', function () {
    $transaction = Transaction::factory()->create([
        'account_id' => $this->account->id,
        'category_id' => $this->category->id,
        'transacted_at' => now()->toDateString(),
    ]);

    Livewire::test(ListTransactions::class)
        ->assertActionExists('deleteTransaction', arguments: ['transaction' => $transaction->id])
        ->callAction('deleteTransaction', arguments: ['transaction' => $transaction->id]);

    $this->assertSoftDeleted($transaction);
});
