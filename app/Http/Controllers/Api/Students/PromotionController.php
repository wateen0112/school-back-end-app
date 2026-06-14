<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Models\promotion;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PromotionController extends Controller
{
    public function index()
    {
        try {
            $promotions = promotion::with(['student', 'f_grade', 'f_classroom', 'f_section', 't_grade', 't_classroom', 't_section'])->get();

            return response()->json([
                'success' => true,
                'message' => 'Student promotions retrieved successfully',
                'data' => $promotions
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving promotions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'Grade_id' => 'required',
            'Classroom_id' => 'required',
            'section_id' => 'required',
            'academic_year' => 'required',
            'Grade_id_new' => 'required',
            'Classroom_id_new' => 'required',
            'section_id_new' => 'required',
            'academic_year_new' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $students = Student::where('Grade_id', $request->Grade_id)
                ->where('Classroom_id', $request->Classroom_id)
                ->where('section_id', $request->section_id)
                ->where('academic_year', $request->academic_year)
                ->get();

            if ($students->count() < 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'No students found for promotion.'
                ], 404);
            }

            $promotions = [];
            foreach ($students as $student) {
                Student::where('id', $student->id)->update([
                    'Grade_id' => $request->Grade_id_new,
                    'Classroom_id' => $request->Classroom_id_new,
                    'section_id' => $request->section_id_new,
                    'academic_year' => $request->academic_year_new,
                ]);

                $promotions[] = promotion::updateOrCreate([
                    'student_id' => $student->id,
                    'from_grade' => $request->Grade_id,
                    'from_Classroom' => $request->Classroom_id,
                    'from_section' => $request->section_id,
                    'to_grade' => $request->Grade_id_new,
                    'to_Classroom' => $request->Classroom_id_new,
                    'to_section' => $request->section_id_new,
                    'academic_year' => $request->academic_year,
                    'academic_year_new' => $request->academic_year_new,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Students promoted successfully',
                'data' => [
                    'promoted_count' => count($promotions),
                    'promotions' => $promotions
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Error promoting students',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $student = promotion::with(['student', 'f_grade', 'f_classroom', 'f_section', 't_grade', 't_classroom', 't_section'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Promoted student retrieved successfully',
                'data' => $student
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Promoted student not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'to_grade_id' => 'required|exists:Grades,id',
            'to_classroom_id' => 'required|exists:Classrooms,id',
            'to_section_id' => 'required|exists:sections,id',
            'promotion_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $student = Student::findOrFail($id);

            $student->grade_id = $request->to_grade_id;
            $student->classroom_id = $request->to_classroom_id;
            $student->section_id = $request->to_section_id;
            $student->promotion_date = $request->promotion_date;
            $student->save();

            return response()->json([
                'success' => true,
                'message' => 'Student promotion updated successfully',
                'data' => $student->load(['grade', 'classroom', 'section', 'parent'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating student promotion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $id = null)
    {
        DB::beginTransaction();
        try {
            if ($request->page_id == 1) {
                foreach (promotion::all() as $promotion) {
                    Student::where('id', $promotion->student_id)->update([
                        'Grade_id' => $promotion->from_grade,
                        'Classroom_id' => $promotion->from_Classroom,
                        'section_id' => $promotion->from_section,
                        'academic_year' => $promotion->academic_year,
                    ]);
                }
                promotion::truncate();
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'All student promotions reverted successfully'
                ], 200);
            }

            $promotion = promotion::findOrFail($request->input('id', $id));
            Student::where('id', $promotion->student_id)->update([
                'Grade_id' => $promotion->from_grade,
                'Classroom_id' => $promotion->from_Classroom,
                'section_id' => $promotion->from_section,
                'academic_year' => $promotion->academic_year,
            ]);
            $promotion->delete();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Student promotion reverted successfully'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error reverting student promotion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getStudentsForPromotion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'grade_id' => 'required|exists:Grades,id',
            'classroom_id' => 'required|exists:Classrooms,id',
            'section_id' => 'required|exists:sections,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $students = Student::where('grade_id', $request->grade_id)
                ->where('classroom_id', $request->classroom_id)
                ->where('section_id', $request->section_id)
                ->with(['grade', 'classroom', 'section'])
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Students for promotion retrieved successfully',
                'data' => $students
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving students for promotion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk promote selected students by their IDs.
     * Accepts specific student_ids array and promotes only those students.
     */
    public function bulkPromote(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'integer|exists:students,id',
            'Grade_id' => 'required|exists:Grades,id',
            'Classroom_id' => 'required|exists:Classrooms,id',
            'section_id' => 'required|exists:sections,id',
            'academic_year' => 'required|string',
            'Grade_id_new' => 'required|exists:Grades,id',
            'Classroom_id_new' => 'required|exists:Classrooms,id',
            'section_id_new' => 'required|exists:sections,id',
            'academic_year_new' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $studentIds = $request->student_ids;

            // Fetch only the selected students that match the source criteria
            $students = Student::whereIn('id', $studentIds)
                ->where('Grade_id', $request->Grade_id)
                ->where('Classroom_id', $request->Classroom_id)
                ->where('section_id', $request->section_id)
                ->where('academic_year', $request->academic_year)
                ->get();

            if ($students->count() < 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'No matching students found for the selected IDs and source criteria.'
                ], 404);
            }

            $promotions = [];
            foreach ($students as $student) {
                Student::where('id', $student->id)->update([
                    'Grade_id' => $request->Grade_id_new,
                    'Classroom_id' => $request->Classroom_id_new,
                    'section_id' => $request->section_id_new,
                    'academic_year' => $request->academic_year_new,
                ]);

                $promotions[] = promotion::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'from_grade' => $request->Grade_id,
                        'from_Classroom' => $request->Classroom_id,
                        'from_section' => $request->section_id,
                    ],
                    [
                        'to_grade' => $request->Grade_id_new,
                        'to_Classroom' => $request->Classroom_id_new,
                        'to_section' => $request->section_id_new,
                        'academic_year' => $request->academic_year,
                        'academic_year_new' => $request->academic_year_new,
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Students promoted successfully',
                'data' => [
                    'requested_count' => count($studentIds),
                    'promoted_count' => count($promotions),
                    'promotions' => $promotions
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Error promoting students',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function getNewAcademicYear($currentYear)
    {
        // Extract the year part and increment
        $year = substr($currentYear, -4);
        $nextYear = $year + 1;
        return substr($currentYear, 0, -4) . $nextYear;
    }

    private function getPreviousAcademicYear($currentYear)
    {
        // Extract the year part and decrement
        $year = substr($currentYear, -4);
        $prevYear = $year - 1;
        return substr($currentYear, 0, -4) . $prevYear;
    }
}
