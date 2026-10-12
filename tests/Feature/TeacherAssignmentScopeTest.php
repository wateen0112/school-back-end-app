<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Grade;
use App\Models\Section;
use App\Models\School;
use App\Models\SectionSubjectTeacher;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Specialization;
use App\Models\Teacher;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class TeacherAssignmentScopeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private School $schoolOne;
    private School $schoolTwo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->schoolOne = School::create([
            'name' => 'School One',
            'email' => 'one@example.test',
        ]);
        $this->schoolTwo = School::create([
            'name' => 'School Two',
            'email' => 'two@example.test',
        ]);

        // A plain authenticated non-teacher; the `users` table has no role column.
        $this->admin = User::factory()->create([
            'school_id' => $this->schoolOne->id,
        ]);

        $this->ensureLookups();
    }

    /**
     * RefreshDatabase empties the lookup tables that teacher/student rows
     * require, so seed the minimum set once per test.
     */
    private function ensureLookups(): void
    {
        Specialization::firstOrCreate(['Name' => 'General']);

        foreach (['genders' => 'Male', 'nationalities' => 'Egyptian', 'type__bloods' => 'O+'] as $table => $name) {
            if (! DB::table($table)->exists()) {
                DB::table($table)->insert(['Name' => $name]);
            }
        }
    }

    private function lookupId(string $table): int
    {
        return (int) DB::table($table)->value('id');
    }

    private function makeSection(string $gradeName, string $sectionName): Section
    {
        $grade = Grade::create(['Name' => $gradeName]);

        $classroom = Classroom::create([
            'Name_Class' => $gradeName . ' class',
            'Grade_id' => $grade->id,
        ]);

        return Section::create([
            'Name_Section' => $sectionName,
            'Status' => 1,
            'Grade_id' => $grade->id,
            'Class_id' => $classroom->id,
        ]);
    }

    private function makeSubject(string $name, ?int $classroomId = null, ?int $ownerId = null): Subject
    {
        return Subject::create([
            'Name' => $name,
            'Classroom_id' => $classroomId,
            'teacher_id' => $ownerId,
        ]);
    }

    private function makeTeacher(?School $school, string $name): Teacher
    {
        $teacher = new Teacher();
        $teacher->forceFill([
            'Name' => $name,
            'email' => Str::slug($name, '-').'@example.test',
            'password' => bcrypt('password123'),
            'Specialization_id' => Specialization::firstOrCreate(['Name' => 'General'])->id,
            'Gender_id' => $this->lookupId('genders'),
            'Joining_Date' => '2026-01-01',
            'Address' => 'Test Address',
            'school_id' => $school?->id,
        ]);
        $teacher->save();

        return $teacher;
    }

    private function makeStudent(Section $section, ?School $school, string $name): Student
    {
        return Student::create([
            'name' => $name,
            'email' => Str::slug($name, '-').'@example.test',
            'password' => bcrypt('password123'),
            'gender_id' => $this->lookupId('genders'),
            'nationalitie_id' => $this->lookupId('nationalities'),
            'blood_id' => $this->lookupId('type__bloods'),
            'Date_Birth' => '2015-01-01',
            'Grade_id' => $section->Grade_id,
            'Classroom_id' => $section->Class_id,
            'section_id' => $section->id,
            'academic_year' => '2026-2027',
            'school_id' => $school?->id,
        ]);
    }

    public function test_sections_are_scoped_to_the_teacher_assignments(): void
    {
        $teacher = $this->makeTeacher($this->schoolOne, 'Teacher A');
        $mine = $this->makeSection('Grade 1', 'Section A');
        $theirs = $this->makeSection('Grade 2', 'Section B');

        $subject = $this->makeSubject('Math', null);
        SectionSubjectTeacher::create([
            'section_id' => $mine->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);
        SectionSubjectTeacher::create([
            'section_id' => $theirs->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->makeTeacher($this->schoolOne, 'Teacher B')->id,
        ]);

        $response = $this->actingAs($teacher, 'api')->getJson('/api/teacher/sections');

        $response->assertOk();
        $this->assertSame([$mine->id], $response->json('data.*.id'));
    }

    public function test_sections_can_be_narrowed_by_subject(): void
    {
        $teacher = $this->makeTeacher($this->schoolOne, 'Teacher A');
        $mathSection = $this->makeSection('Grade 1', 'Section A');
        $scienceSection = $this->makeSection('Grade 1', 'Section B');

        $math = $this->makeSubject('Math', null);
        $science = $this->makeSubject('Science', null);

        SectionSubjectTeacher::create([
            'section_id' => $mathSection->id, 'subject_id' => $math->id, 'teacher_id' => $teacher->id,
        ]);
        SectionSubjectTeacher::create([
            'section_id' => $scienceSection->id, 'subject_id' => $science->id, 'teacher_id' => $teacher->id,
        ]);

        $response = $this->actingAs($teacher, 'api')
            ->getJson('/api/teacher/sections?subject_id=' . $math->id);

        $response->assertOk();
        $this->assertSame([$mathSection->id], $response->json('data.*.id'));
    }

    public function test_students_are_limited_to_sections_for_the_selected_subject(): void
    {
        $teacher = $this->makeTeacher($this->schoolOne, 'Teacher A');
        $mathSection = $this->makeSection('Grade 1', 'Section A');
        $scienceSection = $this->makeSection('Grade 1', 'Section B');

        $math = $this->makeSubject('Math', null);
        $science = $this->makeSubject('Science', null);

        SectionSubjectTeacher::create([
            'section_id' => $mathSection->id, 'subject_id' => $math->id, 'teacher_id' => $teacher->id,
        ]);
        SectionSubjectTeacher::create([
            'section_id' => $scienceSection->id, 'subject_id' => $science->id, 'teacher_id' => $teacher->id,
        ]);

        $mathStudent = $this->makeStudent($mathSection, $this->schoolOne, 'Math Student');
        $scienceStudent = $this->makeStudent($scienceSection, $this->schoolOne, 'Science Student');

        $response = $this->actingAs($teacher, 'api')
            ->getJson('/api/teacher/students?subject_id=' . $math->id);

        $response->assertOk();
        $ids = $response->json('data.*.id');
        $this->assertContains($mathStudent->id, $ids);
        $this->assertNotContains($scienceStudent->id, $ids);
    }

    public function test_students_reject_a_section_the_teacher_does_not_teach_for_that_subject(): void
    {
        $teacher = $this->makeTeacher($this->schoolOne, 'Teacher A');
        $mathSection = $this->makeSection('Grade 1', 'Section A');
        $scienceSection = $this->makeSection('Grade 1', 'Section B');

        $math = $this->makeSubject('Math', null);
        $science = $this->makeSubject('Science', null);

        SectionSubjectTeacher::create([
            'section_id' => $mathSection->id, 'subject_id' => $math->id, 'teacher_id' => $teacher->id,
        ]);
        SectionSubjectTeacher::create([
            'section_id' => $scienceSection->id, 'subject_id' => $science->id, 'teacher_id' => $teacher->id,
        ]);

        $this->makeStudent($mathSection, $this->schoolOne, 'Math Student');

        // Math is taught in Section A only, so asking for Math in Section B is invalid.
        $response = $this->actingAs($teacher, 'api')->getJson(
            '/api/teacher/students?subject_id=' . $math->id . '&section_id=' . $scienceSection->id
        );

        $response->assertStatus(403);
    }

    public function test_students_reject_an_unassigned_subject_instead_of_falling_back(): void
    {
        $teacher = $this->makeTeacher($this->schoolOne, 'Teacher A');
        $section = $this->makeSection('Grade 1', 'Section A');
        $subject = $this->makeSubject('Math', null);

        SectionSubjectTeacher::create([
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->makeTeacher($this->schoolOne, 'Teacher B')->id,
        ]);

        $this->makeStudent($section, $this->schoolOne, 'Someone Elses Student');

        $response = $this->actingAs($teacher, 'api')
            ->getJson('/api/teacher/students?subject_id=' . $subject->id);

        $response->assertStatus(403);
    }

    public function test_subjects_can_be_narrowed_by_section(): void
    {
        $teacher = $this->makeTeacher($this->schoolOne, 'Teacher A');
        $sectionA = $this->makeSection('Grade 1', 'Section A');
        $sectionB = $this->makeSection('Grade 2', 'Section B');

        $math = $this->makeSubject('Math', null);
        $science = $this->makeSubject('Science', null);

        SectionSubjectTeacher::create([
            'section_id' => $sectionA->id, 'subject_id' => $math->id, 'teacher_id' => $teacher->id,
        ]);
        SectionSubjectTeacher::create([
            'section_id' => $sectionB->id, 'subject_id' => $science->id, 'teacher_id' => $teacher->id,
        ]);

        $response = $this->actingAs($teacher, 'api')
            ->getJson('/api/teacher/subjects?section_id=' . $sectionA->id);

        $response->assertOk();
        $this->assertSame([$math->id], $response->json('data.*.id'));
    }

    public function test_assignments_return_only_the_teachers_own_pairs(): void
    {
        $teacher = $this->makeTeacher($this->schoolOne, 'Teacher A');
        $mine = $this->makeSection('Grade 1', 'Section A');
        $theirs = $this->makeSection('Grade 2', 'Section B');

        $math = $this->makeSubject('Math', null);
        $science = $this->makeSubject('Science', null);

        SectionSubjectTeacher::create([
            'section_id' => $mine->id, 'subject_id' => $math->id, 'teacher_id' => $teacher->id,
        ]);
        SectionSubjectTeacher::create([
            'section_id' => $theirs->id, 'subject_id' => $science->id,
            'teacher_id' => $this->makeTeacher($this->schoolOne, 'Teacher B')->id,
        ]);

        $response = $this->actingAs($teacher, 'api')->getJson('/api/teacher/assignments');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame($mine->id, $response->json('data.0.section_id'));
        $this->assertSame($math->id, $response->json('data.0.subject_id'));
        $this->assertSame($math->id, $response->json('data.0.subject.id'));
    }

    public function test_students_never_cross_the_school_boundary(): void
    {
        $teacher = $this->makeTeacher($this->schoolOne, 'Teacher A');
        $section = $this->makeSection('Grade 1', 'Section A');
        $subject = $this->makeSubject('Math', null);

        SectionSubjectTeacher::create([
            'section_id' => $section->id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id,
        ]);

        // A stale pivot row pointing at another school's section must not leak.
        $otherSchoolSection = $this->makeSection('Grade 9', 'Section Z');
        SectionSubjectTeacher::create([
            'section_id' => $otherSchoolSection->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);
        $this->makeStudent($otherSchoolSection, $this->schoolTwo, 'Other School Student');

        $this->actingAs($teacher, 'api')->getJson('/api/teacher/students')
            ->assertOk()
            ->assertJsonMissing(['name' => 'Other School Student']);
    }

    public function test_teacher_routes_reject_non_teachers(): void
    {
        foreach (['/api/teacher/sections', '/api/teacher/subjects', '/api/teacher/students', '/api/teacher/assignments'] as $uri) {
            $this->actingAs($this->admin, 'api')->getJson($uri)->assertStatus(403);
        }
    }

    public function test_teacher_details_list_the_subjects_and_sections_taught(): void
    {
        $teacher = $this->makeTeacher($this->schoolOne, 'Teacher A');
        $first = $this->makeSection('Grade 1', 'Section A');
        $second = $this->makeSection('Grade 1', 'Section B');

        $math = $this->makeSubject('Math', $first->Class_id);
        $science = $this->makeSubject('Science', $first->Class_id);

        SectionSubjectTeacher::create([
            'section_id' => $first->id, 'subject_id' => $math->id, 'teacher_id' => $teacher->id,
        ]);
        SectionSubjectTeacher::create([
            'section_id' => $second->id, 'subject_id' => $math->id, 'teacher_id' => $teacher->id,
        ]);
        SectionSubjectTeacher::create([
            'section_id' => $first->id, 'subject_id' => $science->id,
            'teacher_id' => $this->makeTeacher($this->schoolOne, 'Teacher B')->id,
        ]);

        $response = $this->actingAs($this->admin, 'api')->getJson("/api/teachers/{$teacher->id}");

        $response->assertOk();

        // Two subjects of his own across two sections; the colleague's Science is not his.
        $this->assertSame(1, $response->json('data.teaching.subjects_count'));
        $this->assertSame(2, $response->json('data.teaching.sections_count'));
        $this->assertCount(2, $response->json('data.teaching.assignments'));

        foreach ($response->json('data.teaching.assignments') as $assignment) {
            $this->assertSame($math->id, $assignment['subject_id']);
            $this->assertContains($assignment['section_id'], [$first->id, $second->id]);
            $this->assertNotEmpty($assignment['subject_name']);
            $this->assertNotEmpty($assignment['section_name']);
        }
    }

    public function test_teacher_details_count_only_live_weekly_lessons(): void
    {
        $teacher = $this->makeTeacher($this->schoolOne, 'Teacher A');
        $section = $this->makeSection('Grade 1', 'Section A');
        $subject = $this->makeSubject('Math', $section->Class_id);

        SectionSubjectTeacher::create([
            'section_id' => $section->id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id,
        ]);

        $scenarioId = DB::table('timetable_scenarios')->insertGetId([
            'name' => 'Draft plan',
            'version' => 1,
            'status' => 'draft',
            'is_active' => false,
        ]);

        $lessons = [
            // Two live lessons for this teacher.
            ['scenario_id' => null, 'day_of_week' => 0, 'start_time' => '08:00', 'end_time' => '08:45', 'is_active' => true],
            ['scenario_id' => null, 'day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '08:45', 'is_active' => true],
            // A draft scenario row, an inactive row and another teacher's lesson:
            // none of these belong on the live schedule.
            ['scenario_id' => $scenarioId, 'day_of_week' => 2, 'start_time' => '08:00', 'end_time' => '08:45', 'is_active' => true],
            ['scenario_id' => null, 'day_of_week' => 3, 'start_time' => '08:00', 'end_time' => '08:45', 'is_active' => false],
        ];

        foreach ($lessons as $lesson) {
            DB::table('weekly_programs')->insert($lesson + [
                'section_id' => $section->id,
                'subject_id' => $subject->id,
                'teacher_id' => $teacher->id,
            ]);
        }

        $other = $this->makeTeacher($this->schoolOne, 'Teacher B');
        DB::table('weekly_programs')->insert([
            'scenario_id' => null, 'section_id' => $section->id, 'subject_id' => $subject->id,
            'teacher_id' => $other->id, 'day_of_week' => 4, 'start_time' => '08:00',
            'end_time' => '08:45', 'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'api')->getJson("/api/teachers/{$teacher->id}");

        $response->assertOk();
        $this->assertSame(2, $response->json('data.teaching.weekly_lessons'));
        $this->assertSame(2, $response->json('data.teaching.assignments.0.weekly_lessons'));
    }

    public function test_teacher_without_assignments_reports_empty_teaching(): void
    {
        $teacher = $this->makeTeacher($this->schoolOne, 'Teacher A');

        $response = $this->actingAs($this->admin, 'api')->getJson("/api/teachers/{$teacher->id}");

        $response->assertOk();
        $this->assertSame(0, $response->json('data.teaching.subjects_count'));
        $this->assertSame(0, $response->json('data.teaching.sections_count'));
        $this->assertSame([], $response->json('data.teaching.assignments'));
    }
}