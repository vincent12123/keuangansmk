<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\JurnalKasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJurnalKas extends EditRecord
{
    protected static string $resource = JurnalKasResource::class;

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
        return JurnalKasResource::prepareFormDataBeforeSave($data);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
