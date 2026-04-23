<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('jury_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('display_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['edition_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jury_members');
    }
};
