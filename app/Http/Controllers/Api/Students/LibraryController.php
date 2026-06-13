<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Models\Library;
use App\Models\Grade;
use App\Models\Classroom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class LibraryController extends Controller
{
    public function index()
    {
        try {
            $libraryItems = Library::with(['grade', 'classroom', 'section', 'teacher'])->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Library items retrieved successfully',
                'data' => $libraryItems
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving library items',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'file_name' => 'required|file|mimes:pdf,doc,docx,txt|max:10240',
            'Grade_id' => 'required',
            'Classroom_id' => 'required',
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
            $library = new Library();
            $library->title = $request->title;
            $library->Grade_id = $request->Grade_id;
            $library->Classroom_id = $request->Classroom_id;
            $library->section_id = $request->section_id;
            $library->teacher_id = $request->input('teacher_id', 1);

            if ($request->hasFile('file_name')) {
                $file = $request->file('file_name');
                $filename = $file->getClientOriginalName();
                $file->storeAs('attachments/library', $filename, 'upload_attachments');
                $library->file_name = $filename;
            }

            $library->save();

            return response()->json([
                'success' => true,
                'message' => 'Library item created successfully',
                'data' => $library->load(['grade', 'classroom', 'section', 'teacher'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating library item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $libraryItem = Library::with(['grade', 'classroom', 'section', 'teacher'])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Library item retrieved successfully',
                'data' => $libraryItem
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Library item not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id = null)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'file_name' => 'nullable|file|mimes:pdf,doc,docx,txt|max:10240',
            'Grade_id' => 'required',
            'Classroom_id' => 'required',
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
            $library = Library::findOrFail($request->input('id', $id));
            $library->title = $request->title;
            $library->Grade_id = $request->Grade_id;
            $library->Classroom_id = $request->Classroom_id;
            $library->section_id = $request->section_id;
            $library->teacher_id = $request->input('teacher_id', 1);

            if ($request->hasFile('file_name')) {
                Storage::disk('upload_attachments')->delete('attachments/library/' . $library->file_name);

                $file = $request->file('file_name');
                $filename = $file->getClientOriginalName();
                $file->storeAs('attachments/library', $filename, 'upload_attachments');
                $library->file_name = $filename;
            }

            $library->save();

            return response()->json([
                'success' => true,
                'message' => 'Library item updated successfully',
                'data' => $library->load(['grade', 'classroom', 'section', 'teacher'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating library item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $id = null)
    {
        try {
            $library = Library::findOrFail($request->input('id', $id));
            Storage::disk('upload_attachments')->delete('attachments/library/' . $request->input('file_name', $library->file_name));
            
            $library->delete();

            return response()->json([
                'success' => true,
                'message' => 'Library item deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting library item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function downloadAttachment($filename)
    {
        try {
            return response()->download(public_path('attachments/library/' . $filename));

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error downloading file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getLibraryByGrade($gradeId)
    {
        try {
            $grade = Grade::findOrFail($gradeId);
            $libraryItems = Library::where('grade_id', $gradeId)
                ->with(['classroom', 'section', 'student'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Library items by grade retrieved successfully',
                'data' => [
                    'grade' => $grade,
                    'library_items' => $libraryItems
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving library items by grade',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getLibraryByClassroom($classroomId)
    {
        try {
            $classroom = Classroom::findOrFail($classroomId);
            $libraryItems = Library::where('classroom_id', $classroomId)
                ->with(['grade', 'section', 'student'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Library items by classroom retrieved successfully',
                'data' => [
                    'classroom' => $classroom,
                    'library_items' => $libraryItems
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving library items by classroom',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getLibraryByCategory($category)
    {
        try {
            $validCategories = ['book', 'note', 'material', 'reference', 'other'];
            
            if (!in_array($category, $validCategories)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid category',
                    'valid_categories' => $validCategories
                ], 400);
            }

            $libraryItems = Library::where('category', $category)
                ->with(['grade', 'classroom', 'section', 'student'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Library items by category retrieved successfully',
                'data' => [
                    'category' => $category,
                    'library_items' => $libraryItems
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving library items by category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function searchLibrary(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'query' => 'required|string|min:2|max:255',
                'grade_id' => 'nullable|exists:grades,id',
                'category' => 'nullable|in:book,note,material,reference,other',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = Library::with(['grade', 'classroom', 'section', 'student'])
                ->where(function ($q) use ($request) {
                    $q->where('title->en', 'like', '%' . $request->query . '%')
                      ->orWhere('title->ar', 'like', '%' . $request->query . '%')
                      ->orWhere('description', 'like', '%' . $request->query . '%');
                });

            if ($request->has('grade_id')) {
                $query->where('grade_id', $request->grade_id);
            }

            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            $libraryItems = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'message' => 'Library search completed successfully',
                'data' => [
                    'query' => $request->query,
                    'filters' => [
                        'grade_id' => $request->grade_id,
                        'category' => $request->category
                    ],
                    'library_items' => $libraryItems
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error searching library',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
