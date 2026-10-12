<?php

use App\Models\Gender;
use App\Models\My_Parent;
use App\Models\Nationalitie;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Type_Blood;
use App\Models\WeeklyProgram;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ParentChildrenTimetableSeeder extends Seeder
{
    private const PARENT_EMAIL = 'parent1@school.local';

    /**
     * Parent one extra child and seed their weekly timetable.
     *
     * - Creates a second demo child (student2@school.local) linked to the
     *   demo parent parent1@school.local, placed in the sibling section (B)
     *   of the same classroom as the existing child.
     * - Seeds a full weekly timetable (day 0=Sunday .. 4=Thursday) for the
     *   sections of EVERY child of that parent, using the subjects assigned
     *   to their classroom.
     */
    public function run()
    {
        $parent = My_Parent::where('email', self::PARENT_EMAIL)->first();
        if (! $parent) {
            $this->command->warn('Parent not found: ' . self::PARENT_EMAIL);
            return;
        }

        $this->ensureSecondChild($parent);

        $children = $parent->students()->get();
        $sectionIds = $children->pluck('section_id')->unique()->values();

        if ($sectionIds->isEmpty()) {
            $this->command->warn('Parent has no children to schedule.');
            return;
        }

        foreach ($sectionIds as $sectionId) {
            $seeded = $this->seedSectionTimetable((int) $sectionId);
            $this->command->info("Section {$sectionId}: {$seeded} timetable sessions ensured.");
        }
    }

    protected function ensureSecondChild(My_Parent $parent): void
    {
        $email = 'student2@school.local';
        if (Student::where('email', $email)->exists()) {
            $this->command->info("Child {$email} already exists.");
            return;
        }

        $firstChild = $parent->students()->orderBy('id')->first();

        // Classroom id + grade come from the parent's first child.
        $gradeId = $firstChild->Grade_id ?? 1;
        $classroomId = $firstChild->Classroom_id ?? 1;

        // Sibling section (B) in the same classroom, else the classroom's first section.
        $section = Section::where('Grade_id', $gradeId)
            ->where('Class_id', $classroomId)
            ->orderBy('id')
            ->where('id', '!=', $firstChild->section_id ?? 0)
            ->first()
            ?? Section::where('Grade_id', $gradeId)
                ->where('Class_id', $classroomId)
                ->orderBy('id')
                ->first();

        $gender = Gender::first();
        $nationality = Nationalitie::first();
        $blood = Type_Blood::first();

        Student::create([
            'name' => ['en' => 'Youssef Mohammed', 'ar' => 'يوسف محمد'],
            'email' => $email,
            'password' => Hash::make('password123'),
            'gender_id' => $gender ? $gender->id : 1,
            'nationalitie_id' => $nationality ? $nationality->id : 1,
            'blood_id' => $blood ? $blood->id : 1,
            'Date_Birth' => '2015-08-22',
            'Grade_id' => $gradeId,
            'Classroom_id' => $classroomId,
            'section_id' => $section ? $section->id : $firstChild->section_id,
            'parent_id' => $parent->id,
            'academic_year' => $firstChild->academic_year ?: date('Y') . '-' . (date('Y') + 1),
        ]);

        $this->command->info("Created new child {$email} -> section " . ($section ? $section->id : 'n/a') . '.');
    }

    protected function seedSectionTimetable(int $sectionId): int
    {
        $section = Section::find($sectionId);
        if (! $section) {
            $this->command->warn("Section {$sectionId} not found; skipping timetable.");
            return 0;
        }

        $classroomId = (int) $section->Class_id;
        $subjects = Subject::where('Classroom_id', $classroomId)->get()->keyBy(function ($s) {
            return strtolower(($s->Name['en'] ?? $s->Name) ?? '');
        });

        $math = $subjects->get('mathematics');
        $arabic = $subjects->get('arabic');

        if (! $math && ! $arabic) {
            $this->command->warn("No subjects for classroom {$classroomId}; using any available subject.");
            $math = $subjects->first();
            $arabic = $subjects->get(1) ?? $subjects->first();
        }

        $periodMin = 45;
        // [day_of_week, start_time, subject]
        $sessions = [
            [0, '08:00', 'math'],
            [0, '08:50', 'arabic'],
            [1, '08:00', 'arabic'],
            [1, '08:50', 'math'],
            [2, '09:40', 'math'],
            [2, '10:30', 'arabic'],
            [3, '08:00', 'math'],
            [3, '08:50', 'arabic'],
            [4, '10:30', 'math'],
            [4, '11:20', 'arabic'],
        ];

        $count = 0;
        foreach ($sessions as [$day, $start, $which]) {
            $subject = $which === 'math' ? $math : $arabic;
            if (! $subject) {
                continue;
            }
            $existing = WeeklyProgram::where('section_id', $sectionId)
                ->where('day_of_week', $day)
                ->where('start_time', $start)
                ->exists();
            if ($existing) {
                continue;
            }

            $startDt = \DateTime::createFromFormat('H:i', $start);
            $startDt->add(new \DateInterval("PT{$periodMin}M"));

            WeeklyProgram::create([
                'section_id' => $sectionId,
                'day_of_week' => $day,
                'start_time' => $start,
                'end_time' => $startDt->format('H:i'),
                'subject_id' => $subject->id,
                'teacher_id' => $subject->teacher_id ?: 1,
                'location' => 'Room ' . ($sectionId + 100),
                'notes' => null,
                'is_active' => true,
            ]);
            $count++;
        }

        return $count;
    }
}