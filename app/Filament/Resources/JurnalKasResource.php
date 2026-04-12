<?php

namespace App\Filament\Resources;

use App\Models\JurnalKas;
use App\Models\KodeAkun;
use App\Models\Siswa;
use App\Models\Kelas;
use App\Models\KartuSpp;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class JurnalKasResource extends Resource
{
    protected static ?string $model = JurnalKas::class;
    protected static string | \BackedEnum | null $navigationIcon  = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'Jurnal Cash & Bank';
    protected static string | \UnitEnum | null $navigationGroup = 'Transaksi';
    protected static ?int    $navigationSort  = 1;
    protected static ?string $modelLabel      = 'Transaksi';
    protected static ?string $pluralModelLabel = 'Jurnal Cash & Bank';

    public static function form(Schema $form): Schema
    {
        return $form->schema([

            Forms\Components\Section::make('Informasi Transaksi')
                ->columns(3)
                ->schema([
                    Forms\Components\DatePicker::make('tanggal')
                        ->label('Tanggal')
                        ->required()
                        ->default(today())
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('no_kwitansi')
                        ->label('No. Kwitansi')
                        ->maxLength(20)
                        ->placeholder('005056')
                        ->helperText('Kosongkan untuk pengeluaran / otomatis')
                        ->columnSpan(1),

                    Forms\Components\Select::make('kode_akun_id')
                        ->label('Kode Akun')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            return KodeAkun::aktif()
                                ->whereRaw("RIGHT(kode, 2) != '00'")
                                ->orderBy('kode')
                                ->get()
                                ->mapWithKeys(fn ($k) => [
                                    $k->id => "[{$k->kode}] {$k->nama}"
                                ]);
                        })
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set) {
                            if (! $state) return;
                            $kode = KodeAkun::find($state);
                            if (! $kode) return;
                            // Jika pengeluaran, kosongkan NIS
                            if ($kode->tipe === 'pengeluaran') {
                                $set('nis', null);
                                $set('nama_penyetor', null);
                                $set('kelas_id', null);
                            }
                        })
                        ->columnSpan(1),
                ]),

            Forms\Components\Section::make('Data Penyetor / Penerima')
                ->columns(3)
                ->description('Isi untuk transaksi penerimaan SPP dari siswa')
                ->schema([
                    Forms\Components\TextInput::make('nis')
                        ->label('NIS Siswa')
                        ->maxLength(20)
                        ->live(debounce: 500)
                        ->afterStateUpdated(function ($state, Set $set) {
                            if (! $state) return;
                            $siswa = Siswa::where('nis', $state)->first();
                            if ($siswa) {
                                $set('nama_penyetor', $siswa->nama);
                                $set('kelas_id', $siswa->kelas_id);
                            }
                        })
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('nama_penyetor')
                        ->label('Nama Penyetor / Vendor')
                        ->maxLength(100)
                        ->columnSpan(1),

                    Forms\Components\Select::make('kelas_id')
                        ->label('Kelas')
                        ->searchable()
                        ->preload()
                        ->relationship('kelas', 'nama_kelas')
                        ->columnSpan(1),
                ]),

            Forms\Components\Section::make('Detail Transaksi')
                ->columns(3)
                ->schema([
                    Forms\Components\Textarea::make('uraian')
                        ->label('Uraian Transaksi')
                        ->required()
                        ->rows(2)
                        ->placeholder('PENERIMAAN SPP JANUARI / Pembayaran Gaji Guru')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('cash')
                        ->label('Nominal Cash (Rp)')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->prefix('Rp')
                        ->live(debounce: 300)
                        ->helperText('Uang tunai yang diterima/dibayar')
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('bank')
                        ->label('Nominal Bank (Rp)')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->prefix('Rp')
                        ->live(debounce: 300)
                        ->helperText('Transfer bank / rekening')
                        ->columnSpan(1),

                    Forms\Components\Placeholder::make('total_preview')
                        ->label('Total')
                        ->content(function (Get $get): string {
                            $total = ((float) $get('cash')) + ((float) $get('bank'));
                            return 'Rp ' . number_format($total, 0, ',', '.');
                        })
                        ->columnSpan(1),
                ]),

            // Section khusus: auto-update Kartu SPP
            Forms\Components\Section::make('Kartu SPP (opsional)')
                ->description('Jika transaksi ini adalah pembayaran SPP, pilih bulan yang dibayar')
                ->collapsed()
                ->schema([
                    Forms\Components\CheckboxList::make('bulan_spp')
                        ->label('Bulan SPP yang Dibayar')
                        ->options([
                            1  => 'Januari',  2  => 'Februari', 3  => 'Maret',
                            4  => 'April',    5  => 'Mei',       6  => 'Juni',
                            7  => 'Juli',     8  => 'Agustus',   9  => 'September',
                            10 => 'Oktober',  11 => 'November',  12 => 'Desember',
                        ])
                        ->columns(4)
                        ->helperText('Centang bulan-bulan yang tercakup dalam pembayaran ini')
                        ->dehydrated(false), // tidak disimpan langsung ke DB
                ])
                ->visible(function (Get $get): bool {
                    if (! $get('kode_akun_id')) return false;
                    $kode = KodeAkun::find($get('kode_akun_id'));
                    // Tampilkan hanya jika kode akun adalah SPP (4.01.01 / 4.01.02 / 4.01.03)
                    return $kode && in_array($kode->kode, [
                        '4.01.01.00', '4.01.02.00', '4.01.03.00', '4.01.06.00',
                    ]);
                }),
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

                Tables\Columns\TextColumn::make('no_kwitansi')
                    ->label('No. Kwitansi')
                    ->searchable()
                    ->fontFamily('mono')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('kodeAkun.kode')
                    ->label('Kode')
                    ->fontFamily('mono')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('kodeAkun.nama')
                    ->label('Akun')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('nama_penyetor')
                    ->label('Nama / Siswa')
                    ->searchable()
                    ->limit(25)
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('kelas.nama_kelas')
                    ->label('Kelas')
                    ->badge()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('uraian')
                    ->label('Uraian')
                    ->limit(35)
                    ->tooltip(fn ($record) => $record->uraian)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('cash')
                    ->label('Cash')
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd()
                    ->color(fn ($record) => $record->jenis === 'masuk' ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('bank')
                    ->label('Bank')
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd()
                    ->color(fn ($record) => $record->jenis === 'masuk' ? 'success' : 'danger'),

                Tables\Columns\BadgeColumn::make('jenis')
                    ->label('Jenis')
                    ->colors([
                        'success' => 'masuk',
                        'danger'  => 'keluar',
                    ])
                    ->formatStateUsing(fn ($state) => $state === 'masuk' ? 'Masuk' : 'Keluar'),

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
                        $tahunIni = now()->year;
                        return array_combine(
                            range($tahunIni, $tahunIni - 2),
                            range($tahunIni, $tahunIni - 2)
                        );
                    })
                    ->default(now()->year),

                Tables\Filters\SelectFilter::make('jenis')
                    ->label('Jenis')
                    ->options(['masuk' => 'Masuk', 'keluar' => 'Keluar']),

                Tables\Filters\SelectFilter::make('kode_akun_id')
                    ->label('Kode Akun')
                    ->searchable()
                    ->options(fn () => KodeAkun::aktif()
                        ->whereRaw("RIGHT(kode, 2) != '00'")
                        ->orderBy('kode')
                        ->pluck('nama', 'id')),
            ])
            ->headerActions([
                // Ringkasan bulan ini di header tabel
                Actions\Action::make('ringkasan')
                    ->label(function () {
                        $bulan = now()->month;
                        $tahun = now()->year;
                        $masuk  = JurnalKas::where('bulan', $bulan)->where('tahun', $tahun)->where('jenis', 'masuk')->selectRaw('SUM(cash+bank) as total')->value('total') ?? 0;
                        $keluar = JurnalKas::where('bulan', $bulan)->where('tahun', $tahun)->where('jenis', 'keluar')->selectRaw('SUM(cash+bank) as total')->value('total') ?? 0;
                        return 'Bulan ini: Masuk ' . 'Rp ' . number_format($masuk,0,',','.') . ' | Keluar Rp ' . number_format($keluar,0,',','.');
                    })
                    ->disabled()
                    ->color('gray'),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('tanggal', 'desc')
            ->striped()
            ->paginated([25, 50, 100]);
    }

    // ─── Summary Footer ───────────────────────────────────────
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['kodeAkun', 'kelas', 'createdBy']);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListJurnalKas::route('/'),
            'create' => Pages\CreateJurnalKas::route('/create'),
            'edit'   => Pages\EditJurnalKas::route('/{record}/edit'),
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

    public static function getNavigationBadgeColor(): string
    {
        return 'info';
    }
}
