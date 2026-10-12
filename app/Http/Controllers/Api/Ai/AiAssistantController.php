<?php

namespace App\Http\Controllers\Api\Ai;

use App\Http\Controllers\Controller;
use App\Services\Ai\AiNotConfiguredException;
use App\Services\Ai\AssistantService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiAssistantController extends Controller
{
    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'history' => 'nullable|array|max:50',
            'history.*.role' => 'required_with:history|string|in:user,assistant',
            'history.*.content' => 'required_with:history|string|max:500',
        ]);

        $service = app(AssistantService::class);
        if (! $service->isConfigured()) {
            return ApiResponse::error(
                'المساعد الذكي غير مُعَدّ حالياً. يرجى تكوين مفتاح الذكاء الاصطناعي في إعدادات المنصة.',
                503,
                'ai_not_configured'
            );
        }

        try {
            $result = $service->send($request->input('message'), $request->input('history', []));

            return ApiResponse::ok($result, 'تم استلام رد المساعد الذكي');
        } catch (AiNotConfiguredException $e) {
            return ApiResponse::error($e->getMessage(), 503, 'ai_not_configured');
        } catch (\Throwable $e) {
            $detail = $e->getMessage();

            if (str_contains($detail, 'location is not supported') || str_contains($detail, 'FAILED_PRECONDITION')) {
                return ApiResponse::error(
                    'خدمة الذكاء الاصطناعي غير متوفرة في منطقتك الجغرافية. يمكنك استخدام بروكسي أو تغيير مزود الخدمة.',
                    503,
                    $detail,
                    ['code' => 'region_blocked']
                );
            }

            if (str_contains($detail, 'quota') || str_contains($detail, '429')) {
                return ApiResponse::error(
                    'تم تجاوز حصة الاستخدام لمفتاح الذكاء الاصطناعي أو أن الخطة التجريبية غير مفعّلة. تفقّد لوحة مزود الخدمة أو اشترك في خطة مدفوعة.',
                    429,
                    $detail,
                    ['code' => 'quota_exceeded']
                );
            }

            if (str_contains($detail, 'no longer available') || str_contains($detail, 'not found') || str_contains($detail, 'does not exist') || str_contains($detail, '404') || str_contains($detail, 'model')) {
                return ApiResponse::error(
                    'نموذج الذكاء الاصطناعي المُحدَّد غير متاح حالياً. تحقق من إعدادات النموذج في ملف الإعدادات.',
                    404,
                    $detail,
                    ['code' => 'model_unavailable']
                );
            }

            return ApiResponse::error('حدث خطأ أثناء الاتصال بالمساعد الذكي.', 500, $detail, ['code' => 'ai_request_failed']);
        }
    }

    public function questions(): JsonResponse
    {
        $service = app(AssistantService::class);
        if (! $service->isConfigured()) {
            return ApiResponse::ok(['questions' => []], 'لا توجد أسئلة مقترحة');
        }

        return ApiResponse::ok(['questions' => $service->suggestedQuestions()], 'تم استلام الأسئلة المقترحة');
    }
}