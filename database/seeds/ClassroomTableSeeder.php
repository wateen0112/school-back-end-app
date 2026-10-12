<?php

use App\Models\Classroom;
use App\Models\Grade;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClassroomTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('Classrooms')->delete();

        $grades = Grade::orderBy('id')->get();

        $classroomNames = [
            [
                ['en' => 'Grade 1', 'ar' => 'الصف الأول الابتدائي'],
                ['en' => 'Grade 2', 'ar' => 'الصف الثاني الابتدائي'],
                ['en' => 'Grade 3', 'ar' => 'الصف الثالث الابتدائي'],
            ],
            [
                ['en' => 'Grade 7', 'ar' => 'الصف الأول المتوسط'],
                ['en' => 'Grade 8', 'ar' => 'الصف الثاني المتوسط'],
                ['en' => 'Grade 9', 'ar' => 'الصف الثالث المتوسط'],
            ],
            [
                ['en' => 'Grade 10', 'ar' => 'الصف الأول الثانوي'],
                ['en' => 'Grade 11', 'ar' => 'الصف الثاني الثانوي'],
                ['en' => 'Grade 12', 'ar' => 'الصف الثالث الثانوي'],
            ],
        ];

        foreach ($grades as $index => $grade) {
            foreach ($classroomNames[$index] as $name) {
                Classroom::create([
                    'Name_Class' => $name,
                    'Grade_id' => $grade->id,
                ]);
            }
        }
    }
}
