<?php

use App\Models\Announcement;
use App\Models\Integration;
use App\Models\PermissionTemplate;
use App\Models\Plan;
use App\Models\PlatformSetting;
use App\Models\SecurityPolicy;
use App\Models\Subscription;
use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PlatformTableSeeder extends Seeder
{
    /**
     * Maintained by the SchoolController once a school is created.
     *
     * @return void
     */
    public function run()
    {
        $this->flushPlatformTables();

        $this->seedPlans();
        $this->seedPlatformSettings();
        $this->seedIntegrations();
        $this->seedPermissionTemplates();
        $this->seedSecurityPolicies();
        $this->seedAnnouncements();
        $this->seedDemoSubscription();
    }

    protected function flushPlatformTables(): void
    {
        $tables = [
            'subscriptions',
            'announcements',
            'integrations',
            'permission_templates',
            'security_policies',
            'usage_metrics',
            'audit_logs',
            'platform_settings',
            'plans',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }

    protected function seedPlans(): void
    {
        $plans = [
            [
                'name' => ['ar' => 'الخطة الأساسية', 'en' => 'Basic'],
                'code' => 'basic',
                'price' => 0,
                'billing_cycle' => 'monthly',
                'max_students' => 100,
                'max_teachers' => 20,
                'max_branches' => 1,
                'max_storage_gb' => 5,
                'max_api_calls_monthly' => 100000,
                'ai_enabled' => false,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => ['ar' => 'الخطة القياسية', 'en' => 'Standard'],
                'code' => 'standard',
                'price' => 199,
                'billing_cycle' => 'monthly',
                'max_students' => 1000,
                'max_teachers' => 100,
                'max_branches' => 3,
                'max_storage_gb' => 50,
                'max_api_calls_monthly' => 1000000,
                'ai_enabled' => true,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => ['ar' => 'الخطة المميزة', 'en' => 'Premium'],
                'code' => 'premium',
                'price' => 399,
                'billing_cycle' => 'monthly',
                'max_students' => null,
                'max_teachers' => null,
                'max_branches' => 20,
                'max_storage_gb' => 200,
                'max_api_calls_monthly' => 5000000,
                'ai_enabled' => true,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => ['ar' => 'الخطة المؤسسية', 'en' => 'Enterprise'],
                'code' => 'enterprise',
                'price' => 899,
                'billing_cycle' => 'monthly',
                'max_students' => null,
                'max_teachers' => null,
                'max_branches' => 50,
                'max_storage_gb' => 1000,
                'max_api_calls_monthly' => 20000000,
                'ai_enabled' => true,
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::create($plan);
        }
    }

    protected function seedPlatformSettings(): void
    {
        $settings = [
            // general
            ['key' => 'platform_name', 'value' => 'فنتك للأنظمة التعليمية', 'type' => 'string', 'group' => 'general', 'is_public' => true],
            ['key' => 'support_email', 'value' => 'support@school.local', 'type' => 'string', 'group' => 'general', 'is_public' => false],
            ['key' => 'current_academic_year', 'value' => '2026', 'type' => 'string', 'group' => 'general', 'is_public' => false],
            ['key' => 'maintenance_mode', 'value' => '0', 'type' => 'boolean', 'group' => 'general', 'is_public' => true],
            ['key' => 'allow_registration', 'value' => '1', 'type' => 'boolean', 'group' => 'general', 'is_public' => false],

            // features
            ['key' => 'feature_attendance', 'value' => '1', 'type' => 'boolean', 'group' => 'features', 'is_public' => true],
            ['key' => 'feature_exams', 'value' => '1', 'type' => 'boolean', 'group' => 'features', 'is_public' => true],
            ['key' => 'feature_fees', 'value' => '1', 'type' => 'boolean', 'group' => 'features', 'is_public' => true],
            ['key' => 'feature_sms', 'value' => '0', 'type' => 'boolean', 'group' => 'features', 'is_public' => false],
            ['key' => 'feature_ai', 'value' => '1', 'type' => 'boolean', 'group' => 'features', 'is_public' => true],
            ['key' => 'feature_notifications', 'value' => '1', 'type' => 'boolean', 'group' => 'features', 'is_public' => true],

            // security
            ['key' => 'min_password_length', 'value' => '8', 'type' => 'number', 'group' => 'security', 'is_public' => false],
            ['key' => 'session_timeout_minutes', 'value' => '60', 'type' => 'number', 'group' => 'security', 'is_public' => false],
            ['key' => 'login_lockout_attempts', 'value' => '5', 'type' => 'number', 'group' => 'security', 'is_public' => false],
            ['key' => 'force_two_factor', 'value' => '0', 'type' => 'boolean', 'group' => 'security', 'is_public' => false],

            // payments
            ['key' => 'online_payments_enabled', 'value' => '0', 'type' => 'boolean', 'group' => 'payments', 'is_public' => false],
            ['key' => 'currency', 'value' => 'USD', 'type' => 'string', 'group' => 'payments', 'is_public' => false],
        ];

        foreach ($settings as $setting) {
            PlatformSetting::create($setting);
        }
    }

    protected function seedIntegrations(): void
    {
        Integration::create([
            'name' => 'بوابة الدفع (مدى)',
            'code' => 'payment_gateway_mada',
            'description' => 'تكامل مع بوابة مدى لمعالجة المدفوعات المدرسية',
            'config' => ['merchant_id' => '', 'test_mode' => true],
            'is_enabled' => false,
        ]);

        Integration::create([
            'name' => 'بوابة الرسائل النصية',
            'code' => 'sms_gateway',
            'description' => 'إرسال الاشعارات والرسائل النصية لأولياء الأمور',
            'config' => ['provider' => 'unifonic', 'test_mode' => true],
            'is_enabled' => false,
        ]);

        Integration::create([
            'name' => 'تسجيل دخول موحد (OAuth)',
            'code' => 'oauth_sso',
            'description' => 'دخول أحادي للمستخدمين عبر مزود الهوية',
            'config' => ['provider' => 'azure_ad'],
            'is_enabled' => true,
        ]);
    }

    protected function seedPermissionTemplates(): void
    {
        PermissionTemplate::create([
            'name' => ['ar' => 'مدير مدرسة', 'en' => 'School Admin'],
            'description' => 'صلاحيات كاملة على إدارة المدرسة',
            'permissions' => [
                'students.manage' => true,
                'teachers.manage' => true,
                'fees.manage' => true,
                'exams.manage' => true,
                'attendance.manage' => true,
                'reports.view' => true,
                'expenses.manage' => true,
                'salaries.manage' => true,
                'accounting_reports.manage' => true,
            ],
            'is_active' => true,
        ]);

        PermissionTemplate::create([
            'name' => ['ar' => 'معلم', 'en' => 'Teacher'],
            'description' => 'إدارة الفصول والاختبارات والحضور',
            'permissions' => [
                'students.manage' => false,
                'teachers.manage' => false,
                'fees.manage' => false,
                'exams.manage' => true,
                'attendance.manage' => true,
                'reports.view' => false,
            ],
            'is_active' => true,
        ]);
    }

    protected function seedSecurityPolicies(): void
    {
        SecurityPolicy::create([
            'name' => 'التحقق بخطوتين',
            'code' => 'force_2fa',
            'description' => 'إجبار المسؤولين على تمكين المصادقة الثنائية',
            'config' => ['grace_period_days' => 7],
            'is_enabled' => false,
        ]);

        SecurityPolicy::create([
            'name' => 'قفل الحساب',
            'code' => 'account_lockout',
            'description' => 'قفل الحساب بعد محاولات فاشلة متتالية',
            'config' => ['attempts' => 5, 'lock_minutes' => 15],
            'is_enabled' => true,
        ]);
    }

    protected function seedAnnouncements(): void
    {
        Announcement::create([
            'title' => ['ar' => 'مرحباً بكم في منصة الإدارة المدرسية', 'en' => 'Welcome to the System'],
            'body' => ['ar' => 'نظام الإدارة المدرسية في خدمتكم. يمكنكم إدارة كامل أعمال المدرسة إلكترونياً.', 'en' => 'Manage your school digitally.'],
            'audience' => 'all',
            'priority' => 'medium',
            'is_active' => true,
        ]);
    }

    /**
     * Give the demo school a live Standard subscription.
     */
    protected function seedDemoSubscription(): void
    {
        $admin = User::where('email', 'admin@school.local')->first();

        if (! $admin || ! $admin->school_id) {
            return;
        }

        $plan = Plan::where('code', 'standard')->first();

        if (! $plan) {
            return;
        }

        Subscription::firstOrCreate(
            ['school_id' => $admin->school_id],
            [
                'plan_id' => $plan->id,
                'status' => 'active',
                'starts_at' => now()->subMonths(2),
                'ends_at' => now()->addMonths(10),
                'auto_renew' => true,
            ]
        );
    }
}