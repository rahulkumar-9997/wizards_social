<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_facebook_page', function (Blueprint $table) {
            $table->text('profile_picture')->change();
        });

        Schema::table('user_instagram_pages', function (Blueprint $table) {
            $table->text('profile_picture')->change();
        });
    }

    public function down(): void
    {
        Schema::table('user_facebook_page', function (Blueprint $table) {
            $table->string('profile_picture', 255)->change();
        });

        Schema::table('user_instagram_pages', function (Blueprint $table) {
            $table->string('profile_picture', 255)->change();
        });
    }
};
