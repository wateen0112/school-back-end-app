<?php

namespace App\Repository;

use App\Http\Traits\AttachFilesTrait;
use App\Models\Grade;
use App\Models\Library;
use App\Models\Teacher;

class LibraryRepository implements LibraryRepositoryInterface
{

    use AttachFilesTrait;

    public function index()
    {
        $books = Library::all();
        return view('pages.library.index',compact('books'));
    }

    public function create()
    {
        $grades = Grade::all();
        $teachers = Teacher::all();
        return view('pages.library.create',compact('grades','teachers'));
    }

    public function store($request)
    {
        try {
            if (!$request->has('title') || empty($request->title)) {
                return redirect()->back()->with(['error' => 'اسم الكتاب مطلوب']);
            }
            
            if (!$request->has('teacher_id') || empty($request->teacher_id)) {
                return redirect()->back()->with(['error' => 'المعلم مطلوب']);
            }
            
            if (!$request->has('Grade_id') || empty($request->Grade_id)) {
                return redirect()->back()->with(['error' => 'المرحلة الدراسية مطلوبة']);
            }
            if (!$request->has('Classroom_id') || empty($request->Classroom_id)) {
                return redirect()->back()->with(['error' => 'الصف الدراسي مطلوب']);
            }
            if (!$request->has('section_id') || empty($request->section_id)) {
                return redirect()->back()->with(['error' => 'القسم مطلوب']);
            }
            
            $books = new Library();
            $books->title = $request->title;
            $books->file_name =  $request->file('file_name')->getClientOriginalName();
            $books->Grade_id = $request->Grade_id;
            $books->classroom_id = $request->Classroom_id;
            $books->section_id = $request->section_id;
            $books->teacher_id = $request->teacher_id;
            $books->save();
            $this->uploadFile($request,'file_name','library');

            toastr()->success(trans('messages.success'));
            return redirect()->route('library.create');
        } catch (\Exception $e) {
            return redirect()->back()->with(['error' => $e->getMessage()]);
        }
    }

    public function edit($id)
    {
        $grades = Grade::all();
        $teachers = Teacher::all();
        $book = library::findorFail($id);
        return view('pages.library.edit',compact('book','grades','teachers'));
    }

    public function update($request)
    {
        try {

            $book = library::findorFail($request->id);
            $book->title = $request->title;

            if($request->hasfile('file_name')){

                $this->deleteFile($book->file_name);

                $this->uploadFile($request,'file_name','library');

                $file_name_new = $request->file('file_name')->getClientOriginalName();
                $book->file_name = $book->file_name !== $file_name_new ? $file_name_new : $book->file_name;
            }

            $book->Grade_id = $request->Grade_id;
            $book->classroom_id = $request->Classroom_id;
            $book->section_id = $request->section_id;
            $book->teacher_id = $request->teacher_id;
            $book->save();
            toastr()->success(trans('messages.Update'));
            return redirect()->route('library.index');
        } catch (\Exception $e) {
            return redirect()->back()->with(['error' => $e->getMessage()]);
        }
    }

    public function destroy($request)
    {
        $this->deleteFile($request->file_name);
        library::destroy($request->id);
        toastr()->error(trans('messages.Delete'));
        return redirect()->route('library.index');
    }

    public function download($filename)
    {
        return response()->download(public_path('attachments/library/'.$filename));
    }
}
