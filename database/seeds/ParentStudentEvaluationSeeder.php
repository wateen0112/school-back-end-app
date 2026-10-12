<?php

use App\Models\Evaluation;
use App\Models\Grade;
use App\Models\Classroom;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\My_Parent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ParentStudentEvaluationSeeder extends Seeder
{
    /**
     * Seed a demo parent, ensure the parent has linked children,
     * and create evaluations for all of the parent's children.
     *
     * Parent login: parent@school.local / password123
     * Student login: student@school.local / password123
     */
    public function run(): void
    {
        $grade = Grade::first();
        $classroom = Classroom::first();
        $section = Section::first();
        $subject = Subject::first();
        $teacher = Teacher::first();

        if (!$grade || !$classroom || !$section || !$subject) {
            $this->command->error('Missing required data. Need at least one grade, classroom, section and subject.');
            return;
        }

        $classroom->update(['Grade_id' => $grade->id]);
        $section->update(['Grade_id' => $grade->id, 'Class_id' => $classroom->id]);

        if (!$teacher) {
            $teacher = Teacher::create([
                'Email' => 'demo.teacher@school.test',
                'Password' => Hash::make('password123'),
                'Name_ar' => 'معلم تجريبي',
                'Name_en' => 'Demo Teacher',
                'Specialization_id' => 1,
                'Gender_id' => 1,
                'Joining_Date' => now()->format('Y-m-d'),
                'Address' => 'Demo address',
            ]);
        }

        $subject->update([
            'Grade_id' => $grade->id,
            'Classroom_id' => $classroom->id,
            'teacher_id' => $teacher->id,
        ]);
        $teacher->Sections()->syncWithoutDetaching([$section->id]);

        // Ensure the demo parent exists.
        $parent = My_Parent::firstOrCreate(
            ['email' => 'parent@school.local'],
            [
                'password' => Hash::make('password123'),
                'Name_Father' => ['en' => 'Emad', 'ar' => 'عماد محمد'],
                'National_ID_Father' => '1234567810',
                'Passport_ID_Father' => '1234567810',
                'Phone_Father' => '1234567810',
                'Job_Father' => ['en' => 'programmer', 'ar' => 'مبرمج'],
                'Nationality_Father_id' => 1,
                'Blood_Type_Father_id' => 1,
                'Religion_Father_id' => 1,
                'Address_Father' => 'القاهرة',
                'Name_Mother' => ['en' => 'Samar', 'ar' => 'سمر'],
                'National_ID_Mother' => '1234567810',
                'Passport_ID_Mother' => '1234567810',
                'Phone_Mother' => '1234567810',
                'Job_Mother' => ['en' => 'Teacher', 'ar' => 'معلمة'],
                'Nationality_Mother_id' => 1,
                'Blood_Type_Mother_id' => 1,
                'Religion_Mother_id' => 1,
                'Address_Mother' => 'القاهرة',
            ]
        );

        // Ensure a primary demo student exists and is linked to the parent.
        $primaryStudent = Student::firstOrCreate(
            ['email' => 'student@school.local'],
            [
                'name' => ['ar' => 'احمد ابراهيم', 'en' => 'Ahmed Ibrahim'],
                'password' => Hash::make('password123'),
                'Grade_id' => $grade->id,
                'Classroom_id' => $classroom->id,
                'section_id' => $section->id,
                'academic_year' => now()->year,
                'Date_Birth' => now()->subYears(10)->format('Y-m-d'),
                'gender_id' => 7,
                'nationalitie_id' => 739,
                'blood_id' => 25,
                'parent_id' => $parent->id,
            ]
        );
        if ($primaryStudent->parent_id !== $parent->id) {
            $primaryStudent->update(['parent_id' => $parent->id]);
        }

        // Ensure all existing students in the section are linked to this parent.
        Student::where('section_id', $section->id)
            ->whereNull('parent_id')
            ->update(['parent_id' => $parent->id]);

        // Prepare subjects to evaluate with. Prefer subjects in the same classroom.
        $subjects = Subject::where('Classroom_id', $classroom->id)->get();
        if ($subjects->isEmpty()) {
            $subjects = collect([$subject]);
        }

        $levels = ['bad', 'good', 'very_good', 'excellent'];
        $currentYear = now()->year;
        $createdEvaluations = 0;

        // Create evaluations for every child of this parent.
        $children = $parent->students()->get();
        foreach ($children as $student) {
            foreach ($subjects as $subjectItem) {
                foreach ([1, 2, 3, 4, 5, 6] as $month) {
                    $existing = Evaluation::where('student_id', $student->id)
                        ->where('subject_id', $subjectItem->id)
                        ->where('month', $month)
                        ->where('year', $currentYear)
                        ->first();

                    if (!$existing) {
                        Evaluation::create([
                            'student_id' => $student->id,
                            'teacher_id' => $teacher->id,
                            'subject_id' => $subjectItem->id,
                            'grade_id' => $student->Grade_id ?? $grade->id,
                            'classroom_id' => $student->Classroom_id ?? $classroom->id,
                            'section_id' => $student->section_id ?? $section->id,
                            'evaluation_level' => $levels[array_rand($levels)],
                            'notes' => 'Demo evaluation for ' . ($student->name['en'] ?? 'student'),
                            'month' => $month,
                            'year' => $currentYear,
                        ]);
                        $createdEvaluations++;
                    }
                }
            }
        }

        $this->command->info('Parent seeded: ' . $parent->email . ' (ID: ' . $parent->id . ')');
        $this->command->info('Children linked: ' . $children->count());
        $this->command->info('New evaluations created: ' . $createdEvaluations);
    }
}
