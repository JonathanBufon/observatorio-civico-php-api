<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('argumentative_strategies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analysis_id')->constrained('analyses')->cascadeOnDelete();
            $table->string('strategy_name', 200);
            $table->text('excerpt');
            $table->text('explanation');
            $table->string('severity', 20)->default('medium');
            $table->timestampTz('created_at')->useCurrent();

            $table->index('analysis_id');
            $table->index('strategy_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('argumentative_strategies');
    }
};
