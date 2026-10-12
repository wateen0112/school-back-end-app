<?php

use App\Models\Grade;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GradeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('Grades')->delete();

        $grades = [
            ['en' => 'Primary Stage', 'ar' => 'المرحلة الابتدائية'],
            ['en' => 'Middle School', 'ar' => 'المرحلة المتوسطة'],
            ['en' => 'High School', 'ar' => 'المرحلة الثانوية'],
        ];

        foreach ($grades as $grade) {
            Grade::create(['Name' => $grade]);
        }
    }
}
