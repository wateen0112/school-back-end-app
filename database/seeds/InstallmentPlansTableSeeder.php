<?php

use App\Models\Classroom;
use App\Models\Fee;
use App\Models\Fee_invoice;
use App\Models\Installment;
use App\Models\InstallmentPlan;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Models\StudentInstallment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InstallmentPlansTableSeeder extends Seeder
{
    /**
     * Create 3 installment plans for different classrooms.
     *
     * Each plan:
     * - mirrors the classroom fee as total_amount
     * - splits the total into 3-4 installments across the academic year
     * - generates StudentInstallment records for every student in the classroom
     * - generates one Fee_invoice + StudentAccount record per student
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('student_installments')->delete();
        DB::table('installments')->delete();
        DB::table('installment_plans')->delete();
        DB::table('student_accounts')->delete();
        DB::table('fee_invoices')->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $academicYear = '2026-2027';
        $classrooms = Classroom::with('grade')->get();
        $fees = Fee::all()->keyBy('Classroom_id');

        // Pick 3 different classrooms for installment plans.
        $selectedClassrooms = $classrooms->shuffle()->take(3)->values();

        foreach ($selectedClassrooms as $classroom) {
            $fee = $fees[$classroom->id] ?? null;

            if (! $fee) {
                continue;
            }

            $totalAmount = (float) $fee->amount;
            $students = Student::where('Classroom_id', $classroom->id)->get();

            if ($students->isEmpty()) {
                continue;
            }

            $classroomNameEn = $classroom->getTranslation('Name_Class', 'en');
            $classroomNameAr = $classroom->getTranslation('Name_Class', 'ar');

            $plan = InstallmentPlan::create([
                'title' => [
                    'en' => "Installment Plan - {$classroomNameEn}",
                    'ar' => "خطة تقسيط - {$classroomNameAr}",
                ],
                'description' => [
                    'en' => 'Annual tuition installment plan',
                    'ar' => 'خطة تقسيط الرسوم الدراسية السنوية',
                ],
                'grade_id' => $classroom->Grade_id,
                'classroom_id' => $classroom->id,
                'fee_id' => $fee->id,
                'total_amount' => $totalAmount,
                'academic_year' => $academicYear,
                'start_date' => '2026-09-01',
                'end_date' => '2027-06-01',
                'is_active' => true,
            ]);

            $installmentCount = rand(3, 4);
            $baseAmount = round($totalAmount / $installmentCount, 2);
            $installmentAmounts = [];
            $sum = 0;

            for ($i = 0; $i < $installmentCount - 1; $i++) {
                $installmentAmounts[] = $baseAmount;
                $sum += $baseAmount;
            }

            // Last installment absorbs any rounding remainder.
            $installmentAmounts[] = round($totalAmount - $sum, 2);

            $dueDates = [
                '2026-05-01',
                '2026-06-15',
                now()->addDays(2)->toDateString(),
                now()->addDays(5)->toDateString(),
            ];

            $installments = [];
            foreach ($installmentAmounts as $index => $amount) {
                $installments[] = Installment::create([
                    'installment_plan_id' => $plan->id,
                    'title' => 'Installment ' . ($index + 1),
                    'amount' => $amount,
                    'due_date' => $dueDates[$index],
                    'sort_order' => $index + 1,
                    'description' => null,
                ]);
            }

            foreach ($students as $student) {
                $feeInvoice = Fee_invoice::create([
                    'invoice_date' => '2026-09-01',
                    'student_id' => $student->id,
                    'Grade_id' => $student->Grade_id,
                    'Classroom_id' => $student->Classroom_id,
                    'fee_id' => $fee->id,
                    'amount' => $totalAmount,
                    'description' => 'Annual tuition fee',
                ]);

                StudentAccount::create([
                    'date' => '2026-09-01',
                    'type' => 'invoice',
                    'fee_invoice_id' => $feeInvoice->id,
                    'receipt_id' => null,
                    'processing_id' => null,
                    'payment_id' => null,
                    'student_id' => $student->id,
                    'Debit' => $totalAmount,
                    'credit' => 0,
                    'description' => 'Annual tuition fee debit',
                ]);

                foreach ($installments as $installment) {
                    StudentInstallment::create([
                        'installment_plan_id' => $plan->id,
                        'installment_id' => $installment->id,
                        'student_id' => $student->id,
                        'fee_invoice_id' => $feeInvoice->id,
                        'amount' => $installment->amount,
                        'paid_amount' => 0,
                        'remaining_amount' => $installment->amount,
                        'status' => 'pending',
                        'due_date' => $installment->due_date,
                    ]);
                }
            }
        }
    }
}
