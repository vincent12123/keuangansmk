<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\JurnalKasResource;
use App\Models\JurnalKas;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateJurnalKas extends CreateRecord
{
    protected static string $resource = JurnalKasResource::class;

    protected array $pendingSppMonths = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingSppMonths = JurnalKasResource::extractPendingSppMonths($data);

        return JurnalKasResource::prepareFormDataBeforeSave($data);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $record = new JurnalKas($data);
        $record->bulanSppPending = $this->pendingSppMonths;
        $record->save();

        return $record;
    }
}
