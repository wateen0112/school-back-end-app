<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Gender;
use App\Models\Grade;
use App\Models\My_Parent;
use App\Models\Nationalitie;
use App\Models\Religion;
use App\Models\School;
use App\Models\Section;
use App\Models\Student;
use App\Models\Type_Blood;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StudentParentLinkApiTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $admin;
    protected Student $student;
    protected int $nationalityId;
    protected int $bloodId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'name' => ['en' => 'Link School', 'ar' => 'مدرسة الربط'],
            'email' => 'link-school@test.local',
        ]);
        $this->admin = User::factory()->create(['school_id' => $this->school->id]);
        $this->student = $this->makeStudent();
    }

    private function makeParent(string $suffix): My_Parent
    {
        return My_Parent::create([
            'email' => 'parent'.$suffix.'@test.local',
            'password' => bcrypt('password'),
            'Name_Father' => 'Father '.$suffix,
            'National_ID_Father' => 'N'.$suffix,
            'Phone_Father' => '0'.$suffix,
            'Job_Father' => 'Job',
            'Passport_ID_Father' => 'P'.$suffix,
            'Nationality_Father_id' => $this->nationalityId,
            'Blood_Type_Father_id' => $this->bloodId,
            'Religion_Father_id' => Religion::create(['Name' => 'Islam '.$suffix])->id,
            'Address_Father' => 'addr',
        ]);
    }

    private function makeStudent(): Student
    {
        $gender = Gender::create(['Name' => 'Male']);
        $this->nationalityId = Nationalitie::create(['Name' => 'Syrian'])->id;
        $this->bloodId = Type_Blood::create(['Name' => 'O+'])->id;
        $grade = Grade::create(['Name' => 'First Grade', 'Notes' => '']);
        $classroom = Classroom::create(['Name_Class' => '1-A', 'Grade_id' => $grade->id]);

        return Student::create([
            'name' => ['en' => 'Test Student', 'ar' => 'طالب تجريبي'],
            'email' => 'student-link@test.local',
            'password' => bcrypt('password'),
            'gender_id' => $gender->id,
            'nationalitie_id' => $this->nationalityId,
            'blood_id' => $this->bloodId,
            'Grade_id' => $grade->id,
            'Classroom_id' => $classroom->id,
            'section_id' => Section::create([
                'Name_Section' => json_encode(['en' => 'A', 'ar' => 'أ']),
                'Grade_id' => $grade->id,
                'Class_id' => $classroom->id,
                'Status' => 1,
            ])->id,
            'Date_Birth' => '2015-01-01',
            'academic_year' => '2026-2027',
            'school_id' => $this->school->id,
        ]);
    }

    #[Test]
    public function it_links_a_parent_to_a_student(): void
    {
        $parent = $this->makeParent('A');

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/students/{$this->student->id}/parent", ['parent_id' => $parent->id])
            ->assertStatus(200)
            ->assertJsonPath('data.parent_id', $parent->id);

        $this->assertDatabaseHas('students', [
            'id' => $this->student->id,
            'parent_id' => $parent->id,
        ]);
    }

    #[Test]
    public function it_rejects_an_unknown_parent(): void
    {
        $this->actingAs($this->admin, 'api')
            ->putJson("/api/students/{$this->student->id}/parent", ['parent_id' => 999999])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['parent_id']);
    }

    #[Test]
    public function it_allows_detaching_a_parent(): void
    {
        $parent = $this->makeParent('B');
        $this->student->update(['parent_id' => $parent->id]);

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/students/{$this->student->id}/parent", ['parent_id' => null])
            ->assertStatus(200);

        $this->assertDatabaseHas('students', [
            'id' => $this->student->id,
            'parent_id' => null,
        ]);
    }
}
