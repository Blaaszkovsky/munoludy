<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('editions', function (Blueprint $table) {
            if (Schema::hasColumn('editions', 'user_com_type_field')) {
                $table->dropColumn('user_com_type_field');
            }
        });
    }

    public function down(): void
    {
        Schema::table('editions', function (Blueprint $table) {
            if (!Schema::hasColumn('editions', 'user_com_type_field')) {
                $table->string('user_com_type_field')->default('munoludy2026_typ')->after('user_com_code_field');
            }
        });
    }
};
