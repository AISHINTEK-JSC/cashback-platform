<?php

namespace App\Filament\Resources\UserLevels\Pages;

use App\Filament\Resources\UserLevels\UserLevelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageUserLevels extends ManageRecords
{
    protected static string $resource = UserLevelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
