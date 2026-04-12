<?php

namespace App\Filament\Resources;

use App\Models\Siswa;
use App\Models\Jurusan;
use App\Models\Kelas;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;
use Illuminate\Database\Eloquent\Builder;

class SiswaResource extends Resource
{
    protected static ?string $model = Siswa::class;
    protected static string | \BackedEnum | null $navigationIcon  = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Data Siswa';
    protected static string | \UnitEnum | null $navigationGroup = 'Master Data';
    protected static ?int    $navigationSort  = 4;
    protected static ?string $modelLabel      = 'Siswa';

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Identitas Siswa')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('nis')
                        ->label('NIS')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(20)
                        ->placeholder('24010262'),

                    Forms\Components\TextInput::make('nama')
                        ->label('Nama Lengkap')
                        ->required()
                        ->maxLength(100)
                        ->columnSpan(1),

                    Forms\Components\Select::make('jurusan_id')
                        ->label('Jurusan')
                        ->required()
                        ->relationship('jurusan', 'nama')
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(fn ($state, Set $set) => $set('kelas_id', null)),

                    Forms\Components\Select::make('kelas_id')
                        ->label('Kelas')
                        ->required()
                        ->options(function (Get $get) {
                            $jurusanId = $get('jurusan_id');
                            if (! $jurusanId) return [];
                            return Kelas::where('jurusan_id', $jurusanId)
                                ->where('aktif', true)
                                ->pluck('nama_kelas', 'id');
                        })
                        ->searchable(),

                    Forms\Components\Select::make('angkatan')
                        ->label('Angkatan (Tahun Masuk)')
                        ->required()
                        ->options(function () {
                            $tahunIni = now()->year;
                            $options = [];
                            for ($t = $tahunIni; $t >= $tahunIni - 5; $t--) {
                                $options[$t] = $t . '/' . ($t + 1);
                            }
                            return $options;
                        }),

                    Forms\Components\TextInput::make('nominal_spp')
                        ->label('Nominal SPP per Bulan (Rp)')
                        ->required()
                        ->numeric()
                        ->default(400000)
                        ->prefix('Rp')
                        ->minValue(0),
                ]),

            Section::make('Data Wali & Kontak')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('nama_wali')
                        ->label('Nama Orang Tua / Wali')
                        ->maxLength(100),

                    Forms\Components\TextInput::make('no_hp_wali')
                        ->label('No HP Wali (WhatsApp)')
                        ->maxLength(20)
                        ->placeholder('0812xxxx / 628xxxx')
                        ->helperText('Format internasional (628xxx) untuk notifikasi WA'),
                ]),

            Section::make('Status')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label('Status Siswa')
                        ->required()
                        ->options([
                            'aktif'  => 'Aktif',
                            'alumni' => 'Alumni / Lulus',
                            'keluar' => 'Keluar',
                        ])
                        ->default('aktif'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nis')
                    ->label('NIS')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('kelas.nama_kelas')
                    ->label('Kelas')
                    ->sortable()
                    ->badge(),

                Tables\Columns\TextColumn::make('jurusan.kode')
                    ->label('Jurusan')
                    ->sortable(),

                Tables\Columns\TextColumn::make('angkatan')
                    ->label('Angkatan')
                    ->sortable(),

                Tables\Columns\TextColumn::make('nominal_spp')
                    ->label('SPP/Bulan')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('no_hp_wali')
                    ->label('HP Wali')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'aktif',
                        'gray'    => 'alumni',
                        'danger'  => 'keluar',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('jurusan_id')
                    ->label('Jurusan')
                    ->relationship('jurusan', 'nama'),

                Tables\Filters\SelectFilter::make('kelas_id')
                    ->label('Kelas')
                    ->relationship('kelas', 'nama_kelas'),

                Tables\Filters\SelectFilter::make('angkatan')
                    ->label('Angkatan')
                    ->options(function () {
                        return Siswa::distinct()->pluck('angkatan', 'angkatan')->toArray();
                    }),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'aktif'  => 'Aktif',
                        'alumni' => 'Alumni',
                        'keluar' => 'Keluar',
                    ]),
            ])
            ->actions([
                Actions\Action::make('kartu_spp')
                    ->label('Kartu SPP')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->url(fn (Siswa $record) => KartuSppResource::getUrl('index', ['nis' => $record->nis])),
                Actions\EditAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('nama')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSiswa::route('/'),
            'create' => Pages\CreateSiswa::route('/create'),
            'edit'   => Pages\EditSiswa::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::aktif()->count();
    }
}
