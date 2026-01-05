<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommentReplyRequest;
use App\Models\Account;
use App\Models\Comment;
use App\Services\CommentService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminCommentController extends Controller
{
    public function __construct(
        protected CommentService $commentService,
        protected NotificationService $notificationService
    ) {}

    /**
     * Danh sách comments với filter
     */
    public function index(Request $request): View
    {
        $query = Comment::onlyRoot()
            ->with(['account', 'commentable', 'adminReply.account']);

        // Filter theo type
        if ($request->filled('type')) {
            $query->filterType($request->type);
        }

        // Filter theo object_id
        if ($request->filled('object_id')) {
            $query->filterObjectId($request->object_id);
        }

        // Filter theo rating
        if ($request->filled('rating')) {
            $query->filterRating($request->rating);
        }

        // Filter theo trạng thái duyệt
        if ($request->filled('status')) {
            $query->filterStatus($request->status);
        }

        // Search
        if ($request->filled('search')) {
            $query->filterSearch($request->search);
        }

        $comments = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        // Tính tổng rating statistics
        $totalStats = Comment::onlyRoot()
            ->whereNotNull('rating')
            ->approved()
            ->selectRaw('
                COUNT(*) as total_comments,
                AVG(rating) as average_rating,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as star_1_count,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as star_2_count,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as star_3_count,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as star_4_count,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as star_5_count
            ')
            ->first();

        $stats = [
            'total_comments' => (int) ($totalStats->total_comments ?? 0),
            'average_rating' => round((float) ($totalStats->average_rating ?? 0), 2),
            'star_1_count' => (int) ($totalStats->star_1_count ?? 0),
            'star_2_count' => (int) ($totalStats->star_2_count ?? 0),
            'star_3_count' => (int) ($totalStats->star_3_count ?? 0),
            'star_4_count' => (int) ($totalStats->star_4_count ?? 0),
            'star_5_count' => (int) ($totalStats->star_5_count ?? 0),
        ];

        return view('admins.comments.index', [
            'comments' => $comments,
            'stats' => $stats,
            'filters' => $request->only(['type', 'object_id', 'rating', 'status', 'search']),
        ]);
    }

    /**
     * Xem chi tiết comment
     */
    public function show(int $id): View
    {
        $comment = Comment::with(['account', 'commentable', 'adminReply.account', 'replies'])
            ->findOrFail($id);

        return view('admins.comments.show', [
            'comment' => $comment,
        ]);
    }

    /**
     * Duyệt comment
     */
    public function approve(int $id): RedirectResponse
    {
        try {
            $comment = Comment::findOrFail($id);
            $this->commentService->approve($id);

            // Gửi thông báo cho người dùng khi comment được duyệt
            if ($comment->account_id) {
                $this->notificationService->notifyCommentApproved(
                    $comment->account_id,
                    $comment->id,
                    $comment->type,
                    $comment->object_id
                );
            }

            return redirect()->back()->with('success', 'Đã duyệt bình luận thành công.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Có lỗi xảy ra: '.$e->getMessage());
        }
    }

    /**
     * Hủy duyệt comment
     */
    public function reject(int $id): RedirectResponse
    {
        try {
            $this->commentService->reject($id);

            return redirect()->back()->with('success', 'Đã hủy duyệt bình luận thành công.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Có lỗi xảy ra: '.$e->getMessage());
        }
    }

    /**
     * Reply comment
     */
    public function reply(int $id, CommentReplyRequest $request): RedirectResponse
    {
        try {
            /** @var Account|null $admin */
            $admin = Auth::user();

            if (! $admin || ! in_array($admin->role, ['admin', 'writer'])) {
                return redirect()->back()->with('error', 'Bạn không có quyền thực hiện hành động này.');
            }

            $this->commentService->reply($id, $request->reply_content, $admin);

            return redirect()->back()->with('success', 'Đã trả lời bình luận thành công.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Có lỗi xảy ra: '.$e->getMessage());
        }
    }

    /**
     * Cập nhật reply
     */
    public function updateReply(int $id, CommentReplyRequest $request): RedirectResponse
    {
        try {
            /** @var Account|null $admin */
            $admin = Auth::user();

            if (! $admin || ! in_array($admin->role, ['admin', 'writer'])) {
                return redirect()->back()->with('error', 'Bạn không có quyền thực hiện hành động này.');
            }

            $this->commentService->updateReply($id, $request->reply_content, $admin);

            return redirect()->back()->with('success', 'Đã cập nhật trả lời thành công.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Có lỗi xảy ra: '.$e->getMessage());
        }
    }

    /**
     * Xóa reply
     */
    public function deleteReply(int $id): RedirectResponse
    {
        try {
            $this->commentService->deleteReply($id);

            return redirect()->back()->with('success', 'Đã xóa trả lời thành công.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Có lỗi xảy ra: '.$e->getMessage());
        }
    }

    /**
     * Xóa comment
     */
    public function destroy(int $id): RedirectResponse
    {
        try {
            $this->commentService->delete($id);

            return redirect()->route('admin.comments.index')
                ->with('success', 'Đã xóa bình luận thành công.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Có lỗi xảy ra: '.$e->getMessage());
        }
    }
}
