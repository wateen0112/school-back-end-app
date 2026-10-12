<?php

namespace App\Repository;

use App\Models\Classroom;
use App\Models\Fee_invoice;
use App\Models\Installment;
use App\Models\InstallmentPlan;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Models\StudentInstallment;
use Illuminate\Support\Facades\DB;

class InstallmentPlanRepository implements InstallmentPlanRepositoryInterface
{
    public function index()
    {
        return InstallmentPlan::with(['grade', 'classroom', 'fee'])
            ->withCount(['installments'])
            ->withCount(['studentInstallments as students_count' => function ($query) {
                $query->select(DB::raw('count(distinct student_id)'));
            }])
            ->get();
    }

    public function show($id)
    {
        return InstallmentPlan::with(['grade', 'classroom', 'fee', 'installments'])
            ->withCount(['studentInstallments as students_count' => function ($query) {
                $query->select(DB::raw('count(distinct student_id)'));
            }])
            ->findOrFail($id);
    }

    public function studentsCount(InstallmentPlan $plan)
    {
        return $plan->studentInstallments()->distinct('student_id')->count('student_id');
    }

    public function create()
    {
        return null;
    }

    public function store($request)
    {
        DB::beginTransaction();

        try {
            $plan = $this->createPlan($request);
            $installments = $this->createInstallments($plan, $request->installments);
            $this->generateStudentRecords($plan, $installments);

            DB::commit();

            return $plan->load(['grade', 'classroom', 'fee', 'installments']);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function edit($id)
    {
        return $this->show($id);
    }

    public function update($request, $id)
    {
        DB::beginTransaction();

        try {
            $plan = InstallmentPlan::findOrFail($id);

            if ($this->hasPaidOrPartialInstallments($plan)) {
                throw new \Exception(__('messages.cannot_update_paid_plan'));
            }

            $plan->title = $this->normalizeTranslatable($request->title, $request->title_ar, $request->title_en);
            $plan->description = $this->normalizeTranslatable($request->description, $request->description_ar, $request->description_en);
            $plan->grade_id = $request->grade_id;
            $plan->classroom_id = $request->classroom_id;
            $plan->fee_id = $request->fee_id;
            $plan->total_amount = $request->total_amount;
            $plan->academic_year = $request->academic_year;
            $plan->start_date = $request->start_date;
            $plan->end_date = $request->end_date;
            $plan->is_active = $request->boolean('is_active', $plan->is_active);
            $plan->save();

            $this->syncInstallments($plan, $request->installments);

            $this->regenerateStudentRecords($plan);

            DB::commit();

            return $plan->load(['grade', 'classroom', 'fee', 'installments']);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $plan = InstallmentPlan::findOrFail($id);

            if ($this->hasPaidOrPartialInstallments($plan)) {
                throw new \Exception(__('messages.cannot_delete_paid_plan'));
            }

            $plan->delete();

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function generate($id)
    {
        DB::beginTransaction();

        try {
            $plan = InstallmentPlan::with(['installments'])->findOrFail($id);
            $result = $this->generateMissingStudentRecords($plan);

            DB::commit();

            return $result;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    private function createPlan($request)
    {
        return InstallmentPlan::create([
            'title' => $this->normalizeTranslatable($request->title, $request->title_ar, $request->title_en),
            'description' => $this->normalizeTranslatable($request->description, $request->description_ar, $request->description_en),
            'grade_id' => $request->grade_id,
            'classroom_id' => $request->classroom_id,
            'fee_id' => $request->fee_id,
            'total_amount' => $request->total_amount,
            'academic_year' => $request->academic_year,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'is_active' => $request->boolean('is_active', true),
        ]);
    }

    private function createInstallments(InstallmentPlan $plan, array $installmentsData)
    {
        $installments = [];

        foreach ($installmentsData as $index => $item) {
            $installments[] = $plan->installments()->create([
                'title' => $item['title'],
                'amount' => $item['amount'],
                'due_date' => $item['due_date'],
                'sort_order' => $item['sort_order'] ?? ($index + 1),
                'description' => $item['description'] ?? null,
            ]);
        }

        return $installments;
    }

    private function syncInstallments(InstallmentPlan $plan, array $installmentsData)
    {
        $existingIds = $plan->installments()->pluck('id')->toArray();
        $keptIds = [];

        foreach ($installmentsData as $index => $item) {
            if (! empty($item['id']) && in_array($item['id'], $existingIds)) {
                $installment = Installment::findOrFail($item['id']);
                $installment->update([
                    'title' => $item['title'],
                    'amount' => $item['amount'],
                    'due_date' => $item['due_date'],
                    'sort_order' => $item['sort_order'] ?? ($index + 1),
                    'description' => $item['description'] ?? null,
                ]);
                $keptIds[] = $installment->id;
            } else {
                $newInstallment = $plan->installments()->create([
                    'title' => $item['title'],
                    'amount' => $item['amount'],
                    'due_date' => $item['due_date'],
                    'sort_order' => $item['sort_order'] ?? ($index + 1),
                    'description' => $item['description'] ?? null,
                ]);
                $keptIds[] = $newInstallment->id;
            }
        }

        $deletedIds = array_diff($existingIds, $keptIds);
        if (! empty($deletedIds)) {
            Installment::whereIn('id', $deletedIds)->delete();
        }
    }

    private function generateStudentRecords(InstallmentPlan $plan, array $installments)
    {
        $students = Student::where('Classroom_id', $plan->classroom_id)->get();

        foreach ($students as $student) {
            $feeInvoice = $this->createFeeInvoice($plan, $student);
            $this->createStudentAccountForInvoice($feeInvoice, $plan, $student);

            foreach ($installments as $installment) {
                StudentInstallment::create([
                    'installment_plan_id' => $plan->id,
                    'installment_id' => $installment->id,
                    'student_id' => $student->id,
                    'fee_invoice_id' => $feeInvoice->id,
                    'amount' => $installment->amount,
                    'paid_amount' => 0.00,
                    'remaining_amount' => $installment->amount,
                    'status' => 'pending',
                    'due_date' => $installment->due_date,
                ]);
            }
        }
    }

    private function regenerateStudentRecords(InstallmentPlan $plan)
    {
        $this->deleteGeneratedRecords($plan);
        $installments = $plan->installments()->orderBy('sort_order')->get()->all();
        $this->generateStudentRecords($plan, $installments);
    }

    private function deleteGeneratedRecords(InstallmentPlan $plan)
    {
        $studentInstallmentIds = $plan->studentInstallments()->pluck('id')->toArray();
        $feeInvoiceIds = $plan->studentInstallments()
            ->whereNotNull('fee_invoice_id')
            ->pluck('fee_invoice_id')
            ->unique()
            ->toArray();

        StudentAccount::whereIn('student_installment_id', $studentInstallmentIds)->delete();
        StudentInstallment::whereIn('id', $studentInstallmentIds)->delete();

        if (! empty($feeInvoiceIds)) {
            Fee_invoice::whereIn('id', $feeInvoiceIds)->delete();
        }
    }

    private function generateMissingStudentRecords(InstallmentPlan $plan)
    {
        $students = Student::where('Classroom_id', $plan->classroom_id)->get();
        $installments = $plan->installments()->orderBy('sort_order')->get();
        $generatedCount = 0;

        foreach ($students as $student) {
            $existingFeeInvoiceId = StudentInstallment::where('installment_plan_id', $plan->id)
                ->where('student_id', $student->id)
                ->whereNotNull('fee_invoice_id')
                ->value('fee_invoice_id');

            if ($existingFeeInvoiceId) {
                $feeInvoice = Fee_invoice::find($existingFeeInvoiceId);
            } else {
                $feeInvoice = $this->createFeeInvoice($plan, $student);
                $this->createStudentAccountForInvoice($feeInvoice, $plan, $student);
            }

            if (! $feeInvoice) {
                continue;
            }

            foreach ($installments as $installment) {
                $exists = StudentInstallment::where([
                    'installment_plan_id' => $plan->id,
                    'installment_id' => $installment->id,
                    'student_id' => $student->id,
                ])->exists();

                if (! $exists) {
                    StudentInstallment::create([
                        'installment_plan_id' => $plan->id,
                        'installment_id' => $installment->id,
                        'student_id' => $student->id,
                        'fee_invoice_id' => $feeInvoice->id,
                        'amount' => $installment->amount,
                        'paid_amount' => 0.00,
                        'remaining_amount' => $installment->amount,
                        'status' => 'pending',
                        'due_date' => $installment->due_date,
                    ]);
                    $generatedCount++;
                }
            }
        }

        return [
            'generated_count' => $generatedCount,
            'students_count' => $students->count(),
        ];
    }

    private function createFeeInvoice(InstallmentPlan $plan, Student $student)
    {
        $feeInvoice = new Fee_invoice();
        $feeInvoice->invoice_date = date('Y-m-d');
        $feeInvoice->student_id = $student->id;
        $feeInvoice->Grade_id = $plan->grade_id;
        $feeInvoice->Classroom_id = $plan->classroom_id;
        $feeInvoice->fee_id = $plan->fee_id;
        $feeInvoice->amount = $plan->total_amount;
        $feeInvoice->description = $this->getPlanDescription($plan);
        $feeInvoice->save();

        return $feeInvoice;
    }

    private function createStudentAccountForInvoice(Fee_invoice $feeInvoice, InstallmentPlan $plan, Student $student)
    {
        $studentAccount = new StudentAccount();
        $studentAccount->date = date('Y-m-d');
        $studentAccount->type = 'invoice';
        $studentAccount->fee_invoice_id = $feeInvoice->id;
        $studentAccount->student_id = $student->id;
        $studentAccount->Debit = $plan->total_amount;
        $studentAccount->credit = 0.00;
        $studentAccount->description = $this->getPlanDescription($plan);
        $studentAccount->save();

        return $studentAccount;
    }

    private function getPlanDescription(InstallmentPlan $plan)
    {
        $description = $plan->getTranslation('description', 'en') ?? $plan->getTranslation('description', 'ar');

        return $description ?? 'Installment plan';
    }

    private function hasPaidOrPartialInstallments(InstallmentPlan $plan)
    {
        return $plan->studentInstallments()
            ->whereIn('status', ['paid', 'partial'])
            ->exists();
    }

    private function normalizeTranslatable($value, $arValue = null, $enValue = null)
    {
        if (is_array($value)) {
            return $value;
        }

        if ($arValue || $enValue) {
            return array_filter([
                'ar' => $arValue,
                'en' => $enValue,
            ], function ($item) {
                return ! is_null($item);
            });
        }

        return ['en' => $value, 'ar' => $value];
    }
}
