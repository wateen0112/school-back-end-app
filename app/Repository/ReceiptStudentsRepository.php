<?php

namespace App\Repository;

use App\Models\FundAccount;
use App\Models\ReceiptStudent;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Models\StudentInstallment;
use Illuminate\Support\Facades\DB;

class ReceiptStudentsRepository implements ReceiptStudentsRepositoryInterface
{
    protected $studentInstallmentRepository;

    public function __construct(StudentInstallmentRepositoryInterface $studentInstallmentRepository)
    {
        $this->studentInstallmentRepository = $studentInstallmentRepository;
    }

    public function index()
    {
        $receipt_students = ReceiptStudent::all();
        return view('pages.Receipt.index', compact('receipt_students'));
    }

    public function show($id)
    {
        $student = Student::findorfail($id);
        return view('pages.Receipt.add', compact('student'));
    }

    public function edit($id)
    {
        $receipt_student = ReceiptStudent::findorfail($id);
        return view('pages.Receipt.edit', compact('receipt_student'));
    }

    public function store($request)
    {
        DB::beginTransaction();

        try {
            $amount = $this->resolveAmount($request);
            $studentInstallmentId = $request->student_installment_id ?? $request->input('student_installment_id');

            $this->validateInstallment($studentInstallmentId, $request->student_id);

            $receipt_students = new ReceiptStudent();
            $receipt_students->date = date('Y-m-d');
            $receipt_students->student_id = $request->student_id;
            $receipt_students->student_installment_id = $studentInstallmentId;
            $receipt_students->Debit = $amount;
            $receipt_students->description = $request->description;
            $receipt_students->save();

            $fund_accounts = new FundAccount();
            $fund_accounts->date = date('Y-m-d');
            $fund_accounts->receipt_id = $receipt_students->id;
            $fund_accounts->Debit = $amount;
            $fund_accounts->credit = 0.00;
            $fund_accounts->description = $request->description;
            $fund_accounts->save();

            $fund_accounts = new StudentAccount();
            $fund_accounts->date = date('Y-m-d');
            $fund_accounts->type = 'receipt';
            $fund_accounts->receipt_id = $receipt_students->id;
            $fund_accounts->student_installment_id = $studentInstallmentId;
            $fund_accounts->student_id = $request->student_id;
            $fund_accounts->Debit = 0.00;
            $fund_accounts->credit = $amount;
            $fund_accounts->description = $request->description;
            $fund_accounts->save();

            if ($studentInstallmentId) {
                $studentInstallment = StudentInstallment::find($studentInstallmentId);
                if ($studentInstallment) {
                    $this->studentInstallmentRepository->updatePaymentStatus($studentInstallment);
                }
            }

            DB::commit();
            toastr()->success(trans('messages.success'));
            return redirect()->route('receipt_students.index');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function update($request)
    {
        DB::beginTransaction();

        try {
            $amount = $this->resolveAmount($request);
            $studentInstallmentId = $request->student_installment_id ?? $request->input('student_installment_id');

            $receipt_students = ReceiptStudent::findorfail($request->id);
            $oldStudentInstallmentId = $receipt_students->student_installment_id;

            $this->validateInstallment($studentInstallmentId, $request->student_id);

            $receipt_students->date = date('Y-m-d');
            $receipt_students->student_id = $request->student_id;
            $receipt_students->student_installment_id = $studentInstallmentId;
            $receipt_students->Debit = $amount;
            $receipt_students->description = $request->description;
            $receipt_students->save();

            $fund_accounts = FundAccount::where('receipt_id', $request->id)->first();
            $fund_accounts->date = date('Y-m-d');
            $fund_accounts->Debit = $amount;
            $fund_accounts->credit = 0.00;
            $fund_accounts->description = $request->description;
            $fund_accounts->save();

            $fund_accounts = StudentAccount::where('receipt_id', $request->id)->first();
            $fund_accounts->date = date('Y-m-d');
            $fund_accounts->type = 'receipt';
            $fund_accounts->student_id = $request->student_id;
            $fund_accounts->student_installment_id = $studentInstallmentId;
            $fund_accounts->Debit = 0.00;
            $fund_accounts->credit = $amount;
            $fund_accounts->description = $request->description;
            $fund_accounts->save();

            if ($studentInstallmentId) {
                $studentInstallment = StudentInstallment::find($studentInstallmentId);
                if ($studentInstallment) {
                    $this->studentInstallmentRepository->updatePaymentStatus($studentInstallment);
                }
            }

            if ($oldStudentInstallmentId && $oldStudentInstallmentId != $studentInstallmentId) {
                $oldStudentInstallment = StudentInstallment::find($oldStudentInstallmentId);
                if ($oldStudentInstallment) {
                    $this->studentInstallmentRepository->updatePaymentStatus($oldStudentInstallment);
                }
            }

            DB::commit();
            toastr()->success(trans('messages.Update'));
            return redirect()->route('receipt_students.index');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroy($request)
    {
        try {
            $receipt = ReceiptStudent::findorfail($request->id);
            $studentInstallmentId = $receipt->student_installment_id;
            $receipt->delete();

            if ($studentInstallmentId) {
                $studentInstallment = StudentInstallment::find($studentInstallmentId);
                if ($studentInstallment) {
                    $this->studentInstallmentRepository->updatePaymentStatus($studentInstallment);
                }
            }

            toastr()->error(trans('messages.Delete'));
            return redirect()->back();
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    private function resolveAmount($request)
    {
        if ($request->has('Debit')) {
            return $request->Debit;
        }

        if ($request->has('debit')) {
            return $request->debit;
        }

        return $request->amount ?? 0;
    }

    private function validateInstallment($studentInstallmentId, $studentId)
    {
        if (! $studentInstallmentId) {
            return;
        }

        $installment = StudentInstallment::where('id', $studentInstallmentId)
            ->where('student_id', $studentId)
            ->first();

        if (! $installment) {
            throw new \Exception(__('messages.invalid_student_installment'));
        }
    }
}
