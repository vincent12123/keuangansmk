<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smartsis_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->string('sync_type')->default('year_to_date');
            $table->unsignedSmallInteger('tahun');
            $table->string('status')->default('queued');
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->json('months_synced')->nullable();
            $table->json('result_summary')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['sync_type', 'tahun', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smartsis_sync_runs');
    }
};
