<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\PlatformSetting;
use App\Models\School;
use App\Models\SchoolBranch;
use App\Models\Subscription;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PlatformApiTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->create(['is_super_admin' => true]);
    }

    private function plainAdmin(): User
    {
        return User::factory()->create(['is_super_admin' => false]);
    }

    private function makeSchool(string $email = 'demo@school.local', bool $withSubscription = false, int $branchCount = 0): School
    {
        $school = School::create([
            'name' => ['ar' => 'المدرسة التجريبية', 'en' => 'Demo School'],
            'email' => $email,
            'status' => 'active',
        ]);

        User::factory()->create([
            'name' => 'المدير',
            'email' => $email,
            'school_id' => $school->id,
            'is_super_admin' => false,
        ]);

        if ($withSubscription) {
            $plan = Plan::create([
                'name' => ['ar' => 'قياسية', 'en' => 'Standard'],
                'code' => 'std',
                'price' => 100,
                'max_branches' => 1,
            ]);
            Subscription::create([
                'school_id' => $school->id,
                'plan_id' => $plan->id,
                'status' => 'active',
            ]);
        }

        for ($i = 1; $i <= $branchCount; $i++) {
            SchoolBranch::create([
                'school_id' => $school->id,
                'name' => ['ar' => "فرع $i", 'en' => "Branch $i"],
            ]);
        }

        return $school;
    }

    #[Test]
    public function school_can_be_suspended_activated_archived_and_restored()
    {
        $school = $this->makeSchool();
        $admin = $this->superAdmin();

        $this->actingAs($admin, 'api')->postJson("/api/school/{$school->id}/suspend")
            ->assertStatus(200)->assertJsonPath('data.is_suspended', true);

        $this->actingAs($admin, 'api')->postJson("/api/school/{$school->id}/activate")
            ->assertStatus(200)->assertJsonPath('data.is_suspended', false);

        $this->actingAs($admin, 'api')->postJson("/api/school/{$school->id}/archive")
            ->assertStatus(200)->assertJsonPath('data.is_archived', true);

        $this->actingAs($admin, 'api')->postJson("/api/school/{$school->id}/restore")
            ->assertStatus(200)->assertJsonPath('data.is_archived', false);
    }

    #[Test]
    public function deleting_non_archived_school_is_blocked()
    {
        $school = $this->makeSchool();

        $response = $this->actingAs($this->superAdmin(), 'api')
            ->deleteJson("/api/schools/{$school->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('schools', ['id' => $school->id]);
    }

    #[Test]
    public function deleting_school_with_branches_is_blocked()
    {
        $school = $this->makeSchool(branchCount: 1);
        $school->update(['archived_at' => now()]);

        $response = $this->actingAs($this->superAdmin(), 'api')
            ->deleteJson("/api/schools/{$school->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('schools', ['id' => $school->id]);
    }

    #[Test]
    public function deleting_school_with_active_subscription_is_blocked()
    {
        $school = $this->makeSchool(withSubscription: true);
        $school->update(['archived_at' => now()]);

        $response = $this->actingAs($this->superAdmin(), 'api')
            ->deleteJson("/api/schools/{$school->id}");

        $response->assertStatus(422);
    }

    #[Test]
    public function archived_school_with_no_branches_and_no_subscription_soft_deletes()
    {
        $school = $this->makeSchool();
        $school->update(['archived_at' => now()]);

        $response = $this->actingAs($this->superAdmin(), 'api')
            ->deleteJson("/api/schools/{$school->id}");

        $response->assertStatus(200)->assertJsonPath('success', true);
        $this->assertSoftDeleted('schools', ['id' => $school->id]);
    }

    #[Test]
    public function soft_deleted_school_can_be_purged_permanently()
    {
        $school = $this->makeSchool();
        $school->update(['archived_at' => now()]);
        $school->delete();

        $response = $this->actingAs($this->superAdmin(), 'api')
            ->deleteJson("/api/schools/{$school->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('schools', ['id' => $school->id]);
    }

    #[Test]
    public function school_update_syncs_admin_email()
    {
        $school = $this->makeSchool();

        $response = $this->actingAs($this->superAdmin(), 'api')
            ->putJson("/api/schools/{$school->id}", [
                'name' => 'مدرسة محدثة',
                'name_en' => 'Updated School',
                'email' => 'updated@school.local',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name.ar', 'مدرسة محدثة')
            ->assertJsonPath('data.admin_email', 'updated@school.local');

        $this->assertDatabaseHas('users', [
            'email' => 'updated@school.local',
            'school_id' => $school->id,
        ]);
    }

    #[Test]
    public function plans_can_be_listed_created_updated_and_deleted()
    {
        $admin = $this->superAdmin();

        $created = $this->actingAs($admin, 'api')->postJson('/api/platform/plans', [
            'name' => 'خطة جديدة',
            'name_en' => 'New Plan',
            'code' => 'new_plan',
            'price' => 50,
            'max_students' => 500,
        ])->assertStatus(201);
        $planId = $created->json('data.id');

        $this->actingAs($admin, 'api')->putJson("/api/platform/plans/{$planId}", [
            'price' => 75,
            'max_students' => 700,
        ])->assertStatus(200)
            ->assertJsonPath('data.price', fn ($price) => $price == 75);

        $this->actingAs($admin, 'api')->getJson('/api/platform/plans')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $this->actingAs($admin, 'api')->deleteJson("/api/platform/plans/{$planId}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('plans', ['id' => $planId]);
    }

    #[Test]
    public function plan_with_subscriptions_cannot_be_deleted()
    {
        $plan = Plan::create([
            'name' => ['ar' => 'قياسية', 'en' => 'Standard'],
            'code' => 'std',
            'price' => 100,
            'max_branches' => 1,
        ]);
        $school = $this->makeSchool();
        Subscription::create([
            'school_id' => $school->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superAdmin(), 'api')
            ->deleteJson("/api/platform/plans/{$plan->id}");

        $response->assertStatus(422);
    }

    #[Test]
    public function subscription_can_be_assigned_and_updated()
    {
        $admin = $this->superAdmin();
        $school = $this->makeSchool();
        $plan = Plan::create([
            'name' => ['ar' => 'مميزة', 'en' => 'Premium'],
            'code' => 'prem',
            'price' => 300,
            'max_branches' => 5,
        ]);

        $this->actingAs($admin, 'api')->postJson('/api/platform/subscriptions', [
            'school_id' => $school->id,
            'plan_id' => $plan->id,
            'status' => 'trial',
            'trial_days' => 14,
        ])->assertStatus(201)
            ->assertJsonPath('data.status', 'trial');

        $this->assertDatabaseHas('subscriptions', [
            'school_id' => $school->id,
            'plan_id' => $plan->id,
            'status' => 'trial',
        ]);

        $subscription = Subscription::where('school_id', $school->id)->firstOrFail();

        $this->actingAs($admin, 'api')->putJson("/api/platform/subscriptions/{$subscription->id}", [
            'status' => 'active',
        ])->assertStatus(200)
            ->assertJsonPath('data.status', 'active');
    }

    #[Test]
    public function school_list_filters_by_status()
    {
        $active = $this->makeSchool('active@school.local');
        $suspended = $this->makeSchool('suspended@school.local');
        $suspended->update(['status' => 'inactive', 'suspended_at' => now()]);

        $admin = $this->superAdmin();

        $all = $this->actingAs($admin, 'api')->getJson('/api/schools')->assertStatus(200);
        $this->assertCount(2, $all->json('data'));

        $withSuspended = $this->actingAs($admin, 'api')->getJson('/api/schools?status=suspended')->assertStatus(200);
        $this->assertCount(1, $withSuspended->json('data'));
        $this->assertEquals('suspended@school.local', $withSuspended->json('data')[0]['email']);

        $activeOnly = $this->actingAs($admin, 'api')->getJson('/api/schools?status=active')->assertStatus(200);
        $this->assertCount(1, $activeOnly->json('data'));
        $this->assertEquals('active@school.local', $activeOnly->json('data')[0]['email']);
    }

    #[Test]
    public function branches_can_be_managed_for_a_school()
    {
        $admin = $this->superAdmin();
        $school = $this->makeSchool();

        $created = $this->actingAs($admin, 'api')->postJson('/api/platform/branches', [
            'school_id' => $school->id,
            'name' => 'فرع المدينة',
            'name_en' => 'City Branch',
            'city' => 'الرياض',
        ])->assertStatus(201);
        $branchId = $created->json('data.id');

        $this->actingAs($admin, 'api')->putJson("/api/platform/branches/{$branchId}", [
            'city' => 'جدة',
        ])->assertStatus(200)
            ->assertJsonPath('data.city', 'جدة');

        $this->actingAs($admin, 'api')->getJson('/api/platform/branches?school_id='.$school->id)
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $this->actingAs($admin, 'api')->deleteJson("/api/platform/branches/{$branchId}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('school_branches', ['id' => $branchId]);
    }

    #[Test]
    public function dashboard_stats_return_expected_shape()
    {
        $school = $this->makeSchool(withSubscription: true, branchCount: 2);

        $response = $this->actingAs($this->superAdmin(), 'api')
            ->getJson('/api/platform/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.schools.total', 1)
            ->assertJsonPath('data.schools.active', 1)
            ->assertJsonPath('data.branches', 2)
            ->assertJsonPath('data.plans', 1)
            ->assertJsonStructure([
                'data' => ['schools', 'subscriptions', 'users', 'plans', 'branches', 'audit', 'schools_monthly', 'usage_this_month'],
            ]);
    }

    #[Test]
    public function audit_log_is_written_when_school_is_created()
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin, 'api')->postJson('/api/schools', [
            'name' => 'مدرسة جديدة',
            'email' => 'new-school@school.local',
            'password' => 'secret123',
        ])->assertStatus(201);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'school.created',
            'entity_type' => 'School',
            'user_id' => $admin->id,
        ]);
    }

    #[Test]
    public function failed_login_is_audited()
    {
        User::factory()->create([
            'email' => 'who@school.local',
            'password' => bcrypt('right-password'),
        ]);

        $this->postJson('/api/login', [
            'email' => 'who@school.local',
            'password' => 'wrong-password',
        ])->assertStatus(401);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.login_failed',
        ]);
    }

    #[Test]
    public function plain_admin_cannot_access_platform_endpoints()
    {
        $this->actingAs($this->plainAdmin(), 'api')
            ->getJson('/api/platform/dashboard')->assertStatus(403);
        $this->actingAs($this->plainAdmin(), 'api')
            ->getJson('/api/platform/plans')->assertStatus(403);
    }

    #[Test]
    public function platform_admins_can_be_managed_without_self_demotion()
    {
        $admin = $this->superAdmin();

        $created = $this->actingAs($admin, 'api')->postJson('/api/platform/admins', [
            'name' => 'مشرف',
            'email' => 'platform-admin@school.local',
            'password' => 'secret123',
        ])->assertStatus(201);
        $newAdminId = $created->json('data.id');

        $this->actingAs($admin, 'api')->getJson('/api/platform/admins')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');

        $this->actingAs($admin, 'api')->deleteJson("/api/platform/admins/{$admin->id}")
            ->assertStatus(422);

        $this->actingAs($admin, 'api')->putJson("/api/platform/admins/{$newAdminId}", [
            'name' => 'مشرف محدث',
        ])->assertStatus(200)->assertJsonPath('data.name', 'مشرف محدث');

        $this->actingAs($admin, 'api')->deleteJson("/api/platform/admins/{$newAdminId}")
            ->assertStatus(200);

        $this->assertDatabaseHas('users', ['id' => $newAdminId, 'is_super_admin' => 0]);
    }

    #[Test]
    public function settings_can_be_updated_and_published()
    {
        PlatformSetting::create([
            'key' => 'maintenance_mode',
            'value' => '0',
            'type' => 'boolean',
            'group' => 'general',
            'is_public' => true,
        ]);

        $admin = $this->superAdmin();

        $this->actingAs($admin, 'api')->postJson('/api/platform/settings', [
            'settings' => ['maintenance_mode' => true],
        ])->assertStatus(200);

        $public = $this->actingAs($this->plainAdmin(), 'api')
            ->getJson('/api/platform/public-settings');

        $public->assertStatus(200)
            ->assertJsonPath('data.maintenance_mode', true);
    }

    #[Test]
    public function announcements_can_be_crudded_and_broadcast()
    {
        $admin = $this->superAdmin();

        $created = $this->actingAs($admin, 'api')->postJson('/api/platform/announcements', [
            'title' => 'إعلان جديد',
            'title_en' => 'New Announcement',
            'body' => 'تفاصيل الإعلان',
            'audience' => 'all',
            'priority' => 'high',
        ])->assertStatus(201)
            ->assertJsonPath('data.title.ar', 'إعلان جديد')
            ->assertJsonPath('data.priority', 'high');

        $announcementId = $created->json('data.id');

        $this->actingAs($admin, 'api')->postJson("/api/platform/announcements/{$announcementId}/broadcast")
            ->assertStatus(200)->assertJsonPath('data.is_active', true);

        $this->actingAs($admin, 'api')->deleteJson("/api/platform/announcements/{$announcementId}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('announcements', ['id' => $announcementId]);
    }

    #[Test]
    public function integrations_templates_and_policies_can_be_toggled()
    {
        $admin = $this->superAdmin();
        $school = $this->makeSchool();

        $this->actingAs($admin, 'api')->postJson('/api/platform/integrations', [
            'name' => 'Portals',
            'code' => 'portal',
            'description' => 'test',
        ])->assertStatus(201);

        $this->actingAs($admin, 'api')->postJson('/api/platform/permission-templates', [
            'name' => 'أساسي',
            'name_en' => 'Basic',
            'permissions' => ['students.manage' => true],
        ])->assertStatus(201);

        $this->actingAs($admin, 'api')->postJson('/api/platform/security-policies', [
            'name' => 'كلمة مرور قوية',
            'code' => 'strong_password',
            'config' => ['min_length' => 10],
        ])->assertStatus(201);

        $this->assertDatabaseCount('integrations', 1);
        $this->assertDatabaseCount('permission_templates', 1);
        $this->assertDatabaseCount('security_policies', 1);

        $integration = \App\Models\Integration::firstOrFail();
        $policy = \App\Models\SecurityPolicy::firstOrFail();

        $this->actingAs($admin, 'api')->postJson("/api/platform/integrations/{$integration->id}/toggle")
            ->assertStatus(200)->assertJsonPath('data.is_enabled', true);

        $this->actingAs($admin, 'api')->postJson("/api/platform/security-policies/{$policy->id}/toggle")
            ->assertStatus(200)->assertJsonPath('data.is_enabled', true);
    }

    #[Test]
    public function school_show_is_accessible()
    {
        $school = $this->makeSchool('show@school.local');

        $this->actingAs($this->superAdmin(), 'api')
            ->getJson("/api/school/{$school->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.email', 'show@school.local')
            ->assertJsonPath('data.admin_email', 'show@school.local');
    }
}