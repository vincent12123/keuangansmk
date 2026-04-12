<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\KartuSppResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKartuSpp extends ListRecords
{
    protected static string $resource = KartuSppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
