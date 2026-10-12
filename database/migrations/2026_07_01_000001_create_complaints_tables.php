<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateComplaintsTables extends Migration
{
    public function up()
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'resolved', 'rejected'])->default('pending');
            $table->enum('priority', ['normal', 'high', 'urgent'])->default('normal');
            $table->unsignedBigInteger('complainant_id');
            $table->string('complainant_type');
            $table->timestamps();

            $table->index(['complainant_id', 'complainant_type']);
            $table->index('status');
        });

        Schema::create('complaint_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->constrained('complaints')->onDelete('cascade');
            $table->unsignedBigInteger('user_id');
            $table->string('user_type');
            $table->text('message');
            $table->timestamps();

            $table->index(['user_id', 'user_type']);
        });

        Schema::create('complaint_attachments', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('original_filename');
            $table->string('file_type');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->foreignId('complaint_id')->nullable()->constrained('complaints')->onDelete('cascade');
            $table->foreignId('complaint_reply_id')->nullable()->constrained('complaint_replies')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('complaint_attachments');
        Schema::dropIfExists('complaint_replies');
        Schema::dropIfExists('complaints');
    }
}
