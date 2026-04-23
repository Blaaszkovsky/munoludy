<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('answer_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('answer_group_id')->constrained()->cascadeOnDelete();
            $table->string('variant');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['answer_group_id', 'variant']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answer_aliases');
    }
};
