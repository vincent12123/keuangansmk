<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\KasKecilResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKasKecil extends CreateRecord
{
    protected static string $resource = KasKecilResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return KasKecilResource::prepareFormDataBeforeSave($data);
    }
}
