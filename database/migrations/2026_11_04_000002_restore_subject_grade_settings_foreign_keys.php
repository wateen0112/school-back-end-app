<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * استعادة قيود المفاتيح الأجنبية (subject_id/grade_id) على جدول
 * subject_grade_settings بعد إزالة الـ unique القديم.
 */
class RestoreSubjectGradeSettingsForeignKeys extends Migration
{
    public function up()
    {
        foreach (['subject_id' => 'subjects', 'grade_id' => 'grades'] as $column => $refTable) {
            $name = "subject_grade_settings_{$column}_foreign";
            $exists = count(DB::select(
                'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?',
                ['subject_grade_settings', $name]
            )) > 0;

            if (!Schema::hasColumn('subject_grade_settings', $column) || $exists) {
                continue;
            }

            Schema::table('subject_grade_settings', function (Blueprint $table) use ($column, $refTable) {
                $table->foreign($column)->references('id')->on($refTable)->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        //
    }
}
