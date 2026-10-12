<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Models\SchoolBranch;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $query = SchoolBranch::with('school:id,name,email')->orderByDesc('id');

        if ($request->query('school_id')) {
            $query->where('school_id', $request->query('school_id'));
        }

        $branches = $query->get()->map(function ($branch) {
            $data = $branch->toArray();
            $data['name'] = $branch->getTranslations('name');
            $data['school_name'] = $branch->school?->getTranslations('name')['ar'] ?? null;

            return $data;
        });

        return response()->json(['success' => true, 'message' => 'Branches retrieved successfully', 'data' => $branches], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'school_id' => 'required|integer|exists:schools,id',
            'name' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:200',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation errors', 'errors' => $validator->errors()], 422);
        }

        $branch = SchoolBranch::create([
            'school_id' => $request->school_id,
            'name' => ['ar' => $request->name, 'en' => $request->name_en ?: $request->name],
            'address' => $request->address,
            'city' => $request->city,
            'phone' => $request->phone,
            'email' => $request->email,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        AuditLogger::log('branch.created', 'SchoolBranch', $branch->id, 'إنشاء فرع مدرسة', [
            'school_id' => $branch->school_id,
        ]);

        return response()->json(['success' => true, 'message' => 'Branch created successfully', 'data' => $branch], 201);
    }

    public function update(Request $request, $id)
    {
        $branch = SchoolBranch::find($id);

        if (! $branch) {
            return response()->json(['success' => false, 'message' => 'Branch not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:200',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation errors', 'errors' => $validator->errors()], 422);
        }

        if ($request->filled('name') || $request->filled('name_en')) {
            $translations = $branch->getTranslations('name');
            $translations['ar'] = $request->input('name', $translations['ar'] ?? '');
            $translations['en'] = $request->input('name_en', $translations['en'] ?? $translations['ar'] ?? '');
            $branch->name = $translations;
        }

        $branch->fill($request->except(['name', 'name_en']));
        $branch->save();

        AuditLogger::log('branch.updated', 'SchoolBranch', $branch->id, 'تعديل فرع المدرسة');

        return response()->json(['success' => true, 'message' => 'Branch updated successfully', 'data' => $branch], 200);
    }

    public function destroy(Request $request, $id)
    {
        $branch = SchoolBranch::find($id);

        if (! $branch) {
            return response()->json(['success' => false, 'message' => 'Branch not found'], 404);
        }

        $branch->delete();

        AuditLogger::log('branch.deleted', 'SchoolBranch', $id, 'حذف فرع المدرسة');

        return response()->json(['success' => true, 'message' => 'Branch deleted successfully'], 200);
    }
}