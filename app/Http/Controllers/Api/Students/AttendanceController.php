<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\Classroom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        try {
            $attendance = Attendance::with(['students', 'grade', 'classroom', 'section'])
                ->when($request->filled('grade_id'), function ($query) use ($request) {
                    $query->where('grade_id', $request->grade_id);
                })
                ->when($request->filled('classroom_id'), function ($query) use ($request) {
                    $query->where('classroom_id', $request->classroom_id);
                })
                ->when($request->filled('section_id'), function ($query) use ($request) {
                    $query->where('section_id', $request->section_id);
                })
                ->when($request->filled('date') || $request->filled('attendence_date'), function ($query) use ($request) {
                    $query->where('attendence_date', $request->input('date', $request->input('attendence_date')));
                })
                ->orderBy('attendence_date', 'desc')
                ->orderBy('id', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Attendance records retrieved successfully',
                'data' => $attendance
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving attendance records',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'attendences' => 'required|array',
            'grade_id' => 'required',
            'classroom_id' => 'required',
            'section_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $created = [];
            foreach ($request->attendences as $studentId => $attendence) {
                $attendanceStatus = $attendence === 'presence';
                $created[] = Attendance::create([
                    'student_id' => $studentId,
                    'grade_id' => $request->grade_id,
                    'classroom_id' => $request->classroom_id,
                    'section_id' => $request->section_id,
                    'teacher_id' => $request->input('teacher_id', 1),
                    'attendence_date' => date('Y-m-d'),
                    'attendence_status' => $attendanceStatus,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Attendance recorded successfully',
                'data' => $created
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error recording attendance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $students = Student::with('attendance')->where('section_id', $id)->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Section attendance students retrieved successfully',
                'data' => $students
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance record not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id = null)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'grade_id' => 'required|exists:grades,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'section_id' => 'required|exists:sections,id',
            'attendance_date' => 'required|date',
            'attendance_status' => 'required|in:present,absent,late,excused',
            'notes' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $attendance = Attendance::findOrFail($request->input('id', $id));
            $attendance->student_id = $request->student_id;
            $attendance->grade_id = $request->grade_id;
            $attendance->classroom_id = $request->classroom_id;
            $attendance->section_id = $request->section_id;
            $attendance->attendence_date = $request->input('attendence_date', $request->attendance_date);
            $attendance->attendence_status = in_array($request->input('attendence_status', $request->attendance_status), ['presence', 'present', true, 1], true);
            $attendance->save();

            return response()->json([
                'success' => true,
                'message' => 'Attendance updated successfully',
                'data' => $attendance->load(['students', 'grade', 'section'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating attendance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $id = null)
    {
        try {
            $attendance = Attendance::findOrFail($request->input('id', $id));
            $attendance->delete();

            return response()->json([
                'success' => true,
                'message' => 'Attendance record deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting attendance record',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function bulkStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'attendance_records' => 'required|array',
            'attendance_records.*.student_id' => 'required|exists:students,id',
            'attendance_records.*.grade_id' => 'required|exists:grades,id',
            'attendance_records.*.classroom_id' => 'required|exists:classrooms,id',
            'attendance_records.*.section_id' => 'required|exists:sections,id',
            'attendance_records.*.attendance_date' => 'required|date',
            'attendance_records.*.attendance_status' => 'required|in:present,absent,late,excused',
            'attendance_records.*.notes' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $createdAttendance = [];
            $errors = [];

            foreach ($request->attendance_records as $record) {
                try {
                    // Check if attendance already exists
                    $existingAttendance = Attendance::where('student_id', $record['student_id'])
                        ->where('attendance_date', $record['attendance_date'])
                        ->first();

                    if ($existingAttendance) {
                        $errors[] = "Attendance already exists for student {$record['student_id']} on {$record['attendance_date']}";
                        continue;
                    }

                    $attendance = new Attendance();
                    $attendance->student_id = $record['student_id'];
                    $attendance->grade_id = $record['grade_id'];
                    $attendance->classroom_id = $record['classroom_id'];
                    $attendance->section_id = $record['section_id'];
                    $attendance->attendance_date = $record['attendance_date'];
                    $attendance->attendance_status = $record['attendance_status'];
                    $attendance->notes = $record['notes'] ?? null;
                    $attendance->save();

                    $createdAttendance[] = $attendance;
                } catch (\Exception $e) {
                    $errors[] = "Error processing student {$record['student_id']}: " . $e->getMessage();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Bulk attendance processed successfully',
                'data' => [
                    'created_count' => count($createdAttendance),
                    'attendance_records' => $createdAttendance,
                    'errors' => $errors
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing bulk attendance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getStudentAttendance($studentId)
    {
        try {
            $student = Student::findOrFail($studentId);
            $attendance = Attendance::where('student_id', $studentId)
                ->with(['grade', 'classroom', 'section'])
                ->orderBy('attendance_date', 'desc')
                ->get();

            $attendanceStats = [
                'present' => $attendance->where('attendance_status', 'present')->count(),
                'absent' => $attendance->where('attendance_status', 'absent')->count(),
                'late' => $attendance->where('attendance_status', 'late')->count(),
                'excused' => $attendance->where('attendance_status', 'excused')->count(),
                'total' => $attendance->count()
            ];

            return response()->json([
                'success' => true,
                'message' => 'Student attendance retrieved successfully',
                'data' => [
                    'student' => $student,
                    'attendance' => $attendance,
                    'statistics' => $attendanceStats
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving student attendance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getClassroomAttendance($classroomId, Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'date' => 'required|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors()
                ], 422);
            }

            $classroom = Classroom::findOrFail($classroomId);
            $attendance = Attendance::where('classroom_id', $classroomId)
                ->where('attendance_date', $request->date)
                ->with(['student', 'grade', 'section'])
                ->get();

            $students = Student::where('classroom_id', $classroomId)
                ->with(['grade', 'section'])
                ->get();

            // Mark attendance status for each student
            $studentsWithAttendance = $students->map(function ($student) use ($attendance) {
                $studentAttendance = $attendance->where('student_id', $student->id)->first();
                $student->attendance_status = $studentAttendance ? $studentAttendance->attendance_status : null;
                $student->attendance_notes = $studentAttendance ? $studentAttendance->notes : null;
                $student->attendance_id = $studentAttendance ? $studentAttendance->id : null;
                return $student;
            });

            $attendanceStats = [
                'present' => $attendance->where('attendance_status', 'present')->count(),
                'absent' => $attendance->where('attendance_status', 'absent')->count(),
                'late' => $attendance->where('attendance_status', 'late')->count(),
                'excused' => $attendance->where('attendance_status', 'excused')->count(),
                'total_students' => $students->count(),
                'marked_attendance' => $attendance->count()
            ];

            return response()->json([
                'success' => true,
                'message' => 'Classroom attendance retrieved successfully',
                'data' => [
                    'classroom' => $classroom,
                    'date' => $request->date,
                    'students' => $studentsWithAttendance,
                    'attendance' => $attendance,
                    'statistics' => $attendanceStats
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving classroom attendance',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
