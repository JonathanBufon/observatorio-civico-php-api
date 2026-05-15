<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('source_comparisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->unique()->constrained()->cascadeOnDelete();
            $table->jsonb('common_facts')->default('[]');
            $table->jsonb('divergent_frames')->default('[]');
            $table->jsonb('missing_aspects')->default('[]');
            $table->text('synthesis')->nullable();
            $table->string('model_used', 100)->nullable();
            $table->string('prompt_version', 50)->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('source_comparisons');
    }
};
