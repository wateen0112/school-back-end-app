<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationRecipientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notification_recipients', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('notification_id');
            $table->unsignedBigInteger('recipient_id');
            $table->string('recipient_type');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->foreign('notification_id')
                  ->references('id')
                  ->on('notifications')
                  ->onDelete('cascade');

            $table->index(['recipient_id', 'recipient_type']);
            $table->index('is_read');
            $table->index(['notification_id', 'recipient_id', 'recipient_type'], 'notif_recip_notif_id_recip_id_recip_type_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notification_recipients');
    }
}
