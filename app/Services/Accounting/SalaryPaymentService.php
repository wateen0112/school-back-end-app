<?php

namespace App\Services\Accounting;

use App\Models\SalaryPayment;
use App\Support\CacheVersion;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Domain operations on salary payments.
 *
 * Kept out of the controller so the approval flow can replay the exact same
 * logic when an admin approves a queued "pay salary" request
 * (see App\Services\Pending\PendingActionRegistry).
 */
class SalaryPaymentService
{
    /**
     * Mark a pending salary as paid and write the matching cash-out entry.
     *
     * @throws ConflictHttpException when the payment is no longer payable.
     */
    public function pay(SalaryPayment $payment, array $data = [], ?int $actorId = null): SalaryPayment
    {
        if ($payment->status === 'paid') {
            throw new ConflictHttpException('This salary has already been paid');
        }

        if ($payment->status === 'cancelled') {
            throw new ConflictHttpException('This salary entry was cancelled');
        }

        DB::beginTransaction();

        try {
            $payment->update([
                'status'         => 'paid',
                'paid_at'        => $data['paid_at'] ?? now()->toDateString(),
                'payment_method' => $data['payment_method'] ?? $payment->payment_method,
                'notes'          => $data['notes'] ?? $payment->notes,
                'paid_by'        => $actorId,
            ]);

            // Custom-contract installments are settled with their payment.
            if ($payment->installment_id) {
                $payment->installment()->update(['status' => 'paid']);
            }

            $payment->fundEntry()->create([
                'date'        => $payment->paid_at,
                'credit'      => $payment->amount,
                'description' => __('messages.salary_payment_description', [
                    'name' => $payment->employee_name,
                    'month' => $payment->period_month,
                    'year' => $payment->period_year,
                ]),
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        CacheVersion::bump('accounting');

        return $payment->fresh();
    }
}