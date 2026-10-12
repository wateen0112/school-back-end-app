<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Teacher;
use App\Models\Gender;
use App\Models\Grade;
use App\Models\Classroom;
use App\Models\Section;
use App\Models\Nationalitie;
use App\Models\Type_Blood;
use App\Models\Specialization;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AiAssistantApiTest extends TestCase
{
    use RefreshDatabase;

    private function adminWithSchool(): array
    {
        $school = School::create([
            'name' => ['ar' => 'مدرسة الاختبار'],
            'email' => 'ai-test@school.local',
            'status' => 'active',
        ]);
        $admin = User::factory()->create(['school_id' => $school->id, 'is_super_admin' => false]);
        return ['admin' => $admin, 'school' => $school];
    }

    private function seedLookups(): array
    {
        $gender = Gender::create(['Name' => ['ar' => 'ذكر', 'en' => 'Male']]);
        $grade = Grade::create(['Name' => ['ar' => 'الأولى', 'en' => 'First']]);
        $class = Classroom::create(['Name_Class' => ['ar' => 'أ', 'en' => 'A'], 'Grade_id' => $grade->id]);
        $section = Section::create(['Name_Section' => ['ar' => 'ش', 'en' => 'Sh'], 'Status' => 1, 'Grade_id' => $grade->id, 'Class_id' => $class->id]);
        $nat = Nationalitie::create(['Name' => ['ar' => 'سعودي', 'en' => 'Saudi']]);
        $blood = Type_Blood::create(['Name' => 'O+']);
        $spec = Specialization::create(['Name' => ['ar' => 'رياضيات', 'en' => 'Math']]);
        return compact('gender', 'grade', 'class', 'section', 'nat', 'blood', 'spec');
    }

    #[Test]
    public function chat_returns_503_when_gemini_key_not_configured()
    {
        ['admin' => $admin] = $this->adminWithSchool();

        config(['services.gemini.key' => '']);

        $response = $this->actingAs($admin, 'api')
            ->postJson('/api/ai/chat', ['message' => 'مرحبا']);

        $response->assertStatus(503)->assertJsonPath('code', 'ai_not_configured');
    }

    #[Test]
    public function chat_calls_gemini_and_returns_reply()
    {
        ['admin' => $admin, 'school' => $school] = $this->adminWithSchool();
        config(['services.gemini.key' => 'fake-key-for-testing']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => ['parts' => [['text' => 'مرحباً، كيف يمكنني مساعدتك؟']]],
                        'finishReason' => 'STOP',
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($admin, 'api')
            ->postJson('/api/ai/chat', ['message' => 'مرحبا']);

        $response->assertOk()->assertJsonPath('reply', 'مرحباً، كيف يمكنني مساعدتك؟');
        $this->assertDatabaseHas('audit_logs', ['action' => 'ai_assistant_query']);
    }

    #[Test]
    public function chat_with_absent_today_tool_returns_scoped_data()
    {
        ['admin' => $admin, 'school' => $school] = $this->adminWithSchool();
        config(['services.gemini.key' => 'fake-key-for-testing']);

        $callCount = 0;
        Http::fake(function ($request) use (&$callCount) {
            $callCount++;
            if ($callCount === 1) {
                return Http::response([
                    'candidates' => [
                        [
                            'content' => ['parts' => [[
                                'functionCall' => [
                                    'name' => 'absent_today',
                                    'args' => [],
                                ],
                            ]]],
                            'finishReason' => 'STOP',
                        ],
                    ],
                ], 200);
            }
            return Http::response([
                'candidates' => [
                    [
                        'content' => ['parts' => [['text' => 'لا يوجد غياب اليوم.']]],
                        'finishResult' => 'STOP',
                    ],
                ],
            ], 200);
        });

        $response = $this->actingAs($admin, 'api')
            ->postJson('/api/ai/chat', ['message' => 'كم عدد الغياب اليوم؟']);

        $response->assertOk();
        $toolCalls = $response->json('tool_calls');
        $this->assertNotEmpty($toolCalls);
        $this->assertEquals('absent_today', $toolCalls[0]['name']);
        $this->assertEquals($school->id, $response->json('school_id'));
    }

    #[Test]
    public function chat_scopes_data_to_school()
    {
        ['admin' => $admin, 'school' => $school] = $this->adminWithSchool();
        config(['services.gemini.key' => 'fake-key']);

        $callCount = 0;
        Http::fake(function ($request) use (&$callCount) {
            $callCount++;
            if ($callCount === 1) {
                return Http::response([
                    'candidates' => [['content' => ['parts' => [[
                        'functionCall' => ['name' => 'absent_today', 'args' => []],
                    ]]], 'finishReason' => 'STOP']],
                ], 200);
            }
            return Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'لا يوجد غياب.']]], 'finishResult' => 'STOP']],
            ], 200);
        });

        $response = $this->actingAs($admin, 'api')
            ->postJson('/api/ai/chat', ['message' => 'الغياب اليوم']);

        if ($response->status() !== 200) {
            fwrite(STDERR, "\nAI ERROR BODY: " . $response->getContent() . "\n");
        }
        $response->assertOk();
        $this->assertEquals($school->id, $response->json('school_id'));
        $this->assertEquals('admin', $response->json('role'));
    }

    #[Test]
    public function teacher_can_use_ai_assistant()
    {
        ['admin' => $admin, 'school' => $school] = $this->adminWithSchool();
        $l = $this->seedLookups();
        $teacher = Teacher::create([
            'Name' => ['ar' => 'المعلم', 'en' => 'Teacher'],
            'email' => 'teacher-ai@test.com',
            'password' => bcrypt('password'),
            'Specialization_id' => $l['spec']->id,
            'Gender_id' => $l['gender']->id,
            'Joining_Date' => '2024-01-01',
            'Address' => 'الرياض',
            'school_id' => $school->id,
        ]);

        config(['services.gemini.key' => 'fake-key']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'مرحباً بالمعلم.']]], 'finishReason' => 'STOP']],
            ], 200),
        ]);

        $response = $this->actingAs($teacher, 'api')
            ->postJson('/api/ai/chat', ['message' => 'مرحبا']);

        $response->assertOk()->assertJsonPath('reply', 'مرحباً بالمعلم.');
    }

    #[Test]
    public function questions_endpoint_returns_suggested_prompts()
    {
        ['admin' => $admin] = $this->adminWithSchool();
        config(['services.gemini.key' => 'fake-key']);

        $response = $this->actingAs($admin, 'api')->getJson('/api/ai/questions');
        $response->assertOk()->assertJsonStructure(['questions']);
        $this->assertNotEmpty($response->json('questions'));
    }

    #[Test]
    public function questions_returns_empty_when_not_configured()
    {
        ['admin' => $admin] = $this->adminWithSchool();
        config(['services.gemini.key' => '']);

        $response = $this->actingAs($admin, 'api')->getJson('/api/ai/questions');
        $response->assertOk()->assertJsonPath('questions', []);
    }

    #[Test]
    public function unauthenticated_user_cannot_access_ai()
    {
        $this->postJson('/api/ai/chat', ['message' => 'hi'])->assertStatus(401);
        $this->getJson('/api/ai/questions')->assertStatus(401);
    }
}
