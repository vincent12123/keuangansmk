<?php

namespace App\Filament\Resources;

use App\Models\Kelas;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;

class KelasResource extends Resource
{
    protected static ?string $model = Kelas::class;
    protected static string | \BackedEnum | null $navigationIcon  = 'heroicon-o-building-library';
    protected static ?string $navigationLabel = 'Kelas';
    protected static string | \UnitEnum | null $navigationGroup = 'Master Data';
    protected static ?int    $navigationSort  = 3;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->columns(2)->schema([
                Forms\Components\Select::make('jurusan_id')
                    ->label('Jurusan')
                    ->required()
                    ->relationship('jurusan', 'nama')
                    ->searchable()
                    ->preload(),

                Forms\Components\Select::make('tingkat')
                    ->label('Tingkat')
                    ->required()
                    ->options(['X' => 'X (Sepuluh)', 'XI' => 'XI (Sebelas)', 'XII' => 'XII (Dua Belas)']),

                Forms\Components\TextInput::make('nama_kelas')
                    ->label('Nama Kelas')
                    ->required()
                    ->maxLength(30)
                    ->placeholder('X RPL'),

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
                Tables\Columns\TextColumn::make('nama_kelas')->label('Kelas')->sortable()->badge(),
                Tables\Columns\TextColumn::make('jurusan.nama')->label('Jurusan')->sortable(),
                Tables\Columns\TextColumn::make('tingkat')->label('Tingkat')->sortable(),
                Tables\Columns\TextColumn::make('siswa_count')->label('Jml Siswa')->counts('siswa'),
                Tables\Columns\IconColumn::make('aktif')->boolean(),
            ])
            ->actions([Actions\EditAction::make()])
            ->defaultSort('nama_kelas');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListKelas::route('/'),
            'create' => Pages\CreateKelas::route('/create'),
            'edit'   => Pages\EditKelas::route('/{record}/edit'),
        ];
    }
}
