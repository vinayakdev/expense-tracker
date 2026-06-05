<?php

namespace App\Filament\Resources\Budgets\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BudgetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category.name')
                    ->label('Category')
                    ->formatStateUsing(
                        static fn($state, $record) => (
                            ($record->category?->icon ? $record->category->icon . ' ' : '') . $state
                        ),
                    )
                    ->color(static fn($record) => $record->category?->color)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->money(static fn($record) => $record->account->currency ?? auth()->user()->reporting_currency)
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('category.name')
            ->filters([])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
