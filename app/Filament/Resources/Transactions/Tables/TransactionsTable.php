<?php

namespace App\Filament\Resources\Transactions\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(static fn (Builder $query) => $query->with(['account', 'category']))
            ->columns([
                TextColumn::make('transacted_at')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('category.name')->searchable()->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->color(static fn (string $state) => match ($state) {
                        'income' => 'success',
                        'expense' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('amount')
                    ->money(static fn ($record) => $record->account->currency)
                    ->sortable(),
                TextColumn::make('description')
                    ->searchable()
                    ->limit(30)
                    ->toggleable(),
                TextColumn::make('recurrence')->badge()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('transacted_at', 'desc')
            ->groups([
                Group::make('transacted_at')
                    ->label('Date')
                    ->date()
                    ->collapsible(),
            ])
            ->filters([
                SelectFilter::make('type')->options([
                    'expense' => 'Expense',
                    'income' => 'Income',
                ]),
                SelectFilter::make('category')->relationship('category', 'name'),
            ])
            ->groupingSettingsHidden()
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                // BulkActionGroup::make([
                //     DeleteBulkAction::make(),
                // ]),
            ]);
    }
}
