<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('argumentative_strategies', function (Blueprint $table) {
            $table->string('who_uses', 200)->nullable()->after('strategy_name');
        });
    }

    public function down(): void
    {
        Schema::table('argumentative_strategies', function (Blueprint $table) {
            $table->dropColumn('who_uses');
        });
    }
};
