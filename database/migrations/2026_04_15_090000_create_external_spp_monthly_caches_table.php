<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_spp_monthly_caches', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('bulan');
            $table->unsignedSmallInteger('tahun');
            $table->decimal('total_cash', 15, 2)->default(0);
            $table->decimal('total_bank', 15, 2)->default(0);
            $table->decimal('total_nominal', 15, 2)->default(0);
            $table->json('payload')->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();

            $table->unique(['bulan', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_spp_monthly_caches');
    }
};
