<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jurnal_kas', function (Blueprint $table) {
            $table->id();
            $table->string('no_kwitansi', 20)->nullable()->index(); // 005056
            $table->date('tanggal');
            $table->string('nis', 20)->nullable()->index();         // nullable: bisa pengeluaran
            $table->string('nama_penyetor', 100)->nullable();       // nama siswa / vendor
            $table->foreignId('kelas_id')->nullable()->constrained('kelas')->nullOnDelete();
            $table->foreignId('kode_akun_id')->constrained('kode_akun')->onDelete('restrict');
            $table->text('uraian');
            $table->decimal('cash', 15, 2)->default(0);            // kolom Cash
            $table->decimal('bank', 15, 2)->default(0);            // kolom Bank
            $table->enum('jenis', ['masuk', 'keluar']);             // 4.xx=masuk, 5.xx=keluar
            $table->tinyInteger('bulan')->index();                  // 1-12
            $table->smallInteger('tahun')->index();                 // 2025
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tanggal', 'jenis']);
            $table->index(['bulan', 'tahun']);
            $table->index(['kode_akun_id', 'bulan', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jurnal_kas');
    }
};
