<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Saldo awal operasional per bulan
        // Diisi otomatis dari saldo akhir bulan sebelumnya
        // Bisa juga diinput manual untuk bulan pertama (Januari)
        Schema::create('saldo_kas_bulanan', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('bulan');
            $table->smallInteger('tahun');
            $table->decimal('saldo_awal_cash', 15, 2)->default(0);
            $table->decimal('saldo_awal_bank', 15, 2)->default(0);
            $table->boolean('is_locked')->default(false); // dikunci setelah bulan selesai
            $table->timestamps();

            $table->unique(['bulan', 'tahun']);
        });

        // Anggaran per kode akun per tahun
        Schema::create('anggaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kode_akun_id')->constrained('kode_akun')->onDelete('cascade');
            $table->smallInteger('tahun');
            $table->decimal('target', 15, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();

            $table->unique(['kode_akun_id', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anggaran');
        Schema::dropIfExists('saldo_kas_bulanan');
    }
};
