<?php

namespace App\Http\Controllers\Api\Teachers;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Services\Scoping\SchoolContext;
use App\Services\TeacherAssignmentService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class TeacherEvaluationController extends Controller
{
    public function __construct(private readonly TeacherAssignmentService $assignments)
    {
    }

    /**
     * Sections the authenticated teacher may see.
     *
     * Pass `subject_id` to get only the sections where the teacher actually
     * teaches that subject, which is what the rating screen needs so the
     * section options can never drift away from the selected subject.
     */
    public function sections(Request $request)
    {
        $teacher = $this->currentTeacher();

        if (! $teacher) {
            return ApiResponse::forbidden('Only teachers can access this endpoint');
        }

        $subjectId = $this->subjectIdFrom($request);

        if ($request->filled('subject_id') && ! $this->assignments->teachesSubject($teacher, $subjectId)) {
            return ApiResponse::forbidden('This subject is not assigned to you');
        }

        $sections = Section::with(['Grades', 'My_classs'])
            ->whereIn('id', $this->assignments->sectionIds($teacher, $subjectId))
            ->orderBy('Name_Section')
            ->get();

        return ApiResponse::ok($sections, 'Teacher sections retrieved successfully');
    }

    /**
     * Subjects the authenticated teacher teaches, optionally narrowed to those
     * available in one section.
     */
    public function subjects(Request $request)
    {
        $teacher = $this->currentTeacher();

        if (! $teacher) {
            return ApiResponse::forbidden('Only teachers can access this endpoint');
        }

        $query = Subject::with(['grade', 'classroom', 'teacher'])
            ->whereIn('id', $this->assignments->subjectIds($teacher));

        if ($request->filled('section_id')) {
            $sectionId = (int) $request->section_id;

            if (! $this->assignments->teachesSection($teacher, $sectionId)) {
                return ApiResponse::forbidden('This section is not assigned to you');
            }

            $query->whereIn('id', $this->assignments->subjectIdsForSection($teacher, $sectionId) ?: [-1]);
        }

        return ApiResponse::ok($query->orderBy('Name')->get(), 'Teacher subjects retrieved successfully');
    }

    /**
     * Section/subject pairs assigned to the authenticated teacher.
     *
     * The client builds its grade -> classroom -> section -> subject filters
     * from this single list, so every offered combination is one the teacher is
     * genuinely assigned to teach.
     */
    public function assignments(Request $request)
    {
        $teacher = $this->currentTeacher();

        if (! $teacher) {
            return ApiResponse::forbidden('Only teachers can access this endpoint');
        }

        return ApiResponse::ok(
            $this->assignments->assignments($teacher),
            'Teacher assignments retrieved successfully'
        );
    }

    /**
     * Students the authenticated teacher may evaluate.
     *
     * Scoped to the teacher's assigned sections, and when `subject_id` is given
     * strictly to the sections where that subject is assigned — an unassigned
     * subject yields an empty list rather than falling back to every section.
     */
    public function students(Request $request)
    {
        $teacher = $this->currentTeacher();

        if (! $teacher) {
            return ApiResponse::forbidden('Only teachers can access this endpoint');
        }

        $subjectId = $this->subjectIdFrom($request);
        $sectionId = $request->filled('section_id') ? (int) $request->section_id : null;

        if ($request->filled('subject_id') && ! $this->assignments->teachesSubject($teacher, $subjectId)) {
            return ApiResponse::forbidden('This subject is not assigned to you');
        }

        if ($sectionId !== null && ! $this->assignments->teachesSection($teacher, $sectionId, $subjectId)) {
            return ApiResponse::forbidden('This section is not assigned to you for the selected subject');
        }

        $sectionIds = $this->assignments->sectionIds($teacher, $subjectId);

        if ($sectionId !== null) {
            $sectionIds = array_values(array_intersect($sectionIds, [$sectionId]));
        }

        if ($sectionIds === []) {
            return ApiResponse::ok([], 'No students found for the selected filters');
        }

        $query = Student::with(['grade', 'classroom', 'section', 'myparent', 'images'])
            ->whereIn('section_id', $sectionIds)
            ->when($request->filled('grade_id') || $request->filled('Grade_id'), function ($query) use ($request) {
                $query->where('Grade_id', $request->input('grade_id', $request->input('Grade_id')));
            })
            ->when($request->filled('classroom_id') || $request->filled('Classroom_id'), function ($query) use ($request) {
                $query->where('Classroom_id', $request->input('classroom_id', $request->input('Classroom_id')));
            })
            ->orderBy('name');

        SchoolContext::scope($query, $this->assignments->schoolId($teacher));

        $students = $query->get();

        $students->each(function ($student) {
            $student->images->transform(function ($image) {
                $image->filename = asset('storage/' . $image->filename);

                return $image;
            });
        });

        return ApiResponse::ok($students, 'Teacher students retrieved successfully');
    }

    private function subjectIdFrom(Request $request): ?int
    {
        if (! $request->filled('subject_id')) {
            return null;
        }

        $subjectId = (int) $request->subject_id;

        return $subjectId > 0 ? $subjectId : null;
    }

    /**
     * Resolve the authenticated teacher, if any.
     */
    private function currentTeacher(): ?Teacher
    {
        $user = auth()->user();

        return $user instanceof Teacher ? $user : null;
    }
}