<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInstallmentPlanRequest;
use App\Http\Requests\UpdateInstallmentPlanRequest;
use App\Repository\InstallmentPlanRepositoryInterface;
use Illuminate\Http\Request;

class InstallmentPlanController extends Controller
{
    protected $installmentPlanRepository;

    public function __construct(InstallmentPlanRepositoryInterface $installmentPlanRepository)
    {
        $this->installmentPlanRepository = $installmentPlanRepository;
    }

    public function index()
    {
        try {
            $plans = $this->installmentPlanRepository->index();

            $data = $plans->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'title' => $plan->title,
                    'description' => $plan->description,
                    'grade_id' => $plan->grade_id,
                    'classroom_id' => $plan->classroom_id,
                    'fee_id' => $plan->fee_id,
                    'total_amount' => $plan->total_amount,
                    'academic_year' => $plan->academic_year,
                    'start_date' => $plan->start_date ? $plan->start_date->format('Y-m-d') : null,
                    'end_date' => $plan->end_date ? $plan->end_date->format('Y-m-d') : null,
                    'is_active' => $plan->is_active,
                    'installments_count' => $plan->installments_count ?? $plan->installments->count(),
                    'students_count' => $plan->students_count ?? $plan->studentInstallments->distinct('student_id')->count('student_id'),
                    'created_at' => $plan->created_at,
                    'updated_at' => $plan->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Installment plans retrieved successfully',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving installment plans',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(StoreInstallmentPlanRequest $request)
    {
        try {
            $plan = $this->installmentPlanRepository->store($request);

            return response()->json([
                'success' => true,
                'message' => 'Installment plan created successfully',
                'data' => [
                    'id' => $plan->id,
                    'title' => $plan->title,
                    'total_amount' => $plan->total_amount,
                    'installments_count' => $plan->installments->count(),
                    'students_count' => $plan->studentInstallments->pluck('student_id')->unique()->count(),
                    'created_at' => $plan->created_at,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $plan = $this->installmentPlanRepository->show($id);

            return response()->json([
                'success' => true,
                'message' => 'Installment plan retrieved successfully',
                'data' => [
                    'id' => $plan->id,
                    'title' => $plan->title,
                    'description' => $plan->description,
                    'grade_id' => $plan->grade_id,
                    'classroom_id' => $plan->classroom_id,
                    'fee_id' => $plan->fee_id,
                    'total_amount' => $plan->total_amount,
                    'academic_year' => $plan->academic_year,
                    'start_date' => $plan->start_date ? $plan->start_date->format('Y-m-d') : null,
                    'end_date' => $plan->end_date ? $plan->end_date->format('Y-m-d') : null,
                    'is_active' => $plan->is_active,
                    'grade' => $plan->grade ? ['id' => $plan->grade->id, 'name' => $plan->grade->Name] : null,
                    'classroom' => $plan->classroom ? ['id' => $plan->classroom->id, 'name_class' => $plan->classroom->Name_Class] : null,
                    'fee' => $plan->fee ? [
                        'id' => $plan->fee->id,
                        'title' => $plan->fee->title,
                        'amount' => $plan->fee->amount,
                    ] : null,
                    'installments' => $plan->installments->map(function ($installment) {
                        return [
                            'id' => $installment->id,
                            'installment_plan_id' => $installment->installment_plan_id,
                            'title' => $installment->title,
                            'amount' => $installment->amount,
                            'due_date' => $installment->due_date ? $installment->due_date->format('Y-m-d') : null,
                            'sort_order' => $installment->sort_order,
                            'description' => $installment->description,
                        ];
                    }),
                    'created_at' => $plan->created_at,
                    'updated_at' => $plan->updated_at,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Installment plan not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function update(UpdateInstallmentPlanRequest $request, $id)
    {
        try {
            $plan = $this->installmentPlanRepository->update($request, $id);

            return response()->json([
                'success' => true,
                'message' => 'Installment plan updated successfully',
                'data' => [
                    'id' => $plan->id,
                    'title' => $plan->title,
                    'total_amount' => $plan->total_amount,
                ],
            ], 200);
        } catch (\Exception $e) {
            $status = strpos($e->getMessage(), 'paid') !== false ? 422 : 500;

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $status === 422 ? ['installments' => [$e->getMessage()]] : null,
            ], $status);
        }
    }

    public function destroy($id)
    {
        try {
            $this->installmentPlanRepository->destroy($id);

            return response()->json([
                'success' => true,
                'message' => 'Installment plan deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            $status = strpos($e->getMessage(), 'paid') !== false ? 422 : 500;

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $status);
        }
    }

    public function generate($id)
    {
        try {
            $result = $this->installmentPlanRepository->generate($id);

            return response()->json([
                'success' => true,
                'message' => 'Student installments generated successfully',
                'data' => $result,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
