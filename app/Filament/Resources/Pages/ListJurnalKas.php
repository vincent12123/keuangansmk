<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\JurnalKasResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJurnalKas extends ListRecords
{
    protected static string $resource = JurnalKasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
