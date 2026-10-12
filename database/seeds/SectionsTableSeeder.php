<?php

use App\Models\Classroom;
use App\Models\Section;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SectionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('sections')->delete();

        $sections = [
            ['en' => 'A', 'ar' => 'أ'],
            ['en' => 'B', 'ar' => 'ب'],
        ];

        $classrooms = Classroom::orderBy('id')->get();

        foreach ($classrooms as $classroom) {
            foreach ($sections as $section) {
                Section::create([
                    'Name_Section' => $section,
                    'Status' => 1,
                    'Grade_id' => $classroom->Grade_id,
                    'Class_id' => $classroom->id,
                ]);
            }
        }
    }
}
