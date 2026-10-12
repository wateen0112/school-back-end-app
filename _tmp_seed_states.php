<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Student;
use App\Models\Quizze;
use App\Models\Degree;

$student = Student::where('email', 'student1@school.local')->first();
if (! $student) {
    echo "student not found\n";
    exit(1);
}

$quizzes = Quizze::where('grade_id', $student->Grade_id)
    ->where('classroom_id', $student->Classroom_id)
    ->where('section_id', $student->section_id)
    ->get();

foreach ($quizzes as $i => $q) {
    $d = Degree::where('student_id', $student->id)->where('quizze_id', $q->id)->first();
    if ($i === 0 && $d) {
        $d->status = 'started';
        $d->started_at = now();
        $d->save();
        echo "started: quiz {$q->id} named {$q->name}\n";
    }
    if ($i === 2 && $d) {
        $d->delete();
        echo "deleted degree for quiz {$q->id} named {$q->name} (not_started)\n";
    }
}

echo 'quizzes total: ' . $quizzes->count() . "\n";
foreach ($quizzes as $q) {
    $d = Degree::where('student_id', $student->id)->where('quizze_id', $q->id)->first();
    echo " - quiz {$q->id}: " . ($d ? $d->status : 'not_started') . "\n";
}