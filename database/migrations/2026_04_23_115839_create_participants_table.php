<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('public');
            $table->string('email');
            $table->string('link_hash', 64)->unique();
            $table->string('access_code', 10);
            $table->boolean('consented_privacy')->default(false);
            $table->boolean('consented_marketing')->default(false);
            $table->string('registered_ip', 45)->nullable();
            $table->string('registered_user_agent', 500)->nullable();
            $table->string('registered_fingerprint')->nullable();
            $table->string('user_com_user_id')->nullable();
            $table->dateTime('voted_at')->nullable();
            $table->timestamps();

            $table->unique(['edition_id', 'email']);
            $table->index('type');
            $table->index('voted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};
