<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_events', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->decimal('similarity_score', 4, 3)->nullable();

            $table->primary(['article_id', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_events');
    }
};
