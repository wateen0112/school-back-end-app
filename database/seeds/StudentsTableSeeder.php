<?php

use App\Models\Gender;
use App\Models\My_Parent;
use App\Models\Nationalitie;
use App\Models\Section;
use App\Models\Student;
use App\Models\Type_Blood;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudentsTableSeeder extends Seeder
{
    /**
     * Demo student for guard "student" (/login/student).
     *
     * Email: student@school.local · Password: password123
     *
     * @return void
     */
    public function run()
    {
        DB::table('students')->delete();
        $students = new Student();
        $students->name = ['ar' => 'احمد ابراهيم', 'en' => 'Ahmed Ibrahim'];
        $students->email = 'student@school.local';
        $students->password = Hash::make('password123');

        $section = Section::query()->firstOrFail();
        $students->Grade_id = $section->Grade_id;
        $students->Classroom_id = $section->Class_id;
        $students->section_id = $section->id;

        $students->gender_id = Gender::query()->firstOrFail()->id;
        $students->nationalitie_id = Nationalitie::query()->firstOrFail()->id;
        $students->blood_id = Type_Blood::query()->firstOrFail()->id;
        $students->Date_Birth = date('1995-01-01');
        $students->parent_id = My_Parent::query()->firstOrFail()->id;
        $students->academic_year ='2021';
        $students->save();
    }
}
