<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->enum('permission_level', ['basic', 'standard', 'full'])->default('basic');
            $table->text('granted_permissions')->nullable();
            $table->text('meta_data')->nullable();
            $table->text('posts_data')->nullable();
        });

        Schema::table('ad_accounts', function (Blueprint $table) {
            $table->decimal('amount_spent', 10, 2)->default(0);
            $table->decimal('balance', 10, 2)->default(0);
            $table->text('meta_data')->nullable();
        });
    }

    public function down()
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->dropColumn(['permission_level','granted_permissions','meta_data','posts_data']);
        });

        Schema::table('ad_accounts', function (Blueprint $table) {
            $table->dropColumn(['amount_spent','balance','meta_data']);
        });
    }
};
