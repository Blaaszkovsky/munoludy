<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('jury_members', function (Blueprint $table) {
            $table->string('link_hash', 64)->nullable()->unique()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('jury_members', function (Blueprint $table) {
            $table->dropUnique(['link_hash']);
            $table->dropColumn('link_hash');
        });
    }
};
