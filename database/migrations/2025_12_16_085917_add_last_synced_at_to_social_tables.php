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
        Schema::table('user_facebook_page', function (Blueprint $table) {
            if (!Schema::hasColumn('user_facebook_page', 'last_synced_at')) {
                $table->timestamp('last_synced_at')->nullable()->after('profile_picture');
            }
        });

        Schema::table('user_instagram_pages', function (Blueprint $table) {
            if (!Schema::hasColumn('user_instagram_pages', 'last_synced_at')) {
                $table->timestamp('last_synced_at')->nullable()->after('followers_count');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_facebook_page', function (Blueprint $table) {
            if (Schema::hasColumn('user_facebook_page', 'last_synced_at')) {
                $table->dropColumn('last_synced_at');
            }
        });

        Schema::table('user_instagram_pages', function (Blueprint $table) {
            if (Schema::hasColumn('user_instagram_pages', 'last_synced_at')) {
                $table->dropColumn('last_synced_at');
            }
        });
    }
};
