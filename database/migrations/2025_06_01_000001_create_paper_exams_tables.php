<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaperExamsTables extends Migration
{
    public function up()
    {
        Schema::create('paper_exams', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->date('exam_date');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->foreignId('grade_id')->constrained('grades')->onDelete('cascade');
            $table->foreignId('classroom_id')->constrained('classrooms')->onDelete('cascade');
            $table->foreignId('section_id')->constrained('sections')->onDelete('cascade');
            $table->decimal('max_score', 8, 2)->default(100);
            $table->string('status')->default('draft'); // draft, published
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('paper_exam_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paper_exam_id')->constrained('paper_exams')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->decimal('marks_obtained', 8, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['paper_exam_id', 'student_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('paper_exam_marks');
        Schema::dropIfExists('paper_exams');
    }
}
