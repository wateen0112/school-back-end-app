<?php

namespace App\Http\Controllers\Api\Grades;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Student;
use App\Services\TranscriptService;
use Illuminate\Http\Request;

/**
 * الجلاء المدرسي: إصدار وعرض وطباعة جلاء الطالب مع شعار المدرسة.
 */
class TranscriptController extends Controller
{
    protected $service;

    public function __construct(TranscriptService $service)
    {
        $this->service = $service;
    }

    /**
     * الجلاء السنوي: فصل أول + فصل ثاني + المحصلة النهائية المجمعة.
     */
    public function yearly(Request $request, $studentId)
    {
        try {
            $student = Student::findOrFail($studentId);
            $transcript = $this->service->generateYearly($student);

            return response()->json([
                'success' => true,
                'message' => 'Yearly transcript generated successfully',
                'data' => $transcript,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating yearly transcript',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * عرض الجلاء كصفحة HTML قابلة للطباعة.
     */
    public function show(Request $request, $studentId)
    {
        try {
            $student = Student::findOrFail($studentId);

            $transcript = $this->service->generate($student, $request->input('academic_term_id'));

            // شعار المدرسة الحالية (أول مدرسة نشطة)
            $school = School::where('status', 'active')->first();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Transcript generated successfully',
                    'data' => $transcript,
                ], 200);
            }

            return view('transcripts.student', compact('transcript', 'school'));
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error generating transcript',
                    'error' => $e->getMessage(),
                ], 500);
            }

            abort(500, $e->getMessage());
        }
    }
}
