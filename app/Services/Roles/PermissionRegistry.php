<?php

namespace App\Services\Roles;

/**
 * Central registry of every permission a school admin can hold.
 *
 * Each permission maps to one dashboard "tab". The registry is the single
 * source of truth used by:
 *  - the roles API (available permissions list),
 *  - the backend permission middleware (URI → permission resolution),
 *  - the frontend tab filtering (mirrored in the dashboard).
 */
class PermissionRegistry
{
    /**
     * Granularity: one "manage" permission per tab.
     *
     * @return array<int, array{key: string, label_ar: string, label_en: string, group: string, group_label: string, path: string}>
     */
    public static function all(): array
    {
        return [
            ['key' => 'dashboard.view',           'label_ar' => 'الرئيسية',               'label_en' => 'Dashboard',              'group' => 'general', 'group_label' => 'عام',                'path' => '/'],
            ['key' => 'audit.view',               'label_ar' => 'سجل المراجعة',           'label_en' => 'Audit Log',              'group' => 'general', 'group_label' => 'عام',                'path' => '/audit-logs'],
            ['key' => 'students.manage',          'label_ar' => 'قائمة الطلاب',           'label_en' => 'Students',               'group' => 'students', 'group_label' => 'الطلاب',            'path' => '/students'],
            ['key' => 'attendance.manage',        'label_ar' => 'الحضور والغياب',         'label_en' => 'Attendance',             'group' => 'students', 'group_label' => 'الطلاب',            'path' => '/attendance'],
            ['key' => 'graduated.manage',         'label_ar' => 'الخريجون',               'label_en' => 'Graduated',              'group' => 'students', 'group_label' => 'الطلاب',            'path' => '/graduated'],
            ['key' => 'promotion.manage',         'label_ar' => 'الترقية',                'label_en' => 'Promotion',              'group' => 'students', 'group_label' => 'الطلاب',            'path' => '/promotion'],
            ['key' => 'evaluations.manage',       'label_ar' => 'التقييمات',              'label_en' => 'Evaluations',            'group' => 'students', 'group_label' => 'الطلاب',            'path' => '/evaluations'],
            ['key' => 'grades.manage',            'label_ar' => 'المراحل الدراسية',       'label_en' => 'Grades',                 'group' => 'academics', 'group_label' => 'الأكاديمية',       'path' => '/grades'],
            ['key' => 'classrooms.manage',        'label_ar' => 'الفصول الدراسية',        'label_en' => 'Classrooms',             'group' => 'academics', 'group_label' => 'الأكاديمية',       'path' => '/classrooms'],
            ['key' => 'sections.manage',          'label_ar' => 'الشعب',                  'label_en' => 'Sections',               'group' => 'academics', 'group_label' => 'الأكاديمية',       'path' => '/sections'],
            ['key' => 'subjects.manage',          'label_ar' => 'المواد الدراسية',        'label_en' => 'Subjects',               'group' => 'academics', 'group_label' => 'الأكاديمية',       'path' => '/subjects'],
            ['key' => 'weekly_programs.manage',   'label_ar' => 'البرنامج الأسبوعي',      'label_en' => 'Weekly Programs',        'group' => 'academics', 'group_label' => 'الأكاديمية',       'path' => '/weekly-programs'],
            ['key' => 'scheduling.view',          'label_ar' => 'الجدول السنوي',          'label_en' => 'Annual Schedule',        'group' => 'scheduling', 'group_label' => 'الجدول السنوي',   'path' => '/scheduling/academic-years'],
            ['key' => 'academic_years.manage',    'label_ar' => 'السنة الدراسية والأسابيع', 'label_en' => 'Academic Years',        'group' => 'scheduling', 'group_label' => 'الجدول السنوي',   'path' => '/scheduling/academic-years'],
            ['key' => 'holidays.manage',          'label_ar' => 'الأعياد والإجازات',      'label_en' => 'Holidays',               'group' => 'scheduling', 'group_label' => 'الجدول السنوي',   'path' => '/scheduling/holidays'],
            ['key' => 'timetable_scenarios.manage', 'label_ar' => 'سيناريوهات الجدول',    'label_en' => 'Timetable Scenarios',    'group' => 'academics', 'group_label' => 'الأكاديمية',       'path' => '/timetable-scenarios'],
            ['key' => 'specializations.manage',   'label_ar' => 'التخصصات',               'label_en' => 'Specializations',        'group' => 'academics', 'group_label' => 'الأكاديمية',       'path' => '/specializations'],
            ['key' => 'teachers.manage',          'label_ar' => 'المعلمون',               'label_en' => 'Teachers',               'group' => 'teachers', 'group_label' => 'المعلمون',          'path' => '/teachers'],
            ['key' => 'parents.manage',           'label_ar' => 'أولياء الأمور',          'label_en' => 'Parents',                'group' => 'parents', 'group_label' => 'أولياء الأمور',      'path' => '/parents'],
            ['key' => 'quizzes.manage',           'label_ar' => 'الاختبارات الإلكترونية', 'label_en' => 'Quizzes',                'group' => 'tests', 'group_label' => 'الاختبارات',          'path' => '/quizzes'],
            ['key' => 'questions.manage',         'label_ar' => 'الأسئلة',                'label_en' => 'Questions',              'group' => 'tests', 'group_label' => 'الاختبارات',          'path' => '/questions'],
            ['key' => 'paper_exams.manage',       'label_ar' => 'الاختبارات الورقية',     'label_en' => 'Paper Exams',            'group' => 'tests', 'group_label' => 'الاختبارات',          'path' => '/paper-exams'],
            ['key' => 'fees.manage',              'label_ar' => 'الرسوم الدراسية',        'label_en' => 'Fees',                   'group' => 'finance', 'group_label' => 'المالية',           'path' => '/fees'],
            ['key' => 'fee_invoices.manage',      'label_ar' => 'فواتير الرسوم',          'label_en' => 'Fee Invoices',           'group' => 'finance', 'group_label' => 'المالية',           'path' => '/fees-invoices'],
            ['key' => 'installment_plans.manage', 'label_ar' => 'خطط الأقساط',            'label_en' => 'Installment Plans',      'group' => 'finance', 'group_label' => 'المالية',           'path' => '/installment-plans'],
            ['key' => 'student_installments.manage', 'label_ar' => 'أقساط الطلاب',        'label_en' => 'Student Installments',   'group' => 'finance', 'group_label' => 'المالية',           'path' => '/student-installments'],
            ['key' => 'financial_reports.manage', 'label_ar' => 'التقارير المالية',       'label_en' => 'Financial Reports',      'group' => 'finance', 'group_label' => 'المالية',           'path' => '/financial-reports'],
            ['key' => 'receipts.manage',          'label_ar' => 'السندات',                'label_en' => 'Receipts',               'group' => 'finance', 'group_label' => 'المالية',           'path' => '/receipts'],
            ['key' => 'payments.manage',          'label_ar' => 'المدفوعات',              'label_en' => 'Payments',               'group' => 'finance', 'group_label' => 'المالية',           'path' => '/payments'],
            ['key' => 'processing_fee.manage',    'label_ar' => 'رسوم المعالجة',          'label_en' => 'Processing Fees',        'group' => 'finance', 'group_label' => 'المالية',           'path' => '/processing-fee'],
            ['key' => 'expenses.manage',          'label_ar' => 'المصروفات',              'label_en' => 'Expenses',               'group' => 'finance', 'group_label' => 'المالية',           'path' => '/expenses'],
            ['key' => 'salaries.manage',          'label_ar' => 'الرواتب',                'label_en' => 'Salaries',               'group' => 'finance', 'group_label' => 'المالية',           'path' => '/salaries'],
            ['key' => 'work_hours.manage',        'label_ar' => 'ساعات عمل المعلمين',     'label_en' => 'Teacher Work Hours',     'group' => 'teachers', 'group_label' => 'المعلمون',          'path' => '/work-hours'],
            ['key' => 'accounting_reports.manage', 'label_ar' => 'الأرباح والخسائر',      'label_en' => 'Profit & Loss',          'group' => 'finance', 'group_label' => 'المالية',           'path' => '/profit-loss'],
            ['key' => 'library.manage',           'label_ar' => 'المكتبة',                'label_en' => 'Library',                'group' => 'library', 'group_label' => 'المكتبة',           'path' => '/library'],
            ['key' => 'notifications.manage',     'label_ar' => 'الإشعارات',              'label_en' => 'Notifications',          'group' => 'notifications', 'group_label' => 'الإشعارات',   'path' => '/notifications'],
            ['key' => 'my_notifications.manage',  'label_ar' => 'صندوق الوارد',           'label_en' => 'Inbox',                  'group' => 'notifications', 'group_label' => 'الإشعارات',   'path' => '/my-notifications'],
            ['key' => 'complaints.manage',        'label_ar' => 'الشكاوى والملاحظات',     'label_en' => 'Complaints',             'group' => 'complaints', 'group_label' => 'الشكاوى',        'path' => '/complaints'],
            ['key' => 'transportation.view',            'label_ar' => 'المواصلات',                'label_en' => 'Transportation',           'group' => 'transportation', 'group_label' => 'المواصلات',      'path' => '/transportation'],
            ['key' => 'transportation.buses.manage',    'label_ar' => 'إدارة الحافلات',         'label_en' => 'Buses',                  'group' => 'transportation', 'group_label' => 'المواصلات',      'path' => '/transportation/buses'],
            ['key' => 'transportation.drivers.manage',  'label_ar' => 'إدارة السائقين',         'label_en' => 'Drivers',                'group' => 'transportation', 'group_label' => 'المواصلات',      'path' => '/transportation/drivers'],
            ['key' => 'transportation.routes.manage',   'label_ar' => 'إدارة الخطوط',           'label_en' => 'Routes',                 'group' => 'transportation', 'group_label' => 'المواصلات',      'path' => '/transportation/routes'],
            ['key' => 'transportation.students.manage', 'label_ar' => 'مواصلات الطلاب',         'label_en' => 'Transportation Students', 'group' => 'transportation', 'group_label' => 'المواصلات',   'path' => '/transportation/students'],
            ['key' => 'transportation.trips.manage',    'label_ar' => 'إدارة الرحلات',          'label_en' => 'Trips',                  'group' => 'transportation', 'group_label' => 'المواصلات',      'path' => '/transportation/trips'],
            ['key' => 'transportation.live_tracking.view', 'label_ar' => 'التتبع المباشر',      'label_en' => 'Live Tracking',          'group' => 'transportation', 'group_label' => 'المواصلات',      'path' => '/transportation/live'],
            ['key' => 'transportation.history.view',    'label_ar' => 'سجل الرحلات',            'label_en' => 'Trip History',           'group' => 'transportation', 'group_label' => 'المواصلات',      'path' => '/transportation/history'],
            ['key' => 'transportation.settings.manage', 'label_ar' => 'إعدادات المواصلات',      'label_en' => 'Transportation Settings', 'group' => 'transportation', 'group_label' => 'المواصلات',   'path' => '/transportation/settings'],
            ['key' => 'rewards.manage',           'label_ar' => 'المكافآت والعقوبات',      'label_en' => 'Rewards & Penalties',    'group' => 'students', 'group_label' => 'الطلاب',             'path' => '/rewards-penalties'],
            ['key' => 'rewards.settings.manage',  'label_ar' => 'إعدادات المكافآت والعقوبات', 'label_en' => 'Rewards Settings',    'group' => 'students', 'group_label' => 'الطلاب',             'path' => '/rewards-penalties/settings'],
            ['key' => 'online_classes.manage',    'label_ar' => 'الحصص الافتراضية',       'label_en' => 'Online Classes',         'group' => 'online', 'group_label' => 'الحصص الافتراضية',   'path' => '/online-classes'],
            ['key' => 'settings.manage',          'label_ar' => 'الإعدادات',              'label_en' => 'Settings',               'group' => 'general', 'group_label' => 'عام',                'path' => '/settings'],
            ['key' => 'roles.manage',             'label_ar' => 'الأدوار والصلاحيات',     'label_en' => 'Roles & Permissions',    'group' => 'general', 'group_label' => 'عام',                'path' => '/roles'],

            // ---- Sensitive action approvals ----
            // Sensitive actions (paying a salary, publishing marks to parents,
            // sending a monthly report, editing teacher data) do not run
            // directly: they become a pending request an approver decides on.
            // `pending_requests.manage` opens the queue, `approvals.sensitive`
            // reviews everything, and the remaining keys scope a reviewer to one
            // module. See App\Services\Pending\PendingActionRegistry.
            ['key' => 'pending_requests.manage',  'label_ar' => 'الطلبات المعلقة',        'label_en' => 'Pending Requests',       'group' => 'approvals', 'group_label' => 'الموافقات الحساسة', 'path' => '/pending-requests'],
            ['key' => 'approvals.sensitive',      'label_ar' => 'اعتماد كل الإجراءات الحساسة', 'label_en' => 'Approve All Sensitive Actions', 'group' => 'approvals', 'group_label' => 'الموافقات الحساسة', 'path' => '/pending-requests'],
            ['key' => 'salaries.approve',         'label_ar' => 'اعتماد صرف الرواتب',       'label_en' => 'Approve Salary Payments', 'group' => 'approvals', 'group_label' => 'الموافقات الحساسة', 'path' => '/pending-requests'],
            ['key' => 'paper_exams.approve',      'label_ar' => 'اعتماد نشر الدرجات',       'label_en' => 'Approve Marks Publishing', 'group' => 'approvals', 'group_label' => 'الموافقات الحساسة', 'path' => '/pending-requests'],
            ['key' => 'student_reports.approve',  'label_ar' => 'اعتماد إرسال التقارير',    'label_en' => 'Approve Report Sending',  'group' => 'approvals', 'group_label' => 'الموافقات الحساسة', 'path' => '/pending-requests'],
            ['key' => 'teachers.approve',         'label_ar' => 'اعتماد تعديل بيانات المعلمين', 'label_en' => 'Approve Teacher Edits', 'group' => 'approvals', 'group_label' => 'الموافقات الحساسة', 'path' => '/pending-requests'],
        ];
    }

    /**
     * Flat list grouped by group label, ready for the roles UI.
     *
     * @return array<int, array{group: string, label: string, permissions: array}>
     */
    public static function grouped(): array
    {
        $groups = [];
        foreach (self::all() as $permission) {
            $groups[$permission['group']] ??= [
                'group' => $permission['group'],
                'label' => $permission['group_label'],
                'permissions' => [],
            ];
            $groups[$permission['group']]['permissions'][] = [
                'key' => $permission['key'],
                'label_ar' => $permission['label_ar'],
                'label_en' => $permission['label_en'],
                'path' => $permission['path'],
            ];
        }
        return array_values($groups);
    }

    /**
     * Resolve which permission protects a given URI path, or null.
     * Handles the legacy uppercase/underscore route spellings too.
     */
    public static function permissionForUri(string $uri): ?string
    {
        $uri = '/' . trim(strtolower(urldecode($uri)), '/');

        $aliases = [
            'delete_all' => 'classrooms.manage',
            'filter_classes' => 'classrooms.manage',
            'upload_attachment' => 'students.manage',
            'delete_attachment' => 'students.manage',
            'indirect_admin' => 'online_classes.manage',
            'fees_invoices' => 'fee_invoices.manage',
            'receipt_students' => 'receipts.manage',
            'payment_students' => 'payments.manage',
            'processing_fee' => 'processing_fee.manage',
            'installment_plans' => 'installment_plans.manage',
            'student_installments' => 'student_installments.manage',
            'online_classes' => 'online_classes.manage',
            'expense_categories' => 'expenses.manage',
            'accounting' => 'accounting_reports.manage',
        ];

        foreach ($aliases as $fragment => $permission) {
            if (strpos($uri, '/' . $fragment) === 0) {
                return $permission;
            }
        }

        // Always-accessible admin endpoints that should never be gated by a tab permission.
        $alwaysAllowed = [
            '/platform/public-settings',
            '/imports',
        ];
        foreach ($alwaysAllowed as $fragment) {
            if ($uri === $fragment || strpos($uri, $fragment . '/') === 0) {
                return null;
            }
        }

        foreach (self::all() as $permission) {
            $path = '/' . trim(strtolower($permission['path']), '/');
            if ($uri === $path || strpos($uri, $path . '/') === 0) {
                return $permission['key'];
            }
        }

        return null;
    }
}