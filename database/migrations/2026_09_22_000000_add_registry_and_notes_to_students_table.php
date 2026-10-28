<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRegistryAndNotesToStudentsTable extends Migration
{
    public function up()
    {
        Schema::table('students', function (Blueprint $table) {
            // رقم الطالب في السجل العام
            $table->string('registry_number', 50)->nullable()->after('residence_address');
            // ملاحظات تُطبع في الجلاء المدرسي
            $table->text('transcript_notes')->nullable()->after('registry_number');
        });
    }

    public function down()
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['registry_number', 'transcript_notes']);
        });
    }
}
