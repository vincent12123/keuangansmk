<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\JurnalKasResource;
use Filament\Resources\Pages\CreateRecord;

class CreateJurnalKas extends CreateRecord
{
    protected static string $resource = JurnalKasResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return JurnalKasResource::prepareFormDataBeforeSave($data);
    }
}
