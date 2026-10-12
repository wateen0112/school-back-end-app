<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Section;
use App\Models\Subject;
use App\Models\SectionSubjectTeacher;

class SectionSubjectTeacherSeeder extends Seeder
{
    /**
     * Migrate legacy subject.teacher_id / subject.Classroom_id data into the
     * new section_subject_teacher pivot so the optimizer can use it.
     */
    public function run()
    {
        $subjects = Subject::whereNotNull('teacher_id')->get();

        foreach ($subjects as $subject) {
            $query = Section::query();

            if ($subject->Grade_id) {
                $query->where('Grade_id', $subject->Grade_id);
            }

            if ($subject->Classroom_id) {
                $query->where('Class_id', $subject->Classroom_id);
            }

            foreach ($query->get() as $section) {
                SectionSubjectTeacher::firstOrCreate(
                    [
                        'section_id' => $section->id,
                        'subject_id' => $subject->id,
                    ],
                    [
                        'teacher_id' => $subject->teacher_id,
                    ]
                );
            }
        }

        $this->command->info('Migrated ' . SectionSubjectTeacher::count() . ' section-subject-teacher assignments.');
    }
}
