<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGradeTranscriptSettingsTable extends Migration
{
    /**
     * إعدادات حساب المحصلة والجلاء المدرسي لكل صف على حدة:
     * - direct: إدخال المحصلة النهائية مباشرة (الصفوف 1-4) دون تقسيم.
     * - coursework_exam: درجة الأعمال (افتراضياً 60) + درجة الامتحان (40)
     *   لتصبح المحصلة من 100 (الصفوف 5-6 والحلقة الأولى والثانية).
     * درجة الأعمال تتكون من: الشفهي + الوظائف + المذاكرات.
     */
    public function up()
    {
        if (!Schema::hasTable('grade_transcript_settings')) {
            Schema::create('grade_transcript_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('grade_id')->unique()->constrained('Grades')->onDelete('cascade');

                $table->string('calculation_mode')->default('coursework_exam');

                // وزن درجة الأعمال والامتحان في المحصلة النهائية
                $table->decimal('coursework_max', 6, 2)->default(60);
                $table->decimal('exam_max', 6, 2)->default(40);

                // توزيع درجة الأعمال على مكوناتها (الشفهي/الوظائف/المذاكرات)
                $table->decimal('oral_max', 6, 2)->default(20);
                $table->decimal('homework_max', 6, 2)->default(20);
                $table->decimal('quizzes_max', 6, 2)->default(20);

                // أنواع الامتحانات المحتسبة ضمن درجة الامتحان
                $table->json('exam_types')->nullable();

                // نسبة النجاح العامة للصف
                $table->decimal('pass_percentage', 5, 2)->default(50);

                $table->boolean('show_grade_level')->default(true);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('grade_transcript_settings');
    }
}
