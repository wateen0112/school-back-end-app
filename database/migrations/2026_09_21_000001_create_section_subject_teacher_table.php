<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSectionSubjectTeacherTable extends Migration
{
    public function up()
    {
        Schema::create('section_subject_teacher', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('sections')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->onDelete('set null');
            $table->timestamps();

            $table->unique(['section_id', 'subject_id'], 'section_subject_unique');
            $table->index(['subject_id', 'section_id']);
            $table->index('teacher_id');
        });

        // Make the old direct columns nullable so subjects can exist without
        // being hard-linked to one classroom/teacher.
        Schema::table('subjects', function (Blueprint $table) {
            if (Schema::hasColumn('subjects', 'Classroom_id')) {
                $table->unsignedBigInteger('Classroom_id')->nullable()->change();
            }
            if (Schema::hasColumn('subjects', 'teacher_id')) {
                $table->unsignedBigInteger('teacher_id')->nullable()->change();
            }
        });
    }

    public function down()
    {
        Schema::dropIfExists('section_subject_teacher');

        Schema::table('subjects', function (Blueprint $table) {
            if (Schema::hasColumn('subjects', 'Classroom_id')) {
                $table->unsignedBigInteger('Classroom_id')->nullable(false)->change();
            }
            if (Schema::hasColumn('subjects', 'teacher_id')) {
                $table->unsignedBigInteger('teacher_id')->nullable(false)->change();
            }
        });
    }
}
