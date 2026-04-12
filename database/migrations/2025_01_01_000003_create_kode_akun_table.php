<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kode_akun', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 15)->unique();       // 4.01.01.00
            $table->string('nama');                      // Jurusan RPL
            $table->enum('tipe', ['pendapatan', 'pengeluaran']); // 4.xx / 5.xx
            $table->string('kategori', 60)->nullable(); // PENERIMAAN PENDIDIKAN, BEBAN PEGAWAI
            $table->string('sub_kategori', 60)->nullable(); // BEBAN GAJI, BEBAN OPERASIONAL
            $table->boolean('aktif')->default(true);
            $table->boolean('kas_kecil')->default(false); // apakah sering muncul di kas kecil
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kode_akun');
    }
};
