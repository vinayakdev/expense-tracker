<?php

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->account = Account::factory()->create(['user_id' => $this->user->id]);
    actingAs($this->user);
    Filament::setTenant($this->account);
});

test('can list categories', function () {
    $categories = Category::factory(3)->create(['user_id' => $this->user->id]);

    Livewire::test(ListCategories::class)
        ->assertCanSeeTableRecords($categories);
});

test('cannot see other users categories', function () {
    $otherUser = User::factory()->create();
    $otherCategory = Category::factory()->create(['user_id' => $otherUser->id]);
    $myCategory = Category::factory()->create(['user_id' => $this->user->id]);

    Livewire::test(ListCategories::class)
        ->assertCanSeeTableRecords([$myCategory])
        ->assertCanNotSeeTableRecords([$otherCategory]);
});

test('can create a category', function () {
    Livewire::test(CreateCategory::class)
        ->fillForm([
            'name' => 'Food',
            'type' => 'expense',
            'color' => '#ff0000',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('categories', [
        'user_id' => $this->user->id,
        'name' => 'Food',
        'type' => 'expense',
    ]);
});

test('can edit a category', function () {
    $category = Category::factory()->create(['user_id' => $this->user->id]);

    Livewire::test(EditCategory::class, ['record' => $category->id])
        ->fillForm(['name' => 'Updated Category'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($category->refresh()->name)->toBe('Updated Category');
});

test('can delete a category', function () {
    $category = Category::factory()->create(['user_id' => $this->user->id]);

    Livewire::test(EditCategory::class, ['record' => $category->id])
        ->callAction(DeleteAction::class);

    $this->assertModelMissing($category);
});
