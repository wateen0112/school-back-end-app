<?php

use App\Models\Classroom;
use App\Models\Fee;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('fees')->delete();

        $classrooms = Classroom::with('grade')->get();

        $amountsByGrade = [
            1 => 1500, // Primary
            2 => 3000, // Middle
            3 => 4500, // High
        ];

        foreach ($classrooms as $classroom) {
            $classroomName = $classroom->getTranslation('Name_Class', 'en');
            $classroomNameAr = $classroom->getTranslation('Name_Class', 'ar');

            Fee::create([
                'title' => [
                    'en' => "Tuition Fee - {$classroomName}",
                    'ar' => "رسوم دراسية - {$classroomNameAr}",
                ],
                'amount' => $amountsByGrade[$classroom->Grade_id] ?? 2000,
                'Grade_id' => $classroom->Grade_id,
                'Classroom_id' => $classroom->id,
                'description' => 'Annual tuition fee for academic year 2026-2027',
                'year' => '2026-2027',
                'Fee_type' => 1,
            ]);
        }
    }
}
