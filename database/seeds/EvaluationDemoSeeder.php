<?php

use App\Models\Student;
use App\Models\Grade;
use App\Models\Classroom;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EvaluationDemoSeeder extends Seeder
{
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
                'Password' => Hash::make('password'),
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

        // Ensure the teacher is linked to the section so teacher-scoped endpoints work.
        $teacher->Sections()->syncWithoutDetaching([$section->id]);
        // Update the name directly to avoid translatable attribute case mismatch.
        DB::table('subjects')->where('id', $subject->id)->update(['name' => 'Mathematics']);

        $parent = \App\Models\My_Parent::first();
        $parentId = $parent ? $parent->id : null;

        $demoStudents = [
            ['name_en' => 'Ahmed Ali', 'name_ar' => 'أحمد علي', 'email' => 'ahmed.ali@student.test'],
            ['name_en' => 'Sara Hassan', 'name_ar' => 'سارة حسن', 'email' => 'sara.hassan@student.test'],
            ['name_en' => 'Omar Khaled', 'name_ar' => 'عمر خالد', 'email' => 'omar.khaled@student.test'],
            ['name_en' => 'Lina Mahmoud', 'name_ar' => 'لينا محمود', 'email' => 'lina.mahmoud@student.test'],
            ['name_en' => 'Youssef Tarek', 'name_ar' => 'يوسف طارق', 'email' => 'youssef.tarek@student.test'],
            ['name_en' => 'Nour Adel', 'name_ar' => 'نور عادل', 'email' => 'nour.adel@student.test'],
            ['name_en' => 'Khaled Samir', 'name_ar' => 'خالد سمير', 'email' => 'khaled.samir@student.test'],
            ['name_en' => 'Mariam Said', 'name_ar' => 'مريم سعيد', 'email' => 'mariam.said@student.test'],
        ];

        $created = [];
        foreach ($demoStudents as $index => $data) {
            $student = Student::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => ['en' => $data['name_en'], 'ar' => $data['name_ar']],
                    'password' => Hash::make('password'),
                    'Grade_id' => $grade->id,
                    'Classroom_id' => $classroom->id,
                    'section_id' => $section->id,
                    'academic_year' => now()->year,
                    'Date_Birth' => now()->subYears(10)->format('Y-m-d'),
                    'gender_id' => ($index % 2) === 0 ? 7 : 8,
                    'nationalitie_id' => 739,
                    'blood_id' => 25,
                    'parent_id' => $parentId,
                ]
            );
            $created[] = $student;
        }

        $this->command->info('Evaluation demo data seeded successfully.');
        $gradeName = is_array($grade->Name) ? ($grade->Name['en'] ?? $grade->Name['ar'] ?? reset($grade->Name)) : $grade->Name;
        $classroomName = is_array($classroom->Name_Class) ? ($classroom->Name_Class['en'] ?? $classroom->Name_Class['ar'] ?? reset($classroom->Name_Class)) : $classroom->Name_Class;
        $sectionName = is_array($section->Name_Section) ? ($section->Name_Section['en'] ?? $section->Name_Section['ar'] ?? reset($section->Name_Section)) : $section->Name_Section;
        $this->command->info('Grade:     ' . $grade->id . ' - ' . $gradeName);
        $this->command->info('Classroom: ' . $classroom->id . ' - ' . $classroomName);
        $this->command->info('Section:   ' . $section->id . ' - ' . $sectionName);
        $subjectName = is_array($subject->name) ? ($subject->name['en'] ?? $subject->name['ar'] ?? reset($subject->name)) : $subject->name;
        $this->command->info('Subject:   ' . $subject->id . ' - ' . $subjectName);
        $this->command->info('Teacher:   ' . $teacher->id . ' - ' . ($teacher->Name_en ?? $teacher->Email));
        $this->command->info('Students created: ' . count($created));
    }
}
