<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\KodeAkunResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKodeAkun extends EditRecord
{
    protected static string $resource = KodeAkunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
