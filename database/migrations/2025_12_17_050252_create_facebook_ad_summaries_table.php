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
        Schema::create('facebook_ad_summaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('ad_account_id')->index();
            $table->string('campaign_id')->index();
            $table->string('title')->nullable()->index();
            $table->string('campaign_name')->nullable()->index();
            $table->string('status')->nullable();
            /* Campaign dates */
            $table->timestamp('start_date')->nullable()->index();
            $table->timestamp('end_date')->nullable()->index();
            /* Insights - numeric */
            $table->integer('reach')->default(0);
            $table->integer('impressions')->default(0);
            $table->integer('inline_link_clicks')->default(0);
            $table->integer('clicks')->default(0);
            $table->integer('views')->default(0);
            $table->integer('viewers')->default(0);
            $table->decimal('spend', 10, 2)->default(0);
            $table->decimal('cpc', 10, 2)->default(0);
            $table->decimal('cpm', 10, 2)->default(0);
            $table->decimal('ctr', 10, 2)->default(0);
            $table->decimal('frequency', 10, 2)->default(0);

            $table->string('cost_per_result')->nullable();
            $table->string('amount_spent')->nullable();
            /* Creative */
            $table->text('ad_creative_url')->nullable();
            $table->text('ad_thumbnail_url')->nullable();
            $table->timestamps();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            /* Prevent duplicates */
            $table->unique(['ad_account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facebook_ad_summaries');
    }
};
