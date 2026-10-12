<?php

namespace Tests\Feature;

use App\Models\School;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SchoolBrandingApiTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin()
    {
        return User::factory()->create(['is_super_admin' => true]);
    }

    private function schoolAdmin(School $school)
    {
        return User::factory()->create([
            'is_super_admin' => false,
            'school_id' => $school->id,
        ]);
    }

    private function makeSchool(array $overrides = [])
    {
        return School::create(array_merge([
            'name' => ['ar' => 'مدرسة الاختبار', 'en' => 'Test School'],
            'email' => 'branding@school.local',
            'status' => 'active',
        ], $overrides));
    }

    #[Test]
    public function super_admin_can_set_school_theme()
    {
        $school = $this->makeSchool();
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin, 'api')
            ->postJson("/api/schools/{$school->id}/branding", [
                'theme' => [
                    'primary' => '#3366FF',
                    'primary_dark' => '#0B1B4D',
                    'accent' => '#00C2FF',
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.theme.primary', '#3366FF')
            ->assertJsonPath('data.theme.primary_dark', '#0B1B4D')
            ->assertJsonPath('data.theme.accent', '#00C2FF');

        $this->assertDatabaseHas('schools', ['id' => $school->id]);
        $this->assertEquals('#3366FF', $school->refresh()->theme['primary']);
    }

    #[Test]
    public function super_admin_can_set_school_logo()
    {
        $school = $this->makeSchool();
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin, 'api')
            ->post('/api/schools/'.$school->id.'/branding', [
                'logo' => UploadedFile::fake()->image('school-logo.png', 120, 120),
            ], ['Accept' => 'application/json']);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertNotNull($school->refresh()->logo);
        $this->assertNotNull($response->json('data.logo_url'));
    }

    #[Test]
    public function school_admin_can_read_their_school_theme()
    {
        $school = $this->makeSchool(['theme' => [
            'primary' => '#DD5500',
            'primary_dark' => '#2A1500',
            'accent' => '#FFAA00',
        ]]);
        $user = $this->schoolAdmin($school);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/schools/theme');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.school_id', $school->id)
            ->assertJsonPath('data.theme.primary', '#DD5500')
            ->assertJsonPath('data.name.ar', 'مدرسة الاختبار');
    }

    #[Test]
    public function user_without_school_gets_404()
    {
        $user = $this->schoolAdmin($this->makeSchool());
        $user->update(['school_id' => null]);

        $this->actingAs($user, 'api')->getJson('/api/schools/theme')->assertStatus(404);
    }

    #[Test]
    public function unauthenticated_theme_request_is_rejected()
    {
        $this->getJson('/api/schools/theme')->assertStatus(401);
    }

    #[Test]
    public function plain_admin_cannot_update_school_branding()
    {
        $school = $this->makeSchool();
        $user = $this->schoolAdmin($school);

        $this->actingAs($user, 'api')
            ->postJson("/api/schools/{$school->id}/branding", [
                'theme' => ['primary' => '#3366FF'],
            ])
            ->assertStatus(403);
    }

    #[Test]
    public function invalid_theme_hex_is_rejected()
    {
        $school = $this->makeSchool();
        $admin = $this->superAdmin();

        $this->actingAs($admin, 'api')
            ->postJson("/api/schools/{$school->id}/branding", [
                'theme' => ['primary' => 'not-a-color'],
            ])
            ->assertStatus(422);
    }

    #[Test]
    public function defaults_are_used_when_no_theme_given()
    {
        $school = $this->makeSchool();
        $admin = $this->superAdmin();

        $this->actingAs($admin, 'api')
            ->postJson("/api/schools/{$school->id}/branding", [
                'theme' => ['primary' => '#123456'],
            ])
            ->assertStatus(200);

        $theme = $school->refresh()->theme;
        $this->assertEquals('#123456', $theme['primary']);
        $this->assertEquals(School::THEME_DEFAULTS['primary_dark'], $theme['primary_dark']);
        $this->assertEquals(School::THEME_DEFAULTS['accent'], $theme['accent']);
    }
}