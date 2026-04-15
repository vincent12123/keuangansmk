<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jurusan', function (Blueprint $table) {
            $table->string('external_source', 50)->nullable()->after('aktif');
            $table->string('external_reference', 100)->nullable()->after('external_source');
            $table->json('external_payload')->nullable()->after('external_reference');
            $table->timestamp('external_synced_at')->nullable()->after('external_payload');
            $table->index(['external_source', 'external_reference'], 'jurusan_external_idx');
        });

        Schema::table('kelas', function (Blueprint $table) {
            $table->string('external_source', 50)->nullable()->after('aktif');
            $table->string('external_reference', 100)->nullable()->after('external_source');
            $table->json('external_payload')->nullable()->after('external_reference');
            $table->timestamp('external_synced_at')->nullable()->after('external_payload');
            $table->index(['external_source', 'external_reference'], 'kelas_external_idx');
        });

        Schema::table('siswa', function (Blueprint $table) {
            $table->string('external_source', 50)->nullable()->after('nama_wali');
            $table->string('external_reference', 100)->nullable()->after('external_source');
            $table->json('external_payload')->nullable()->after('external_reference');
            $table->timestamp('external_synced_at')->nullable()->after('external_payload');
            $table->index(['external_source', 'external_reference'], 'siswa_external_idx');
        });
    }

    public function down(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->dropIndex('siswa_external_idx');
            $table->dropColumn(['external_source', 'external_reference', 'external_payload', 'external_synced_at']);
        });

        Schema::table('kelas', function (Blueprint $table) {
            $table->dropIndex('kelas_external_idx');
            $table->dropColumn(['external_source', 'external_reference', 'external_payload', 'external_synced_at']);
        });

        Schema::table('jurusan', function (Blueprint $table) {
            $table->dropIndex('jurusan_external_idx');
            $table->dropColumn(['external_source', 'external_reference', 'external_payload', 'external_synced_at']);
        });
    }
};
