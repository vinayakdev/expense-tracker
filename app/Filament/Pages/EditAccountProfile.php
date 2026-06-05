<?php

namespace App\Filament\Pages;

use App\Filament\Schemas\AccountForm;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Schema;

class EditAccountProfile extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return 'Edit Account';
    }

    public function form(Schema $schema): Schema
    {
        return AccountForm::configure($schema);
    }
}
