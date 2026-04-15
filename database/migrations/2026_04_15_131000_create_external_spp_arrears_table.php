<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_spp_arrears', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('bulan');
            $table->unsignedSmallInteger('tahun');
            $table->string('nis', 20)->nullable();
            $table->string('nama', 120)->nullable();
            $table->unsignedBigInteger('jurusan_id')->nullable();
            $table->unsignedBigInteger('kelas_id')->nullable();
            $table->string('jurusan', 120)->nullable();
            $table->string('kelas', 60)->nullable();
            $table->decimal('nominal_spp', 15, 2)->default(0);
            $table->string('no_hp_wali', 30)->nullable();
            $table->string('nama_wali', 120)->nullable();
            $table->string('external_source', 50);
            $table->string('external_reference', 120);
            $table->json('external_payload')->nullable();
            $table->timestamp('external_synced_at')->nullable();
            $table->timestamps();

            $table->index(['bulan', 'tahun']);
            $table->index(['jurusan_id', 'kelas_id']);
            $table->index('nis');
            $table->unique(['external_source', 'external_reference'], 'external_spp_arrears_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_spp_arrears');
    }
};
