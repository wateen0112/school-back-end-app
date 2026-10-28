<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * إضافة خانة النسبة (الاسم الثاني) للطالب — تُستخدم في الجلاء
 * وفي جميع النماذج والجداول.
 */
class AddSecondNameToStudentsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('students', 'second_name')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('second_name')->nullable()->after('name');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('students', 'second_name')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('second_name');
            });
        }
    }
}
