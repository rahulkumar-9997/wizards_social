<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->boolean('display_sidebar_status')
                  ->default(0)
                  ->after('is_active')
                  ->comment('0 = Hidden in sidebar, 1 = Visible in sidebar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
             $table->dropColumn('display_sidebar_status');
        });
    }
};
