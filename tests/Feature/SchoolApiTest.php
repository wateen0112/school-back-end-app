<?php

namespace Tests\Feature;

use App\Models\School;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SchoolApiTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin()
    {
        return User::factory()->create(['is_super_admin' => true]);
    }

    private function plainAdmin()
    {
        return User::factory()->create(['is_super_admin' => false]);
    }

    #[Test]
    public function super_admin_can_create_school_and_store_admin_credentials()
    {
        $response = $this->actingAs($this->superAdmin(), 'api')
            ->postJson('/api/schools', [
                'name' => 'المدرسة الأهلية',
                'name_en' => 'Ahliya School',
                'email' => 'school2@school.local',
                'password' => 'secret123',
                'phone' => '0112345678',
                'address' => 'دمشق',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.admin_email', 'school2@school.local')
            ->assertJsonPath('data.admin_password', 'secret123')
            ->assertJsonPath('data.school.name.ar', 'المدرسة الأهلية')
            ->assertJsonPath('data.school.name.en', 'Ahliya School');

        $schoolId = $response->json('data.school.id');

        $this->assertDatabaseHas('schools', [
            'id' => $schoolId,
            'email' => 'school2@school.local',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'school2@school.local',
            'school_id' => $schoolId,
            'is_super_admin' => 0,
        ]);
    }

    #[Test]
    public function plain_admin_cannot_create_school()
    {
        $response = $this->actingAs($this->plainAdmin(), 'api')
            ->postJson('/api/schools', [
                'name' => 'مدرسة',
                'email' => 'admin2@school.local',
                'password' => 'secret123',
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseCount('schools', 0);
    }

    #[Test]
    public function school_list_requires_super_admin()
    {
        $response = $this->actingAs($this->plainAdmin(), 'api')
            ->getJson('/api/schools');

        $response->assertStatus(403);
    }

    #[Test]
    public function unauthenticated_user_is_rejected()
    {
        $this->getJson('/api/schools')->assertStatus(401);
        $this->postJson('/api/schools', [])->assertStatus(401);
    }

    #[Test]
    public function super_admin_can_list_schools_with_admin_accounts()
    {
        $school = School::create([
            'name' => ['ar' => 'مدرسة إعدادية', 'en' => 'Prep School'],
            'email' => 'prep@school.local',
            'status' => 'active',
        ]);
        User::factory()->create([
            'name' => 'مدير المدرسة',
            'email' => 'prep@school.local',
            'password' => bcrypt('secret123'),
            'school_id' => $school->id,
            'is_super_admin' => false,
        ]);

        $response = $this->actingAs($this->superAdmin(), 'api')
            ->getJson('/api/schools');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name.ar', 'مدرسة إعدادية')
            ->assertJsonPath('data.0.admin_name', 'مدير المدرسة')
            ->assertJsonPath('data.0.admin_email', 'prep@school.local');
    }

    #[Test]
    public function email_must_be_unique_across_schools_and_users()
    {
        School::create([
            'name' => ['ar' => 'مدرسة', 'en' => 'School'],
            'email' => 'taken@school.local',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superAdmin(), 'api')
            ->postJson('/api/schools', [
                'name' => 'مدرسة أخرى',
                'email' => 'taken@school.local',
                'password' => 'secret123',
            ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function password_is_required_and_min_length_eight()
    {
        $response = $this->actingAs($this->superAdmin(), 'api')
            ->postJson('/api/schools', [
                'name' => 'مدرسة',
                'email' => 'short@school.local',
                'password' => 'short',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}