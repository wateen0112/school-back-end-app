<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentsRequest;
use App\Models\Image;
use App\Models\Student;
use App\Models\Classroom;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        try {
            $students = Student::with(['grade', 'classroom', 'section', 'myparent', 'images', 'academicYear'])
                ->when($request->filled('Grade_id') || $request->filled('grade_id'), function ($query) use ($request) {
                    $query->where('Grade_id', $request->input('Grade_id', $request->input('grade_id')));
                })
                ->when($request->filled('Classroom_id') || $request->filled('classroom_id'), function ($query) use ($request) {
                    $query->where('Classroom_id', $request->input('Classroom_id', $request->input('classroom_id')));
                })
                ->when($request->filled('section_id') || $request->filled('department_id'), function ($query) use ($request) {
                    $query->where('section_id', $request->input('section_id', $request->input('department_id')));
                })
                ->when($request->filled('search'), function ($query) use ($request) {
                    $search = $request->input('search');
                    $query->where(function ($q) use ($search) {
                        $q->where('email', 'like', "%{$search}%")
                            // name is translatable (stored as JSON {"en":..., "ar":...})
                            ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"en\"')) LIKE ?", ["%{$search}%"])
                            ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"ar\"')) LIKE ?", ["%{$search}%"]);
                    });
                })
                ->when($request->filled('subject_id'), function ($query) use ($request) {
                    $subject = Subject::find($request->subject_id);
                    if ($subject) {
                        $query->where('Grade_id', $subject->Grade_id)
                            ->where('Classroom_id', $subject->Classroom_id);
                    }
                })
                ->when($request->filled('academic_year_id'), function ($query) use ($request) {
                    $query->where('academic_year_id', $request->input('academic_year_id'));
                });

            // A parent may only see their own children
            $user = auth()->user();
            if ($user) {
                $userClass = get_class($user);
                if (strpos($userClass, 'My_Parent') !== false || strpos($userClass, 'Parent') !== false) {
                    $students->where('parent_id', $user->id);
                }
            }

            $paginated = $students->paginate($request->integer('per_page', 50));

            // Format image URLs for each student
            $paginated->getCollection()->each(function ($student) {
                $student->images->transform(function ($image) {
                    $image->filename = asset('storage/' . $image->filename);
                    return $image;
                });
            });

            return response()->json([
                'success' => true,
                'message' => 'Students retrieved successfully',
                'data' => $paginated
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving students',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(StoreStudentsRequest $request)
    {
        DB::beginTransaction();
        try {
            $student = new Student();
            $student->name = ['en' => $request->name_en ?: $request->name_ar, 'ar' => $request->name_ar];
            $student->second_name = $request->second_name;
            $student->grandfather_name = $request->grandfather_name;
            $student->national_id = $request->national_id;
            $student->general_record_number = $request->general_record_number;
            $student->residence_address = $request->residence_address;
            $student->email = $request->email;
            $student->password = Hash::make($request->password);
            $student->gender_id = $request->gender_id;
            $student->nationalitie_id = $request->nationalitie_id;
            $student->blood_id = $request->blood_id;
            $student->Date_Birth = $request->Date_Birth;
            $student->Grade_id = $request->Grade_id;
            $student->Classroom_id = $request->Classroom_id;
            $student->section_id = $request->section_id;
            $student->parent_id = $request->parent_id;
            $this->bindAcademicYear($student, $request->input('academic_year_id'));
            $student->school_id = \App\Services\Scoping\SchoolContext::effectiveSchoolId($request->user());
            $student->save();

            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $file) {
                    $name = $file->getClientOriginalName();
                    $file->storeAs('attachments/students/' . $student->name, $name, 'upload_attachments');

                    Image::create([
                        'filename' => $name,
                        'imageable_id' => $student->id,
                        'imageable_type' => 'App\Models\Student',
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Student created successfully',
                'data' => $student->load(['grade', 'classroom', 'section', 'myparent', 'images', 'academicYear'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating student',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $student = Student::with(['grade', 'classroom', 'section', 'myparent', 'attendance', 'images', 'academicYear'])->findOrFail($id);
            
            // Format image URLs for the student
            $student->images->transform(function ($image) {
                $image->filename = asset('storage/' . $image->filename);
                return $image;
            });
            
            return response()->json([
                'success' => true,
                'message' => 'Student retrieved successfully',
                'data' => $student
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(StoreStudentsRequest $request, $id = null)
    {
        try {
            $student = Student::findOrFail($request->input('id', $id));
            $student->name = ['en' => $request->name_en ?: $request->name_ar, 'ar' => $request->name_ar];
            $student->second_name = $request->second_name;
            $student->grandfather_name = $request->grandfather_name;
            $student->national_id = $request->national_id;
            $student->general_record_number = $request->general_record_number;
            $student->residence_address = $request->residence_address;
            $student->email = $request->email;
            if ($request->filled('password')) {
                $student->password = Hash::make($request->password);
            }
            $student->gender_id = $request->gender_id;
            $student->nationalitie_id = $request->nationalitie_id;
            $student->blood_id = $request->blood_id;
            $student->Date_Birth = $request->Date_Birth;
            $student->Grade_id = $request->Grade_id;
            $student->Classroom_id = $request->Classroom_id;
            $student->section_id = $request->section_id;
            $student->parent_id = $request->parent_id;
            $this->bindAcademicYear($student, $request->input('academic_year_id', $student->academic_year_id));
            $student->save();

            return response()->json([
                'success' => true,
                'message' => 'Student updated successfully',
                'data' => $student->load(['grade', 'classroom', 'section', 'myparent', 'academicYear'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating student',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function linkParent(Request $request, $id = null)
    {
        try {
            $student = Student::findOrFail($id);

            $request->validate([
                'parent_id' => 'nullable|exists:my__parents,id',
            ]);

            $student->parent_id = $request->input('parent_id');
            $student->save();

            return response()->json([
                'success' => true,
                'message' => 'Parent linked successfully',
                'data' => $student->load(['myparent']),
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error linking parent',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $id = null)
    {
        try {
            $student = Student::findOrFail($request->input('id', $id));
            $student->delete();

            return response()->json([
                'success' => true,
                'message' => 'Student deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting student',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Link the student to an academic year and keep the legacy free-text
     * `academic_year` label in sync with that year's (localized) name.
     */
    private function bindAcademicYear(Student $student, $academicYearId): void
    {
        $year = $academicYearId ? \App\Models\AcademicYear::find($academicYearId) : null;

        $student->academic_year_id = $year?->id;

        if ($year) {
            $student->academic_year = $year->getTranslation('name', app()->getLocale())
                ?: $year->getTranslation('name', 'ar');
        }
    }

    public function Get_classrooms($id)
    {
        try {
            $classrooms = Classroom::where('Grade_id', $id)->pluck('Name_Class', 'id');
            
            return response()->json([
                'success' => true,
                'message' => 'Classrooms retrieved successfully',
                'data' => $classrooms
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving classrooms',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function Get_Sections($id)
    {
        try {
            $sections = Section::where('Class_id', $id)->pluck('Name_Section', 'id');
            
            return response()->json([
                'success' => true,
                'message' => 'Sections retrieved successfully',
                'data' => $sections
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving sections',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function sectionsByGrade($id)
    {
        try {
            $sections = Section::where('Grade_id', $id)
                ->with(['Grades', 'My_classs'])
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Grade sections retrieved successfully',
                'data' => $sections
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving grade sections',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function Upload_attachment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'student_name' => 'required|string',
            'photos' => 'required',
            'photos.*' => 'file',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $files = [];
            foreach ($request->file('photos') as $file) {
                $name = $file->getClientOriginalName();
                $path = $file->storeAs('attachments/students/' . $request->student_name, $name, 'upload_attachments');

                $image = Image::create([
                    'filename' => $name,
                    'imageable_id' => $request->student_id,
                    'imageable_type' => 'App\Models\Student',
                ]);

                $files[] = ['path' => $path, 'image' => $image];
            }

            return response()->json([
                'success' => true,
                'message' => 'Files uploaded successfully',
                'data' => $files
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error uploading file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function Download_attachment($studentsname, $filename)
    {
        try {
            return response()->download(public_path('attachments/students/' . $studentsname . '/' . $filename));

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error downloading file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function Delete_attachment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:images,id',
            'student_id' => 'required|exists:students,id',
            'student_name' => 'required|string',
            'filename' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            Storage::disk('upload_attachments')->delete('attachments/students/' . $request->student_name . '/' . $request->filename);
            Image::where('id', $request->id)->where('filename', $request->filename)->delete();

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting file',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
