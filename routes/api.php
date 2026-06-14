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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// API Authentication Routes
Route::group(['namespace' => 'Api\Auth'], function () {
    // Unified login endpoint - handles all user types (admin, teacher, student, parent)
    Route::post('/login', 'LoginController@login')->name('api.login');
    
    // Auth prefixed endpoints for mobile app compatibility
    Route::post('/auth/login', 'LoginController@login')->name('api.auth.login');
    
    // Legacy login endpoint for backward compatibility
    Route::post('/login/{type}', 'LoginController@loginWithType')->name('api.login.legacy');
    
    Route::post('/logout', 'LoginController@logout')->name('api.logout')->middleware('auth:api');
    Route::post('/refresh', 'LoginController@refresh')->name('api.refresh')->middleware('auth:api');
    Route::post('/me', 'LoginController@me')->name('api.me')->middleware('auth:api');
});

// Simple test routes first
Route::get('/simple-test', function() {
    return response()->json(['message' => 'Simple test working']);
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

// Temporary test route for parent creation without auth
Route::post('/test-parent', 'Api\Parents\ParentController@store');

// Direct API routes for mobile app compatibility (no localization prefix)
Route::group(['middleware' => 'auth:api'], function () {
    // Test endpoint
    Route::get('/test', function() {
        return response()->json(['message' => 'API test working', 'user' => auth()->user()]);
    });
    
    // Dashboard endpoints
    Route::get('/dashboard', 'Api\DashboardController@index');
    Route::get('/dashboard/stats', 'Api\DashboardController@stats');

    /*
    |--------------------------------------------------------------------------
    | Web workflow compatible API routes
    |--------------------------------------------------------------------------
    |
    | These aliases intentionally mirror routes/web.php URI names so mobile/API
    | clients can call the same Laravel Blade workflow endpoints with JSON
    | responses and the same request payload shapes used by the web forms.
    |
    */
    Route::get('/', 'Api\HomeController@publicInfo')->name('api.web.selection');
    Route::apiResource('Grades', 'Api\Grades\GradeController')->names('api.web.Grades');
    Route::apiResource('Classrooms', 'Api\Classrooms\ClassroomController')->names('api.web.Classrooms');
    Route::post('delete_all', 'Api\Classrooms\ClassroomController@delete_all')->name('api.web.delete_all');
    Route::post('Filter_Classes', 'Api\Classrooms\ClassroomController@Filter_Classes')->name('api.web.Filter_Classes');
    Route::apiResource('Sections', 'Api\Sections\SectionController')->names('api.web.Sections');
    Route::get('classes/{id}', 'Api\Sections\SectionController@getclasses')->name('api.web.classes');
    Route::apiResource('Teachers', 'Api\Teachers\TeacherController')->names('api.web.Teachers');
    Route::get('Students/classrooms-by-grade/{id}', 'Api\Students\StudentController@Get_classrooms')->name('api.web.Students.classroomsByGrade');
    Route::get('Students/sections-by-classroom/{id}', 'Api\Students\StudentController@Get_Sections')->name('api.web.Students.sectionsByClassroom');
    Route::get('Students/sections-by-grade/{id}', 'Api\Students\StudentController@sectionsByGrade')->name('api.web.Students.sectionsByGrade');
    Route::apiResource('Students', 'Api\Students\StudentController')->names('api.web.Students');
    Route::apiResource('online_classes', 'Api\Students\OnlineClasseController')->names('api.web.online_classes');
    Route::get('indirect_admin', 'Api\Students\OnlineClasseController@indirectCreate')->name('api.web.indirect.create.admin');
    Route::post('indirect_admin', 'Api\Students\OnlineClasseController@storeIndirect')->name('api.web.indirect.store.admin');
    Route::apiResource('Graduated', 'Api\Students\GraduatedController')->names('api.web.Graduated');
    Route::apiResource('Promotion', 'Api\Students\PromotionController')->names('api.web.Promotion');
    Route::apiResource('Fees_Invoices', 'Api\Students\FeesInvoicesController')->names('api.web.Fees_Invoices');
    Route::apiResource('Fees', 'Api\Students\FeesController')->names('api.web.Fees');
    Route::apiResource('receipt_students', 'Api\Students\ReceiptStudentsController')->names('api.web.receipt_students');
    Route::apiResource('ProcessingFee', 'Api\Students\ProcessingFeeController')->names('api.web.ProcessingFee');
    Route::apiResource('Payment_students', 'Api\Students\PaymentController')->names('api.web.Payment_students');
    Route::apiResource('Attendance', 'Api\Students\AttendanceController')->names('api.web.Attendance');
    Route::get('download_file/{filename}', 'Api\Students\LibraryController@downloadAttachment')->name('api.web.downloadAttachment');
    Route::apiResource('library', 'Api\Students\LibraryController')->names('api.web.library');
    Route::post('Upload_attachment', 'Api\Students\StudentController@Upload_attachment')->name('api.web.Upload_attachment');
    Route::get('Download_attachment/{studentsname}/{filename}', 'Api\Students\StudentController@Download_attachment')->name('api.web.Download_attachment');
    Route::post('Delete_attachment', 'Api\Students\StudentController@Delete_attachment')->name('api.web.Delete_attachment');
    Route::apiResource('subjects', 'Api\Subjects\SubjectController')->names('api.web.subjects');
    Route::apiResource('Quizzes', 'Api\Quizzes\QuizzController')->names('api.web.Quizzes');
    Route::apiResource('questions', 'Api\Questions\QuestionController')->names('api.web.questions');
    Route::apiResource('settings', 'Api\SettingController')->names('api.web.settings');
    Route::post('settings/school', 'Api\SettingController@updateSchool')->name('api.web.settings.school');

    // Grades API
    Route::group(['namespace' => 'Api\Grades'], function () {
        Route::apiResource('grades', 'GradeController');
    });

    // Classrooms API
    Route::group(['namespace' => 'Api\Classrooms'], function () {
        Route::apiResource('classrooms', 'ClassroomController');
        Route::post('classrooms/delete_all', 'ClassroomController@delete_all');
        Route::post('classrooms/filter', 'ClassroomController@Filter_Classes');
    });

    // Sections API
    Route::group(['namespace' => 'Api\Sections'], function () {
        Route::apiResource('sections', 'SectionController');
        Route::get('sections/classes/{id}', 'SectionController@getclasses');
    });

    // Specializations API
    Route::group(['namespace' => 'Api\Specializations'], function () {
        Route::apiResource('specializations', 'SpecializationController');
    });

    // Parents API
    Route::group(['namespace' => 'Api\Parents'], function () {
        Route::apiResource('parents', 'ParentController');
    });

    // Teachers API
    Route::group(['namespace' => 'Api\Teachers'], function () {
        Route::apiResource('teachers', 'TeacherController');
    });

    // Students API
    Route::group(['namespace' => 'Api\Students'], function () {
        Route::get('students/classrooms-by-grade/{id}', 'StudentController@Get_classrooms');
        Route::get('students/sections-by-classroom/{id}', 'StudentController@Get_Sections');
        Route::get('students/sections-by-grade/{id}', 'StudentController@sectionsByGrade');
        Route::apiResource('students', 'StudentController');
        
        // Online Classes
        Route::apiResource('online-classes', 'OnlineClasseController');
        Route::get('online-classes/indirect-admin', 'OnlineClasseController@indirectCreate');
        Route::post('online-classes/indirect-admin', 'OnlineClasseController@storeIndirect');
        
        // Graduated Students
        Route::apiResource('graduated', 'GraduatedController');
        
        // Student Promotion
        Route::apiResource('promotion', 'PromotionController');
        Route::post('promotion/bulk', 'PromotionController@bulkPromote');
        
        // Fees Management
        Route::apiResource('fees-invoices', 'FeesInvoicesController');
        Route::apiResource('fees', 'FeesController');
        
        // Payment Management
        Route::apiResource('receipt-students', 'ReceiptStudentsController');
        Route::apiResource('processing-fee', 'ProcessingFeeController');
        Route::apiResource('payment-students', 'PaymentController');
        
        // Attendance
        Route::apiResource('attendance', 'AttendanceController');
        
        // Library
        Route::apiResource('library', 'LibraryController');
        Route::get('library/download/{filename}', 'LibraryController@downloadAttachment');
        
        // Student Attachments
        Route::post('students/upload-attachment', 'StudentController@Upload_attachment');
        Route::get('students/download-attachment/{studentsname}/{filename}', 'StudentController@Download_attachment');
        Route::post('students/delete-attachment', 'StudentController@Delete_attachment');
    });

    // Subjects API
    Route::group(['namespace' => 'Api\Subjects'], function () {
        Route::apiResource('subjects', 'SubjectController');
    });

    // Quizzes API
    Route::group(['namespace' => 'Api\Quizzes'], function () {
        Route::apiResource('quizzes', 'QuizzController');
    });

    // Questions API
    Route::group(['namespace' => 'Api\Questions'], function () {
        Route::apiResource('questions', 'QuestionController');
    });

    // Settings API
    Route::post('settings/school', 'Api\SettingController@updateSchool');
    Route::apiResource('settings', 'Api\SettingController');

    // Gender API
    Route::get('genders', 'Api\GenderController@index');
    Route::get('genders/{id}', 'Api\GenderController@show');

    // Nationalities API
    Route::get('nationalities', 'Api\NationalityController@index');
    Route::get('nationalities/{id}', 'Api\NationalityController@show');

    // Blood Types API
    Route::get('blood-types', 'Api\BloodTypeController@index');
    Route::get('blood-types/{id}', 'Api\BloodTypeController@show');
});

// API Routes with authentication and localization
Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath', 'auth:api']
    ], 
    function () {

    // All API routes moved to direct mobile-compatible routes above
    // This section kept for any future localized-specific endpoints
    });
