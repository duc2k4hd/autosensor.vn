<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentStoreRequest;
use App\Models\Comment;
use App\Services\CommentService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class CommentController extends Controller
{
    public function __construct(
        protected CommentService $commentService,
        protected NotificationService $notificationService
    ) {}

    /**
     * Lấy danh sách comments theo type và object_id
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['required', 'string', 'in:product,post'],
            'object_id' => ['required', 'integer', 'min:1'],
        ]);

        $comments = Comment::where('commentable_type', $request->type)
            ->where('commentable_id', $request->object_id)
            ->whereNull('parent_id') // Chỉ lấy comment gốc, không lấy reply
            ->approved()
            ->with(['account', 'adminReply.account'])
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $comments,
        ]);
    }

    /**
     * Load thêm comments (load more)
     */
    public function loadMore(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['required', 'string', 'in:product,post'],
            'object_id' => ['required', 'integer', 'min:1'],
            'offset' => ['required', 'integer', 'min:0'],
        ]);

        $offset = $request->get('offset', 0);
        $limit = 10;

        $comments = Comment::where('commentable_type', $request->type)
            ->where('commentable_id', $request->object_id)
            ->whereNull('parent_id')
            ->approved()
            ->with(['account', 'adminReply.account'])
            ->orderByDesc('created_at')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $total = Comment::where('commentable_type', $request->type)
            ->where('commentable_id', $request->object_id)
            ->whereNull('parent_id')
            ->approved()
            ->count();

        $hasMore = ($offset + $limit) < $total;

        // Format comments for JSON response
        $formattedComments = $comments->map(function ($comment) {
            return [
                'id' => $comment->id,
                'name' => $comment->name,
                'content' => $comment->content,
                'rating' => $comment->rating,
                'created_at' => $comment->created_at->toIso8601String(),
                'account' => $comment->account ? [
                    'id' => $comment->account->id,
                    'name' => $comment->account->name,
                ] : null,
                'admin_reply' => $comment->adminReply ? [
                    'id' => $comment->adminReply->id,
                    'content' => $comment->adminReply->content,
                    'created_at' => $comment->adminReply->created_at->toIso8601String(),
                    'account' => $comment->adminReply->account ? [
                        'id' => $comment->adminReply->account->id,
                        'name' => $comment->adminReply->account->name,
                    ] : null,
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedComments,
            'hasMore' => $hasMore,
            'nextOffset' => $hasMore ? $offset + $limit : null,
            'total' => $total,
        ]);
    }

    /**
     * Lấy rating statistics
     */
    public function ratingStats(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['required', 'string', 'in:product,post'],
            'object_id' => ['required', 'integer', 'min:1'],
        ]);

        $stats = $this->commentService->calculateRatingStats(
            $request->type,
            $request->object_id
        );

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Tạo comment mới
     */
    public function store(CommentStoreRequest $request): JsonResponse
    {
        $ip = $request->ip();
        $type = $request->validated()['type'];
        $objectId = $request->validated()['object_id'];
        $account = auth('web')->user();

        // Giới hạn cứng: 1 người dùng (hoặc 1 IP) chỉ được comment tối đa 5 lần cho 1 product/post cụ thể
        $query = Comment::where('commentable_type', $type)
            ->where('commentable_id', $objectId)
            ->whereNull('parent_id');

        // Nếu có đăng nhập, kiểm tra theo account_id, nếu không thì kiểm tra theo IP
        if ($account) {
            $totalComments = (clone $query)->where('account_id', $account->id)->count();
        } else {
            $totalComments = (clone $query)->where('ip', $ip)->count();
        }

        if ($totalComments >= 5) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn đã đạt giới hạn tối đa 5 bình luận cho '.($type === 'product' ? 'sản phẩm' : 'bài viết').' này.',
            ], 429);
        }

        // Rate limiting mềm: tối đa 5 comments mỗi 15 phút từ cùng IP cho cùng product/post
        $rateLimitKey = 'comment:'.$type.':'.$objectId.':'.$ip;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return response()->json([
                'success' => false,
                'message' => "Bạn đã gửi quá nhiều bình luận. Vui lòng thử lại sau {$seconds} giây.",
            ], 429);
        }

        RateLimiter::hit($rateLimitKey, 900); // 15 phút = 900 giây

        try {
            $comment = $this->commentService->create($request->validated(), $account);

            // Gửi thông báo cho admin về comment mới
            $commenterName = $account?->name ?? $request->validated()['name'];
            $this->notificationService->notifyNewComment(
                $comment->id,
                $request->validated()['type'],
                $request->validated()['object_id'],
                $commenterName
            );

            return response()->json([
                'success' => true,
                'message' => 'Bình luận của bạn đã được gửi và đang chờ duyệt.',
                'data' => $comment,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi gửi bình luận. Vui lòng thử lại.',
            ], 500);
        }
    }
}
