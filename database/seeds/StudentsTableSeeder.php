<?php

use App\Models\Gender;
use App\Models\My_Parent;
use App\Models\Nationalitie;
use App\Models\Section;
use App\Models\Student;
use App\Models\Type_Blood;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StudentsTableSeeder extends Seeder
{
    /**
     * Demo student for guard "student" (/login/student).
     *
     * Email: student1@school.local
     * Password: password123
     *
     * Linked to the demo parent (parent1@school.local).
     *
     * @return void
     */
    public function run()
    {
        Student::query()->delete();

        $section = Section::with('My_classs', 'Grades')->first();
        $gender = Gender::first();
        $nationality = Nationalitie::first();
        $blood = Type_Blood::first();
        $parent = My_Parent::first();

        if (! $section || ! $parent) {
            return;
        }

        $grade = $section->Grades;
        $classroom = $section->My_classs;

        Student::create([
            'name' => ['en' => 'Omar Mohammed', 'ar' => 'عمر محمد'],
            'email' => 'student1@school.local',
            'password' => Hash::make('password123'),
            'gender_id' => $gender ? $gender->id : 1,
            'nationalitie_id' => $nationality ? $nationality->id : 1,
            'blood_id' => $blood ? $blood->id : 1,
            'Date_Birth' => '2013-05-15',
            'Grade_id' => $grade->id,
            'Classroom_id' => $classroom->id,
            'section_id' => $section->id,
            'parent_id' => $parent->id,
            'academic_year' => '2026-2027',
        ]);
    }
}
