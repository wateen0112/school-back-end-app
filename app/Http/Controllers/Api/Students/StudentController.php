<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentsRequest;
use App\Models\Image;
use App\Models\Student;
use App\Models\Classroom;
use App\Models\Section;
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
            $students = Student::with(['grade', 'classroom', 'section', 'myparent', 'images'])
                ->when($request->filled('Grade_id') || $request->filled('grade_id'), function ($query) use ($request) {
                    $query->where('Grade_id', $request->input('Grade_id', $request->input('grade_id')));
                })
                ->when($request->filled('section_id') || $request->filled('department_id'), function ($query) use ($request) {
                    $query->where('section_id', $request->input('section_id', $request->input('department_id')));
                })
                ->get();
            
            // Format image URLs for each student
            $students->each(function ($student) {
                $student->images->transform(function ($image) {
                    $image->filename = asset('storage/' . $image->filename);
                    return $image;
                });
            });
            
            return response()->json([
                'success' => true,
                'message' => 'Students retrieved successfully',
                'data' => $students
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
            $student->name = ['en' => $request->name_en, 'ar' => $request->name_ar];
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
            $student->academic_year = $request->academic_year;
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
                'data' => $student->load(['grade', 'classroom', 'section', 'myparent', 'images'])
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
            $student = Student::with(['grade', 'classroom', 'section', 'myparent', 'attendance', 'images'])->findOrFail($id);
            
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
            $student->name = ['en' => $request->name_en, 'ar' => $request->name_ar];
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
            $student->academic_year = $request->academic_year;
            $student->save();

            return response()->json([
                'success' => true,
                'message' => 'Student updated successfully',
                'data' => $student->load(['grade', 'classroom', 'section', 'myparent'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating student',
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
