<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentMonthlyReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('student_monthly_reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('attendance_total_days')->default(0);
            $table->unsignedInteger('attendance_present')->default(0);
            $table->unsignedInteger('attendance_absent')->default(0);
            $table->decimal('attendance_rate', 5, 2)->default(0);
            $table->unsignedInteger('evaluations_total')->default(0);
            $table->decimal('evaluations_average_level', 4, 2)->default(0);
            $table->json('evaluations_breakdown')->nullable();
            $table->json('exam_subjects')->nullable();
            $table->decimal('exam_overall_percentage', 5, 2)->default(0);
            $table->unsignedInteger('quizzes_total')->default(0);
            $table->decimal('quiz_average_percentage', 5, 2)->default(0);
            $table->decimal('progress_score', 5, 2)->default(0);
            $table->decimal('previous_month_score', 5, 2)->nullable();
            $table->enum('trend', ['improved', 'declined', 'stable', 'new'])->default('new');
            $table->json('teacher_notes')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'month', 'year']);
            $table->index(['month', 'year']);
            $table->index(['student_id', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('student_monthly_reports');
    }
}