<?php

namespace App\Filament\Resources\Transactions\Pages;

use App\Filament\Resources\Transactions\TransactionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;

    public ?string $tableGrouping = 'transacted_at:desc';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
