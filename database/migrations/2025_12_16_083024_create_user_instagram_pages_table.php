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
        Schema::create('user_instagram_pages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('instagram_id')->unique();
            $table->string('account_name');
            $table->string('user_name')->nullable();
            $table->longText('access_token');
            $table->string('profile_picture')->nullable();
            $table->unsignedBigInteger('followers_count')->default(0);
            $table->string('connected_page')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_instagram_pages');
    }
};
