<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengisian_kas_kecil', function (Blueprint $table) {
            $table->foreignId('updated_by')
                ->nullable()
                ->after('created_by')
                ->constrained('users')
                ->nullOnDelete();

            $table->softDeletes()->after('updated_at');
            $table->index(['bulan', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::table('pengisian_kas_kecil', function (Blueprint $table) {
            $table->dropIndex(['bulan', 'tahun']);
            $table->dropSoftDeletes();
            $table->dropConstrainedForeignId('updated_by');
        });
    }
};
