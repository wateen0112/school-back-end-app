<?php

namespace App\Services\Scheduling;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Resolves the active academic context (current year + current term) and
 * enforces it: operational actions are locked (423) without it.
 */
class AcademicContextService
{
    /** @return array{year: AcademicYear, term: AcademicTerm}|null */
    public function context(): ?array
    {
        return AcademicYear::activeContext();
    }

    public function isLocked(): bool
    {
        return $this->context() === null;
    }

    /** @return array{year: AcademicYear, term: AcademicTerm} */
    public function require(): array
    {
        $context = $this->context();

        if (! $context) {
            throw new HttpException(
                423,
                app()->getLocale() === 'ar'
                    ? 'العملية محظورة: لا توجد سنة دراسية فعالة مع فصل دراسي فعال.'
                    : 'Action prohibited. No active academic year/term found.'
            );
        }

        return $context;
    }

    /**
     * Validates an explicitly submitted term binding against the active
     * context. Term must be the active term and not completed.
     */
    public function validateTermBinding(?int $termId): AcademicTerm
    {
        $context = $this->require();
        $term = $context['term'];

        if ($termId !== null && (int) $termId !== (int) $term->id) {
            throw new HttpException(
                422,
                app()->getLocale() === 'ar'
                    ? 'الفصل الدراسي المحدد لا يطابق الفصل الدراسي الفعال.'
                    : 'Exam term ID mismatch: the submitted term is not the active term.'
            );
        }

        return $term;
    }
}
