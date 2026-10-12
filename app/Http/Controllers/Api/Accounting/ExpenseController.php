<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Support\CacheVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Expense::with('category')->orderByDesc('expense_date');

            if ($request->filled('category_id')) {
                $query->where('category_id', $request->category_id);
            }
            if ($request->filled('from')) {
                $query->whereDate('expense_date', '>=', $request->from);
            }
            if ($request->filled('to')) {
                $query->whereDate('expense_date', '<=', $request->to);
            }

            return response()->json([
                'success' => true,
                'message' => 'Expenses retrieved successfully',
                'data' => $query->get(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving expenses',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $expense = Expense::with('category')->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Expense retrieved successfully',
                'data' => $expense,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Expense not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'nullable|exists:expense_categories,id',
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'reference_no' => 'nullable|string|max:100',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $expense = Expense::create([
                'category_id' => $request->category_id,
                'title' => $request->title,
                'amount' => $request->amount,
                'expense_date' => $request->expense_date,
                'reference_no' => $request->reference_no,
                'description' => $request->description,
                'created_by' => auth('api')->id(),
            ]);

            // Mirror into the school fund ledger (cash OUT).
            $expense->fundEntry()->create([
                'date' => $expense->expense_date,
                'credit' => $expense->amount,
                'description' => $request->description ?: $expense->title,
            ]);

            DB::commit();
            CacheVersion::bump('accounting');

            return response()->json([
                'success' => true,
                'message' => 'Expense created successfully',
                'data' => $expense->load('category'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error creating expense',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'nullable|exists:expense_categories,id',
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'reference_no' => 'nullable|string|max:100',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $expense = Expense::findOrFail($id);

            DB::beginTransaction();

            $expense->update($request->only([
                'category_id', 'title', 'amount', 'expense_date', 'reference_no', 'description',
            ]));

            if ($fund = $expense->fundEntry) {
                $fund->update([
                    'date' => $expense->expense_date,
                    'credit' => $expense->amount,
                    'description' => $expense->description ?: $expense->title,
                ]);
            }

            DB::commit();
            CacheVersion::bump('accounting');

            return response()->json([
                'success' => true,
                'message' => 'Expense updated successfully',
                'data' => $expense->load('category'),
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error updating expense',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $expense = Expense::findOrFail($id);

            DB::beginTransaction();
            $expense->fundEntry()->delete();
            $expense->delete();
            DB::commit();
            CacheVersion::bump('accounting');

            return response()->json([
                'success' => true,
                'message' => 'Expense deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error deleting expense',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
