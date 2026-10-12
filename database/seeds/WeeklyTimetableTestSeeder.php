<?php

use App\Models\Classroom;
use App\Models\Gender;
use App\Models\Section;
use App\Models\Specialization;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\WeeklyProgram;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seed extra teachers + subjects for ONE classroom/section and build the
 * full weekly timetable there so it can be inspected end to end.
 *
 * - Creates teacher2..teacher6@school.local (password123), one per subject.
 * - All subjects belong to the first classroom and its first section.
 * - Each teacher is linked to the section (teacher_section pivot).
 * - Replaces the section's weekly_programs with a 5-subject x 5-day grid
 *   (Sunday .. Thursday), one period per subject per day, no overlap.
 */
class WeeklyTimetableTestSeeder extends Seeder
{
    private const TARGET_SUBJECTS = [
        ['key' => 'math', 'names' => ['en' => 'Mathematics', 'ar' => 'الرياضيات']],
        ['key' => 'arabic', 'names' => ['en' => 'Arabic', 'ar' => 'اللغة العربية']],
        ['key' => 'english', 'names' => ['en' => 'English', 'ar' => 'اللغة الإنجليزية']],
        ['key' => 'sciences', 'names' => ['en' => 'Sciences', 'ar' => 'العلوم']],
        ['key' => 'computer', 'names' => ['en' => 'Computer', 'ar' => 'الحاسب الآلي']],
    ];

    private const TEACHERS = [
        ['email' => 'teacher2@school.local', 'name' => ['en' => 'Ali Mahmoud', 'ar' => 'علي محمود'], 'subject' => 'math'],
        ['email' => 'teacher3@school.local', 'name' => ['en' => 'Mona Ibrahim', 'ar' => 'منى إبراهيم'], 'subject' => 'arabic'],
        ['email' => 'teacher4@school.local', 'name' => ['en' => 'Sara Hany', 'ar' => 'سارة هاني'], 'subject' => 'english'],
        ['email' => 'teacher5@school.local', 'name' => ['en' => 'Kareem Samir', 'ar' => 'كريم سمير'], 'subject' => 'sciences'],
        ['email' => 'teacher6@school.local', 'name' => ['en' => 'Nada Tarek', 'ar' => 'ندى طارق'], 'subject' => 'computer'],
    ];

    // [day_of_week => subject keys per period]
    private const DAY_ORDER = [
        0 => ['math', 'arabic', 'english', 'sciences', 'computer'],
        1 => ['arabic', 'english', 'math', 'computer', 'sciences'],
        2 => ['english', 'math', 'arabic', 'sciences', 'computer'],
        3 => ['sciences', 'computer', 'arabic', 'math', 'english'],
        4 => ['computer', 'sciences', 'english', 'arabic', 'math'],
    ];

    // [start, end] for the 5 daily periods
    private const PERIODS = [
        ['08:00', '08:50'],
        ['08:50', '09:40'],
        ['09:40', '10:30'],
        ['10:30', '11:20'],
        ['11:20', '12:10'],
    ];

    public function run()
    {
        $classroom = Classroom::orderBy('id')->first();
        if (! $classroom) {
            $this->command->warn('No classrooms found; seeding teachers/subjects first.');
            return;
        }

        $section = Section::where('Class_id', $classroom->id)->orderBy('id')->first();
        if (! $section) {
            $this->command->warn("No section found for classroom {$classroom->id}.");
            return;
        }

        $specializations = $this->specializationByName();
        $gender = Gender::first();

        $teacherBySubject = [];
        foreach (self::TEACHERS as $entry) {
            $specName = array_search($entry['subject'], ['english' => 'English', 'sciences' => 'Sciences', 'computer' => 'Computer', 'arabic' => 'Arabic'], true) ?: null;

            $teacher = Teacher::firstOrCreate(
                ['email' => $entry['email']],
                [
                    'password' => Hash::make('password123'),
                    'Name' => $entry['name'],
                    'Specialization_id' => ($specName && isset($specializations[$specName]))
                        ? $specializations[$specName]->id
                        : (isset($specializations[0])
                            ? $specializations[0]->id
                            // Never hardcode 1 — auto-increment moves on re-seed
                            // and id 1 may no longer exist (FK violation).
                            : (Specialization::query()->first()?->id ?? 1)),
                    'Gender_id' => $gender ? $gender->id : 1,
                    'Joining_Date' => '2021-09-01',
                    'Address' => '12 Demo Street, Demo City',
                ],
            );

            DB::table('teacher_section')->updateOrInsert([
                'teacher_id' => $teacher->id,
                'section_id' => $section->id,
            ]);

            $teacherBySubject[$entry['subject']] = $teacher;
            $this->command->info("Teacher ready: {$entry['email']} -> subject {$entry['subject']}.");
        }

        $subjectsByKey = [];
        foreach (self::TARGET_SUBJECTS as $item) {
            $subject = $this->findOrCreateSubject($classroom, $teacherBySubject[$item['key']], $item['names']);
            $subjectsByKey[$item['key']] = $subject;
            $this->command->info("Subject ready: {$item['names']['en']} (id {$subject->id}) -> teacher " . $subject->teacher_id . '.');
        }

        $this->seedSectionTimetable($section, $subjectsByKey);
    }

    protected function findOrCreateSubject(Classroom $classroom, Teacher $teacher, array $names): Subject
    {
        $existing = Subject::where('Classroom_id', $classroom->id)->get()->first(function ($s) use ($names) {
            $name = $s->Name;
            if (is_string($name)) {
                $decoded = json_decode($name, true);
                if (is_array($decoded)) {
                    $name = $decoded;
                }
            }
            $en = is_array($name) ? ($name['en'] ?? null) : (is_string($name) ? $name : null);

            return $en === $names['en'];
        });

        if ($existing) {
            $existing->teacher_id = $teacher->id;
            $existing->save();

            return $existing;
        }

        return Subject::create([
            'Name' => $names,
            'Grade_id' => $classroom->Grade_id,
            'Classroom_id' => $classroom->id,
            'teacher_id' => $teacher->id,
        ]);
    }

    protected function specializationByName(): array
    {
        $result = [];
        foreach (Specialization::all() as $spec) {
            $name = $spec->Name;
            if (is_array($name)) {
                $result[$name['en'] ?? ''] = $spec;
            } elseif (is_string($name)) {
                $result[$name] = $spec;
            }
        }

        return $result;
    }

    protected function seedSectionTimetable(Section $section, array $subjectsByKey): int
    {
        WeeklyProgram::where('section_id', $section->id)->delete();

        $count = 0;
        foreach (self::DAY_ORDER as $day => $subjectKeys) {
            foreach (self::PERIODS as $i => [$start, $end]) {
                $subject = $subjectsByKey[$subjectKeys[$i]] ?? null;
                if (! $subject) {
                    continue;
                }

                WeeklyProgram::create([
                    'section_id' => $section->id,
                    'day_of_week' => $day,
                    'start_time' => $start,
                    'end_time' => $end,
                    'subject_id' => $subject->id,
                    'teacher_id' => $subject->teacher_id,
                    'location' => 'Room ' . (100 + $section->id),
                    'notes' => null,
                    'is_active' => true,
                ]);
                $count++;
            }
        }

        $this->command->info("Section {$section->id} timetable: {$count} weekly sessions seeded.");

        return $count;
    }
}