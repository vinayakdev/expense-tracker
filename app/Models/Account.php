<?php

namespace App\Models;

use Database\Factories\AccountFactory;
use Filament\Models\Contracts\HasCurrentTenantLabel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'name', 'currency', 'balance'])]
class Account extends Model implements HasCurrentTenantLabel
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:4',
        ];
    }

    public function getCurrentTenantLabel(): string
    {
        return 'Active account';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
