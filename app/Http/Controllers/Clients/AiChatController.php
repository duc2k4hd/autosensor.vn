<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Http\Requests\AiChatRequest;
use App\Services\AiAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class AiChatController extends Controller
{
    public function __construct(
        private AiAssistantService $aiAssistantService
    ) {}

    public function __invoke(AiChatRequest $request): JsonResponse
    {
        $ip = $request->ip();
        $ipKeyPerMinute = 'ai-chat-per-minute:'.$ip;
        $ipKeyPerDay = 'ai-chat-per-day:'.$ip;

        // Kiểm tra giới hạn 5 lần/phút
        if (RateLimiter::tooManyAttempts($ipKeyPerMinute, 5)) {
            $seconds = RateLimiter::availableIn($ipKeyPerMinute);

            return response()->json([
                'success' => false,
                'message' => "Bạn đang hỏi quá nhanh. Vui lòng thử lại sau {$seconds} giây.",
            ], 429);
        }

        // Kiểm tra giới hạn 20 lần/ngày
        if (RateLimiter::tooManyAttempts($ipKeyPerDay, 20)) {
            $seconds = RateLimiter::availableIn($ipKeyPerDay);
            $minutes = (int) ceil($seconds / 60);
            $hours = (int) ceil($minutes / 60);

            $timeMessage = $hours > 0
                ? "{$hours} giờ"
                : ($minutes > 0 ? "{$minutes} phút" : "{$seconds} giây");

            return response()->json([
                'success' => false,
                'message' => "Bạn đã sử dụng hết 20 lượt hỏi trong ngày. Vui lòng thử lại sau {$timeMessage}.",
            ], 429);
        }

        // Tăng counter cho cả 2 rate limiters
        RateLimiter::hit($ipKeyPerMinute, 60); // 1 phút
        RateLimiter::hit($ipKeyPerDay, 86400); // 24 giờ (1 ngày)

        try {
            /** @var \App\Models\Account|null $account */
            $account = auth('web')->user();

            $result = $this->aiAssistantService->answer(
                trim((string) $request->validated('question')),
                $account,
                $request->sanitizedHistory()
            );

            return response()->json([
                'success' => true,
                'answer' => $result['answer'],
                'references' => $result['references'],
            ]);
        } catch (\Throwable $e) {
            Log::error('AI chat failed', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Trợ lý đang quá tải. Bạn vui lòng thử lại sau ít phút.',
            ], 500);
        }
    }
}
