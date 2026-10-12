<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FixQuizzesTeacherForeignKey extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Drop the incorrect foreign key that references Classrooms
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropForeign(['teacher_id']);
        });

        // Add the correct foreign key that references teachers
        Schema::table('quizzes', function (Blueprint $table) {
            $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop the correct foreign key
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropForeign(['teacher_id']);
        });

        // Re-add the incorrect foreign key (for rollback compatibility)
        Schema::table('quizzes', function (Blueprint $table) {
            $table->foreign('teacher_id')->references('id')->on('Classrooms')->onDelete('cascade');
        });
    }
}
