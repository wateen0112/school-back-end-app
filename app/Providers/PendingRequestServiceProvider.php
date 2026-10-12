<?php

namespace App\Providers;

use App\Models\PaperExam;
use App\Models\PendingRequest;
use App\Models\SalaryPayment;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\Accounting\SalaryPaymentService;
use App\Services\Exams\PaperExamPublishingService;
use App\Services\Pending\PendingActionRegistry;
use App\Services\Pending\PendingRequestService;
use App\Services\StudentReportService;
use App\Services\Teachers\TeacherProfileService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Attaches the executable half of every sensitive action.
 *
 * Each executor is the exact code path the matching endpoint runs when the
 * caller *is* an approver, so approving a queued request behaves identically to
 * performing the action directly. They run inside the transaction opened by
 * PendingRequestService::apply(), so they must not open their own.
 */
class PendingRequestServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerExecutors();
    }

    private function registerExecutors(): void
    {
        PendingActionRegistry::executor(PendingActionRegistry::PAY_SALARY, function (PendingRequest $request, ?Model $actor) {
            $payment = $this->resolveEntity($request, SalaryPayment::class);

            (new SalaryPaymentService())->pay($payment, (array) ($request->payload ?? []), $actor?->getKey());

            return ['entity_type' => SalaryPayment::class, 'entity_id' => $payment->getKey()];
        });

        PendingActionRegistry::executor(PendingActionRegistry::SEND_MARKS, function (PendingRequest $request, ?Model $actor) {
            $exam = $this->resolveEntity($request, PaperExam::class);

            (new PaperExamPublishingService())->publish($exam, $actor);

            return ['entity_type' => PaperExam::class, 'entity_id' => $exam->getKey()];
        });

        PendingActionRegistry::executor(PendingActionRegistry::SEND_REPORT, function (PendingRequest $request, ?Model $actor) {
            $payload = (array) ($request->payload ?? []);
            $month = (int) ($payload['month'] ?? now()->month);
            $year = (int) ($payload['year'] ?? now()->year);
            $studentId = $payload['student_id'] ?? null;

            $service = new StudentReportService();

            // Scoped to the request's school: an approval for "everyone" must
            // never reach another school's students. A single-student request is
            // re-checked here too, because it may have been queued before the
            // controller validated the school.
            if ($studentId) {
                $student = Student::where('school_id', $request->school_id)
                    ->whereKey((int) $studentId)
                    ->first();

                if (! $student) {
                    throw new ConflictHttpException('The student this request targets is not in this school.');
                }

                $result = ['student' => $service->generateForStudent($student->getKey(), $month, $year)];
            } else {
                $result = $service->generateForAll($month, $year, true, $request->school_id);
            }

            return ['month' => $month, 'year' => $year, 'result' => $result];
        });

        PendingActionRegistry::executor(PendingActionRegistry::UPDATE_TEACHER, function (PendingRequest $request, ?Model $actor) {
            $teacher = $this->resolveEntity($request, Teacher::class);

            (new TeacherProfileService())->apply($teacher, (array) ($request->payload ?? []), $actor);

            return ['entity_type' => Teacher::class, 'entity_id' => $teacher->getKey()];
        });
    }

    /**
     * Load the record the request targets.
     *
     * A request whose target disappeared between submission and review is a
     * conflict, not a crash: the approver sees 409 and the request stays open.
     *
     * @template T of Model
     *
     * @param  class-string<T>  $expected
     * @return T
     */
    private function resolveEntity(PendingRequest $request, string $expected): Model
    {
        if ($request->entity_type !== $expected || ! $request->entity_id) {
            throw new ConflictHttpException('This request no longer points at a valid record.');
        }

        $model = $expected::find($request->entity_id);

        if (! $model) {
            throw new ConflictHttpException('The requested record no longer exists.');
        }

        return $model;
    }
}