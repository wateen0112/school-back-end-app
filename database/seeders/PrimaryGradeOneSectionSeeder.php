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
use Illuminate\Support\Facades\Hash;

/**
 * Seeds a complete, self-contained classroom for the first grade of the primary stage:
 * school scope, primary level, grade 1, its first section, teachers, subjects (materials)
 * assigned to those teachers, plus parents and students enrolled in that section.
 *
 * The seeder is idempotent: every record is matched by its natural key (translated name or
 * unique e-mail) and updated in place, so re-running it never duplicates data and never
 * wipes unrelated records.
 *
 * Usage:
 *   php artisan db:seed --class="Database\Seeders\PrimaryGradeOneSectionSeeder"
 *
 * Custom targets can be injected when the seeder is called from another seeder:
 *   $this->callWith([PrimaryGradeOneSectionSeeder::class], ['school' => 2, 'students' => 12, 'year' => '2026-2027']);
 *
 * Demo credentials: every teacher/parent/student uses the password "password123".
 */
class PrimaryGradeOneSectionSeeder extends Seeder
{
    /** Password given to every seeded account. */
    private const PASSWORD = 'password123';

    /** @var int */
    private $schoolId;

    /** @var string */
    private $academicYear;

    /** @var int|null */
    private $studentLimit;

    /**
     * Laravel's `db:seed` command does not forward unknown CLI flags, so the seeder accepts
     * named parameters instead (usable via `$this->callWith([...], ['school' => 1])`) and
     * falls back to options when the invoking command defines them.
     */
    public function run(?string $school = null, ?string $year = null, ?int $students = null): void
    {
        $this->schoolId = $this->resolveSchoolId($school);
        $this->academicYear = $this->resolveAcademicYear($year);
        $this->studentLimit = $students;

        $grade = $this->ensurePrimaryGrade();
        $classroom = $this->ensureGradeOneClassroom($grade);
        $section = $this->ensureFirstSection($grade, $classroom);

        $lookups = $this->resolveLookups();
        $teachers = $this->seedTeachers($lookups);
        $subjects = $this->seedSubjects($grade, $classroom, $section, $teachers);
        $this->seedFamilies($section, $grade, $classroom, $lookups);

        $this->report($grade, $classroom, $section, $teachers, $subjects);
    }

    /**
     * Read a CLI option, tolerating being invoked from another seeder (where the calling
     * command does not necessarily define the option).
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
     * School (tenant) the classroom belongs to. Takes precedence over the tenant scopes so
     * the seeded students/teachers/parents are visible to that school's dashboard users.
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

            throw new \RuntimeException('Cannot seed a classroom without a school.');
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
     * "Primary level" — the Grade row that represents the primary stage (not grade 1 itself).
     */
    private function ensurePrimaryGrade(): Grade
    {
        $grade = Grade::where('Name->en', 'Primary Stage')
            ->orWhere('Name->ar', 'المرحلة الابتدائية')
            ->orderBy('id')
            ->first();

        if (! $grade) {
            $grade = Grade::create([
                'Name' => ['en' => 'Primary Stage', 'ar' => 'المرحلة الابتدائية'],
            ]);

            $this->command?->info('Created missing level: Primary Stage.');
        }

        return $grade;
    }

    private function ensureGradeOneClassroom(Grade $grade): Classroom
    {
        $classroom = Classroom::where('Name_Class->en', 'Grade 1')
            ->where('Grade_id', $grade->id)
            ->orderBy('id')
            ->first();

        if (! $classroom) {
            $classroom = Classroom::create([
                'Name_Class' => ['en' => 'Grade 1', 'ar' => 'الصف الأول الابتدائي'],
                'Grade_id' => $grade->id,
                'capacity' => 30,
            ]);

            $this->command?->info('Created missing classroom: Grade 1.');
        }

        return $classroom;
    }

    /**
     * "Section 1" — the first section belonging to the grade 1 classroom.
     */
    private function ensureFirstSection(Grade $grade, Classroom $classroom): Section
    {
        $section = Section::where('Class_id', $classroom->id)
            ->where('Grade_id', $grade->id)
            ->orderBy('id')
            ->first();

        if (! $section) {
            $section = Section::create([
                'Name_Section' => ['en' => 'Section 1', 'ar' => 'الفصل الأول'],
                'Status' => 1,
                'Grade_id' => $grade->id,
                'Class_id' => $classroom->id,
            ]);

            $this->command?->info('Created missing section: Section 1.');
        }

        if ((int) $section->Status !== 1) {
            $section->update(['Status' => 1]);
        }

        return $section;
    }

    /**
     * Reference data (gender, nationality, blood type, religion, specializations) resolved by
     * name so the seeder does not depend on hard-coded ids.
     *
     * @return array<string, mixed>
     */
    private function resolveLookups(): array
    {
        $male = Gender::where('Name->en', 'Male')->orderBy('id')->first();
        $female = Gender::where('Name->en', 'Female')->orderBy('id')->first();

        if (! $male) {
            $male = Gender::create(['Name' => ['en' => 'Male', 'ar' => 'ذكر']]);
        }

        if (! $female) {
            $female = Gender::create(['Name' => ['en' => 'Female', 'ar' => 'أنثى']]);
        }

        $specialization = static function (string $en, string $ar): Specialization {
            $found = Specialization::where('Name->en', $en)->orderBy('id')->first();

            if (! $found) {
                $found = Specialization::create(['Name' => ['en' => $en, 'ar' => $ar]]);
            }

            return $found;
        };

        $blood = Type_Blood::where('Name', 'O+')->orderBy('id')->first();

        if (! $blood) {
            $blood = Type_Blood::create(['Name' => 'O+']);
        }

        return [
            'male' => $male,
            'female' => $female,
            'nationality' => $this->firstOrCreateLookup(Nationalitie::class, 'Egyptian', 'مصري'),
            'blood' => $blood,
            'religion' => $this->firstOrCreateLookup(Religion::class, 'Muslim', 'مسلم'),
            'spec_arabic' => $specialization('Arabic', 'عربي'),
            'spec_sciences' => $specialization('Sciences', 'علوم'),
            'spec_computer' => $specialization('Computer', 'حاسب الي'),
            'spec_english' => $specialization('English', 'انجليزي'),
        ];
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    private function firstOrCreateLookup(string $model, string $en, string $ar)
    {
        $found = $model::where('Name->en', $en)->orderBy('id')->first();

        if (! $found) {
            $found = $model::create(['Name' => ['en' => $en, 'ar' => $ar]]);
        }

        return $found;
    }

    /**
     * Subject teachers for the section. E-mail is the unique key, so this is safe to re-run.
     *
     * @param  array<string, mixed>  $lookups
     * @return array<string, Teacher>
     */
    private function seedTeachers(array $lookups): array
    {
        $definitions = [
            'arabic' => ['email' => 'g1.teacher.arabic@school.local', 'en' => 'Fatima Al Sayed', 'ar' => 'فاطمة السيد', 'gender' => 'female', 'specialization' => 'spec_arabic', 'salary' => 3200],
            'math' => ['email' => 'g1.teacher.math@school.local', 'en' => 'Youssef Ibrahim', 'ar' => 'يوسف إبراهيم', 'gender' => 'male', 'specialization' => 'spec_sciences', 'salary' => 3400],
            'english' => ['email' => 'g1.teacher.english@school.local', 'en' => 'Sara Mahmoud', 'ar' => 'سارة محمود', 'gender' => 'female', 'specialization' => 'spec_english', 'salary' => 3100],
            'science' => ['email' => 'g1.teacher.science@school.local', 'en' => 'Omar Khalil', 'ar' => 'عمر خليل', 'gender' => 'male', 'specialization' => 'spec_sciences', 'salary' => 3300],
            'islamic' => ['email' => 'g1.teacher.islamic@school.local', 'en' => 'Ahmed Nour', 'ar' => 'أحمد نور', 'gender' => 'male', 'specialization' => 'spec_arabic', 'salary' => 3000],
            'computer' => ['email' => 'g1.teacher.computer@school.local', 'en' => 'Hala Mansour', 'ar' => 'هالة منصور', 'gender' => 'female', 'specialization' => 'spec_computer', 'salary' => 2900],
            'social' => ['email' => 'g1.teacher.social@school.local', 'en' => 'Mona Adel', 'ar' => 'منى عادل', 'gender' => 'female', 'specialization' => 'spec_sciences', 'salary' => 3000],
            'activities' => ['email' => 'g1.teacher.activities@school.local', 'en' => 'Karim Fouad', 'ar' => 'كريم فؤاد', 'gender' => 'male', 'specialization' => 'spec_sciences', 'salary' => 2800],
        ];

        $teachers = [];

        foreach ($definitions as $key => $definition) {
            $gender = $lookups[$definition['gender']];
            $specialization = $lookups[$definition['specialization']];

            $teacher = Teacher::where('email', $definition['email'])->first();

            if (! $teacher) {
                $teacher = new Teacher();
            }

            $teacher->email = $definition['email'];
            $teacher->Name = ['en' => $definition['en'], 'ar' => $definition['ar']];
            $teacher->Gender_id = $gender->id;
            $teacher->Specialization_id = $specialization->id;
            $teacher->Joining_Date = '2021-09-01';
            $teacher->Address = '1 Primary Stage Road, Demo City';
            $teacher->salary = $definition['salary'];
            $teacher->school_id = $this->schoolId;

            if (! $teacher->password) {
                $teacher->password = Hash::make(self::PASSWORD);
            }

            $teacher->save();

            $teachers[$key] = $teacher;
        }

        $this->command?->info('Teachers ready: '.count($teachers).'.');

        return $teachers;
    }

    /**
     * Materials (subjects) for grade 1 and their assignment to the section's teachers through
     * the section_subject_teacher pivot. The legacy subject.teacher_id is kept in sync so both
     * the timetable optimizer and the older screens resolve the same teacher.
     *
     * @param  array<string, Teacher>  $teachers
     * @return array<string, Subject>
     */
    private function seedSubjects(Grade $grade, Classroom $classroom, Section $section, array $teachers): array
    {
        $definitions = [
            ['en' => 'Arabic', 'ar' => 'اللغة العربية', 'teacher' => 'arabic'],
            ['en' => 'Mathematics', 'ar' => 'الرياضيات', 'teacher' => 'math'],
            ['en' => 'English', 'ar' => 'اللغة الإنجليزية', 'teacher' => 'english'],
            ['en' => 'Sciences', 'ar' => 'العلوم', 'teacher' => 'science'],
            ['en' => 'Islamic Education', 'ar' => 'التربية الإسلامية', 'teacher' => 'islamic'],
            ['en' => 'Computer', 'ar' => 'الحاسب الآلي', 'teacher' => 'computer'],
            ['en' => 'Social Studies', 'ar' => 'الدراسات الاجتماعية', 'teacher' => 'social'],
            ['en' => 'Art', 'ar' => 'التربية الفنية', 'teacher' => 'activities'],
            ['en' => 'Music', 'ar' => 'التربية الموسيقية', 'teacher' => 'activities'],
            ['en' => 'Physical Education', 'ar' => 'التربية الرياضية', 'teacher' => 'activities'],
        ];

        $subjects = [];

        foreach ($definitions as $definition) {
            $teacher = $teachers[$definition['teacher']];

            $subject = Subject::where('Name->en', $definition['en'])
                ->where('Grade_id', $grade->id)
                ->where('Classroom_id', $classroom->id)
                ->orderBy('id')
                ->first();

            if (! $subject) {
                $subject = new Subject();
            }

            $subject->Name = ['en' => $definition['en'], 'ar' => $definition['ar']];
            $subject->Grade_id = $grade->id;
            $subject->Classroom_id = $classroom->id;
            $subject->teacher_id = $teacher->id;
            $subject->save();

            SectionSubjectTeacher::updateOrCreate(
                [
                    'section_id' => $section->id,
                    'subject_id' => $subject->id,
                ],
                [
                    'teacher_id' => $teacher->id,
                ]
            );

            $subjects[$definition['en']] = $subject;
        }

        $this->command?->info('Materials assigned: '.count($subjects).' subjects for section '.$section->id.'.');

        return $subjects;
    }

    /**
     * Parents (one per student) and their children enrolled in the section.
     *
     * @param  array<string, mixed>  $lookups
     */
    private function seedFamilies(Section $section, Grade $grade, Classroom $classroom, array $lookups): void
    {
        $definitions = [
            ['student' => ['en' => 'Youssef Adel', 'ar' => 'يوسف عادل'], 'gender' => 'male', 'birth' => '2019-03-12',
                'father' => ['en' => 'Adel Sobhy', 'ar' => 'عادل صبحي'], 'father_job' => ['en' => 'Accountant', 'ar' => 'محاسب'],
                'mother' => ['en' => 'Nesma Emad', 'ar' => 'نسمة عماد'], 'mother_job' => ['en' => 'Housewife', 'ar' => 'ربة منزل']],
            ['student' => ['en' => 'Mariam Adel', 'ar' => 'مريم عادل'], 'gender' => 'female', 'birth' => '2019-07-05',
                'father' => ['en' => 'Hassan Kamel', 'ar' => 'حسن كامل'], 'father_job' => ['en' => 'Engineer', 'ar' => 'مهندس'],
                'mother' => ['en' => 'Dina Lotfy', 'ar' => 'دينا لطفي'], 'mother_job' => ['en' => 'Teacher', 'ar' => 'معلمة']],
            ['student' => ['en' => 'Adam Kamel', 'ar' => 'آدم كامل'], 'gender' => 'male', 'birth' => '2018-11-20',
                'father' => ['en' => 'Magdy Shaker', 'ar' => 'مجدي شاكر'], 'father_job' => ['en' => 'Merchant', 'ar' => 'تاجر'],
                'mother' => ['en' => 'Amal Nabil', 'ar' => 'أمل نبيل'], 'mother_job' => ['en' => 'Nurse', 'ar' => 'ممرضة']],
            ['student' => ['en' => 'Nour Kamel', 'ar' => 'نور كامل'], 'gender' => 'female', 'birth' => '2019-01-15',
                'father' => ['en' => 'Ashraf Zaki', 'ar' => 'أشرف زكي'], 'father_job' => ['en' => 'Pharmacist', 'ar' => 'صيدلي'],
                'mother' => ['en' => 'Rania Gamal', 'ar' => 'رانيا جمال'], 'mother_job' => ['en' => 'Housewife', 'ar' => 'ربة منزل']],
            ['student' => ['en' => 'Ziad Hassan', 'ar' => 'زياد حسن'], 'gender' => 'male', 'birth' => '2018-09-30',
                'father' => ['en' => 'Hassan Adel', 'ar' => 'حسن عادل'], 'father_job' => ['en' => 'Carpenter', 'ar' => 'نجار'],
                'mother' => ['en' => 'Mona Tarek', 'ar' => 'منى طارق'], 'mother_job' => ['en' => 'Housewife', 'ar' => 'ربة منزل']],
            ['student' => ['en' => 'Salma Hassan', 'ar' => 'سلمى حسن'], 'gender' => 'female', 'birth' => '2019-05-22',
                'father' => ['en' => 'Mostafa Fahmy', 'ar' => 'مصطفى فهمي'], 'father_job' => ['en' => 'Teacher', 'ar' => 'معلم'],
                'mother' => ['en' => 'Hoda Ramzy', 'ar' => 'هدى رمزي'], 'mother_job' => ['en' => 'Nurse', 'ar' => 'ممرضة']],
            ['student' => ['en' => 'Ibrahim Fathy', 'ar' => 'إبراهيم فتحي'], 'gender' => 'male', 'birth' => '2018-12-08',
                'father' => ['en' => 'Fathy Nabil', 'ar' => 'فتحي نبيل'], 'father_job' => ['en' => 'Driver', 'ar' => 'سائق'],
                'mother' => ['en' => 'Sahar Emad', 'ar' => 'سحر عماد'], 'mother_job' => ['en' => 'Housewife', 'ar' => 'ربة منزل']],
            ['student' => ['en' => 'Hana Fathy', 'ar' => 'هنا فتحي'], 'gender' => 'female', 'birth' => '2019-02-27',
                'father' => ['en' => 'Tarek Mounir', 'ar' => 'طارق منير'], 'father_job' => ['en' => 'Engineer', 'ar' => 'مهندس'],
                'mother' => ['en' => 'Aya Sameer', 'ar' => 'آية سمير'], 'mother_job' => ['en' => 'Designer', 'ar' => 'مصممة']],
        ];

        $limit = $this->studentLimit ?? (int) ($this->option('students') ?? count($definitions));
        $limit = max(1, min((int) $limit, count($definitions)));

        foreach (array_slice($definitions, 0, $limit) as $index => $definition) {
            $number = $index + 1;
            $parentEmail = 'g1.parent'.$number.'@school.local';
            $studentEmail = 'g1.student'.$number.'@school.local';

            $father = $definition['father'];
            $mother = $definition['mother'];

            $parent = My_Parent::where('email', $parentEmail)->first();

            if (! $parent) {
                $parent = new My_Parent();
                $parent->email = $parentEmail;
                $parent->password = Hash::make(self::PASSWORD);
            }

            $nationalityId = $lookups['nationality']->id;
            $bloodId = $lookups['blood']->id;
            $religionId = $lookups['religion']->id;

            $parent->Name_Father = $father;
            $parent->National_ID_Father = '2'.str_pad((string) (900000000 + $number), 9, '0');
            $parent->Passport_ID_Father = 'A'.str_pad((string) (80000000 + $number), 8, '0');
            $parent->Phone_Father = '0101'.str_pad((string) (2000000 + $number), 7, '0');
            $parent->Job_Father = $definition['father_job'];
            $parent->Nationality_Father_id = $nationalityId;
            $parent->Blood_Type_Father_id = $bloodId;
            $parent->Religion_Father_id = $religionId;
            $parent->Address_Father = '5 Demo District, Demo City';

            $parent->Name_Mother = $mother;
            $parent->National_ID_Mother = '2'.str_pad((string) (800000000 + $number), 9, '0');
            $parent->Passport_ID_Mother = 'B'.str_pad((string) (70000000 + $number), 8, '0');
            $parent->Phone_Mother = '0111'.str_pad((string) (3000000 + $number), 7, '0');
            $parent->Job_Mother = $definition['mother_job'];
            $parent->Nationality_Mother_id = $nationalityId;
            $parent->Blood_Type_Mother_id = $bloodId;
            $parent->Religion_Mother_id = $religionId;
            $parent->Address_Mother = '5 Demo District, Demo City';
            $parent->school_id = $this->schoolId;
            $parent->save();

            $student = Student::withTrashed()->where('email', $studentEmail)->first();

            if (! $student) {
                $student = new Student();
                $student->email = $studentEmail;
                $student->password = Hash::make(self::PASSWORD);
            }

            $student->name = $definition['student'];
            $student->gender_id = $lookups[$definition['gender']]->id;
            $student->nationalitie_id = $nationalityId;
            $student->blood_id = $bloodId;
            $student->Date_Birth = $definition['birth'];
            $student->Grade_id = $grade->id;
            $student->Classroom_id = $classroom->id;
            $student->section_id = $section->id;
            $student->parent_id = $parent->id;
            $student->academic_year = $this->academicYear;
            $student->school_id = $this->schoolId;

            if ($student->trashed()) {
                $student->restore();
            }

            $student->save();
        }

        $this->command?->info('Families ready: '.$limit.' students with '.$limit.' parents.');
    }

    /**
     * @param  array<string, Teacher>  $teachers
     * @param  array<string, Subject>  $subjects
     */
    private function report(Grade $grade, Classroom $classroom, Section $section, array $teachers, array $subjects): void
    {
        $students = Student::where('section_id', $section->id)->count();

        $lines = [
            '',
            'Primary / Grade 1 classroom seeded:',
            '  School          : #'.$this->schoolId,
            '  Level           : '.$grade->id.' - '.$grade->getTranslation('Name', 'en', false),
            '  Classroom       : '.$classroom->id.' - '.$classroom->getTranslation('Name_Class', 'en', false),
            '  Section         : '.$section->id.' - '.$section->getTranslation('Name_Section', 'en', false),
            '  Teachers        : '.count($teachers),
            '  Materials       : '.count($subjects).' (assigned to teachers)',
            '  Students        : '.$students,
            '  Academic year   : '.$this->academicYear,
            '  Demo password   : '.self::PASSWORD,
            '',
        ];

        foreach ($lines as $line) {
            $this->command?->line($line);
        }
    }
}