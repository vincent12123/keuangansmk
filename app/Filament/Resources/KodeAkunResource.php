<?php

namespace App\Filament\Resources;

use App\Models\KodeAkun;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;

class KodeAkunResource extends Resource
{
    protected static ?string $model = KodeAkun::class;
    protected static string | \BackedEnum | null $navigationIcon  = 'heroicon-o-tag';
    protected static ?string $navigationLabel = 'Kode Akun';
    protected static string | \UnitEnum | null $navigationGroup = 'Master Data';
    protected static ?int    $navigationSort  = 1;
    protected static ?string $modelLabel      = 'Kode Akun';
    protected static ?string $pluralModelLabel = 'Kode Akun';

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Informasi Kode Akun')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('kode')
                        ->label('Kode Akun')
                        ->required()
                        ->maxLength(15)
                        ->unique(ignoreRecord: true)
                        ->placeholder('4.01.01.00')
                        ->helperText('Format: x.xx.xx.xx')
                        ->columnSpan(1),

                    Forms\Components\Select::make('tipe')
                        ->label('Tipe')
                        ->required()
                        ->options([
                            'pendapatan'   => 'Pendapatan (4.xx)',
                            'pengeluaran'  => 'Pengeluaran (5.xx)',
                        ])
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('nama')
                        ->label('Nama Akun')
                        ->required()
                        ->maxLength(150)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('kategori')
                        ->label('Kategori')
                        ->maxLength(60)
                        ->placeholder('PENERIMAAN PENDIDIKAN, BEBAN PEGAWAI')
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('sub_kategori')
                        ->label('Sub Kategori')
                        ->maxLength(60)
                        ->placeholder('Gaji, Operasional Harian')
                        ->columnSpan(1),
                ]),

            Section::make('Pengaturan')
                ->columns(2)
                ->schema([
                    Forms\Components\Toggle::make('aktif')
                        ->label('Aktif')
                        ->default(true)
                        ->helperText('Non-aktif = tidak muncul di dropdown transaksi'),

                    Forms\Components\Toggle::make('kas_kecil')
                        ->label('Digunakan di Kas Kecil')
                        ->default(false)
                        ->helperText('Centang jika kode ini sering muncul di petty cash'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama Akun')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\BadgeColumn::make('tipe')
                    ->label('Tipe')
                    ->colors([
                        'success' => 'pendapatan',
                        'danger'  => 'pengeluaran',
                    ])
                    ->formatStateUsing(fn ($state) => $state === 'pendapatan' ? 'Pendapatan' : 'Pengeluaran'),

                Tables\Columns\TextColumn::make('kategori')
                    ->label('Kategori')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('kas_kecil')
                    ->label('Kas Kecil')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('aktif')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipe')
                    ->label('Tipe Akun')
                    ->options([
                        'pendapatan'  => 'Pendapatan',
                        'pengeluaran' => 'Pengeluaran',
                    ]),

                Tables\Filters\TernaryFilter::make('aktif')
                    ->label('Status Aktif'),

                Tables\Filters\TernaryFilter::make('kas_kecil')
                    ->label('Untuk Kas Kecil'),
            ])
            ->actions([
                Actions\EditAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('kode')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListKodeAkun::route('/'),
            'create' => Pages\CreateKodeAkun::route('/create'),
            'edit'   => Pages\EditKodeAkun::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('aktif', true)->count();
    }
}
