<?php

use App\Models\Question;
use App\Models\Quizze;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuizSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('student_answers')->delete();
        DB::table('degrees')->delete();
        DB::table('questions')->delete();
        DB::table('quizzes')->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Use the first 3 real subjects so all FKs (grade/classroom/teacher) exist.
        $subjects = Subject::limit(3)->get();

        $quizzesData = [
            [
                'name' => ['en' => 'Mathematics Quiz 1', 'ar' => 'اختبار الرياضيات 1'],
                'time_limit' => 10,
            ],
            [
                'name' => ['en' => 'Arabic Quiz 1', 'ar' => 'اختبار اللغة العربية 1'],
                'time_limit' => 15,
            ],
            [
                'name' => ['en' => 'English Quiz 1', 'ar' => 'اختبار اللغة الإنجليزية 1'],
                'time_limit' => 20,
            ],
        ];

        $questionsData = [
            [
                ['title' => 'What is 2 + 2?', 'answers' => '3 - 4 - 5 - 6', 'right_answer' => '4', 'score' => 10],
                ['title' => 'What is 5 x 6?', 'answers' => '30 - 25 - 20 - 35', 'right_answer' => '30', 'score' => 10],
                ['title' => 'What is 10 / 2?', 'answers' => '4 - 5 - 6 - 8', 'right_answer' => '5', 'score' => 10],
            ],
            [
                ['title' => 'ما هو جمع كلمة كتاب؟', 'answers' => 'كتب - كتابان - كتب - الكتب', 'right_answer' => 'كتب', 'score' => 10],
                ['title' => 'ما هو ضد كلمة صغير؟', 'answers' => 'كبير - جميل - قصير - سريع', 'right_answer' => 'كبير', 'score' => 10],
            ],
            [
                ['title' => 'Choose the correct verb: She ___ to school every day.', 'answers' => 'go - goes - going - gone', 'right_answer' => 'goes', 'score' => 10],
                ['title' => 'What is the opposite of "hot"?', 'answers' => 'warm - cold - sunny - dry', 'right_answer' => 'cold', 'score' => 10],
                ['title' => 'Which word is a noun?', 'answers' => 'run - quickly - happiness - happy', 'right_answer' => 'happiness', 'score' => 10],
            ],
        ];

        foreach ($subjects as $index => $subject) {
            // Pick a section that belongs to the same classroom & grade as the subject.
            $section = Section::where('Class_id', $subject->Classroom_id)
                ->where('Grade_id', $subject->Grade_id)
                ->first();

            if (! $section) {
                continue;
            }

            $quiz = Quizze::create([
                'name' => $quizzesData[$index]['name'],
                'subject_id' => $subject->id,
                'grade_id' => $subject->Grade_id,
                'classroom_id' => $subject->Classroom_id,
                'section_id' => $section->id,
                'teacher_id' => $subject->teacher_id,
                'time_limit' => $quizzesData[$index]['time_limit'],
            ]);

            foreach ($questionsData[$index] as $question) {
                Question::create([
                    'title' => $question['title'],
                    'answers' => $question['answers'],
                    'right_answer' => $question['right_answer'],
                    'score' => $question['score'],
                    'quizze_id' => $quiz->id,
                ]);
            }
        }
    }
}
