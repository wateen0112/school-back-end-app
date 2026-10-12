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
     * Email: teacher1@school.local
     * Password: password123
     *
     * @return void
     */
    public function run()
    {
        Teacher::query()->delete();

        $gender = Gender::first();
        $specialization = Specialization::first();

        Teacher::create([
            'email' => 'teacher1@school.local',
            'password' => Hash::make('password123'),
            'Name' => ['en' => 'Ahmed Hassan', 'ar' => 'أحمد حسن'],
            'Specialization_id' => $specialization ? $specialization->id : 1,
            'Gender_id' => $gender ? $gender->id : 1,
            'Joining_Date' => '2020-09-01',
            'Address' => '12 Demo Street, Demo City',
            'salary' => 2500.00,
        ]);
    }
}
