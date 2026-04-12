<?php

namespace App\Filament\Resources;

use App\Models\KasKecil;
use App\Models\KodeAkun;
use App\Models\PengisianKasKecil;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;
use Illuminate\Database\Eloquent\Builder;

class KasKecilResource extends Resource
{
    protected static ?string $model = KasKecil::class;
    protected static string | \BackedEnum | null $navigationIcon  = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Kas Kecil';
    protected static string | \UnitEnum | null $navigationGroup = 'Transaksi';
    protected static ?int    $navigationSort  = 2;
    protected static ?string $modelLabel      = 'Pengeluaran Kas Kecil';
    protected static ?string $pluralModelLabel = 'Kas Kecil (Petty Cash)';

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\Section::make('Detail Pengeluaran')
                ->columns(3)
                ->schema([
                    Forms\Components\DatePicker::make('tanggal')
                        ->label('Tanggal')
                        ->required()
                        ->default(today())
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('no_ref')
                        ->label('No. Referensi')
                        ->placeholder('Otomatis: K25-0001')
                        ->maxLength(20)
                        ->helperText('Kosongkan untuk auto-generate')
                        ->columnSpan(1),

                    Forms\Components\Select::make('kode_akun_id')
                        ->label('Kode Akun')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            return KodeAkun::where('tipe', 'pengeluaran')
                                ->where('aktif', true)
                                ->whereRaw("RIGHT(kode, 2) != '00'")
                                ->orderBy('kode')
                                ->get()
                                ->mapWithKeys(fn ($k) => [
                                    $k->id => "[{$k->kode}] {$k->nama}"
                                ]);
                        })
                        ->columnSpan(1),

                    Forms\Components\Textarea::make('uraian')
                        ->label('Uraian / Keterangan')
                        ->required()
                        ->rows(2)
                        ->placeholder('Bensin motor yayasan, ATK untuk kegiatan, dll.')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('nominal')
                        ->label('Nominal (Rp)')
                        ->required()
                        ->numeric()
                        ->minValue(1)
                        ->prefix('Rp')
                        ->columnSpan(1),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('no_ref')
                    ->label('No. Ref')
                    ->fontFamily('mono')
                    ->searchable(),

                Tables\Columns\TextColumn::make('kodeAkun.kode')
                    ->label('Kode')
                    ->fontFamily('mono')
                    ->sortable(),

                Tables\Columns\TextColumn::make('kodeAkun.nama')
                    ->label('Akun')
                    ->limit(30)
                    ->searchable(),

                Tables\Columns\TextColumn::make('uraian')
                    ->label('Uraian')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->uraian)
                    ->searchable(),

                Tables\Columns\TextColumn::make('nominal')
                    ->label('Nominal')
                    ->money('IDR')
                    ->alignEnd()
                    ->sortable()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('bulan')
                    ->label('Bln')
                    ->formatStateUsing(fn ($state) => [
                        1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',
                        7=>'Jul',8=>'Agt',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des',
                    ][$state] ?? $state)
                    ->sortable(),

                Tables\Columns\TextColumn::make('tahun')
                    ->label('Tahun')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Input oleh')
                    ->toggleable(isToggledHiddenByDefault: true),
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
                        return array_combine(range($y, $y - 2), range($y, $y - 2));
                    })
                    ->default(now()->year),

                Tables\Filters\SelectFilter::make('kode_akun_id')
                    ->label('Kode Akun')
                    ->searchable()
                    ->options(fn () => KodeAkun::where('kas_kecil', true)
                        ->where('aktif', true)
                        ->orderBy('kode')
                        ->pluck('nama', 'id')),
            ])
            ->headerActions([
                // Ringkasan saldo kas kecil
                Tables\Actions\Action::make('saldo_info')
                    ->label(function () {
                        $bulan  = now()->month;
                        $tahun  = now()->year;
                        $pengisian = PengisianKasKecil::where('bulan', $bulan)
                            ->where('tahun', $tahun)
                            ->sum('nominal');
                        $keluar = KasKecil::where('bulan', $bulan)
                            ->where('tahun', $tahun)
                            ->sum('nominal');
                        $saldo = $pengisian - $keluar;
                        return 'Pengisian: Rp ' . number_format($pengisian,0,',','.')
                            . ' | Keluar: Rp ' . number_format($keluar,0,',','.')
                            . ' | Saldo: Rp ' . number_format($saldo,0,',','.');
                    })
                    ->disabled()
                    ->color('gray'),

                Tables\Actions\Action::make('pengisian')
                    ->label('+ Pengisian Kas Kecil')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal Pengisian')
                            ->required()
                            ->default(today())
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\TextInput::make('nominal')
                            ->label('Nominal Pengisian (Rp)')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->prefix('Rp'),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->rows(2),
                    ])
                    ->action(function (array $data) {
                        PengisianKasKecil::create([
                            'tanggal'     => $data['tanggal'],
                            'nominal'     => $data['nominal'],
                            'keterangan'  => $data['keterangan'] ?? null,
                            'bulan'       => Carbon::parse($data['tanggal'])->month,
                            'tahun'       => Carbon::parse($data['tanggal'])->year,
                            'created_by'  => auth()->id(),
                        ]);
                    })
                    ->successNotificationTitle('Pengisian kas kecil berhasil dicatat'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('tanggal', 'desc')
            ->striped()
            ->paginated([25, 50, 100]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['kodeAkun', 'createdBy']);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListKasKecil::route('/'),
            'create' => Pages\CreateKasKecil::route('/create'),
            'edit'   => Pages\EditKasKecil::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return number_format(
            static::getModel()::where('bulan', now()->month)
                ->where('tahun', now()->year)
                ->count()
        );
    }
}
