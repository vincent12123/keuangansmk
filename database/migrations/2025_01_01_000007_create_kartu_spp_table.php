<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kartu_spp', function (Blueprint $table) {
            $table->id();
            $table->string('nis', 20)->index();
            $table->tinyInteger('bulan');               // 1=Januari, 7=Juli, dst
            $table->smallInteger('tahun');
            $table->decimal('nominal', 12, 0);          // nominal yang dibayar bulan itu
            $table->date('tgl_bayar');
            $table->foreignId('jurnal_kas_id')          // link ke transaksi di jurnal
                ->nullable()
                ->constrained('jurnal_kas')
                ->nullOnDelete();
            $table->string('keterangan', 100)->nullable();
            $table->timestamps();

            // Satu siswa hanya bisa bayar SPP bulan tertentu sekali
            // (jika bayar cicilan, jumlahkan di jurnal, insert satu record di kartu_spp)
            $table->unique(['nis', 'bulan', 'tahun']);
            $table->index(['bulan', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kartu_spp');
    }
};
