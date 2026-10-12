<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTimeFieldsToDegreesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('degrees', function (Blueprint $table) {
            $table->dateTime('started_at')->nullable()->after('score');
            $table->dateTime('submitted_at')->nullable()->after('started_at');
            $table->unsignedInteger('time_spent_seconds')->default(0)->after('submitted_at');
            $table->enum('status', ['started', 'submitted', 'timeout', 'abuse'])->default('started')->after('time_spent_seconds');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('degrees', function (Blueprint $table) {
            $table->dropColumn(['started_at', 'submitted_at', 'time_spent_seconds', 'status']);
        });
    }
}
