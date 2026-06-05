<?php

namespace App\Filament\Resources\Transactions\Schemas;

use App\Models\Category;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class TransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                ToggleButtons::make('type')
                    ->required()
                    ->options([
                        'expense' => 'Expense',
                        'income' => 'Income',
                    ])
                    ->colors([
                        'expense' => 'danger',
                        'income' => 'success',
                    ])
                    ->icons([
                        'expense' => Heroicon::OutlinedArrowTrendingDown,
                        'income' => Heroicon::OutlinedArrowTrendingUp,
                    ])
                    ->grouped()
                    ->live()
                    ->default('expense'),
                Select::make('category_id')
                    ->label('Category')
                    ->options(fn () => self::categoryOptions())
                    ->allowHtml()
                    ->required()
                    ->searchable()
                    ->live(),
                View::make('filament.forms.components.budget-meter')
                    ->visible(fn (callable $get) => $get('category_id') && $get('type') === 'expense'),
                Grid::make(2)
                    ->schema([
                        TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->minValue(0),
                        DatePicker::make('transacted_at')
                            ->label('Date')
                            ->required()
                            ->default(now()),
                    ]),
                TextInput::make('description')
                    ->nullable()
                    ->maxLength(255),
                Select::make('recurrence')
                    ->label('Repeat')
                    ->default(null)
                    ->placeholder('None (one-time)')
                    ->options([
                        'weekly' => 'Weekly',
                        'monthly' => 'Monthly',
                        'yearly' => 'Yearly',
                    ])
                    ->nullable(),
            ]);
    }

    /** @return array<int|string, string> */
    private static function categoryOptions(): array
    {
        return Category::where('user_id', auth()->id())
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function (Category $category) {
                $dot = sprintf(
                    '<span style="display:inline-block;width:10px;height:10px;border-radius:50%%;background:%s;flex-shrink:0;margin-right:6px"></span>',
                    e($category->color ?? '#6366f1'),
                );
                $emoji = $category->icon ? '<span style="margin-right:4px">'.e($category->icon).'</span>' : '';
                $name = e($category->name);
                $typeLabel = $category->type === 'income'
                    ? '<span style="font-size:10px;color:#9ca3af;margin-left:6px">'.e(ucfirst($category->type)).'</span>'
                    : '';

                return [
                    $category->id => '<span style="display:inline-flex;align-items:center">'
                        .$dot.$emoji.$name.$typeLabel
                        .'</span>',
                ];
            })
            ->toArray();
    }
}
