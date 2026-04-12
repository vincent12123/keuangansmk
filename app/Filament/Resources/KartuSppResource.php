<?php

namespace App\Filament\Resources;

use App\Models\KartuSpp;
use App\Models\Siswa;
use App\Models\Jurusan;
use App\Models\Kelas;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Actions;
use Illuminate\Database\Eloquent\Builder;

class KartuSppResource extends Resource
{
    protected static ?string $model = KartuSpp::class;
    protected static string | \BackedEnum | null $navigationIcon  = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'Kartu SPP';
    protected static string | \UnitEnum | null $navigationGroup = 'Transaksi';
    protected static ?int    $navigationSort  = 3;
    protected static ?string $modelLabel      = 'Kartu SPP';
    protected static ?string $pluralModelLabel = 'Kartu SPP Siswa';

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\Section::make('Data Pembayaran SPP')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('nis')
                        ->label('NIS Siswa')
                        ->required()
                        ->maxLength(20)
                        ->live(debounce: 500)
                        ->afterStateUpdated(function ($state, Set $set) {
                            if (! $state) return;
                            $siswa = Siswa::where('nis', $state)->first();
                            if ($siswa) {
                                $set('nama_siswa_preview', $siswa->nama . ' — ' . $siswa->kelas->nama_kelas);
                                $set('nominal', $siswa->nominal_spp);
                            }
                        }),

                    Forms\Components\Placeholder::make('nama_siswa_preview')
                        ->label('Nama Siswa')
                        ->content(fn (Get $get): string => $get('nama_siswa_preview') ?? '-'),

                    Forms\Components\Select::make('bulan')
                        ->label('Bulan SPP')
                        ->required()
                        ->options([
                            1=>'Januari', 2=>'Februari', 3=>'Maret',
                            4=>'April',   5=>'Mei',       6=>'Juni',
                            7=>'Juli',    8=>'Agustus',   9=>'September',
                            10=>'Oktober',11=>'November', 12=>'Desember',
                        ])
                        ->default(now()->month),

                    Forms\Components\Select::make('tahun')
                        ->label('Tahun')
                        ->required()
                        ->options(function () {
                            $y = now()->year;
                            return array_combine(range($y, $y - 1), range($y, $y - 1));
                        })
                        ->default(now()->year),

                    Forms\Components\DatePicker::make('tgl_bayar')
                        ->label('Tanggal Bayar')
                        ->required()
                        ->default(today())
                        ->native(false)
                        ->displayFormat('d/m/Y'),

                    Forms\Components\TextInput::make('nominal')
                        ->label('Nominal Dibayar (Rp)')
                        ->required()
                        ->numeric()
                        ->prefix('Rp')
                        ->minValue(1),

                    Forms\Components\TextInput::make('keterangan')
                        ->label('Keterangan')
                        ->maxLength(100)
                        ->placeholder('SPP bulan Januari 2025')
                        ->columnSpanFull(),
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
                    ->fontFamily('mono')
                    ->sortable(),

                Tables\Columns\TextColumn::make('siswa.nama')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->getStateUsing(fn ($record) => Siswa::where('nis', $record->nis)->value('nama') ?? $record->nis),

                Tables\Columns\TextColumn::make('bulan')
                    ->label('Bulan')
                    ->formatStateUsing(fn ($state) => [
                        1=>'Januari', 2=>'Februari', 3=>'Maret',
                        4=>'April',   5=>'Mei',       6=>'Juni',
                        7=>'Juli',    8=>'Agustus',   9=>'September',
                        10=>'Oktober',11=>'November', 12=>'Desember',
                    ][$state] ?? $state)
                    ->sortable(),

                Tables\Columns\TextColumn::make('tahun')
                    ->label('Tahun')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tgl_bayar')
                    ->label('Tgl Bayar')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('nominal')
                    ->label('Nominal')
                    ->money('IDR')
                    ->alignEnd()
                    ->color('success'),

                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(30)
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('bulan')
                    ->label('Bulan')
                    ->options([
                        1=>'Januari', 2=>'Februari', 3=>'Maret',
                        4=>'April',   5=>'Mei',       6=>'Juni',
                        7=>'Juli',    8=>'Agustus',   9=>'September',
                        10=>'Oktober',11=>'November', 12=>'Desember',
                    ])
                    ->default(now()->month),

                Tables\Filters\SelectFilter::make('tahun')
                    ->label('Tahun')
                    ->options(function () {
                        $y = now()->year;
                        return array_combine(range($y, $y-1), range($y, $y-1));
                    })
                    ->default(now()->year),
            ])
            ->headerActions([
                Actions\Action::make('lihat_tunggakan')
                    ->label('Lihat Tunggakan')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('warning')
                    ->url(fn () => route('filament.admin.resources.kartu-spp.tunggakan')),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->defaultSort('tgl_bayar', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index'      => Pages\ListKartuSpp::route('/'),
            'create'     => Pages\CreateKartuSpp::route('/create'),
            'edit'       => Pages\EditKartuSpp::route('/{record}/edit'),
            'tunggakan'  => Pages\TunggakanSpp::route('/tunggakan'),
        ];
    }
}
