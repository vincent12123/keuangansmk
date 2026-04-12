<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel pengisian (debet) kas kecil
        Schema::create('pengisian_kas_kecil', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->decimal('nominal', 15, 2);
            $table->text('keterangan')->nullable();
            $table->tinyInteger('bulan');
            $table->smallInteger('tahun');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
        });

        // Tabel pengeluaran (kredit) kas kecil
        Schema::create('kas_kecil', function (Blueprint $table) {
            $table->id();
            $table->string('no_ref', 20)->nullable()->unique(); // K25-0001
            $table->date('tanggal');
            $table->foreignId('kode_akun_id')->constrained('kode_akun')->onDelete('restrict');
            $table->text('uraian');
            $table->decimal('nominal', 15, 2);                  // kolom Kredit
            $table->tinyInteger('bulan')->index();
            $table->smallInteger('tahun')->index();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['kode_akun_id', 'bulan', 'tahun']);
            $table->index(['bulan', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kas_kecil');
        Schema::dropIfExists('pengisian_kas_kecil');
    }
};
