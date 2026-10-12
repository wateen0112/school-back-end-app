<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->text('body');
            $table->enum('type', ['general', 'targeted', 'role'])->default('general');
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->string('sender_type')->nullable();
            $table->enum('target_role', ['all', 'student', 'teacher', 'parent', 'admin'])->nullable();
            $table->json('data')->nullable();
            $table->timestamps();

            $table->index(['sender_id', 'sender_type']);
            $table->index('type');
            $table->index('target_role');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notifications');
    }
}
