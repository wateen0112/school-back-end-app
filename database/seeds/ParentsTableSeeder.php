<?php

use App\Models\My_Parent;
use App\Models\Nationalitie;
use App\Models\Religion;
use App\Models\Type_Blood;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ParentsTableSeeder extends Seeder
{
    /**
     * Demo parent for guard "parent" (/login/parent).
     *
     * Email: parent1@school.local
     * Password: password123
     *
     * @return void
     */
    public function run()
    {
        My_Parent::query()->delete();

        $nationality = Nationalitie::first();
        $blood = Type_Blood::first();
        $religion = Religion::first();

        $parent = new My_Parent();
        $parent->email = 'parent1@school.local';
        $parent->password = Hash::make('password123');

        $parent->Name_Father = ['en' => 'Mohammed Farouk', 'ar' => 'محمد فاروق'];
        $parent->National_ID_Father = '28406071234';
        $parent->Passport_ID_Father = 'A01347852';
        $parent->Phone_Father = '01012345678';
        $parent->Job_Father = ['en' => 'Engineer', 'ar' => 'مهندس'];
        $parent->Nationality_Father_id = $nationality ? $nationality->id : 1;
        $parent->Blood_Type_Father_id = $blood ? $blood->id : 1;
        $parent->Religion_Father_id = $religion ? $religion->id : 1;
        $parent->Address_Father = '5 Demo District, Demo City';

        $parent->Name_Mother = ['en' => 'Fatima Ali', 'ar' => 'فاطمة علي'];
        $parent->National_ID_Mother = '27405226178';
        $parent->Passport_ID_Mother = 'A02139876';
        $parent->Phone_Mother = '01112345678';
        $parent->Job_Mother = ['en' => 'Teacher', 'ar' => 'معلمة'];
        $parent->Nationality_Mother_id = $parent->Nationality_Father_id;
        $parent->Blood_Type_Mother_id = $parent->Blood_Type_Father_id;
        $parent->Religion_Mother_id = $parent->Religion_Father_id;
        $parent->Address_Mother = '5 Demo District, Demo City';

        $parent->save();
    }
}
