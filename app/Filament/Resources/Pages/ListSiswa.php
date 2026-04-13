<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\SiswaResource;
use App\Imports\SiswaImport;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ListSiswa extends ListRecords
{
    protected static string $resource = SiswaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('import_siswa')
                ->label('Import Excel')
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
                    Forms\Components\Select::make('angkatan')
                        ->label('Angkatan')
                        ->options(fn (): array => array_combine(
                            range(now()->year, now()->year - 5),
                            range(now()->year, now()->year - 5),
                        ))
                        ->default(now()->year)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $path = Storage::disk('local')->path($data['file']);
                    $import = new SiswaImport((int) $data['angkatan']);

                    Excel::import($import, $path);

                    Notification::make()
                        ->title('Import siswa selesai')
                        ->body(count($import->getErrors()) > 0
                            ? 'Ada ' . count($import->getErrors()) . ' baris yang perlu dicek.'
                            : 'Data siswa berhasil diimpor.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
