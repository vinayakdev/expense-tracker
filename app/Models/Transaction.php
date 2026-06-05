<?php

namespace App\Models;

use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'account_id',
    'category_id',
    'type',
    'amount',
    'description',
    'transacted_at',
    'recurrence',
])]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'transacted_at' => 'date',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
