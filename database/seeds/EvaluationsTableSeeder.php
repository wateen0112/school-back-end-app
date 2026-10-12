<?php

use App\Models\Evaluation;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EvaluationsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('evaluations')->delete();

        $levels = ['bad', 'good', 'very_good', 'excellent'];
        $levelNotes = [
            'bad' => 'Needs significant improvement',
            'good' => 'Good performance',
            'very_good' => 'Very good performance',
            'excellent' => 'Excellent performance',
        ];

        $students = Student::all();
        $subjects = Subject::all();

        if ($students->isEmpty() || $subjects->isEmpty()) {
            return;
        }

        $evaluations = [];
        $usedKeys = [];
        // Cap at unique (student, subject, month) combos so the loop
        // terminates even with a single student.
        $target = min(200, $students->count() * $subjects->count() * 12);

        while (count($evaluations) < $target) {
            $student = $students->random();
            $subject = $subjects->random();
            $month = rand(1, 12);
            $year = 2026;

            $key = "{$student->id}-{$subject->id}-{$month}-{$year}";

            if (isset($usedKeys[$key])) {
                continue;
            }

            $usedKeys[$key] = true;
            $level = $levels[array_rand($levels)];

            $evaluations[] = [
                'student_id' => $student->id,
                'teacher_id' => $subject->teacher_id,
                'subject_id' => $subject->id,
                'grade_id' => $student->Grade_id,
                'classroom_id' => $student->Classroom_id,
                'section_id' => $student->section_id,
                'evaluation_level' => $level,
                'notes' => $levelNotes[$level],
                'month' => $month,
                'year' => $year,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Evaluation::insert($evaluations);
    }
}
