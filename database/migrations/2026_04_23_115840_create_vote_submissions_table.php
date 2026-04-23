<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vote_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->string('audience')->default('public');
            $table->dateTime('submitted_at');
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->unsignedInteger('total_points')->default(0);
            $table->timestamps();

            $table->index(['edition_id', 'audience']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vote_submissions');
    }
};
