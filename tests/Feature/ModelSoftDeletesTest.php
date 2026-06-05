<?php

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

test('models are soft deleted', function (string $modelClass) {
    /** @var Model $model */
    $model = $modelClass::factory()->create();

    $model->delete();

    $this->assertSoftDeleted($model);
    expect($modelClass::find($model->getKey()))->toBeNull()
        ->and($modelClass::withTrashed()->find($model->getKey())->trashed())->toBeTrue();
})->with([
    'user' => User::class,
    'account' => Account::class,
    'budget' => Budget::class,
    'category' => Category::class,
    'transaction' => Transaction::class,
]);
