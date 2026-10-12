<?php

use App\Models\Classroom;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubjectsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('subjects')->delete();

        $subjectNames = [
            ['en' => 'Mathematics', 'ar' => 'الرياضيات'],
            ['en' => 'Arabic', 'ar' => 'اللغة العربية'],
            ['en' => 'English', 'ar' => 'اللغة الإنجليزية'],
            ['en' => 'Sciences', 'ar' => 'العلوم'],
            ['en' => 'Computer', 'ar' => 'الحاسب الآلي'],
            ['en' => 'History', 'ar' => 'التاريخ'],
            ['en' => 'Geography', 'ar' => 'الجغرافيا'],
            ['en' => 'Physics', 'ar' => 'الفيزياء'],
            ['en' => 'Chemistry', 'ar' => 'الكيمياء'],
            ['en' => 'Biology', 'ar' => 'الأحياء'],
            ['en' => 'Islamic Education', 'ar' => 'التربية الإسلامية'],
            ['en' => 'Physical Education', 'ar' => 'التربية الرياضية'],
            ['en' => 'Art', 'ar' => 'التربية الفنية'],
            ['en' => 'Music', 'ar' => 'التربية الموسيقية'],
            ['en' => 'Social Studies', 'ar' => 'الدراسات الاجتماعية'],
        ];

        $classrooms = Classroom::all();
        $teachers = Teacher::all();

        // Distribute exactly 15 subjects across the 9 classrooms.
        // First 6 classrooms get 2 subjects each, last 3 get 1 each (6*2 + 3*1 = 15).
        $subjectIndex = 0;
        foreach ($classrooms as $classroomIndex => $classroom) {
            $count = $classroomIndex < 6 ? 2 : 1;

            for ($i = 0; $i < $count; $i++) {
                Subject::create([
                    'Name' => $subjectNames[$subjectIndex],
                    'Grade_id' => $classroom->Grade_id,
                    'Classroom_id' => $classroom->id,
                    'teacher_id' => $teachers->random()->id,
                ]);

                $subjectIndex++;
            }
        }
    }
}
