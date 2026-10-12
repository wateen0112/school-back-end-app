<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFcmTokensTable extends Migration
{
    public function up()
    {
        Schema::create('fcm_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('user_type');
            $table->string('token', 500)->index();
            $table->timestamps();

            $table->unique(['user_id', 'user_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('fcm_tokens');
    }
}
