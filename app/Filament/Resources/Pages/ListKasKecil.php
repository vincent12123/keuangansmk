<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\KasKecilResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKasKecil extends ListRecords
{
    protected static string $resource = KasKecilResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
