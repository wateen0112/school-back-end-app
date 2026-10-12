<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class WidenTranslatableNameColumnsToText extends Migration
{
    /**
     * Translatable JSON columns that must hold both 'en' and 'ar' values.
     */
    protected $columns = [
        'my__parents' => ['Name_Father', 'Job_Father', 'Name_Mother', 'Job_Mother'],
        'classrooms' => ['Name_Class'],
        'fees' => ['title'],
        'genders' => ['Name'],
        'grades' => ['Name'],
        'nationalities' => ['Name'],
        'quizzes' => ['name'],
        'religions' => ['Name'],
        'sections' => ['Name_Section'],
        'specializations' => ['Name'],
        'subjects' => ['Name'],
        'teachers' => ['Name'],
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach ($this->columns as $table => $columns) {
            foreach ($columns as $column) {
                DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` TEXT");
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        foreach ($this->columns as $table => $columns) {
            foreach ($columns as $column) {
                DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` VARCHAR(255)");
            }
        }
    }
}