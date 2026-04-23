<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('answer_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->string('canonical_label');
            $table->unsignedInteger('aggregated_count')->default(0);
            $table->integer('aggregated_points')->default(0);
            $table->integer('points_override')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->timestamps();

            $table->index('question_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answer_groups');
    }
};
