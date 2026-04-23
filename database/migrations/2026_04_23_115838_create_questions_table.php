<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->string('audience')->default('public');
            $table->string('field_type')->default('ranked_text_5');
            $table->unsignedInteger('order')->default(0);
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('is_required')->default(true);
            $table->json('validation_rules')->nullable();
            $table->json('ranked_points')->nullable();
            $table->timestamps();

            $table->index(['edition_id', 'audience', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
