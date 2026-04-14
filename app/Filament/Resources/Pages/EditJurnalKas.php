<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\JurnalKasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditJurnalKas extends EditRecord
{
    protected static string $resource = JurnalKasResource::class;

    protected array $pendingSppMonths = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['bulan_spp'] = $this->record->kartuSpp()
            ->pluck('bulan')
            ->sort()
            ->values()
            ->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingSppMonths = JurnalKasResource::extractPendingSppMonths($data);

        return JurnalKasResource::prepareFormDataBeforeSave($data);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->bulanSppPending = $this->pendingSppMonths;
        $record->fill($data);
        $record->save();

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
