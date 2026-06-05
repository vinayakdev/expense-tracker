<?php

namespace App\Filament\Resources\Budgets\Schemas;

use App\Models\Category;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BudgetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('category_id')
                ->label('Category')
                ->options(
                    static fn() => Category::where('user_id', auth()->id())
                        ->where('type', 'expense')
                        ->pluck('name', 'id'),
                )
                ->required()
                ->searchable(),
            TextInput::make('amount')
                ->required()
                ->numeric()
                ->minValue(0)
                ->prefix(static fn() => self::currency()),
        ]);
    }

    private static function currency(): string
    {
        return Filament::getTenant()->currency ?? auth()->user()->reporting_currency ?? '$';
    }

    private static function accountBadge(): string
    {
        $account = Filament::getTenant();

        if (!$account) {
            return '<span class="text-sm text-gray-500">No active account</span>';
        }

        return sprintf(
            '<span class="text-sm font-medium">%s</span>'
            . '<span class="ml-2 inline-flex items-center rounded-md bg-gray-100 dark:bg-white/10 px-2 py-0.5 text-xs font-medium text-gray-600 dark:text-gray-300">%s</span>',
            e($account->name),
            e($account->currency),
        );
    }
}
