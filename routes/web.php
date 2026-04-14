<?php

use App\Http\Controllers\PdfController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/admin/print/kwitansi/{jurnalKas}', [PdfController::class, 'kwitansi'])
        ->name('pdf.kwitansi');
    Route::get('/admin/print/arus-kas', [PdfController::class, 'arusKas'])
        ->name('pdf.arus-kas');
    Route::get('/admin/print/rekap-kas-kecil', [PdfController::class, 'rekapKasKecil'])
        ->name('pdf.rekap-kas-kecil');
});
