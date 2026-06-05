<?php

namespace App\Filament\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->default('Account 1')
                    ->maxLength(255),
                Select::make('currency')
                    ->required()
                    ->searchable()
                    ->options(self::currencies())
                    ->disabledOn('edit'),
            ]);
    }

    public static function withOpeningBalance(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->default('Account 1')
                    ->maxLength(255),
                Select::make('currency')
                    ->required()
                    ->searchable()
                    ->options(self::currencies()),
                TextInput::make('opening_balance')
                    ->label('Opening Balance')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->prefix(fn ($get) => $get('currency') ?: ''),
            ]);
    }

    /** @return array<string, string> */
    public static function currencies(): array
    {
        return [
            'INR' => 'INR – Indian Rupee',
            'USD' => 'USD – US Dollar',
            'EUR' => 'EUR – Euro',
            'GBP' => 'GBP – British Pound',
            'JPY' => 'JPY – Japanese Yen',
            'AUD' => 'AUD – Australian Dollar',
            'CAD' => 'CAD – Canadian Dollar',
            'CHF' => 'CHF – Swiss Franc',
            'CNY' => 'CNY – Chinese Yuan',
            'SGD' => 'SGD – Singapore Dollar',
            'AED' => 'AED – UAE Dirham',
        ];
    }
}
