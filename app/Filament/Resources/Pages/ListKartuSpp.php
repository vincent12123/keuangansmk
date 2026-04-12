<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\KartuSppResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

class ListKartuSpp extends ListRecords
{
    protected static string $resource = KartuSppResource::class;

    #[Url]
    public ?string $nis = null;

    public function table(Table $table): Table
    {
        return $table->modifyQueryUsing(function (Builder $query): Builder {
            if (blank($this->nis)) {
                return $query;
            }

            return $query->where('nis', $this->nis);
        });
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
