<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('answer_groups', function (Blueprint $table) {
            $table->boolean('is_podium')->default(false)->after('is_locked');
            $table->unsignedTinyInteger('podium_position')->nullable()->after('is_podium');

            $table->index(['question_id', 'is_podium']);
        });
    }

    public function down(): void
    {
        Schema::table('answer_groups', function (Blueprint $table) {
            $table->dropIndex(['question_id', 'is_podium']);
            $table->dropColumn(['is_podium', 'podium_position']);
        });
    }
};
