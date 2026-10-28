<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExamTypeAndSubjectGradeSettings extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('paper_exams', 'exam_type')) {
            Schema::table('paper_exams', function (Blueprint $table) {
                // written_review_1, written_review_2, oral, coursework, final_exam
                $table->string('exam_type')->default('final_exam')->after('title');
                $table->index('exam_type');
            });
        }

        if (!Schema::hasTable('subject_grade_settings')) {
            Schema::create('subject_grade_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
                $table->foreignId('grade_id')->constrained('grades')->onDelete('cascade');
                $table->decimal('max_mark', 8, 2)->default(100);
                $table->decimal('fail_mark', 8, 2)->default(50);
                $table->timestamps();

                $table->unique(['subject_id', 'grade_id']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('subject_grade_settings');

        if (Schema::hasColumn('paper_exams', 'exam_type')) {
            Schema::table('paper_exams', function (Blueprint $table) {
                $table->dropIndex(['exam_type']);
                $table->dropColumn('exam_type');
            });
        }
    }
}
