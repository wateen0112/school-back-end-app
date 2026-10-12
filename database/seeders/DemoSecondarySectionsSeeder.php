<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\Gender;
use App\Models\Grade;
use App\Models\My_Parent;
use App\Models\Nationalitie;
use App\Models\Religion;
use App\Models\School;
use App\Models\Section;
use App\Models\SectionSubjectTeacher;
use App\Models\Specialization;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Type_Blood;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds two real exam classes end to end, each with five single-gender sections:
 *
 *   - الصف الثالث المتوسط  (Grade 9, middle school stage)
 *   - الصف الثالث الثانوي  (Grade 12, high school stage — "بكالوريا")
 *
 * Every section is either boys-only or girls-only and is populated with students of
 * matching gender. Each class also gets a subject curriculum, one teacher per subject,
 * and the `section_subject_teacher` / `teacher_section` assignments so every section
 * has its own teaching staff.
 *
 * Section names follow the school convention
 *   "الصف التاسع - شعبة 1 - ذكور"  /  "بكالوريا - شعبة 1 - ذكور"
 *
 * The seeder is idempotent: sections are matched by their translated name, teachers
 * and students by e-mail, and the pivots by their natural key, so re-running it
 * updates in place instead of duplicating.
 *
 * Usage:
 *   php artisan db:seed --class="Database\Seeders\DemoSecondarySectionsSeeder"
 *   php artisan db:seed --class="Database\Seeders\DemoSecondarySectionsSeeder" --school=1 --year=2026-2027
 *
 * Demo credentials: every teacher / parent / student uses the password "password123".
 */
class DemoSecondarySectionsSeeder extends Seeder
{
    /** Password given to every seeded account. */
    private const PASSWORD = 'password123';

    /** Sections created per class. */
    private const SECTIONS_PER_CLASS = 5;

    /** Students enrolled in each section. */
    private const STUDENTS_PER_SECTION = 20;

    /**
     * Gender of each section, in order. Sections are single-gender: three boys'
     * sections followed by two girls' sections.
     *
     * @var array<int, string>
     */
    private const SECTION_GENDERS = ['male', 'male', 'male', 'female', 'female'];

    /**
     * The two classes to seed, with their curriculum. Each subject carries the
     * teacher who owns it, so subjects and staff stay in sync.
     */
    private const CLASSES = [
        [
            'key' => 'g9',
            'stage' => ['en' => 'Middle School', 'ar' => 'المرحلة المتوسطة'],
            'classroom' => ['en' => 'Grade 9', 'ar' => 'الصف التاسع'],
            // Used to build the section name: "الصف التاسع - شعبة 1 - ذكور".
            'section_label' => ['en' => 'Grade 9', 'ar' => 'الصف التاسع'],
            'birth_year' => 2011,
            'serial_offset' => 0,
            'subjects' => [
                [
                    'subject' => ['en' => 'Arabic', 'ar' => 'اللغة العربية'],
                    'teacher' => ['email' => 'g9.teacher.arabic@school.local', 'en' => 'Rania Haddad', 'ar' => 'رانيا حداد', 'gender' => 'female', 'specialization' => 'spec_arabic', 'salary' => 3400],
                ],
                [
                    'subject' => ['en' => 'Mathematics', 'ar' => 'الرياضيات'],
                    'teacher' => ['email' => 'g9.teacher.math@school.local', 'en' => 'Bassem Nassar', 'ar' => 'باسم نصار', 'gender' => 'male', 'specialization' => 'spec_sciences', 'salary' => 3600],
                ],
                [
                    'subject' => ['en' => 'English', 'ar' => 'اللغة الإنجليزية'],
                    'teacher' => ['email' => 'g9.teacher.english@school.local', 'en' => 'Jana Fares', 'ar' => 'جانا فارس', 'gender' => 'female', 'specialization' => 'spec_english', 'salary' => 3300],
                ],
                [
                    'subject' => ['en' => 'Sciences', 'ar' => 'العلوم'],
                    'teacher' => ['email' => 'g9.teacher.sciences@school.local', 'en' => 'Tareq Zayan', 'ar' => 'طارق زيان', 'gender' => 'male', 'specialization' => 'spec_sciences', 'salary' => 3500],
                ],
                [
                    'subject' => ['en' => 'Islamic Education', 'ar' => 'التربية الإسلامية'],
                    'teacher' => ['email' => 'g9.teacher.islamic@school.local', 'en' => 'Salim Jaber', 'ar' => 'سليم جابر', 'gender' => 'male', 'specialization' => 'spec_arabic', 'salary' => 3100],
                ],
                [
                    'subject' => ['en' => 'Computer', 'ar' => 'الحاسب الآلي'],
                    'teacher' => ['email' => 'g9.teacher.computer@school.local', 'en' => 'Maha Soueidan', 'ar' => 'مها سويدان', 'gender' => 'female', 'specialization' => 'spec_computer', 'salary' => 3200],
                ],
                [
                    'subject' => ['en' => 'Social Studies', 'ar' => 'الدراسات الاجتماعية'],
                    'teacher' => ['email' => 'g9.teacher.social@school.local', 'en' => 'Riham Abboud', 'ar' => 'ريهام عبود', 'gender' => 'female', 'specialization' => 'spec_sciences', 'salary' => 3050],
                ],
                [
                    'subject' => ['en' => 'Physical Education', 'ar' => 'التربية الرياضية'],
                    'teacher' => ['email' => 'g9.teacher.pe@school.local', 'en' => 'Fadi Hamwi', 'ar' => 'فادي حموي', 'gender' => 'male', 'specialization' => 'spec_sciences', 'salary' => 3000],
                ],
            ],
        ],
        [
            'key' => 'g12',
            'stage' => ['en' => 'High School', 'ar' => 'المرحلة الثانوية'],
            'classroom' => ['en' => 'Grade 12', 'ar' => 'الصف الثالث الثانوي'],
            // The graduating class is referred to as "بكالوريا" in section names.
            'section_label' => ['en' => 'Baccalaureate', 'ar' => 'بكالوريا'],
            'birth_year' => 2008,
            'serial_offset' => 10000,
            'subjects' => [
                [
                    'subject' => ['en' => 'Arabic', 'ar' => 'اللغة العربية'],
                    'teacher' => ['email' => 'g12.teacher.arabic@school.local', 'en' => 'Sawsan Daher', 'ar' => 'سوسن ظاهر', 'gender' => 'female', 'specialization' => 'spec_arabic', 'salary' => 3800],
                ],
                [
                    'subject' => ['en' => 'Mathematics', 'ar' => 'الرياضيات'],
                    'teacher' => ['email' => 'g12.teacher.math@school.local', 'en' => 'Iyad Sabbagh', 'ar' => 'إياد الصباغ', 'gender' => 'male', 'specialization' => 'spec_sciences', 'salary' => 4000],
                ],
                [
                    'subject' => ['en' => 'English', 'ar' => 'اللغة الإنجليزية'],
                    'teacher' => ['email' => 'g12.teacher.english@school.local', 'en' => 'Nour Karam', 'ar' => 'نور كرم', 'gender' => 'female', 'specialization' => 'spec_english', 'salary' => 3700],
                ],
                [
                    'subject' => ['en' => 'Physics', 'ar' => 'الفيزياء'],
                    'teacher' => ['email' => 'g12.teacher.physics@school.local', 'en' => 'Ayman Ghali', 'ar' => 'أيمن غالي', 'gender' => 'male', 'specialization' => 'spec_sciences', 'salary' => 3900],
                ],
                [
                    'subject' => ['en' => 'Chemistry', 'ar' => 'الكيمياء'],
                    'teacher' => ['email' => 'g12.teacher.chemistry@school.local', 'en' => 'Hiba Mounir', 'ar' => 'هبة منير', 'gender' => 'female', 'specialization' => 'spec_sciences', 'salary' => 3800],
                ],
                [
                    'subject' => ['en' => 'Biology', 'ar' => 'الأحياء'],
                    'teacher' => ['email' => 'g12.teacher.biology@school.local', 'en' => 'Ziad Attar', 'ar' => 'زياد عطار', 'gender' => 'male', 'specialization' => 'spec_sciences', 'salary' => 3750],
                ],
                [
                    'subject' => ['en' => 'Islamic Education', 'ar' => 'التربية الإسلامية'],
                    'teacher' => ['email' => 'g12.teacher.islamic@school.local', 'en' => 'Wael Shaheen', 'ar' => 'وائل شاهين', 'gender' => 'male', 'specialization' => 'spec_arabic', 'salary' => 3450],
                ],
                [
                    'subject' => ['en' => 'Computer', 'ar' => 'الحاسب الآلي'],
                    'teacher' => ['email' => 'g12.teacher.computer@school.local', 'en' => 'Salma Odeh', 'ar' => 'سلمى عودة', 'gender' => 'female', 'specialization' => 'spec_computer', 'salary' => 3550],
                ],
            ],
        ],
    ];

    /** Arabic / English given names used to compose student and parent names. */
    private const MALE_NAMES = [
        ['ar' => 'محمد', 'en' => 'Mohamed'], ['ar' => 'أحمد', 'en' => 'Ahmed'],
        ['ar' => 'علي', 'en' => 'Ali'], ['ar' => 'عمر', 'en' => 'Omar'],
        ['ar' => 'يوسف', 'en' => 'Youssef'], ['ar' => 'خالد', 'en' => 'Khaled'],
        ['ar' => 'زياد', 'en' => 'Ziad'], ['ar' => 'مازن', 'en' => 'Mazen'],
        ['ar' => 'سيف', 'en' => 'Seif'], ['ar' => 'رامي', 'en' => 'Rami'],
        ['ar' => 'باسم', 'en' => 'Bassam'], ['ar' => 'أنس', 'en' => 'Anas'],
        ['ar' => 'فادي', 'en' => 'Fadi'], ['ar' => 'جاد', 'en' => 'Jad'],
        ['ar' => 'رائد', 'en' => 'Raed'],
    ];

    private const FEMALE_NAMES = [
        ['ar' => 'فاطمة', 'en' => 'Fatima'], ['ar' => 'مريم', 'en' => 'Mariam'],
        ['ar' => 'سارة', 'en' => 'Sara'], ['ar' => 'هبة', 'en' => 'Heba'],
        ['ar' => 'ندى', 'en' => 'Nada'], ['ar' => 'رنا', 'en' => 'Rana'],
        ['ar' => 'ليان', 'en' => 'Layan'], ['ar' => 'تالا', 'en' => 'Tala'],
        ['ar' => 'رهف', 'en' => 'Rahaf'], ['ar' => 'نور', 'en' => 'Nour'],
        ['ar' => 'سلمى', 'en' => 'Salma'], ['ar' => 'إسراء', 'en' => 'Esraa'],
        ['ar' => 'دانا', 'en' => 'Dana'], ['ar' => 'يارا', 'en' => 'Yara'],
        ['ar' => 'مها', 'en' => 'Maha'],
    ];

    /** Family names, so a section reads as a real class rather than repeated names. */
    private const FAMILY_NAMES = [
        ['ar' => 'الأحمد', 'en' => 'Al Ahmad'], ['ar' => 'الحلبي', 'en' => 'Al Helby'],
        ['ar' => 'المصري', 'en' => 'Al Masri'], ['ar' => 'الدمشقي', 'en' => 'Al Dimashqi'],
        ['ar' => 'العلي', 'en' => 'Al Ali'], ['ar' => 'الحسن', 'en' => 'Al Hassan'],
        ['ar' => 'الخطيب', 'en' => 'Al Khatib'], ['ar' => 'طرابلسي', 'en' => 'Traboulsi'],
        ['ar' => 'الحلوان', 'en' => 'Al Helwan'], ['ar' => 'العبيدي', 'en' => 'Al Obeidi'],
        ['ar' => 'الكيلاني', 'en' => 'Al Kilani'], ['ar' => 'الشامي', 'en' => 'Al Shami'],
        ['ar' => 'النجار', 'en' => 'Al Najjar'], ['ar' => 'العساف', 'en' => 'Al Assaf'],
        ['ar' => 'الحمصي', 'en' => 'Al Homssi'], ['ar' => 'الزعبي', 'en' => 'Al Zoubey'],
        ['ar' => 'الرفاعي', 'en' => 'Al Refaee'], ['ar' => 'أبو ريدة', 'en' => 'Abu Rayda'],
        ['ar' => 'بو حلاق', 'en' => 'Bou Hallak'], ['ar' => 'الديب', 'en' => 'Al Deeb'],
    ];

    private const FATHER_JOBS = [
        ['ar' => 'محاسب', 'en' => 'Accountant'], ['ar' => 'مهندس', 'en' => 'Engineer'],
        ['ar' => 'تاجر', 'en' => 'Merchant'], ['ar' => 'معلم', 'en' => 'Teacher'],
        ['ar' => 'طبيب', 'en' => 'Doctor'], ['ar' => 'محامي', 'en' => 'Lawyer'],
        ['ar' => 'صيدلي', 'en' => 'Pharmacist'], ['ar' => 'نجار', 'en' => 'Carpenter'],
        ['ar' => 'سائق', 'en' => 'Driver'], ['ar' => 'ممرض', 'en' => 'Nurse'],
    ];

    private const MOTHER_JOBS = [
        ['ar' => 'ربة منزل', 'en' => 'Housewife'], ['ar' => 'معلمة', 'en' => 'Teacher'],
        ['ar' => 'ممرضة', 'en' => 'Nurse'], ['ar' => 'مصممة', 'en' => 'Designer'],
        ['ar' => 'محاسبة', 'en' => 'Accountant'], ['ar' => 'مصممة داخلية', 'en' => 'Interior Designer'],
    ];

    /** @var int */
    private $schoolId;

    /** @var string */
    private $academicYear;

    /**
     * Laravel's `db:seed` does not forward unknown CLI flags, so named parameters are
     * accepted as well (usable via `$this->callWith([...], ['school' => 1])`) and fall back
     * to options when the invoking command defines them.
     */
    public function run(?string $school = null, ?string $year = null, ?int $students = null): void
    {
        $this->schoolId = $this->resolveSchoolId($school);
        $this->academicYear = $this->resolveAcademicYear($year);

        $lookups = $this->resolveLookups();

        foreach (self::CLASSES as $definition) {
            $this->seedClass($definition, $lookups, $students);
        }
    }

    /**
     * Read a CLI option, tolerating being invoked from another seeder.
     */
    private function option(string $key): ?string
    {
        if ($this->command === null || ! $this->command->getDefinition()->hasOption($key)) {
            return null;
        }

        $value = $this->command->option($key);

        return $value === null || $value === '' ? null : (string) $value;
    }

    /**
     * School (tenant) the classes belong to.
     */
    private function resolveSchoolId(?string $requested = null): int
    {
        $requested = $requested ?? $this->option('school');

        if ($requested !== null) {
            $school = School::query()
                ->where('id', $requested)
                ->orWhere('email', $requested)
                ->first();

            if (! $school) {
                $this->command?->error("No school matched \"{$requested}\".");

                throw new \RuntimeException('Unknown school: '.$requested);
            }

            return (int) $school->id;
        }

        $school = School::query()->orderBy('id')->first();

        if (! $school) {
            $this->command?->error('No school exists yet. Create one first or pass a school id/email to the seeder.');

            throw new \RuntimeException('Cannot seed sections without a school.');
        }

        return (int) $school->id;
    }

    private function resolveAcademicYear(?string $requested = null): string
    {
        $requested = $requested ?? $this->option('year');

        if ($requested !== null) {
            return $requested;
        }

        // The school year starts in September: Sep 2026 -> 2026-2027, Aug 2026 -> 2025-2026.
        $start = (int) date('Y') + (date('n') >= 9 ? 0 : 1);

        return $start.'-'.($start + 1);
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, mixed>  $lookups
     */
    private function seedClass(array $definition, array $lookups, ?int $studentOverride = null): void
    {
        $key = $definition['key'];

        $stage = $this->ensureStage($definition['stage']);
        $classroom = $this->ensureClassroom($stage, $definition['classroom']);

        $sections = $this->ensureSections($stage, $classroom, $definition['section_label']);
        $subjects = $this->seedSubjects($stage, $classroom, $definition['subjects'], $lookups);

        $this->assignTeachersToSections($sections, $subjects);

        $perSection = $studentOverride ?? $this->option('students');
        $perSection = $perSection !== null
            ? max(1, (int) $perSection)
            : self::STUDENTS_PER_SECTION;

        $students = $this->seedStudents(
            $key,
            $definition['birth_year'],
            (int) ($definition['serial_offset'] ?? 0),
            $stage,
            $classroom,
            $sections,
            $lookups,
            $perSection
        );

        $this->report($key, $definition, $stage, $classroom, $sections, $subjects, $students);
    }

    /**
     * The `Grades` row is the stage (primary / middle / high school), not the grade itself.
     *
     * @param  array{en: string, ar: string}  $name
     */
    private function ensureStage(array $name): Grade
    {
        $stage = Grade::where('Name->en', $name['en'])
            ->orWhere('Name->ar', $name['ar'])
            ->orderBy('id')
            ->first();

        if (! $stage) {
            $stage = Grade::create(['Name' => $name]);
            $this->command?->info('Created missing level: '.$name['en'].'.');
        }

        return $stage;
    }

    /**
     * @param  array{en: string, ar: string}  $name
     */
    private function ensureClassroom(Grade $stage, array $name): Classroom
    {
        $classroom = Classroom::where('Name_Class->en', $name['en'])
            ->where('Grade_id', $stage->id)
            ->orderBy('id')
            ->first();

        if (! $classroom) {
            $classroom = Classroom::create([
                'Name_Class' => $name,
                'Grade_id' => $stage->id,
                'capacity' => 30,
            ]);

            $this->command?->info('Created missing classroom: '.$name['en'].'.');
        }

        return $classroom;
    }

    /**
     * Five single-gender sections, named "<class> - Section N - Boys/Girls".
     *
     * @param  array{en: string, ar: string}  $label
     * @return array<int, Section>
     */
    private function ensureSections(Grade $stage, Classroom $classroom, array $label): array
    {
        $sections = [];

        foreach (self::SECTION_GENDERS as $index => $genderKey) {
            $number = $index + 1;
            $isMale = $genderKey === 'male';

            $name = [
                'en' => $label['en'].' - Section '.$number.' - '.($isMale ? 'Boys' : 'Girls'),
                'ar' => $label['ar'].' - شعبة '.$number.' - '.($isMale ? 'ذكور' : 'إناث'),
            ];

            $section = Section::where('Name_Section->en', $name['en'])
                ->where('Grade_id', $stage->id)
                ->where('Class_id', $classroom->id)
                ->orderBy('id')
                ->first();

            if (! $section) {
                $section = new Section();
                $section->Name_Section = $name;
                $section->Status = 1;
                $section->Grade_id = $stage->id;
                $section->Class_id = $classroom->id;
                $section->save();

                $this->command?->info('Created missing section: '.$name['en'].'.');
            } elseif ((int) $section->Status !== 1) {
                $section->update(['Status' => 1]);
            }

            $sections[] = $section;
        }

        return $sections;
    }

    /**
     * Reference data resolved by name so the seeder does not depend on hard-coded ids.
     *
     * @return array<string, mixed>
     */
    private function resolveLookups(): array
    {
        return [
            'male' => $this->ensureGender('Male', 'ذكر'),
            'female' => $this->ensureGender('Female', 'أنثى'),
            'nationality' => $this->firstOrCreateLookup(Nationalitie::class, 'Egyptian', 'مصري'),
            'blood' => $this->ensureBloodType('O+'),
            'religion' => $this->firstOrCreateLookup(Religion::class, 'Muslim', 'مسلم'),
            'spec_arabic' => $this->firstOrCreateLookup(Specialization::class, 'Arabic', 'عربي'),
            'spec_sciences' => $this->firstOrCreateLookup(Specialization::class, 'Sciences', 'علوم'),
            'spec_computer' => $this->firstOrCreateLookup(Specialization::class, 'Computer', 'حاسب الي'),
            'spec_english' => $this->firstOrCreateLookup(Specialization::class, 'English', 'انجليزي'),
        ];
    }

    private function ensureGender(string $en, string $ar): Gender
    {
        $gender = Gender::where('Name->en', $en)->orderBy('id')->first();

        if (! $gender) {
            $gender = Gender::create(['Name' => ['en' => $en, 'ar' => $ar]]);
        }

        return $gender;
    }

    /** Blood types are a plain string column, not a translatable JSON one. */
    private function ensureBloodType(string $name): Type_Blood
    {
        return Type_Blood::where('Name', $name)->orderBy('id')->first()
            ?? Type_Blood::create(['Name' => $name]);
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    private function firstOrCreateLookup(string $model, string $en, string $ar)
    {
        $found = $model::where('Name->en', $en)->orderBy('id')->first()
            ?: $model::where('Name', $en)->orderBy('id')->first();

        if (! $found) {
            $found = $model::create(['Name' => ['en' => $en, 'ar' => $ar]]);
        }

        return $found;
    }

    /**
     * One teacher per subject, plus the subject row itself.
     *
     * The legacy `subjects.teacher_id` column is kept in sync with the pivot so both
     * the timetable optimizer and the older screens resolve the same teacher.
     *
     * @param  array<int, array<string, mixed>>  $definitions
     * @param  array<string, mixed>  $lookups
     * @return array<int, array{subject: Subject, teacher: Teacher}>
     */
    private function seedSubjects(Grade $stage, Classroom $classroom, array $definitions, array $lookups): array
    {
        $result = [];

        foreach ($definitions as $definition) {
            $subjectName = $definition['subject'];
            $teacher = $this->seedTeacher($definition['teacher'], $lookups);

            $subject = Subject::where('Name->en', $subjectName['en'])
                ->where('Grade_id', $stage->id)
                ->where('Classroom_id', $classroom->id)
                ->orderBy('id')
                ->first();

            if (! $subject) {
                $subject = new Subject();
            }

            $subject->Name = $subjectName;
            $subject->Grade_id = $stage->id;
            $subject->Classroom_id = $classroom->id;
            $subject->teacher_id = $teacher->id;
            $subject->save();

            $result[] = ['subject' => $subject, 'teacher' => $teacher];
        }

        $this->command?->info('  Materials ready: '.count($result).' subjects with their teachers.');

        return $result;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, mixed>  $lookups
     */
    private function seedTeacher(array $definition, array $lookups): Teacher
    {
        $teacher = Teacher::where('email', $definition['email'])->first() ?: new Teacher();

        $teacher->email = $definition['email'];
        $teacher->Name = ['en' => $definition['en'], 'ar' => $definition['ar']];
        $teacher->Gender_id = $lookups[$definition['gender']]->id;
        $teacher->Specialization_id = $lookups[$definition['specialization']]->id;
        $teacher->Joining_Date = '2022-09-01';
        $teacher->Address = 'Demo City';
        $teacher->salary = $definition['salary'];
        $teacher->school_id = $this->schoolId;

        if (! $teacher->password) {
            $teacher->password = Hash::make(self::PASSWORD);
        }

        $teacher->save();

        return $teacher;
    }

    /**
     * Give every section the full curriculum with its own teacher, and mirror the
     * assignment into the legacy `teacher_section` pivot the older screens read.
     *
     * @param  array<int, Section>  $sections
     * @param  array<int, array{subject: Subject, teacher: Teacher}>  $subjects
     */
    private function assignTeachersToSections(array $sections, array $subjects): void
    {
        foreach ($sections as $section) {
            foreach ($subjects as $entry) {
                SectionSubjectTeacher::updateOrCreate(
                    [
                        'section_id' => $section->id,
                        'subject_id' => $entry['subject']->id,
                    ],
                    [
                        'teacher_id' => $entry['teacher']->id,
                    ]
                );

                DB::table('teacher_section')->updateOrInsert(
                    [
                        'teacher_id' => $entry['teacher']->id,
                        'section_id' => $section->id,
                    ],
                    []
                );
            }
        }

        $this->command?->info(
            '  Teaching assignments: '.(count($sections) * count($subjects)).' section/subject pairs.'
        );
    }

    /**
     * Enrol students into each section, matching the section's gender, one parent each.
     *
     * @param  array<int, Section>  $sections
     * @param  array<string, mixed>  $lookups
     * @return int total students created
     */
    private function seedStudents(
        string $key,
        int $birthYear,
        int $serialOffset,
        Grade $stage,
        Classroom $classroom,
        array $sections,
        array $lookups,
        int $perSection
    ): int {
        $total = 0;

        foreach ($sections as $index => $section) {
            $sectionNumber = $index + 1;
            $isMale = self::SECTION_GENDERS[$index] === 'male';
            $givenNames = $isMale ? self::MALE_NAMES : self::FEMALE_NAMES;

            for ($i = 1; $i <= $perSection; $i++) {
                $studentEmail = sprintf('%s.sec%d.s%02d@school.local', $key, $sectionNumber, $i);
                $parentEmail = sprintf('%s.parent.sec%d.s%02d@school.local', $key, $sectionNumber, $i);

                // Deterministic but varied: rotate the name pools per index.
                $given = $givenNames[($i - 1) % count($givenNames)];
                $family = self::FAMILY_NAMES[(($i - 1) + ($sectionNumber - 1)) % count(self::FAMILY_NAMES)];

                $parent = $this->seedParent($parentEmail, $family, $sectionNumber, $i, $serialOffset, $lookups);

                $student = Student::withTrashed()->where('email', $studentEmail)->first();

                if (! $student) {
                    $student = new Student();
                    $student->email = $studentEmail;
                    $student->password = Hash::make(self::PASSWORD);
                }

                $student->name = [
                    'en' => $given['en'].' '.$family['en'],
                    'ar' => $given['ar'].' '.$family['ar'],
                ];
                $student->gender_id = $isMale ? $lookups['male']->id : $lookups['female']->id;
                $student->nationalitie_id = $lookups['nationality']->id;
                $student->blood_id = $lookups['blood']->id;
                $student->Date_Birth = sprintf(
                    '%d-%02d-%02d',
                    $birthYear + (($i + $sectionNumber) % 2),
                    (($i + $sectionNumber) % 12) + 1,
                    (($i * 3 + $sectionNumber) % 27) + 1
                );
                $student->Grade_id = $stage->id;
                $student->Classroom_id = $classroom->id;
                $student->section_id = $section->id;
                $student->parent_id = $parent->id;
                $student->academic_year = $this->academicYear;
                $student->school_id = $this->schoolId;

                if ($student->trashed()) {
                    $student->restore();
                }

                $student->save();

                $total++;
            }
        }

        $this->command?->info('  Students ready: '.$total.' across '.count($sections).' sections.');

        return $total;
    }

    /**
     * @param  array{ar: string, en: string}  $family
     * @param  array<string, mixed>  $lookups
     */
    private function seedParent(
        string $email,
        array $family,
        int $sectionNumber,
        int $position,
        int $serialOffset,
        array $lookups
    ): My_Parent {
        $parent = My_Parent::where('email', $email)->first();

        if (! $parent) {
            $parent = new My_Parent();
            $parent->email = $email;
            $parent->password = Hash::make(self::PASSWORD);
        }

        // Offset by class + section so national ids stay unique across the whole seed.
        $serial = $serialOffset + (($sectionNumber - 1) * self::STUDENTS_PER_SECTION) + $position;
        $nationalityId = $lookups['nationality']->id;
        $bloodId = $lookups['blood']->id;
        $religionId = $lookups['religion']->id;

        $motherGiven = self::FEMALE_NAMES[($position + $sectionNumber) % count(self::FEMALE_NAMES)];
        $fatherJob = self::FATHER_JOBS[($position - 1) % count(self::FATHER_JOBS)];
        $motherJob = self::MOTHER_JOBS[($position + $sectionNumber - 1) % count(self::MOTHER_JOBS)];

        $parent->Name_Father = ['en' => $family['en'], 'ar' => $family['ar']];
        $parent->National_ID_Father = '2'.str_pad((string) (900000000 + $serial), 9, '0');
        $parent->Passport_ID_Father = 'A'.str_pad((string) (80000000 + $serial), 8, '0');
        $parent->Phone_Father = '0101'.str_pad((string) (2000000 + $serial), 7, '0');
        $parent->Job_Father = $fatherJob;
        $parent->Nationality_Father_id = $nationalityId;
        $parent->Blood_Type_Father_id = $bloodId;
        $parent->Religion_Father_id = $religionId;
        $parent->Address_Father = 'Demo District, Demo City';

        $parent->Name_Mother = ['en' => $motherGiven['en'].' '.$family['en'], 'ar' => $motherGiven['ar'].' '.$family['ar']];
        $parent->National_ID_Mother = '2'.str_pad((string) (800000000 + $serial), 9, '0');
        $parent->Passport_ID_Mother = 'B'.str_pad((string) (70000000 + $serial), 8, '0');
        $parent->Phone_Mother = '0111'.str_pad((string) (3000000 + $serial), 7, '0');
        $parent->Job_Mother = $motherJob;
        $parent->Nationality_Mother_id = $nationalityId;
        $parent->Blood_Type_Mother_id = $bloodId;
        $parent->Religion_Mother_id = $religionId;
        $parent->Address_Mother = 'Demo District, Demo City';

        $parent->school_id = $this->schoolId;
        $parent->save();

        return $parent;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<int, Section>  $sections
     * @param  array<int, array{subject: Subject, teacher: Teacher}>  $subjects
     */
    private function report(
        string $key,
        array $definition,
        Grade $stage,
        Classroom $classroom,
        array $sections,
        array $subjects,
        int $students
    ): void {
        $lines = [
            '',
            $definition['section_label']['ar'].' seeded:',
            '  School      : #'.$this->schoolId,
            '  Level       : '.$stage->getTranslation('Name', 'ar', false),
            '  Classroom   : '.$classroom->getTranslation('Name_Class', 'ar', false).' (#'.$classroom->id.')',
            '  Sections    : '.count($sections),
            '  Teachers    : '.count(array_unique(array_map(fn ($entry) => $entry['teacher']->id, $subjects))),
            '  Materials   : '.count($subjects),
            '  Students    : '.$students,
            '  Year        : '.$this->academicYear,
        ];

        foreach ($sections as $index => $section) {
            $lines[] = '    - '.$section->getTranslation('Name_Section', 'ar', false)
                .'  ('.Student::where('section_id', $section->id)->count().' طلاب)';
        }

        $lines[] = '  Demo password: '.self::PASSWORD;
        $lines[] = '';

        foreach ($lines as $line) {
            $this->command?->line($line);
        }
    }
}