<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * إصلاح التباس: إعدادات النجاح/الرسوب والجلاء وإعدادات المواد تُطبق
 * على الصف (Classroom) وليس على المرحلة (Grade) — لكل صف إعداداته الخاصة.
 */
class LinkPassFailAndTranscriptSettingsToClassrooms extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('grade_pass_fail_rules', 'classroom_id')) {
            Schema::table('grade_pass_fail_rules', function (Blueprint $table) {
                $table->foreignId('classroom_id')->nullable()->after('grade_id')
                    ->constrained('Classrooms')->onDelete('cascade');
                $table->index(['classroom_id', 'rule_type']);
            });
        }

        if (!Schema::hasColumn('grade_transcript_settings', 'classroom_id')) {
            Schema::table('grade_transcript_settings', function (Blueprint $table) {
                $table->foreignId('classroom_id')->nullable()->after('grade_id')
                    ->constrained('Classrooms')->onDelete('cascade');
                $table->index(['classroom_id']);
            });
            if ($this->hasConstraint('grade_transcript_settings', 'grade_transcript_settings_grade_id_foreign')) {
                Schema::table('grade_transcript_settings', function (Blueprint $table) {
                    $table->dropForeign(['grade_id']);
                });
            }
            if ($this->hasConstraint('grade_transcript_settings', 'grade_transcript_settings_grade_id_unique')) {
                Schema::table('grade_transcript_settings', function (Blueprint $table) {
                    $table->dropUnique(['grade_id']);
                });
            }
        }

        if (!Schema::hasColumn('subject_grade_settings', 'classroom_id')) {
            Schema::table('subject_grade_settings', function (Blueprint $table) {
                $table->foreignId('classroom_id')->nullable()->after('grade_id')
                    ->constrained('Classrooms')->onDelete('cascade');
                $table->index(['subject_id', 'classroom_id']);
            });
            if ($this->hasConstraint('subject_grade_settings', 'subject_grade_settings_subject_id_grade_id_unique')) {
                Schema::table('subject_grade_settings', function (Blueprint $table) {
                    $table->dropUnique(['subject_id', 'grade_id']);
                });
            }
            if ($this->hasConstraint('subject_grade_settings', 'subject_grade_settings_subject_id_foreign')) {
                Schema::table('subject_grade_settings', function (Blueprint $table) {
                    $table->dropForeign(['subject_id']);
                });
            }
            if ($this->hasConstraint('subject_grade_settings', 'subject_grade_settings_grade_id_foreign')) {
                Schema::table('subject_grade_settings', function (Blueprint $table) {
                    $table->dropForeign(['grade_id']);
                });
            }
        }
    }

    /**
     * فحص وجود قيد/مفتاح على الجدول (يتجنب أخطاء إعادة التشغيل الجزئية).
     */
    protected function hasConstraint($table, $name)
    {
        return count(DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?',
            [$table, $name]
        )) > 0;
    }

    public function down()
    {
        Schema::table('grade_pass_fail_rules', function (Blueprint $table) {
            $table->dropIndex(['classroom_id', 'rule_type']);
            $table->dropConstrainedForeignId('classroom_id');
        });

        Schema::table('grade_transcript_settings', function (Blueprint $table) {
            $table->dropIndex(['classroom_id']);
            $table->dropConstrainedForeignId('classroom_id');
            $table->unique('grade_id');
        });

        Schema::table('subject_grade_settings', function (Blueprint $table) {
            $table->dropIndex(['subject_id', 'classroom_id']);
            $table->dropConstrainedForeignId('classroom_id');
            $table->unique(['subject_id', 'grade_id']);
        });
    }
}
