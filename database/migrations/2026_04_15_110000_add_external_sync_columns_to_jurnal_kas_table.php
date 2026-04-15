<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jurnal_kas', function (Blueprint $table) {
            $table->string('external_source', 50)->nullable()->after('updated_by');
            $table->string('external_reference', 100)->nullable()->after('external_source');
            $table->json('external_payload')->nullable()->after('external_reference');
            $table->timestamp('external_synced_at')->nullable()->after('external_payload');

            $table->index('external_source');
            $table->unique(['external_source', 'external_reference'], 'jurnal_kas_external_unique');
        });
    }

    public function down(): void
    {
        Schema::table('jurnal_kas', function (Blueprint $table) {
            $table->dropUnique('jurnal_kas_external_unique');
            $table->dropIndex(['external_source']);
            $table->dropColumn([
                'external_source',
                'external_reference',
                'external_payload',
                'external_synced_at',
            ]);
        });
    }
};
