<?php

namespace Tests\Feature;

use App\Models\Grade;
use App\Models\Classroom;
use App\Models\Section;
use App\Models\Gender;
use App\Models\Nationalitie;
use App\Models\Type_Blood;
use App\Models\School;
use App\Models\AuditLog;
use App\Models\DataImport;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImportApiTest extends TestCase
{
    use RefreshDatabase;

    private function adminWithSchool(): array
    {
        $school = School::create([
            'name' => ['ar' => 'مدرسة الاختبار', 'en' => 'Test School'],
            'email' => 'test@school.local',
            'status' => 'active',
        ]);
        $admin = User::factory()->create(['school_id' => $school->id, 'is_super_admin' => false]);
        return ['admin' => $admin, 'school' => $school];
    }

    private function seedLookups(): array
    {
        $gender = Gender::create(['Name' => ['ar' => 'ذكر', 'en' => 'Male']]);
        Gender::create(['Name' => ['ar' => 'انثي', 'en' => 'Female']]);
        $grade = Grade::create(['Name' => ['ar' => 'الأولى', 'en' => 'First']]);
        $class = Classroom::create(['Name_Class' => ['ar' => 'أ', 'en' => 'A'], 'Grade_id' => $grade->id]);
        $section = Section::create(['Name_Section' => ['ar' => 'ش', 'en' => 'Sh'], 'Status' => 1, 'Grade_id' => $grade->id, 'Class_id' => $class->id]);
        $nat = Nationalitie::create(['Name' => ['ar' => 'سعودي', 'en' => 'Saudi']]);
        $blood = Type_Blood::create(['Name' => 'O+']);
        return compact('gender', 'grade', 'class', 'section', 'nat', 'blood');
    }

    private function makeCsv(string $content): UploadedFile
    {
        $path = storage_path('app/test_import.csv');
        file_put_contents($path, $content);
        return new UploadedFile($path, 'test_import.csv', 'text/csv', null, true);
    }

    #[Test]
    public function preview_returns_auto_mapped_columns_and_sample_rows()
    {
        $this->seedLookups();
        ['admin' => $admin] = $this->adminWithSchool();

        $csv = "Name,Email,Grade,Classroom,Gender\nAhmed,ahmed@test.com,الأولى,أ,ذكر\nSara,sara@test.com,الأولى,أ,انثي\n";
        $file = $this->makeCsv($csv);

        $response = $this->actingAs($admin, 'api')
            ->postJson('/api/imports/preview', ['file' => $file, 'entity' => 'students']);

        $response->assertOk()->assertJsonStructure([
            'entity', 'entity_label', 'source_columns', 'target_columns', 'auto_mapping', 'sample_rows', 'total_rows',
        ]);
        $this->assertEquals(2, $response->json('total_rows'));
        $this->assertNotEmpty($response->json('auto_mapping'));
        $this->assertCount(2, $response->json('sample_rows'));
    }

    #[Test]
    public function preview_rejects_invalid_entity()
    {
        ['admin' => $admin] = $this->adminWithSchool();
        $file = $this->makeCsv("Name\nA\n");

        $this->actingAs($admin, 'api')
            ->postJson('/api/imports/preview', ['file' => $file, 'entity' => 'nonexistent'])
            ->assertStatus(422);
    }

    #[Test]
    public function run_imports_students_with_valid_data_and_creates_audit_log()
    {
        $this->seedLookups();
        ['admin' => $admin, 'school' => $school] = $this->adminWithSchool();

        $csv = "Name,Email,Grade,Classroom,Gender,Date_Birth\nAhmed,ahmed@test.com,الأولى,أ,ذكر,2015-01-01\nSara,sara@test.com,الأولى,أ,انثي,2015-02-02\n";
        $file = $this->makeCsv($csv);

        $response = $this->actingAs($admin, 'api')
            ->postJson('/api/imports/run', [
                'file' => $file,
                'entity' => 'students',
                'mapping' => [
                    'Name' => 'Name',
                    'email' => 'Email',
                    'Grade' => 'Grade',
                    'Classroom' => 'Classroom',
                    'gender' => 'Gender',
                    'Date_Birth' => 'Date_Birth',
                ],
            ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertEquals(2, $response->json('imported'));
        $this->assertEquals(0, $response->json('skipped'));
        $this->assertDatabaseHas('students', ['email' => 'ahmed@test.com', 'school_id' => $school->id]);
        $this->assertDatabaseHas('students', ['email' => 'sara@test.com', 'school_id' => $school->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'data_import']);
        $this->assertDatabaseHas('data_imports', ['entity' => 'students', 'imported_rows' => 2]);
    }

    #[Test]
    public function run_skips_invalid_rows_and_reports_errors()
    {
        $this->seedLookups();
        ['admin' => $admin] = $this->adminWithSchool();

        // Missing required Grade field for second row
        $csv = "Name,Email,Grade,Classroom,Gender\nAhmed,ahmed@test.com,الأولى,أ,ذكر\nSara,sara@test.com,,أ,انثي\n";
        $file = $this->makeCsv($csv);

        $response = $this->actingAs($admin, 'api')
            ->postJson('/api/imports/run', [
                'file' => $file,
                'entity' => 'students',
                'mapping' => ['Name' => 'Name', 'email' => 'Email', 'Grade' => 'Grade', 'Classroom' => 'Classroom', 'gender' => 'Gender'],
            ]);

        $response->assertOk();
        $this->assertEquals(1, $response->json('imported'));
        $this->assertEquals(1, $response->json('skipped'));
        $this->assertNotEmpty($response->json('errors'));
    }

    #[Test]
    public function run_imports_parents_successfully()
    {
        ['admin' => $admin] = $this->adminWithSchool();

        $csv = "Name_Father,Email,Phone_Father,Name_Mother\nAli,ali@test.com,0500000000,Fatima\n";
        $file = $this->makeCsv($csv);

        $response = $this->actingAs($admin, 'api')
            ->postJson('/api/imports/run', [
                'file' => $file,
                'entity' => 'parents',
                'mapping' => ['Name_Father' => 'Name_Father', 'email' => 'Email', 'Phone_Father' => 'Phone_Father', 'Name_Mother' => 'Name_Mother'],
            ]);

        $response->assertOk()->assertJsonPath('success', true);
        if ($response->json('errors')) {
            fwrite(STDERR, "\nPARENT IMPORT ERRORS: " . json_encode($response->json('errors'), JSON_UNESCAPED_UNICODE) . "\n");
        }
        $this->assertEquals(1, $response->json('imported'));
        $this->assertDatabaseHas('my__parents', ['email' => 'ali@test.com']);
    }

    #[Test]
    public function run_imports_classrooms_successfully()
    {
        $this->seedLookups();
        ['admin' => $admin] = $this->adminWithSchool();

        $csv = "Name_Class,Grade\nب,الأولى\n";
        $file = $this->makeCsv($csv);

        $response = $this->actingAs($admin, 'api')
            ->postJson('/api/imports/run', [
                'file' => $file,
                'entity' => 'classrooms',
                'mapping' => ['Name_Class' => 'Name_Class', 'Grade' => 'Grade'],
            ]);

        $response->assertOk()->assertJsonPath('success', true);
        $expectedName = json_encode(['ar' => 'ب', 'en' => 'ب'], JSON_UNESCAPED_UNICODE);
        $this->assertDatabaseHas('classrooms', ['Name_Class' => $expectedName]);
    }

    #[Test]
    public function index_lists_import_history_scoped_to_school()
    {
        ['admin' => $admin, 'school' => $school] = $this->adminWithSchool();
        DataImport::create([
            'school_id' => $school->id,
            'user_id' => $admin->id,
            'entity' => 'students',
            'file_name' => 'test.csv',
            'status' => 'completed',
            'total_rows' => 10,
            'imported_rows' => 8,
            'skipped_rows' => 2,
        ]);

        $response = $this->actingAs($admin, 'api')->getJson('/api/imports');
        $response->assertOk()->assertJsonPath('total', 1);
    }

    #[Test]
    public function entities_endpoint_returns_supported_entities()
    {
        ['admin' => $admin] = $this->adminWithSchool();
        $response = $this->actingAs($admin, 'api')->getJson('/api/imports/entities');
        $response->assertOk()->assertJsonStructure(['students', 'parents', 'teachers', 'classrooms', 'subjects', 'fees', 'attendances']);
    }

    #[Test]
    public function unauthenticated_user_cannot_access_import_endpoints()
    {
        $this->getJson('/api/imports')->assertStatus(401);
        $this->getJson('/api/imports/entities')->assertStatus(401);
    }
}
