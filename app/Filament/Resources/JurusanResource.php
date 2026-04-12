<?php

namespace App\Filament\Resources;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\KodeAkun;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;

// ─── JurusanResource ─────────────────────────────────────────
class JurusanResource extends Resource
{
    protected static ?string $model = Jurusan::class;
    protected static string | \BackedEnum | null $navigationIcon  = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Jurusan';
    protected static string | \UnitEnum | null $navigationGroup = 'Master Data';
    protected static ?int    $navigationSort  = 2;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('kode')
                    ->label('Kode Jurusan')
                    ->required()
                    ->maxLength(10)
                    ->placeholder('RPL')
                    ->unique(ignoreRecord: true),

                Forms\Components\TextInput::make('nama')
                    ->label('Nama Jurusan')
                    ->required()
                    ->maxLength(100)
                    ->placeholder('Rekayasa Perangkat Lunak'),

                Forms\Components\Select::make('kode_akun')
                    ->label('Kode Akun SPP')
                    ->required()
                    ->options(fn () => KodeAkun::where('tipe', 'pendapatan')
                        ->where('aktif', true)
                        ->whereRaw("RIGHT(kode, 2) != '00'")
                        ->orderBy('kode')
                        ->pluck('nama', 'kode')
                        ->mapWithKeys(fn ($nama, $kode) => [$kode => "[$kode] $nama"]))
                    ->searchable()
                    ->helperText('Kode akun yang dipakai saat siswa jurusan ini bayar SPP'),

                Forms\Components\Toggle::make('aktif')
                    ->label('Aktif')
                    ->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode')->label('Kode')->badge()->sortable(),
                Tables\Columns\TextColumn::make('nama')->label('Nama Jurusan')->searchable(),
                Tables\Columns\TextColumn::make('kode_akun')->label('Kode Akun SPP')->fontFamily('mono'),
                Tables\Columns\TextColumn::make('siswa_count')
                    ->label('Jml Siswa')
                    ->counts('siswa')
                    ->sortable(),
                Tables\Columns\IconColumn::make('aktif')->boolean(),
            ])
            ->actions([Actions\EditAction::make()])
            ->defaultSort('kode');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListJurusan::route('/'),
            'create' => Pages\CreateJurusan::route('/create'),
            'edit'   => Pages\EditJurusan::route('/{record}/edit'),
        ];
    }
}
