<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// API Authentication Routes (public - no auth required)
Route::group(['namespace' => 'Api\Auth', 'middleware' => 'throttle:5,1'], function () {
    Route::post('/login', 'LoginController@login')->name('api.login');
    Route::post('/auth/login', 'LoginController@login')->name('api.auth.login');
    Route::post('/login/{type}', 'LoginController@loginWithType')->name('api.login.legacy');
});

// Public endpoints (no authentication required)
Route::get('/public/info', 'Api\HomeController@publicInfo');
Route::get('/public/grades', function() {
    return response()->json([
        'success' => true,
        'message' => 'Public grades retrieved successfully',
        'data' => \App\Models\Grade::select('id', 'Name')->get()
    ]);
});
Route::get('/public/subjects', function() {
    return response()->json([
        'success' => true,
        'message' => 'Public subjects retrieved successfully',
        'data' => \App\Models\Subject::select('id', 'Name')->get()
    ]);
});
Route::get('/public/specializations', function() {
    return response()->json([
        'success' => true,
        'message' => 'Public specializations retrieved successfully',
        'data' => \App\Models\Specialization::select('id', 'Name')->get()
    ]);
});

// ============================================
// READ-ONLY ROUTES - Available to ALL authenticated users (any role)
// Uses auth.any middleware that accepts any user type via Sanctum
// ============================================
Route::group(['middleware' => 'auth.any'], function () {

    // Auth user info
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Logout, refresh, me
    Route::post('/logout', 'Api\Auth\LoginController@logout')->name('api.logout');
    Route::post('/refresh', 'Api\Auth\LoginController@refresh')->name('api.refresh');
    Route::post('/me', 'Api\Auth\LoginController@me')->name('api.me');

    // Mobile profile (self-service for any authenticated role)
    Route::get('/profile', 'Api\ProfileController@show')->name('api.profile.show');
    Route::put('/profile', 'Api\ProfileController@update')->name('api.profile.update');
    Route::post('/profile', 'Api\ProfileController@update')->name('api.profile.update.post');

    // School branding (custom theme + logo) for the school dashboard
    Route::get('/schools/theme', 'Api\Schools\SchoolBrandingController@show')
        ->name('api.schools.theme');

    // Test endpoint
    Route::get('/test', function() {
        return response()->json(['message' => 'API test working', 'user' => auth()->user()]);
    });

    // Dashboard endpoints
    Route::get('/dashboard', 'Api\DashboardController@index');
    Route::get('/dashboard/stats', 'Api\DashboardController@stats');

    // Home
    Route::get('/', 'Api\HomeController@publicInfo')->name('api.web.selection');

    // Grades - GET only
    Route::get('Grades', 'Api\Grades\GradeController@index')->name('api.web.Grades.index');
    Route::get('Grades/{Grade}', 'Api\Grades\GradeController@show')->name('api.web.Grades.show');
    Route::get('grades', 'Api\Grades\GradeController@index');
    Route::get('grades/{grade}', 'Api\Grades\GradeController@show');

    // Classrooms - GET only
    Route::get('Classrooms', 'Api\Classrooms\ClassroomController@index')->name('api.web.Classrooms.index');
    Route::get('Classrooms/{Classroom}', 'Api\Classrooms\ClassroomController@show')->name('api.web.Classrooms.show');
    Route::get('classrooms', 'Api\Classrooms\ClassroomController@index');
    Route::get('classrooms/{classroom}', 'Api\Classrooms\ClassroomController@show');

    // Sections - GET only
    Route::get('Sections', 'Api\Sections\SectionController@index')->name('api.web.Sections.index');
    Route::get('Sections/{Section}', 'Api\Sections\SectionController@show')->name('api.web.Sections.show');
    Route::get('classes/{id}', 'Api\Sections\SectionController@getclasses')->name('api.web.classes');
    Route::get('sections', 'Api\Sections\SectionController@index');
    Route::get('sections/{section}', 'Api\Sections\SectionController@show');
    Route::get('sections/classes/{id}', 'Api\Sections\SectionController@getclasses');

    // Teachers - GET only
    Route::get('Teachers', 'Api\Teachers\TeacherController@index')->name('api.web.Teachers.index');
    Route::get('Teachers/{Teacher}', 'Api\Teachers\TeacherController@show')->name('api.web.Teachers.show');
    Route::get('teachers', 'Api\Teachers\TeacherController@index');
    Route::get('teachers/{teacher}', 'Api\Teachers\TeacherController@show');

    // Students - GET only
    Route::get('Students', 'Api\Students\StudentController@index')->name('api.web.Students.index');
    Route::get('Students/{Student}', 'Api\Students\StudentController@show')->name('api.web.Students.show');
    Route::get('Students/classrooms-by-grade/{id}', 'Api\Students\StudentController@Get_classrooms')->name('api.web.Students.classroomsByGrade');
    Route::get('Students/sections-by-classroom/{id}', 'Api\Students\StudentController@Get_Sections')->name('api.web.Students.sectionsByClassroom');
    Route::get('Students/sections-by-grade/{id}', 'Api\Students\StudentController@sectionsByGrade')->name('api.web.Students.sectionsByGrade');
    Route::get('students', 'Api\Students\StudentController@index');
    Route::get('students/{student}', 'Api\Students\StudentController@show');
    Route::get('students/classrooms-by-grade/{id}', 'Api\Students\StudentController@Get_classrooms');
    Route::get('students/sections-by-classroom/{id}', 'Api\Students\StudentController@Get_Sections');
    Route::get('students/sections-by-grade/{id}', 'Api\Students\StudentController@sectionsByGrade');

    // Online Classes - GET only
    Route::get('online_classes', 'Api\Students\OnlineClasseController@index')->name('api.web.online_classes.index');
    Route::get('online_classes/{online_classes}', 'Api\Students\OnlineClasseController@show')->name('api.web.online_classes.show');
    Route::get('online-classes', 'Api\Students\OnlineClasseController@index');
    Route::get('online-classes/{online_class}', 'Api\Students\OnlineClasseController@show');
    Route::get('online-classes/indirect-admin', 'Api\Students\OnlineClasseController@indirectCreate');

    // Graduated - GET only
    Route::get('Graduated', 'Api\Students\GraduatedController@index')->name('api.web.Graduated.index');
    Route::get('Graduated/{Graduated}', 'Api\Students\GraduatedController@show')->name('api.web.Graduated.show');
    Route::get('graduated', 'Api\Students\GraduatedController@index');
    Route::get('graduated/{graduated}', 'Api\Students\GraduatedController@show');

    // Promotion - GET only
    Route::get('Promotion', 'Api\Students\PromotionController@index')->name('api.web.Promotion.index');
    Route::get('Promotion/{Promotion}', 'Api\Students\PromotionController@show')->name('api.web.Promotion.show');
    Route::get('promotion', 'Api\Students\PromotionController@index');
    Route::get('promotion/students-for-promotion', 'Api\Students\PromotionController@getStudentsForPromotion');
    Route::get('promotion/{promotion}', 'Api\Students\PromotionController@show');

    // Fees Invoices - GET only
    Route::get('Fees_Invoices', 'Api\Students\FeesInvoicesController@index')->name('api.web.Fees_Invoices.index');
    Route::get('Fees_Invoices/{Fees_Invoices}', 'Api\Students\FeesInvoicesController@show')->name('api.web.Fees_Invoices.show');
    Route::get('fees-invoices', 'Api\Students\FeesInvoicesController@index');
    Route::get('fees-invoices/{fees_invoice}', 'Api\Students\FeesInvoicesController@show');

    // Fees - GET only
    Route::get('Fees', 'Api\Students\FeesController@index')->name('api.web.Fees.index');
    Route::get('Fees/{Fees}', 'Api\Students\FeesController@show')->name('api.web.Fees.show');
    Route::get('fees', 'Api\Students\FeesController@index');
    Route::get('fees/{fee}', 'Api\Students\FeesController@show');

    // Receipt Students - GET only
    Route::get('receipt_students', 'Api\Students\ReceiptStudentsController@index')->name('api.web.receipt_students.index');
    Route::get('receipt_students/{receipt_students}', 'Api\Students\ReceiptStudentsController@show')->name('api.web.receipt_students.show');
    Route::get('receipt-students', 'Api\Students\ReceiptStudentsController@index');
    Route::get('receipt-students/{receipt_student}', 'Api\Students\ReceiptStudentsController@show');

    // Processing Fee - GET only
    Route::get('ProcessingFee', 'Api\Students\ProcessingFeeController@index')->name('api.web.ProcessingFee.index');
    Route::get('ProcessingFee/{ProcessingFee}', 'Api\Students\ProcessingFeeController@show')->name('api.web.ProcessingFee.show');
    Route::get('processing-fee', 'Api\Students\ProcessingFeeController@index');
    Route::get('processing-fee/{processing_fee}', 'Api\Students\ProcessingFeeController@show');

    // Payment Students - GET only
    Route::get('Payment_students', 'Api\Students\PaymentController@index')->name('api.web.Payment_students.index');
    Route::get('Payment_students/{Payment_students}', 'Api\Students\PaymentController@show')->name('api.web.Payment_students.show');
    Route::get('payment-students', 'Api\Students\PaymentController@index');
    Route::get('payment-students/{payment_student}', 'Api\Students\PaymentController@show');

    // Attendance - GET only
    Route::get('Attendance', 'Api\Students\AttendanceController@index')->name('api.web.Attendance.index');
    Route::get('Attendance/{Attendance}', 'Api\Students\AttendanceController@show')->name('api.web.Attendance.show');
    Route::get('attendance', 'Api\Students\AttendanceController@index');
    Route::get('attendance/{attendance}', 'Api\Students\AttendanceController@show');
    Route::get('parent/attendance', 'Api\Students\AttendanceController@parentAttendance');
    
    // Attendance - Write (available to all authenticated users for teacher/parent use)
    Route::post('Attendance', 'Api\Students\AttendanceController@store')->name('api.web.Attendance.store');
    Route::post('Attendance/bulk', 'Api\Students\AttendanceController@bulkStore')->name('api.web.Attendance.bulk');
    Route::put('Attendance/{Attendance}', 'Api\Students\AttendanceController@update')->name('api.web.Attendance.update');
    Route::delete('Attendance/{Attendance}', 'Api\Students\AttendanceController@destroy')->name('api.web.Attendance.destroy');
    Route::post('attendance', 'Api\Students\AttendanceController@store')->middleware('academic.context');
    Route::post('attendance/bulk', 'Api\Students\AttendanceController@bulkStore')->middleware('academic.context');
    Route::put('attendance/bulk', 'Api\Students\AttendanceController@bulkStore');
    Route::put('attendance/{attendance}', 'Api\Students\AttendanceController@update');
    Route::delete('attendance/{attendance}', 'Api\Students\AttendanceController@destroy');

    // Library - GET only
    Route::get('library', 'Api\Students\LibraryController@index')->name('api.web.library.index');
    Route::get('library/{library}', 'Api\Students\LibraryController@show')->name('api.web.library.show');
    Route::get('download_file/{filename}', 'Api\Students\LibraryController@downloadAttachment')->name('api.web.downloadAttachment');
    Route::get('library/download/{filename}', 'Api\Students\LibraryController@downloadAttachment');

    // Student Attachments - GET only
    Route::get('Download_attachment/{studentsname}/{filename}', 'Api\Students\StudentController@Download_attachment')->name('api.web.Download_attachment');
    Route::get('students/download-attachment/{studentsname}/{filename}', 'Api\Students\StudentController@Download_attachment');

    // Subjects - GET only
    Route::get('subjects', 'Api\Subjects\SubjectController@index')->name('api.web.subjects.index');
    Route::get('subjects/{subject}', 'Api\Subjects\SubjectController@show')->name('api.web.subjects.show');

    // Weekly Programs (timetable) - GET only
    Route::get('weekly-programs/today', 'Api\WeeklyPrograms\WeeklyProgramController@today')->name('api.web.weekly-programs.today');
    Route::get('weekly-programs/my-schedule', 'Api\WeeklyPrograms\WeeklyProgramController@mySchedule')->name('api.web.weekly-programs.my-schedule');
    Route::get('my-schedule', 'Api\WeeklyPrograms\WeeklyProgramController@mySchedule')->name('api.web.my-schedule');
    Route::get('weekly-programs', 'Api\WeeklyPrograms\WeeklyProgramController@index')->name('api.web.weekly-programs.index');
    Route::get('weekly-programs/{weeklyProgram}', 'Api\WeeklyPrograms\WeeklyProgramController@show')->name('api.web.weekly-programs.show');

    // Timetable Scenarios - GET only
    Route::get('timetable-scenarios', 'Api\WeeklyPrograms\ScenarioController@index')->name('api.web.timetable-scenarios.index');
    Route::get('timetable-scenarios/{id}', 'Api\WeeklyPrograms\ScenarioController@show')->name('api.web.timetable-scenarios.show');

    // Subject Requirements - GET only
    Route::get('subject-requirements', 'Api\WeeklyPrograms\ScenarioController@requirements')->name('api.web.subject-requirements.index');

    // Section-Subject-Teacher assignments - GET only
    Route::get('section-subject-teachers', 'Api\WeeklyPrograms\SectionSubjectTeacherController@index')->name('api.web.section-subject-teachers.index');

    // Teacher availability & preferences - GET only
    Route::get('teacher-schedules', 'Api\WeeklyPrograms\TeacherAvailabilityController@index')->name('api.web.teacher-schedules.index');
    Route::get('teacher-schedules/preferences', 'Api\WeeklyPrograms\TeacherAvailabilityController@preferences')->name('api.web.teacher-schedules.preferences');
    Route::get('teacher-preferences', 'Api\WeeklyPrograms\TeacherAvailabilityController@preferences')->name('api.web.teacher-preferences.index');

    // School schedule settings - GET only
    Route::get('school-schedule-settings', 'Api\WeeklyPrograms\SchoolScheduleSettingController@show')->name('api.web.school-schedule-settings.show');

    // Role-specific dashboards
    Route::get('student/dashboard', 'Api\Dashboards\StudentDashboardController@show')->name('api.web.student.dashboard');
    Route::get('parent/dashboard', 'Api\Dashboards\ParentDashboardController@show')->name('api.web.parent.dashboard');
    Route::get('teacher/dashboard', 'Api\Dashboards\TeacherDashboardController@show')->name('api.web.teacher.dashboard');

    // Teacher self-service availability & preferences (scoped to the authenticated teacher)
    Route::get('teacher/my-availability', 'Api\Teachers\MyAvailabilityController@index')->name('api.web.teacher.my-availability.index');
    Route::post('teacher/my-availability/schedules', 'Api\Teachers\MyAvailabilityController@syncSchedules')->name('api.web.teacher.my-availability.schedules');
    Route::post('teacher/my-availability/preferences', 'Api\Teachers\MyAvailabilityController@updatePreferences')->name('api.web.teacher.my-availability.preferences');

    // Parent financial report (fee invoices + receipts per child)
    Route::get('parent/finance', 'Api\Parents\ParentFinanceController@index')->name('api.web.parent.finance');

    // Student monthly reports
    Route::get('student-reports', 'Api\Dashboards\StudentReportController@index')->name('api.web.student-reports.index');
    Route::get('student-reports/export', 'Api\Dashboards\StudentReportController@export')->name('api.web.student-reports.export');
    Route::get('student-reports/parent', 'Api\Dashboards\StudentReportController@parent')->name('api.web.student-reports.parent');
    Route::get('student-reports/trends/{studentId}', 'Api\Dashboards\StudentReportController@trends')->name('api.web.student-reports.trends');
    Route::get('student-reports/{studentId}', 'Api\Dashboards\StudentReportController@show')->name('api.web.student-reports.show');
    Route::get('student-reports/{studentId}/{month}/{year}', 'Api\Dashboards\StudentReportController@detail')->name('api.web.student-reports.detail');
    Route::post('student-reports/generate', 'Api\Dashboards\StudentReportController@generate')->name('api.web.student-reports.generate');

    // Quizzes - GET only
    Route::get('Quizzes', 'Api\Quizzes\QuizzController@index')->name('api.web.Quizzes.index');
    Route::get('Quizzes/{Quizzes}', 'Api\Quizzes\QuizzController@show')->name('api.web.Quizzes.show');
    Route::get('quizzes', 'Api\Quizzes\QuizzController@index');
    Route::get('quizzes/{quizz}', 'Api\Quizzes\QuizzController@show');

    // Questions - GET only
    Route::get('questions', 'Api\Questions\QuestionController@index')->name('api.web.questions.index');
    Route::get('questions/{question}', 'Api\Questions\QuestionController@show')->name('api.web.questions.show');

    // Settings - GET only
    Route::get('settings', 'Api\SettingController@index')->name('api.web.settings.index');
    Route::get('settings/{setting}', 'Api\SettingController@show')->name('api.web.settings.show');

    // Notifications - GET only
    Route::get('Notifications', 'Api\Notifications\NotificationController@index')->name('api.web.Notifications.index');
    Route::get('Notifications/{Notification}', 'Api\Notifications\NotificationController@show')->name('api.web.Notifications.show');
    Route::get('my-notifications', 'Api\Notifications\NotificationController@myNotifications');
    Route::get('my-notifications/count', 'Api\Notifications\NotificationController@unreadCount');
    Route::post('my-notifications/{id}/read', 'Api\Notifications\NotificationController@markAsRead');
    Route::post('my-notifications/read-all', 'Api\Notifications\NotificationController@markAllAsRead');
    Route::post('my-notifications/{id}/unread', 'Api\Notifications\NotificationController@markAsUnread');
    Route::delete('my-notifications/{id}', 'Api\Notifications\NotificationController@deleteMyNotification');
    Route::get('notifications', 'Api\Notifications\NotificationController@index');
    Route::get('notifications/{notification}', 'Api\Notifications\NotificationController@show');
    // FCM Token registration
    Route::post('register-fcm-token', 'Api\Notifications\FcmTokenController@store');
    Route::delete('fcm-token', 'Api\Notifications\FcmTokenController@destroy');
    // Parents - GET only
    Route::get('parents', 'Api\Parents\ParentController@index');
    Route::get('parents/{parent}', 'Api\Parents\ParentController@show');

    // Installment Plans - GET only
    Route::get('installment-plans', 'Api\Students\InstallmentPlanController@index')->name('api.web.installment-plans.index');
    Route::get('installment-plans/{installmentPlan}', 'Api\Students\InstallmentPlanController@show')->name('api.web.installment-plans.show');
    Route::get('InstallmentPlans', 'Api\Students\InstallmentPlanController@index')->name('api.web.InstallmentPlans.index');
    Route::get('InstallmentPlans/{installmentPlan}', 'Api\Students\InstallmentPlanController@show')->name('api.web.InstallmentPlans.show');

    // Student Installments - GET only
    Route::get('student-installments', 'Api\Students\StudentInstallmentController@index')->name('api.web.student-installments.index');
    Route::get('student-installments/{studentInstallment}', 'Api\Students\StudentInstallmentController@show')->name('api.web.student-installments.show');
    Route::get('StudentInstallments', 'Api\Students\StudentInstallmentController@index')->name('api.web.StudentInstallments.index');
    Route::get('StudentInstallments/{studentInstallment}', 'Api\Students\StudentInstallmentController@show')->name('api.web.StudentInstallments.show');
    Route::get('my-installments', 'Api\Students\StudentInstallmentController@myInstallments')->name('api.web.my-installments');
    Route::get('MyInstallments', 'Api\Students\StudentInstallmentController@myInstallments')->name('api.web.MyInstallments');

    // Specializations - GET only
    Route::get('specializations', 'Api\Specializations\SpecializationController@index');
    Route::get('specializations/{specialization}', 'Api\Specializations\SpecializationController@show');

    // Evaluations - Available to all authenticated users (teachers create, parents read)
    Route::get('evaluations', 'Api\Students\EvaluationController@index');
    Route::get('evaluations/{evaluation}', 'Api\Students\EvaluationController@show');
    Route::post('evaluations', 'Api\Students\EvaluationController@store')->middleware('academic.context');
    Route::post('evaluations/bulk', 'Api\Students\EvaluationController@bulkStore')->middleware('academic.context');
    Route::put('evaluations/{evaluation}', 'Api\Students\EvaluationController@update');
    Route::delete('evaluations/{evaluation}', 'Api\Students\EvaluationController@destroy');
    Route::get('evaluations/report/student/{studentId}', 'Api\Students\EvaluationController@reportByStudent');
    Route::get('evaluations/report/teacher/{teacherId}', 'Api\Students\EvaluationController@reportByTeacher');
    Route::get('evaluations/report/subject/{subjectId}', 'Api\Students\EvaluationController@reportBySubject');
    Route::get('evaluations/report/classroom/{classroomId}', 'Api\Students\EvaluationController@reportByClassroom');
    Route::get('evaluations/report/grade/{gradeId}', 'Api\Students\EvaluationController@reportByGrade');
    Route::get('evaluations/report/monthly-summary', 'Api\Students\EvaluationController@monthlySummary');
    Route::get('evaluations/report/parent', 'Api\Students\EvaluationController@reportForParent');

    // Teacher-scoped endpoints for student selection and evaluation workflows
    Route::get('teacher/sections', 'Api\Teachers\TeacherEvaluationController@sections');
    Route::get('teacher/subjects', 'Api\Teachers\TeacherEvaluationController@subjects');
    Route::get('teacher/assignments', 'Api\Teachers\TeacherEvaluationController@assignments')->name('api.web.teacher.assignments.index');
    Route::get('teacher/students', 'Api\Teachers\TeacherEvaluationController@students');
    Route::get('teacher/exams', 'Api\Students\StudentExamController@teacherExams')->name('api.web.teacher.exams.index');

    // Paper Exams - Read & Write (available to teachers, admins, students, parents based on role)
    Route::get('paper-exams', 'Api\PaperExams\PaperExamController@index');
    Route::get('paper-exams/{id}', 'Api\PaperExams\PaperExamController@show');
    Route::post('paper-exams', 'Api\PaperExams\PaperExamController@store')->middleware('academic.context');
    Route::put('paper-exams/{id}', 'Api\PaperExams\PaperExamController@update');
    Route::delete('paper-exams/{id}', 'Api\PaperExams\PaperExamController@destroy');
    Route::get('paper-exams/{id}/students', 'Api\PaperExams\PaperExamController@students');
    Route::post('paper-exams/{id}/marks', 'Api\PaperExams\PaperExamController@saveMarks')->middleware('academic.context');
    Route::post('paper-exams/{id}/publish', 'Api\PaperExams\PaperExamController@publish')->middleware('academic.context');
    // Teacher-specific paper exam endpoints
    Route::get('teacher/paper-exams/subjects', 'Api\PaperExams\PaperExamController@teacherSubjects');
    Route::get('teacher/paper-exams/{subjectId}', 'Api\PaperExams\PaperExamController@teacherExamsBySubject');
    // Student/Parent - view their marks
    Route::get('paper-exam-marks/my', 'Api\PaperExams\PaperExamController@myMarks');
    Route::get('paper-exam-marks/my/{subjectId}', 'Api\PaperExams\PaperExamController@myMarksBySubject');
    // Admin/Teacher - view a specific student's paper exam marks
    Route::get('paper-exams/student/{studentId}/marks', 'Api\PaperExams\PaperExamController@marksByStudent');

    // Complaints & Feedback - Available to all authenticated users
    Route::get('complaints', 'Api\Complaints\ComplaintController@index');
    Route::get('complaints/unread-count', 'Api\Complaints\ComplaintController@unreadCount');
    Route::get('complaints/{id}', 'Api\Complaints\ComplaintController@show');
    Route::post('complaints', 'Api\Complaints\ComplaintController@store');
    Route::put('complaints/{id}', 'Api\Complaints\ComplaintController@update');
    Route::delete('complaints/{id}', 'Api\Complaints\ComplaintController@destroy');
    Route::post('complaints/{id}/reply', 'Api\Complaints\ComplaintController@reply');
    Route::get('complaints/attachment/{id}/download', 'Api\Complaints\ComplaintController@downloadAttachment');

    // Pending Requests (sensitive action approvals) - Available to all authenticated users.
    // Authorization is per row: reviewers see the school queue, everybody else
    // only their own submissions, and approving requires the action's permission.
    Route::get('pending-requests', 'Api\PendingRequests\PendingRequestController@index');
    Route::get('pending-requests/stats', 'Api\PendingRequests\PendingRequestController@stats');
    Route::get('pending-requests/actions', 'Api\PendingRequests\PendingRequestController@actions');
    Route::get('pending-requests/{id}', 'Api\PendingRequests\PendingRequestController@show');
    Route::get('pending-requests/{id}/preview', 'Api\PendingRequests\PendingRequestController@preview');
    Route::post('pending-requests', 'Api\PendingRequests\PendingRequestController@store');
    Route::post('pending-requests/{id}/approve', 'Api\PendingRequests\PendingRequestController@approve');
    Route::post('pending-requests/{id}/reject', 'Api\PendingRequests\PendingRequestController@reject');
    Route::delete('pending-requests/{id}', 'Api\PendingRequests\PendingRequestController@destroy');

    // Financial Reports
    Route::get('financial-reports/overview', 'Api\Reports\FinancialReportController@overview');
    Route::get('financial-reports/monthly-profit', 'Api\Reports\FinancialReportController@monthlyProfit');
    Route::get('financial-reports/statistics', 'Api\Reports\FinancialReportController@statistics');
    Route::get('financial-reports/fund-movement', 'Api\Reports\FinancialReportController@fundMovement');
    Route::get('financial-reports/student-debts', 'Api\Reports\FinancialReportController@studentDebts');

    // Accounting - Expenses & Salaries (read-only for all authenticated roles)
    Route::get('expenses', 'Api\Accounting\ExpenseController@index');
    Route::get('expenses/{expense}', 'Api\Accounting\ExpenseController@show');
    Route::get('expense-categories', 'Api\Accounting\ExpenseCategoryController@index');
    Route::get('salaries', 'Api\Accounting\SalaryController@index');
    Route::get('salaries/employees', 'Api\Accounting\SalaryController@employees');
    Route::get('salaries/{salary}', 'Api\Accounting\SalaryController@show');
    Route::get('teachers/{teacher}/salary-plan', 'Api\Teachers\TeacherSalaryPlanController@show');
    Route::get('teacher-work-hours', 'Api\Teachers\TeacherWorkHourController@index');
    Route::get('accounting/summary', 'Api\Accounting\AccountingReportController@summary');
    Route::get('accounting/profit-loss', 'Api\Accounting\AccountingReportController@profitLoss');
    Route::get('accounting/upcoming-salaries', 'Api\Accounting\AccountingReportController@upcomingSalaries');

    // Teacher self-service: own salary & payment history
    Route::get('teacher/salaries', 'Api\Teachers\TeacherSalaryController@index');

// Nationalities API
Route::get('nationalities', 'Api\NationalityController@index');
Route::get('nationalities/{id}', 'Api\NationalityController@show');

// Genders API
Route::get('genders', 'Api\GenderController@index');
Route::get('genders/{id}', 'Api\GenderController@show');

    // Blood Types API
    Route::get('blood-types', 'Api\BloodTypeController@index');
    Route::get('blood-types/{id}', 'Api\BloodTypeController@show');

    // Student Exams - Read (available to students and parents)
    Route::get('student/exams', 'Api\Students\StudentExamController@index')->name('api.web.student.exams.index');
    Route::get('student/exams/{quizzeId}', 'Api\Students\StudentExamController@show')->name('api.web.student.exams.show');
    Route::get('student/exams/{quizzeId}/result', 'Api\Students\StudentExamController@result')->name('api.web.student.exams.result');

    // Student Portal - read-only self-service (schedule, grades, attendance)
    Route::get('student/schedule', 'Api\Students\StudentPortalController@schedule')->name('api.web.student.schedule');
    Route::get('student/grades', 'Api\Students\StudentPortalController@grades')->name('api.web.student.grades');
    Route::get('student/attendance', 'Api\Students\StudentPortalController@attendance')->name('api.web.student.attendance');

    // Student Exams - Write (start, answer, submit)
    Route::post('student/exams/{quizzeId}/start', 'Api\Students\StudentExamController@start')->name('api.web.student.exams.start');
    Route::post('student/exams/{quizzeId}/answer', 'Api\Students\StudentExamController@submitAnswer')->name('api.web.student.exams.answer');
    Route::post('student/exams/{quizzeId}/submit', 'Api\Students\StudentExamController@submit')->name('api.web.student.exams.submit');

    // AI Assistant (admin & teacher, tenant-scoped)
    Route::post('ai/chat', 'Api\Ai\AiAssistantController@chat')->name('api.ai.chat');
    Route::get('ai/questions', 'Api\Ai\AiAssistantController@questions')->name('api.ai.questions');

    // Scheduling - Annual Calendar (read-only, all authenticated roles)
    Route::get('/scheduling/academic-years', 'Api\Scheduling\AcademicYearController@index')->name('api.web.scheduling.academic-years.index');
    Route::get('/scheduling/academic-years/{academicYear}', 'Api\Scheduling\AcademicYearController@show')->name('api.web.scheduling.academic-years.show');
    Route::get('/scheduling/academic-years/{academicYear}/calendar', 'Api\Scheduling\AcademicYearController@calendar')->name('api.web.scheduling.academic-years.calendar');
    Route::get('/scheduling/academic-years/{academicYear}/weeks', 'Api\Scheduling\AcademicYearController@weeks')->name('api.web.scheduling.academic-years.weeks');
    Route::get('/scheduling/academic-years/{academicYear}/terms', 'Api\Scheduling\AcademicTermController@index')->name('api.web.scheduling.academic-terms.index');
    Route::get('/academic-context', 'Api\Scheduling\AcademicTermController@context')->name('api.web.academic-context');
    Route::get('/scheduling/holidays', 'Api\Scheduling\HolidayController@index')->name('api.web.scheduling.holidays.index');
    Route::get('/scheduling/schedule-periods', 'Api\Scheduling\SchedulePeriodController@index')->name('api.web.scheduling.schedule-periods.index');
    Route::get('/scheduling/schedule-periods/{academicYear}', 'Api\Scheduling\SchedulePeriodController@index')->name('api.web.scheduling.schedule-periods.by-year');

    // ============================================
    // TRANSPORTATION & BUS TRACKING
    // Admin routes are permission-gated; driver/parent routes enforce
    // authorization inside their controllers.
    // ============================================

    // --- Driver (mobile) ---
    Route::group(['prefix' => 'driver', 'namespace' => 'Api\Transportation\Driver'], function () {
        Route::get('/home', 'DriverTripController@home');
        Route::get('/trips/today', 'DriverTripController@today');
        Route::get('/trips/{id}', 'DriverTripController@show');
        Route::post('/trips/{id}/start', 'DriverTripController@start');
        Route::post('/trips/{id}/end', 'DriverTripController@end');
        Route::post('/trips/{id}/cancel', 'DriverTripController@cancel');
        Route::post('/trips/{id}/location', 'DriverTripController@location');
        Route::get('/trips/{id}/next-stop', 'DriverTripController@nextStop');
        Route::post('/trips/{id}/students/{tripStudentId}/picked-up', 'DriverTripController@pickedUp');
        Route::post('/trips/{id}/students/{tripStudentId}/dropped-off', 'DriverTripController@droppedOff');
        Route::post('/trips/{id}/emergency', 'DriverTripController@emergency');
    });

    // --- Parent (mobile) ---
    Route::group(['prefix' => 'parent/transportation', 'namespace' => 'Api\Transportation\Parent'], function () {
        Route::get('/children', 'ParentTransportController@children');
        Route::get('/children/{studentId}/active-trip', 'ParentTransportController@activeTrip');
        Route::get('/trips/{tripId}/bus-location', 'ParentTransportController@busLocation');
    });

    // --- Admin: live tracking & geocoding (view permissions) ---
    Route::group(['middleware' => 'permission:transportation.live_tracking.view'], function () {
        Route::get('/transportation/live', 'Api\Transportation\Admin\LiveTrackingController@index');
        Route::get('/transportation/live/{id}', 'Api\Transportation\Admin\LiveTrackingController@show');
    });
    Route::get('/transportation/geocode', 'Api\Transportation\Admin\GeocodeController@geocode');
    Route::get('/transportation/reverse-geocode', 'Api\Transportation\Admin\GeocodeController@reverse');

    // --- Admin: overview & read endpoints ---
    Route::group(['middleware' => 'permission:transportation.view'], function () {
        Route::get('/transportation/overview', 'Api\Transportation\Admin\DashboardController@overview');
        Route::get('/transportation/trips/today', 'Api\Transportation\Admin\TripController@today');
        Route::get('/transportation/buses', 'Api\Transportation\Admin\BusController@index');
        Route::get('/transportation/buses/{id}', 'Api\Transportation\Admin\BusController@show');
        Route::get('/transportation/drivers', 'Api\Transportation\Admin\DriverController@index');
        Route::get('/transportation/drivers/{id}', 'Api\Transportation\Admin\DriverController@show');
        Route::get('/transportation/routes', 'Api\Transportation\Admin\RouteController@index');
        Route::get('/transportation/routes/{id}', 'Api\Transportation\Admin\RouteController@show');
        Route::get('/transportation/students', 'Api\Transportation\Admin\StudentTransportController@index');
        Route::get('/transportation/students/available', 'Api\Transportation\Admin\StudentTransportController@availableStudents');
        Route::get('/transportation/students/{studentId}', 'Api\Transportation\Admin\StudentTransportController@show');
        Route::get('/transportation/trips', 'Api\Transportation\Admin\TripController@index');
        Route::get('/transportation/trips/history', 'Api\Transportation\Admin\TripController@history');
        Route::get('/transportation/trips/{id}', 'Api\Transportation\Admin\TripController@show');
    });

    // --- Admin: write endpoints ---
    Route::group(['middleware' => 'permission:transportation.buses.manage'], function () {
        Route::post('/transportation/buses', 'Api\Transportation\Admin\BusController@store');
        Route::put('/transportation/buses/{id}', 'Api\Transportation\Admin\BusController@update');
        Route::delete('/transportation/buses/{id}', 'Api\Transportation\Admin\BusController@destroy');
    });
    Route::group(['middleware' => 'permission:transportation.drivers.manage'], function () {
        Route::post('/transportation/drivers', 'Api\Transportation\Admin\DriverController@store');
        Route::put('/transportation/drivers/{id}', 'Api\Transportation\Admin\DriverController@update');
    });
    Route::group(['middleware' => 'permission:transportation.routes.manage'], function () {
        Route::post('/transportation/routes', 'Api\Transportation\Admin\RouteController@store');
        Route::put('/transportation/routes/{id}', 'Api\Transportation\Admin\RouteController@update');
        Route::delete('/transportation/routes/{id}', 'Api\Transportation\Admin\RouteController@destroy');
        Route::put('/transportation/routes/{id}/stops', 'Api\Transportation\Admin\RouteController@syncStops');
    });
    Route::group(['middleware' => 'permission:transportation.students.manage'], function () {
        Route::put('/transportation/students/{studentId}/transport-profile', 'Api\Transportation\Admin\StudentTransportController@upsert');
    });
    Route::group(['middleware' => 'permission:transportation.trips.manage'], function () {
        Route::post('/transportation/trips', 'Api\Transportation\Admin\TripController@store');
        Route::put('/transportation/trips/{id}', 'Api\Transportation\Admin\TripController@update');
        Route::post('/transportation/trips/{id}/cancel', 'Api\Transportation\Admin\TripController@cancel');
    });
    Route::group(['middleware' => 'permission:transportation.settings.manage'], function () {
        Route::get('/transportation/settings', 'Api\Transportation\Admin\SettingsController@show');
        Route::put('/transportation/settings', 'Api\Transportation\Admin\SettingsController@update');
    });
});

// ============================================
// WRITE ROUTES - Admin only (auth:api)
// POST, PUT, PATCH, DELETE operations
// ============================================
Route::group(['middleware' => ['auth:api', 'permission']], function () {

    // Roles & Permissions — school scope
    Route::group(['middleware' => 'permission:roles.manage'], function () {
        Route::get('roles', 'Api\RoleController@index');
        Route::post('roles', 'Api\RoleController@store');
        Route::put('roles/{role}', 'Api\RoleController@update');
        Route::delete('roles/{role}', 'Api\RoleController@destroy');
        Route::get('roles/permissions', 'Api\RoleController@permissions');
        Route::get('roles/users', 'Api\RoleController@users');
        Route::post('roles/users', 'Api\RoleController@storeUser');
        Route::put('roles/users/{user}/roles', 'Api\RoleController@assignRoles');
    });

    // Classrooms - Write
    Route::post('Classrooms', 'Api\Classrooms\ClassroomController@store')->name('api.web.Classrooms.store');
    Route::put('Classrooms/{Classroom}', 'Api\Classrooms\ClassroomController@update')->name('api.web.Classrooms.update');
    Route::delete('Classrooms/{Classroom}', 'Api\Classrooms\ClassroomController@destroy')->name('api.web.Classrooms.destroy');
    Route::post('delete_all', 'Api\Classrooms\ClassroomController@delete_all')->name('api.web.delete_all');
    Route::post('Filter_Classes', 'Api\Classrooms\ClassroomController@Filter_Classes')->name('api.web.Filter_Classes');
    Route::post('classrooms', 'Api\Classrooms\ClassroomController@store');
    Route::put('classrooms/{classroom}', 'Api\Classrooms\ClassroomController@update');
    Route::delete('classrooms/{classroom}', 'Api\Classrooms\ClassroomController@destroy');
    Route::post('classrooms/delete_all', 'Api\Classrooms\ClassroomController@delete_all');
    Route::post('classrooms/filter', 'Api\Classrooms\ClassroomController@Filter_Classes');

    // Sections - Write
    Route::post('Sections', 'Api\Sections\SectionController@store')->name('api.web.Sections.store');
    Route::put('Sections/{Section}', 'Api\Sections\SectionController@update')->name('api.web.Sections.update');
    Route::delete('Sections/{Section}', 'Api\Sections\SectionController@destroy')->name('api.web.Sections.destroy');
    Route::post('sections', 'Api\Sections\SectionController@store');
    Route::put('sections/{section}', 'Api\Sections\SectionController@update');
    Route::delete('sections/{section}', 'Api\Sections\SectionController@destroy');

    // Teachers - Write
    Route::post('Teachers', 'Api\Teachers\TeacherController@store')->name('api.web.Teachers.store');
    Route::put('Teachers/{Teacher}', 'Api\Teachers\TeacherController@update')->name('api.web.Teachers.update');
    Route::delete('Teachers/{Teacher}', 'Api\Teachers\TeacherController@destroy')->name('api.web.Teachers.destroy');
    Route::post('teachers', 'Api\Teachers\TeacherController@store');
    Route::put('teachers/{teacher}', 'Api\Teachers\TeacherController@update');
    Route::delete('teachers/{teacher}', 'Api\Teachers\TeacherController@destroy');

    // Students - Write
    Route::post('Students', 'Api\Students\StudentController@store')->name('api.web.Students.store');
    Route::put('Students/{Student}', 'Api\Students\StudentController@update')->name('api.web.Students.update');
    Route::delete('Students/{Student}', 'Api\Students\StudentController@destroy')->name('api.web.Students.destroy');
    Route::post('students', 'Api\Students\StudentController@store')->middleware('academic.context');
    Route::put('students/{student}', 'Api\Students\StudentController@update');
    Route::delete('students/{student}', 'Api\Students\StudentController@destroy');
    Route::post('Upload_attachment', 'Api\Students\StudentController@Upload_attachment')->name('api.web.Upload_attachment');
    Route::post('Delete_attachment', 'Api\Students\StudentController@Delete_attachment')->name('api.web.Delete_attachment');
    Route::post('students/upload-attachment', 'Api\Students\StudentController@Upload_attachment');
    Route::post('students/delete-attachment', 'Api\Students\StudentController@Delete_attachment');

    // Online Classes - Write
    Route::post('online_classes', 'Api\Students\OnlineClasseController@store')->name('api.web.online_classes.store');
    Route::put('online_classes/{online_classes}', 'Api\Students\OnlineClasseController@update')->name('api.web.online_classes.update');
    Route::delete('online_classes/{online_classes}', 'Api\Students\OnlineClasseController@destroy')->name('api.web.online_classes.destroy');
    Route::post('indirect_admin', 'Api\Students\OnlineClasseController@storeIndirect')->name('api.web.indirect.store.admin');
    Route::post('online-classes', 'Api\Students\OnlineClasseController@store');
    Route::put('online-classes/{online_class}', 'Api\Students\OnlineClasseController@update');
    Route::delete('online-classes/{online_class}', 'Api\Students\OnlineClasseController@destroy');
    Route::post('online-classes/indirect-admin', 'Api\Students\OnlineClasseController@storeIndirect');

    // Graduated - Write
    Route::post('Graduated', 'Api\Students\GraduatedController@store')->name('api.web.Graduated.store');
    Route::put('Graduated/{Graduated}', 'Api\Students\GraduatedController@update')->name('api.web.Graduated.update');
    Route::delete('Graduated/{Graduated}', 'Api\Students\GraduatedController@destroy')->name('api.web.Graduated.destroy');
    Route::post('graduated', 'Api\Students\GraduatedController@store');
    Route::put('graduated/{graduated}', 'Api\Students\GraduatedController@update');
    Route::delete('graduated/{graduated}', 'Api\Students\GraduatedController@destroy');

    // Promotion - Write
    Route::post('Promotion', 'Api\Students\PromotionController@store')->name('api.web.Promotion.store');
    Route::put('Promotion/{Promotion}', 'Api\Students\PromotionController@update')->name('api.web.Promotion.update');
    Route::delete('Promotion/{Promotion}', 'Api\Students\PromotionController@destroy')->name('api.web.Promotion.destroy');
    Route::post('promotion', 'Api\Students\PromotionController@store')->middleware('academic.context');
    Route::put('promotion/{promotion}', 'Api\Students\PromotionController@update');
    Route::delete('promotion/{promotion}', 'Api\Students\PromotionController@destroy');
    Route::post('promotion/bulk', 'Api\Students\PromotionController@bulkPromote')->middleware('academic.context');

    // Fees Invoices - Write
    Route::post('Fees_Invoices', 'Api\Students\FeesInvoicesController@store')->name('api.web.Fees_Invoices.store');
    Route::put('Fees_Invoices/{Fees_Invoices}', 'Api\Students\FeesInvoicesController@update')->name('api.web.Fees_Invoices.update');
    Route::delete('Fees_Invoices/{Fees_Invoices}', 'Api\Students\FeesInvoicesController@destroy')->name('api.web.Fees_Invoices.destroy');
    Route::post('fees-invoices', 'Api\Students\FeesInvoicesController@store');
    Route::put('fees-invoices/{fees_invoice}', 'Api\Students\FeesInvoicesController@update');
    Route::delete('fees-invoices/{fees_invoice}', 'Api\Students\FeesInvoicesController@destroy');

    // Fees - Write
    Route::post('Fees', 'Api\Students\FeesController@store')->name('api.web.Fees.store');
    Route::put('Fees/{Fees}', 'Api\Students\FeesController@update')->name('api.web.Fees.update');
    Route::delete('Fees/{Fees}', 'Api\Students\FeesController@destroy')->name('api.web.Fees.destroy');
    Route::post('fees', 'Api\Students\FeesController@store');
    Route::put('fees/{fee}', 'Api\Students\FeesController@update');
    Route::delete('fees/{fee}', 'Api\Students\FeesController@destroy');

    // Receipt Students - Write
    Route::post('receipt_students', 'Api\Students\ReceiptStudentsController@store')->name('api.web.receipt_students.store');
    Route::put('receipt_students/{receipt_students}', 'Api\Students\ReceiptStudentsController@update')->name('api.web.receipt_students.update');
    Route::delete('receipt_students/{receipt_students}', 'Api\Students\ReceiptStudentsController@destroy')->name('api.web.receipt_students.destroy');
    Route::post('receipt-students', 'Api\Students\ReceiptStudentsController@store');
    Route::put('receipt-students/{receipt_student}', 'Api\Students\ReceiptStudentsController@update');
    Route::delete('receipt-students/{receipt_student}', 'Api\Students\ReceiptStudentsController@destroy');

    // Processing Fee - Write
    Route::post('ProcessingFee', 'Api\Students\ProcessingFeeController@store')->name('api.web.ProcessingFee.store');
    Route::put('ProcessingFee/{ProcessingFee}', 'Api\Students\ProcessingFeeController@update')->name('api.web.ProcessingFee.update');
    Route::delete('ProcessingFee/{ProcessingFee}', 'Api\Students\ProcessingFeeController@destroy')->name('api.web.ProcessingFee.destroy');
    Route::post('processing-fee', 'Api\Students\ProcessingFeeController@store');
    Route::put('processing-fee/{processing_fee}', 'Api\Students\ProcessingFeeController@update');
    Route::delete('processing-fee/{processing_fee}', 'Api\Students\ProcessingFeeController@destroy');

    // Payment Students - Write
    Route::post('Payment_students', 'Api\Students\PaymentController@store')->name('api.web.Payment_students.store');
    Route::put('Payment_students/{Payment_students}', 'Api\Students\PaymentController@update')->name('api.web.Payment_students.update');
    Route::delete('Payment_students/{Payment_students}', 'Api\Students\PaymentController@destroy')->name('api.web.Payment_students.destroy');
    Route::post('payment-students', 'Api\Students\PaymentController@store');
    Route::put('payment-students/{payment_student}', 'Api\Students\PaymentController@update');
    Route::delete('payment-students/{payment_student}', 'Api\Students\PaymentController@destroy');

    // Accounting - Expenses & Salary Categories - Write
    Route::group(['middleware' => 'permission:expenses.manage'], function () {
        Route::post('expenses', 'Api\Accounting\ExpenseController@store');
        Route::put('expenses/{expense}', 'Api\Accounting\ExpenseController@update');
        Route::delete('expenses/{expense}', 'Api\Accounting\ExpenseController@destroy');
        Route::post('expense-categories', 'Api\Accounting\ExpenseCategoryController@store');
        Route::put('expense-categories/{category}', 'Api\Accounting\ExpenseCategoryController@update');
        Route::delete('expense-categories/{category}', 'Api\Accounting\ExpenseCategoryController@destroy');
    });

    // Accounting - Salaries - Write
    Route::group(['middleware' => 'permission:salaries.manage'], function () {
        Route::post('salaries', 'Api\Accounting\SalaryController@store');
        Route::post('salaries/generate', 'Api\Accounting\SalaryController@generate');
        Route::post('salaries/{salary}/pay', 'Api\Accounting\SalaryController@pay');
        Route::put('salaries/{salary}', 'Api\Accounting\SalaryController@update');
        Route::delete('salaries/{salary}', 'Api\Accounting\SalaryController@destroy');
        Route::put('salaries/employees/{type}/{id}', 'Api\Accounting\SalaryController@updateBaseSalary');
        Route::put('teachers/{teacher}/salary-plan', 'Api\Teachers\TeacherSalaryPlanController@update');
    });

    // Teacher work hours - Write (hourly-contract payroll input)
    Route::group(['middleware' => 'permission:work_hours.manage'], function () {
        Route::post('teacher-work-hours', 'Api\Teachers\TeacherWorkHourController@store');
        Route::delete('teacher-work-hours/{workHour}', 'Api\Teachers\TeacherWorkHourController@destroy');
    });

// Library - Write
    Route::post('library', 'Api\Students\LibraryController@store')->name('api.web.library.store');
    Route::put('library/{library}', 'Api\Students\LibraryController@update')->name('api.web.library.update');
    Route::delete('library/{library}', 'Api\Students\LibraryController@destroy')->name('api.web.library.destroy');

    // Subjects - Write
    Route::post('subjects', 'Api\Subjects\SubjectController@store')->name('api.web.subjects.store');
    Route::put('subjects/{subject}', 'Api\Subjects\SubjectController@update')->name('api.web.subjects.update');
    Route::delete('subjects/{subject}', 'Api\Subjects\SubjectController@destroy')->name('api.web.subjects.destroy');

    // Weekly Programs (timetable) - Write
    Route::post('weekly-programs', 'Api\WeeklyPrograms\WeeklyProgramController@store')->name('api.web.weekly-programs.store');
    Route::put('weekly-programs/{weeklyProgram}', 'Api\WeeklyPrograms\WeeklyProgramController@update')->name('api.web.weekly-programs.update');
    Route::delete('weekly-programs/{weeklyProgram}', 'Api\WeeklyPrograms\WeeklyProgramController@destroy')->name('api.web.weekly-programs.destroy');
    Route::post('weekly-programs/bulk-delete', 'Api\WeeklyPrograms\WeeklyProgramController@bulkDestroy')->name('api.web.weekly-programs.bulk-delete');
    Route::post('weekly-programs/reset', 'Api\WeeklyPrograms\WeeklyProgramController@resetSection')->name('api.web.weekly-programs.reset');
    Route::post('weekly-programs/notify', 'Api\WeeklyPrograms\WeeklyProgramController@notify')->name('api.web.weekly-programs.notify');

    // Timetable Scenarios
    Route::get('timetable-scenarios', 'Api\WeeklyPrograms\ScenarioController@index')->name('api.web.timetable-scenarios.index');
    Route::post('timetable-scenarios', 'Api\WeeklyPrograms\ScenarioController@store')->name('api.web.timetable-scenarios.store');
    Route::get('timetable-scenarios/{id}', 'Api\WeeklyPrograms\ScenarioController@show')->name('api.web.timetable-scenarios.show');
    Route::put('timetable-scenarios/{id}', 'Api\WeeklyPrograms\ScenarioController@update')->name('api.web.timetable-scenarios.update');
    Route::delete('timetable-scenarios/{id}', 'Api\WeeklyPrograms\ScenarioController@destroy')->name('api.web.timetable-scenarios.destroy');
    Route::post('timetable-scenarios/{id}/publish', 'Api\WeeklyPrograms\ScenarioController@publish')->name('api.web.timetable-scenarios.publish');
    Route::post('timetable-scenarios/{id}/unpublish', 'Api\WeeklyPrograms\ScenarioController@unpublish')->name('api.web.timetable-scenarios.unpublish');
    Route::post('timetable-scenarios/{id}/duplicate', 'Api\WeeklyPrograms\ScenarioController@duplicate')->name('api.web.timetable-scenarios.duplicate');
    Route::post('timetable-scenarios/optimize', 'Api\WeeklyPrograms\ScenarioController@optimize')->name('api.web.timetable-scenarios.optimize');
    Route::post('timetable-scenarios/validate', 'Api\WeeklyPrograms\ScenarioController@validateTimetable')->name('api.web.timetable-scenarios.validate');
    Route::post('timetable-scenarios/compare', 'Api\WeeklyPrograms\ScenarioController@compare')->name('api.web.timetable-scenarios.compare');

    // Subject Weekly Requirements
    Route::get('subject-requirements', 'Api\WeeklyPrograms\ScenarioController@requirements')->name('api.web.subject-requirements.index');
    Route::post('subject-requirements', 'Api\WeeklyPrograms\ScenarioController@storeRequirement')->name('api.web.subject-requirements.store');
    Route::delete('subject-requirements/{id}', 'Api\WeeklyPrograms\ScenarioController@destroyRequirement')->name('api.web.subject-requirements.destroy');

    // Timetable scenarios - alternatives & recalculate
    Route::post('timetable-scenarios/alternatives', 'Api\WeeklyPrograms\ScenarioController@generateAlternatives')->name('api.web.timetable-scenarios.alternatives');
    Route::post('timetable-scenarios/{id}/recalculate', 'Api\WeeklyPrograms\ScenarioController@recalculate')->name('api.web.timetable-scenarios.recalculate');

    // Teacher Availability & Preferences - Write
    Route::post('teacher-schedules', 'Api\WeeklyPrograms\TeacherAvailabilityController@store')->name('api.web.teacher-schedules.store');
    Route::post('teacher-schedules/bulk', 'Api\WeeklyPrograms\TeacherAvailabilityController@bulkStore')->name('api.web.teacher-schedules.bulk');
    Route::delete('teacher-schedules/{id}', 'Api\WeeklyPrograms\TeacherAvailabilityController@destroy')->name('api.web.teacher-schedules.destroy');
    Route::post('teacher-preferences', 'Api\WeeklyPrograms\TeacherAvailabilityController@savePreferences')->name('api.web.teacher-preferences.save');

    // Section-Subject-Teacher assignments
    Route::get('section-subject-teachers', 'Api\WeeklyPrograms\SectionSubjectTeacherController@index')->name('api.web.section-subject-teachers.index');
    Route::post('section-subject-teachers', 'Api\WeeklyPrograms\SectionSubjectTeacherController@store')->name('api.web.section-subject-teachers.store');
    Route::get('section-subject-teachers/{id}', 'Api\WeeklyPrograms\SectionSubjectTeacherController@show')->name('api.web.section-subject-teachers.show');
    Route::delete('section-subject-teachers/{id}', 'Api\WeeklyPrograms\SectionSubjectTeacherController@destroy')->name('api.web.section-subject-teachers.destroy');

    // School Schedule Settings - Write
    Route::post('school-schedule-settings', 'Api\WeeklyPrograms\SchoolScheduleSettingController@update')->name('api.web.school-schedule-settings.update');

    // Quizzes - Write
    Route::post('Quizzes', 'Api\Quizzes\QuizzController@store')->name('api.web.Quizzes.store');
    Route::put('Quizzes/{Quizzes}', 'Api\Quizzes\QuizzController@update')->name('api.web.Quizzes.update');
    Route::delete('Quizzes/{Quizzes}', 'Api\Quizzes\QuizzController@destroy')->name('api.web.Quizzes.destroy');
    Route::post('quizzes', 'Api\Quizzes\QuizzController@store')->middleware('academic.context');
    Route::put('quizzes/{quizz}', 'Api\Quizzes\QuizzController@update')->middleware('academic.context');
    Route::delete('quizzes/{quizz}', 'Api\Quizzes\QuizzController@destroy')->middleware('academic.context');

    // Questions - Write
    Route::post('questions', 'Api\Questions\QuestionController@store')->name('api.web.questions.store');
    Route::put('questions/{question}', 'Api\Questions\QuestionController@update')->name('api.web.questions.update');
    Route::delete('questions/{question}', 'Api\Questions\QuestionController@destroy')->name('api.web.questions.destroy');
    Route::post('questions/bulk', 'Api\Questions\QuestionController@bulkStore')->name('api.web.questions.bulk');

    // Settings - Write
    Route::post('settings', 'Api\SettingController@store')->name('api.web.settings.store');
    Route::put('settings/{setting}', 'Api\SettingController@update')->name('api.web.settings.update');
    Route::delete('settings/{setting}', 'Api\SettingController@destroy')->name('api.web.settings.destroy');
    Route::post('settings/school', 'Api\SettingController@updateSchool')->name('api.web.settings.school');

    // Notifications - Write
    Route::post('Notifications', 'Api\Notifications\NotificationController@store')->name('api.web.Notifications.store');
    Route::put('Notifications/{Notification}', 'Api\Notifications\NotificationController@update')->name('api.web.Notifications.update');
    Route::delete('Notifications/{Notification}', 'Api\Notifications\NotificationController@destroy')->name('api.web.Notifications.destroy');
    Route::post('notifications', 'Api\Notifications\NotificationController@store');
    Route::put('notifications/{notification}', 'Api\Notifications\NotificationController@update');
    Route::delete('notifications/{notification}', 'Api\Notifications\NotificationController@destroy');

    // Parents - Write
    Route::post('parents', 'Api\Parents\ParentController@store');
    Route::put('parents/{parent}', 'Api\Parents\ParentController@update');
    Route::delete('parents/{parent}', 'Api\Parents\ParentController@destroy');

    // Specializations - Write
    Route::post('specializations', 'Api\Specializations\SpecializationController@store');
    Route::put('specializations/{specialization}', 'Api\Specializations\SpecializationController@update');
    Route::delete('specializations/{specialization}', 'Api\Specializations\SpecializationController@destroy');

    // Data Import
    Route::get('imports/entities', 'Api\Imports\DataImportController@entities')->name('api.web.imports.entities');
    Route::get('imports', 'Api\Imports\DataImportController@index')->name('api.web.imports.index');
    Route::get('imports/{import}', 'Api\Imports\DataImportController@show')->name('api.web.imports.show');
    Route::post('imports/preview', 'Api\Imports\DataImportController@preview')->name('api.web.imports.preview');
    Route::post('imports/run', 'Api\Imports\DataImportController@run')->name('api.web.imports.run');

    // Installment Plans - Write
    Route::post('installment-plans', 'Api\Students\InstallmentPlanController@store')->name('api.web.installment-plans.store');
    Route::put('installment-plans/{installmentPlan}', 'Api\Students\InstallmentPlanController@update')->name('api.web.installment-plans.update');
    Route::delete('installment-plans/{installmentPlan}', 'Api\Students\InstallmentPlanController@destroy')->name('api.web.installment-plans.destroy');
    Route::post('installment-plans/{installmentPlan}/generate', 'Api\Students\InstallmentPlanController@generate')->name('api.web.installment-plans.generate');
    Route::post('InstallmentPlans', 'Api\Students\InstallmentPlanController@store')->name('api.web.InstallmentPlans.store');
    Route::put('InstallmentPlans/{installmentPlan}', 'Api\Students\InstallmentPlanController@update')->name('api.web.InstallmentPlans.update');
    Route::delete('InstallmentPlans/{installmentPlan}', 'Api\Students\InstallmentPlanController@destroy')->name('api.web.InstallmentPlans.destroy');
    Route::post('InstallmentPlans/{installmentPlan}/generate', 'Api\Students\InstallmentPlanController@generate')->name('api.web.InstallmentPlans.generate');

    // Grades - Write
    Route::post('Grades', 'Api\Grades\GradeController@store')->name('api.web.Grades.store');
    Route::put('Grades/{Grade}', 'Api\Grades\GradeController@update')->name('api.web.Grades.update');
    Route::delete('Grades/{Grade}', 'Api\Grades\GradeController@destroy')->name('api.web.Grades.destroy');
    Route::post('grades', 'Api\Grades\GradeController@store')->middleware('academic.context');
    Route::put('grades/{grade}', 'Api\Grades\GradeController@update');
    Route::delete('grades/{grade}', 'Api\Grades\GradeController@destroy');

    // Schools - Super admin only
    Route::group(['middleware' => 'is.super.admin'], function () {
        Route::get('Schools', 'Api\Schools\SchoolController@index')->name('api.web.Schools.index');
        Route::get('schools', 'Api\Schools\SchoolController@index')->name('api.web.schools.index');
        Route::post('Schools', 'Api\Schools\SchoolController@store')->name('api.web.Schools.store');
        Route::post('schools', 'Api\Schools\SchoolController@store')->name('api.web.schools.store');

        Route::post('School', 'Api\Schools\SchoolController@store');
        Route::post('school', 'Api\Schools\SchoolController@store');
        Route::get('School/{school}', 'Api\Schools\SchoolController@show')->name('api.web.School.show');
        Route::get('school/{school}', 'Api\Schools\SchoolController@show');
        Route::put('Schools/{school}', 'Api\Schools\SchoolController@update')->name('api.web.Schools.update');
        Route::put('schools/{school}', 'Api\Schools\SchoolController@update');
        Route::post('School/{school}/suspend', 'Api\Schools\SchoolController@suspend')->name('api.web.School.suspend');
        Route::post('school/{school}/suspend', 'Api\Schools\SchoolController@suspend');
        Route::post('School/{school}/activate', 'Api\Schools\SchoolController@activate')->name('api.web.School.activate');
        Route::post('school/{school}/activate', 'Api\Schools\SchoolController@activate');
        Route::post('School/{school}/archive', 'Api\Schools\SchoolController@archive')->name('api.web.School.archive');
        Route::post('school/{school}/archive', 'Api\Schools\SchoolController@archive');
        Route::post('School/{school}/restore', 'Api\Schools\SchoolController@restore')->name('api.web.School.restore');
        Route::post('school/{school}/restore', 'Api\Schools\SchoolController@restore');
        Route::delete('Schools/{school}', 'Api\Schools\SchoolController@destroy')->name('api.web.Schools.destroy');
        Route::delete('schools/{school}', 'Api\Schools\SchoolController@destroy');
        Route::post('schools/{school}/branding', 'Api\Schools\SchoolController@updateBranding')
            ->name('api.web.Schools.branding');
    });

    // Platform (super admin) - public settings mirror for any authenticated role
    Route::get('Platform/PublicSettings', 'Api\Platform\PlatformSettingController@publicSettings')
        ->name('api.web.PlatformSettings.public');
    Route::get('platform/public-settings', 'Api\Platform\PlatformSettingController@publicSettings')
        ->name('api.web.platformSettings.public');

    // Platform (super admin) - full management layer
    Route::group(['middleware' => 'is.super.admin', 'prefix' => 'platform'], function () {
        Route::get('Dashboard', 'Api\Platform\DashboardController@stats')->name('api.web.PlatformDashboard.stats');
        Route::get('dashboard', 'Api\Platform\DashboardController@stats');

        // Admins
        Route::get('admins', 'Api\Platform\PlatformAdminController@index');
        Route::post('admins', 'Api\Platform\PlatformAdminController@store');
        Route::put('admins/{admin}', 'Api\Platform\PlatformAdminController@update');
        Route::delete('admins/{admin}', 'Api\Platform\PlatformAdminController@destroy');

        // Plans
        Route::get('plans', 'Api\Platform\PlanController@index');
        Route::post('plans', 'Api\Platform\PlanController@store');
        Route::put('plans/{plan}', 'Api\Platform\PlanController@update');
        Route::delete('plans/{plan}', 'Api\Platform\PlanController@destroy');

        // Subscriptions
        Route::get('subscriptions', 'Api\Platform\SubscriptionController@index');
        Route::post('subscriptions', 'Api\Platform\SubscriptionController@store');
        Route::put('subscriptions/{subscription}', 'Api\Platform\SubscriptionController@update');
        Route::delete('subscriptions/{subscription}', 'Api\Platform\SubscriptionController@destroy');

        // Branches
        Route::get('branches', 'Api\Platform\BranchController@index');
        Route::post('branches', 'Api\Platform\BranchController@store');
        Route::put('branches/{branch}', 'Api\Platform\BranchController@update');
        Route::delete('branches/{branch}', 'Api\Platform\BranchController@destroy');

        // Audit logs
        Route::get('audit-logs', 'Api\Platform\AuditLogController@index');

        // Settings
        Route::get('settings', 'Api\Platform\PlatformSettingController@index');
        Route::post('settings', 'Api\Platform\PlatformSettingController@store');

        // Announcements
        Route::get('announcements', 'Api\Platform\AnnouncementController@index');
        Route::post('announcements', 'Api\Platform\AnnouncementController@store');
        Route::put('announcements/{announcement}', 'Api\Platform\AnnouncementController@update');
        Route::post('announcements/{announcement}/broadcast', 'Api\Platform\AnnouncementController@broadcast');
        Route::delete('announcements/{announcement}', 'Api\Platform\AnnouncementController@destroy');

        // Integrations
        Route::get('integrations', 'Api\Platform\IntegrationController@index');
        Route::post('integrations', 'Api\Platform\IntegrationController@store');
        Route::put('integrations/{integration}', 'Api\Platform\IntegrationController@update');
        Route::post('integrations/{integration}/toggle', 'Api\Platform\IntegrationController@toggle');
        Route::delete('integrations/{integration}', 'Api\Platform\IntegrationController@destroy');

        // Permission templates
        Route::get('permission-templates', 'Api\Platform\PermissionTemplateController@index');
        Route::post('permission-templates', 'Api\Platform\PermissionTemplateController@store');
        Route::put('permission-templates/{permissionTemplate}', 'Api\Platform\PermissionTemplateController@update');
        Route::delete('permission-templates/{permissionTemplate}', 'Api\Platform\PermissionTemplateController@destroy');

        // Security policies
        Route::get('security-policies', 'Api\Platform\SecurityPolicyController@index');
        Route::post('security-policies', 'Api\Platform\SecurityPolicyController@store');
        Route::put('security-policies/{securityPolicy}', 'Api\Platform\SecurityPolicyController@update');
        Route::post('security-policies/{securityPolicy}/toggle', 'Api\Platform\SecurityPolicyController@toggle');
        Route::delete('security-policies/{securityPolicy}', 'Api\Platform\SecurityPolicyController@destroy');
    });

    // Scheduling - Annual Calendar (admin writes)
    Route::group(['middleware' => 'permission:academic_years.manage'], function () {
        Route::post('/scheduling/academic-years', 'Api\Scheduling\AcademicYearController@store')->name('api.web.scheduling.academic-years.store');
        Route::put('/scheduling/academic-years/{academicYear}', 'Api\Scheduling\AcademicYearController@update')->name('api.web.scheduling.academic-years.update');
        Route::delete('/scheduling/academic-years/{academicYear}', 'Api\Scheduling\AcademicYearController@destroy')->name('api.web.scheduling.academic-years.destroy');
        Route::post('/scheduling/academic-years/{academicYear}/set-active', 'Api\Scheduling\AcademicYearController@setActive')->name('api.web.scheduling.academic-years.set-active');
        Route::post('/scheduling/academic-terms/{academicTerm}/set-current', 'Api\Scheduling\AcademicTermController@setCurrent')->name('api.web.scheduling.academic-terms.set-current');
        Route::put('/scheduling/schedule-periods/{academicYear}', 'Api\Scheduling\SchedulePeriodController@update')->name('api.web.scheduling.schedule-periods.update');
    });

    Route::group(['middleware' => 'permission:holidays.manage'], function () {
        Route::post('/scheduling/holidays', 'Api\Scheduling\HolidayController@store')->name('api.web.scheduling.holidays.store');
        Route::put('/scheduling/holidays/{holiday}', 'Api\Scheduling\HolidayController@update')->name('api.web.scheduling.holidays.update');
        Route::delete('/scheduling/holidays/{holiday}', 'Api\Scheduling\HolidayController@destroy')->name('api.web.scheduling.holidays.destroy');
    });
});

// API Routes with authentication and localization (legacy - kept for compatibility)
Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath', 'auth:api']
    ],
    function () {
    // All API routes moved to direct mobile-compatible routes above
    // This section kept for any future localized-specific endpoints
    });
