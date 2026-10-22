<?php

namespace App\Filament\Resources\FraudFlags\Pages;

use App\Filament\Resources\FraudFlags\FraudFlagResource;
use Filament\Resources\Pages\ListRecords;

class ListFraudFlags extends ListRecords
{
    protected static string $resource = FraudFlagResource::class;
}
