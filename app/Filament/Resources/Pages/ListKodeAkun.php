<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\KodeAkunResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKodeAkun extends ListRecords
{
    protected static string $resource = KodeAkunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
