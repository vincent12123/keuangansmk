<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\JurnalKasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJurnalKas extends EditRecord
{
    protected static string $resource = JurnalKasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
