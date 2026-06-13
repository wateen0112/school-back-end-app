<?php

use App\Models\Gender;
use App\Models\Specialization;
use App\Models\Teacher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TeachersTableSeeder extends Seeder
{
    /**
     * Demo teacher for guard "teacher" (/login/teacher).
     *
     * @return void
     */
    public function run()
    {
        Teacher::query()->delete();

        $teacher = new Teacher();
        $teacher->email = 'teacher@school.local';
        $teacher->password = Hash::make('password123');
        $teacher->Name = ['en' => 'Demo Teacher', 'ar' => 'معلم تجريبي'];
        $teacher->Specialization_id = Specialization::query()->firstOrFail()->id;
        $teacher->Gender_id = Gender::query()->firstOrFail()->id;
        $teacher->Joining_Date = now()->format('Y-m-d');
        $teacher->Address = 'Demo';
        $teacher->save();
    }
}
