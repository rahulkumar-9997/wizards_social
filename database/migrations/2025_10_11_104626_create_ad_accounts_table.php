<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('ad_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('social_account_id')->constrained('social_accounts')->onDelete('cascade');
            $table->string('provider'); // facebook (instagram ads via Meta)
            $table->string('ad_account_id'); // act_123... or numeric id
            $table->string('ad_account_name')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ad_accounts');
    }
};
