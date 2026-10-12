<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use App\Support\CacheVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Expense categories retrieved successfully',
                'data' => ExpenseCategory::withCount('expenses')->get(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving expense categories',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|array',
            'name.ar' => 'required|string|max:100',
            'name.en' => 'required|string|max:100',
            'icon' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $category = ExpenseCategory::create($request->only('name', 'icon'));
            CacheVersion::bump('accounting');

            return response()->json([
                'success' => true,
                'message' => 'Expense category created successfully',
                'data' => $category,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating expense category',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|array',
            'name.ar' => 'required|string|max:100',
            'name.en' => 'required|string|max:100',
            'icon' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $category = ExpenseCategory::findOrFail($id);
            $category->update($request->only('name', 'icon'));
            CacheVersion::bump('accounting');

            return response()->json([
                'success' => true,
                'message' => 'Expense category updated successfully',
                'data' => $category,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating expense category',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            ExpenseCategory::findOrFail($id)->delete();
            CacheVersion::bump('accounting');

            return response()->json([
                'success' => true,
                'message' => 'Expense category deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting expense category',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
