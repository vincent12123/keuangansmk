<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\KartuSppResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKartuSpp extends EditRecord
{
    protected static string $resource = KartuSppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
