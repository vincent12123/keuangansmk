<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('siswa', function (Blueprint $table) {
            $table->id();
            $table->string('nis', 20)->unique();
            $table->string('nama');
            $table->foreignId('kelas_id')->constrained('kelas')->onDelete('restrict');
            $table->foreignId('jurusan_id')->constrained('jurusan')->onDelete('restrict');
            $table->year('angkatan');                    // Tahun masuk: 2024, 2025
            $table->decimal('nominal_spp', 12, 0)->default(400000); // SPP per bulan
            $table->enum('status', ['aktif', 'alumni', 'keluar'])->default('aktif');
            $table->string('no_hp_wali', 20)->nullable(); // untuk notif WA tunggakan
            $table->string('nama_wali', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siswa');
    }
};
