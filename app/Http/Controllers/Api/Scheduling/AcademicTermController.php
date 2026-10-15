<?php

namespace App\Http\Controllers\Api\Scheduling;

use App\Http\Controllers\Controller;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Services\Scheduling\AcademicContextService;
use App\Services\Scheduling\AcademicTermService;
use Illuminate\Http\Request;

class AcademicTermController extends Controller
{
    public function __construct(
        private AcademicTermService $terms,
        private AcademicContextService $context,
    ) {
    }

    public function index(AcademicYear $academicYear)
    {
        return response()->json([
            'success' => true,
            'message' => 'Academic terms retrieved successfully',
            'data' => $academicYear->terms()->orderBy('term_code')->get(),
        ], 200);
    }

    /** Promote a term to the active current term of its year. */
    public function setCurrent(AcademicTerm $academicTerm)
    {
        try {
            $term = $this->terms->setCurrentTerm($academicTerm);

            return response()->json([
                'success' => true,
                'message' => app()->getLocale() === 'ar'
                    ? 'تم تفعيل الفصل الدراسي بنجاح'
                    : 'Academic term activated successfully',
                'data' => $term,
            ], 200);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getStatusCode());
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error activating term', 'error' => $e->getMessage()], 500);
        }
    }

    /** Current active context for the top-bar switcher / lock banner. */
    public function context()
    {
        $context = $this->context->context();

        return response()->json([
            'success' => true,
            'locked' => $context === null,
            'message' => $context === null
                ? (app()->getLocale() === 'ar'
                    ? 'العمليات مقفلة حتى يتم ضبط السنة والفصل الدراسيين الفعالين.'
                    : 'System operations are locked until the active academic year and terms are configured.')
                : null,
            'data' => $context ? [
                'academic_year' => [
                    'id' => $context['year']->id,
                    'name' => $context['year']->getTranslations('name'),
                    'start_date' => $context['year']->start_date?->toDateString(),
                    'end_date' => $context['year']->end_date?->toDateString(),
                ],
                'academic_term' => [
                    'id' => $context['term']->id,
                    'name' => $context['term']->getTranslations('name'),
                    'term_code' => $context['term']->term_code,
                    'start_date' => $context['term']->start_date?->toDateString(),
                    'end_date' => $context['term']->end_date?->toDateString(),
                ],
            ] : null,
        ], 200);
    }
}
