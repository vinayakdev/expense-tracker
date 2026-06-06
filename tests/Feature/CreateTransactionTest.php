<?php

use App\Livewire\CreateTransaction;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->account = Account::factory()->create(['user_id' => $this->user->id]);

    actingAs($this->user);
});

it('renders the create transaction component', function (): void {
    Livewire::test(CreateTransaction::class, ['account' => $this->account])
        ->assertOk();
});

it('defaults transacted_at to today', function (): void {
    Livewire::test(CreateTransaction::class, ['account' => $this->account])
        ->assertSet('transactedAt', now()->toDateString());
});

it('resets category when type changes', function (): void {
    $category = Category::factory()->expense()->create(['user_id' => $this->user->id]);

    Livewire::test(CreateTransaction::class, ['account' => $this->account])
        ->set('type', 'expense')
        ->set('categoryId', (string) $category->id)
        ->set('type', 'income')
        ->assertSet('categoryId', '');
});

it('saves a transaction and redirects to dashboard', function (): void {
    $category = Category::factory()->expense()->create(['user_id' => $this->user->id]);

    Livewire::test(CreateTransaction::class, ['account' => $this->account])
        ->set('type', 'expense')
        ->set('categoryId', $category->id)
        ->set('amount', '250.00')
        ->set('transactedAt', now()->toDateString())
        ->set('description', 'Test lunch')
        ->call('save')
        ->assertRedirect(
            route('filament.app.pages.dashboard', ['tenant' => $this->account->id])
        );

    expect(Transaction::where('account_id', $this->account->id)->count())->toBe(1)
        ->and(Transaction::first()->description)->toBe('Test lunch');
});

it('validates required fields', function (): void {
    Livewire::test(CreateTransaction::class, ['account' => $this->account])
        ->call('save')
        ->assertHasErrors(['categoryId', 'amount']);
});

it('rejects a future date', function (): void {
    Livewire::test(CreateTransaction::class, ['account' => $this->account])
        ->set('transactedAt', now()->addDay()->toDateString())
        ->call('save')
        ->assertHasErrors(['transactedAt']);
});

it('returns 403 when account does not belong to user', function (): void {
    $otherAccount = Account::factory()->create();

    Livewire::test(CreateTransaction::class, ['account' => $otherAccount])
        ->assertForbidden();
});
