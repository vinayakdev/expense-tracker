<?php

namespace App\Filament\Resources\Transactions\Schemas;

use App\Models\Category;
use App\Models\Transaction;
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
                    ->afterStateUpdated(function (?string $state, ?string $old, callable $set) {
                        if ($state !== $old) {
                            $set('category_id', null);
                        }
                    })
                    ->default('expense'),
                Select::make('category_id')
                    ->label('Category')
                    ->options(fn (callable $get) => self::categoryOptions($get('type') ?? 'expense'))
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
                            ->default(now())
                            ->live(onBlur: true),
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

    /** @return array<string, array<int, string>> */
    private static function categoryOptions(string $type): array
    {
        $categories = Category::where('user_id', auth()->id())
            ->where('type', $type)
            ->orderBy('name')
            ->get();

        $thirtyDaysAgo = now()->subDays(30)->toDateString();
        $sixtyDaysAgo = now()->subDays(60)->toDateString();

        $scores = Transaction::selectRaw(
            'category_id, SUM(CASE WHEN transacted_at >= ? THEN 3 WHEN transacted_at >= ? THEN 2 ELSE 1 END) as score',
            [$thirtyDaysAgo, $sixtyDaysAgo],
        )
            ->whereIn('category_id', $categories->pluck('id'))
            ->whereNotNull('transacted_at')
            ->groupBy('category_id')
            ->pluck('score', 'category_id');

        $renderOption = function (Category $category): string {
            $dot = sprintf(
                '<span style="display:inline-block;width:10px;height:10px;border-radius:50%%;background:%s;flex-shrink:0;margin-left:6px"></span>',
                e($category->color ?? '#6366f1'),
            );
            $emoji = $category->icon ? '<span style="margin-right:4px">'.e($category->icon).'</span>' : '';

            return '<span style="display:inline-flex;align-items:center;justify-content:space-between;width:100%%">'.
                '<span>'.$emoji.e($category->name).'</span>'.
                $dot.
                '</span>';
        };

        $topIds = $categories
            ->filter(fn (Category $c) => $scores->get($c->id, 0) > 0)
            ->sortByDesc(fn (Category $c) => $scores->get($c->id, 0))
            ->take(5)
            ->pluck('id')
            ->all();

        $result = [];

        if (! empty($topIds)) {
            $result['Top Picks'] = $categories
                ->filter(fn (Category $c) => in_array($c->id, $topIds, true))
                ->sortByDesc(fn (Category $c) => $scores->get($c->id, 0))
                ->mapWithKeys(fn (Category $c) => [$c->id => $renderOption($c)])
                ->toArray();
        }

        $remaining = $categories
            ->filter(fn (Category $c) => ! in_array($c->id, $topIds, true))
            ->mapWithKeys(fn (Category $c) => [$c->id => $renderOption($c)])
            ->toArray();

        if (! empty($remaining)) {
            $result[ucfirst($type)] = $remaining;
        }

        return $result;
    }
}
