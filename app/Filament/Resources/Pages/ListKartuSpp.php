<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\KartuSppResource;
use App\Imports\HistoriSppImport;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Url;
use Maatwebsite\Excel\Facades\Excel;

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
            Actions\Action::make('import_histori_spp')
                ->label('Import Histori SPP')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false)
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label('File Excel')
                        ->disk('local')
                        ->directory('imports')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                        ])
                        ->required(),
                    Forms\Components\Select::make('tahun_ajaran')
                        ->label('Tahun Ajaran Mulai')
                        ->options(fn (): array => array_combine(
                            range(now()->year, now()->year - 5),
                            range(now()->year, now()->year - 5),
                        ))
                        ->default(now()->year)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $path = Storage::disk('local')->path($data['file']);
                    $import = new HistoriSppImport((int) $data['tahun_ajaran']);

                    Excel::import($import, $path);

                    Notification::make()
                        ->title('Import histori SPP selesai')
                        ->body(count($import->getErrors()) > 0
                            ? 'Ada ' . count($import->getErrors()) . ' baris yang perlu dicek.'
                            : 'Histori SPP berhasil diimpor.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
