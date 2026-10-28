<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsJustifiedToAttendancesTable extends Migration
{
    public function up()
    {
        Schema::table('attendances', function (Blueprint $table) {
            // غياب مبرر (بعذر) أم غير مبرر — يُستخدم في الجلاء المدرسي
            $table->boolean('is_justified')->default(false)->after('attendence_status');
        });
    }

    public function down()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('is_justified');
        });
    }
}
