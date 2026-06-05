<?php

use App\Filament\Resources\Budgets\Pages\CreateBudget;
use App\Filament\Resources\Budgets\Pages\EditBudget;
use App\Filament\Resources\Budgets\Pages\ListBudgets;
use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
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
});

test('can list budgets', function () {
    $budgets = Budget::factory(3)->create([
        'user_id' => $this->user->id,
        'account_id' => $this->account->id,
        'category_id' => $this->category->id,
    ]);

    Livewire::test(ListBudgets::class)
        ->assertCanSeeTableRecords($budgets);
});

test('cannot see other users budgets', function () {
    $otherUser = User::factory()->create();
    $otherAccount = Account::factory()->create(['user_id' => $otherUser->id]);
    $otherCategory = Category::factory()->create(['user_id' => $otherUser->id]);

    $otherBudget = Budget::factory()->create([
        'user_id' => $otherUser->id,
        'account_id' => $otherAccount->id,
        'category_id' => $otherCategory->id,
    ]);
    $myBudget = Budget::factory()->create([
        'user_id' => $this->user->id,
        'account_id' => $this->account->id,
        'category_id' => $this->category->id,
    ]);

    Livewire::test(ListBudgets::class)
        ->assertCanSeeTableRecords([$myBudget])
        ->assertCanNotSeeTableRecords([$otherBudget]);
});

test('can create a budget', function () {
    Livewire::test(CreateBudget::class)
        ->fillForm([
            'category_id' => $this->category->id,
            'amount' => 10000,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('budgets', [
        'user_id' => $this->user->id,
        'account_id' => $this->account->id,
        'category_id' => $this->category->id,
    ]);
});

test('can edit a budget', function () {
    $budget = Budget::factory()->create([
        'user_id' => $this->user->id,
        'account_id' => $this->account->id,
        'category_id' => $this->category->id,
    ]);

    Livewire::test(EditBudget::class, ['record' => $budget->id])
        ->fillForm(['amount' => 15000])
        ->call('save')
        ->assertHasNoFormErrors();

    expect((float) $budget->refresh()->amount)->toBe(15000.0);
});

test('can delete a budget', function () {
    $budget = Budget::factory()->create([
        'user_id' => $this->user->id,
        'account_id' => $this->account->id,
        'category_id' => $this->category->id,
    ]);

    Livewire::test(EditBudget::class, ['record' => $budget->id])
        ->callAction(DeleteAction::class);

    $this->assertModelMissing($budget);
});
