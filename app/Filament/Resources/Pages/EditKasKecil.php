<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\KasKecilResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKasKecil extends EditRecord
{
    protected static string $resource = KasKecilResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return KasKecilResource::prepareFormDataBeforeSave($data);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
