<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('rewritten_text');
            $table->jsonb('fact_fragments')->default('[]');
            $table->jsonb('opinion_fragments')->default('[]');
            $table->jsonb('simplified_terms')->default('[]');
            $table->jsonb('bias_indicators')->default('[]');
            $table->text('transparency_log')->nullable();
            $table->string('model_used', 100)->nullable();
            $table->string('prompt_version', 50)->nullable();
            $table->timestampTz('processed_at')->useCurrent();
            $table->timestampTz('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analyses');
    }
};
