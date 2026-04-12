<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jurusan_id')->constrained('jurusan')->onDelete('restrict');
            $table->enum('tingkat', ['X', 'XI', 'XII']);
            $table->string('nama_kelas', 30);   // X RPL, XI TBSM, XII PERHOTELAN
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->unique(['jurusan_id', 'tingkat']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas');
    }
};
