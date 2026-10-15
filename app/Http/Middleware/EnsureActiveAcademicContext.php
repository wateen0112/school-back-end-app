<?php

namespace App\Http\Middleware;

use App\Services\Scheduling\AcademicContextService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Global academic-context guardrail: blocks operational endpoints unless an
 * active academic year with an active current term exists (HTTP 423).
 */
class EnsureActiveAcademicContext
{
    public function __construct(private AcademicContextService $context)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        try {
            $this->context->require();
        } catch (HttpException $e) {
            return response()->json([
                'success' => false,
                'locked' => true,
                'message' => $e->getMessage(),
            ], 423);
        }

        return $next($request);
    }
}
