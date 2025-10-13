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
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->string('account_email')->nullable()->after('account_name');
            $table->text('avatar')->nullable()->after('account_email');
            
            // Relationship Management
            $table->unsignedBigInteger('parent_account_id')->nullable()->after('avatar');
            
            // Enhanced Data Storage
            $table->json('connected_assets')->nullable()->after('posts_data');
            $table->integer('asset_count')->default(0)->after('connected_assets');
            
            // Synchronization Tracking
            $table->timestamp('last_synced_at')->nullable()->after('asset_count');
            
            // Add foreign key constraint
            $table->foreign('parent_account_id')->references('id')->on('social_accounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->dropForeign(['parent_account_id']);
            $table->dropColumn([
                'account_email',
                'avatar',
                'parent_account_id',
                'connected_assets',
                'asset_count',
                'last_synced_at'
            ]);
        });
    }
};
