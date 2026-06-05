<?php

namespace App\Filament\Pages;

use App\Filament\Schemas\AccountForm;
use App\Models\Account;
use App\Models\Transaction;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;

class RegisterAccount extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Add Account';
    }

    public function form(Schema $schema): Schema
    {
        return AccountForm::withOpeningBalance($schema);
    }

    protected function handleRegistration(array $data): Account
    {
        $openingBalance = (float) ($data['opening_balance'] ?? 0);
        unset($data['opening_balance']);

        $account = Account::create([
            ...$data,
            'user_id' => auth()->id(),
            'balance' => $openingBalance,
        ]);

        if ($openingBalance > 0) {
            Transaction::withoutEvents(function () use ($account, $openingBalance): void {
                Transaction::create([
                    'account_id' => $account->id,
                    'category_id' => null,
                    'type' => 'income',
                    'amount' => $openingBalance,
                    'description' => 'Balance rollover',
                    'transacted_at' => now()->toDateString(),
                    'recurrence' => null,
                ]);
            });
        }

        return $account;
    }
}
