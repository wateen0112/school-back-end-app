<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FixSubjectsTableColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Use raw SQL to rename columns and fix foreign key
        DB::statement('ALTER TABLE subjects CHANGE name Name VARCHAR(255)');
        DB::statement('ALTER TABLE subjects CHANGE grade_id Grade_id BIGINT UNSIGNED');
        DB::statement('ALTER TABLE subjects CHANGE classroom_id Classroom_id BIGINT UNSIGNED');
        
        // Drop existing foreign key constraint
        DB::statement('ALTER TABLE subjects DROP FOREIGN KEY subjects_teacher_id_foreign');
        
        // Add new foreign key constraint pointing to Teachers table
        DB::statement('ALTER TABLE subjects ADD CONSTRAINT subjects_teacher_id_foreign FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Use raw SQL to reverse column changes
        DB::statement('ALTER TABLE subjects CHANGE Name name VARCHAR(255)');
        DB::statement('ALTER TABLE subjects CHANGE Grade_id grade_id BIGINT UNSIGNED');
        DB::statement('ALTER TABLE subjects CHANGE Classroom_id classroom_id BIGINT UNSIGNED');
        
        // Drop existing foreign key constraint
        DB::statement('ALTER TABLE subjects DROP FOREIGN KEY subjects_teacher_id_foreign');
        
        // Add back original foreign key constraint pointing to Classrooms table
        DB::statement('ALTER TABLE subjects ADD CONSTRAINT subjects_teacher_id_foreign FOREIGN KEY (teacher_id) REFERENCES Classrooms(id) ON DELETE CASCADE');
    }
}
