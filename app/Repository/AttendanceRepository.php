<?php


namespace App\Repository;


use App\Models\Attendance;
use App\Models\Grade;
use App\Models\Student;
use App\Models\Teacher;

class AttendanceRepository implements AttendanceRepositoryInterface
{

    public function index()
    {
        $Grades = Grade::with(['Sections'])->get();
        $list_Grades = Grade::all();
        $teachers = Teacher::all();
        return view('pages.Attendance.Sections',compact('Grades','list_Grades','teachers'));
    }

    public function show($id)
    {
        $students = Student::with('attendance')->where('section_id',$id)->get();
        return view('pages.Attendance.index',compact('students'));
    }

    public function store($request)
    {
        try {
            $attenddate = date('Y-m-d');
            $teacher_id = $request->input('teacher_id', Teacher::first()->id ?? 1);
            
            // Get all students that should be in attendance (from the section)
            $allStudentIds = $request->student_id ?? [];
            
            // Students that were explicitly submitted with a selection
            $submittedStudentIds = array_keys($request->attendences ?? []);
            
            // Process students who were explicitly selected (presence or absent)
            foreach ($request->attendences as $studentid => $attendence) {
                $attendence_status = ($attendence == 'presence') ? true : false;
                
                Attendance::updateOrCreate(
                    [
                        'student_id' => $studentid,
                        'attendence_date' => $attenddate
                    ],
                    [
                        'student_id' => $studentid,
                        'grade_id' => $request->grade_id,
                        'classroom_id' => $request->classroom_id,
                        'section_id' => $request->section_id,
                        'teacher_id' => $teacher_id,
                        'attendence_date' => $attenddate,
                        'attendence_status' => $attendence_status
                    ]
                );
            }
            
            // Mark students who were NOT selected as ABSENT
            foreach ($allStudentIds as $studentid) {
                if (!in_array($studentid, $submittedStudentIds)) {
                    Attendance::updateOrCreate(
                        [
                            'student_id' => $studentid,
                            'attendence_date' => $attenddate
                        ],
                        [
                            'student_id' => $studentid,
                            'grade_id' => $request->grade_id,
                            'classroom_id' => $request->classroom_id,
                            'section_id' => $request->section_id,
                            'teacher_id' => $teacher_id,
                            'attendence_date' => $attenddate,
                            'attendence_status' => false  // absent
                        ]
                    );
                }
            }

            toastr()->success(trans('messages.success'));
            return redirect()->back();

        }

        catch (\Exception $e){
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function update($request)
    {
        // TODO: Implement update() method.
    }

    public function destroy($request)
    {
        // TODO: Implement destroy() method.
    }
}
