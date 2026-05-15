<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained()->cascadeOnDelete();
            $table->string('external_id', 500)->nullable();
            $table->string('title', 500);
            $table->string('original_url', 1000);
            $table->text('content');
            $table->timestampTz('published_at')->nullable();
            $table->timestampTz('fetched_at')->useCurrent();
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['source_id', 'external_id']);
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
