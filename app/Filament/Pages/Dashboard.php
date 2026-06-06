<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;

class Dashboard extends BaseDashboard
{
    protected function getHeaderActions(): array
    {
        return [
            Action::make('newTransaction')
                ->label('New Transaction')
                ->icon(Heroicon::OutlinedPlus)
                ->url(fn (): string => route('transactions.create', ['account' => Filament::getTenant()]))
                ->color('primary'),
        ];
    }
}
