<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vote_submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(1);
            $table->text('value')->nullable();
            $table->string('value_normalized', 512)->nullable();
            $table->foreignId('question_option_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('points')->default(0);
            $table->foreignId('answer_group_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['question_id', 'value_normalized'], 'answers_q_normalized_idx');
            $table->index('answer_group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answers');
    }
};
