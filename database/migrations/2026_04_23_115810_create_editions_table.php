<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('editions', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->unsignedInteger('user_com_list_id')->default(17);
            $table->string('user_com_link_field')->default('munoludy2026_link');
            $table->string('user_com_code_field')->default('munoludy2026_kod');
            $table->string('user_com_type_field')->default('munoludy2026_typ');
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->string('status')->default('draft');
            $table->dateTime('results_published_at')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('editions');
    }
};
