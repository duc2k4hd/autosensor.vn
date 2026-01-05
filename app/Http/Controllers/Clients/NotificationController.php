<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {
        $this->middleware('auth:web');
    }

    /**
     * Danh sách thông báo của user
     */
    public function index(Request $request): View|JsonResponse
    {
        $accountId = Auth::id();
        $unreadOnly = $request->boolean('unread_only', false);
        $type = $request->get('type');

        $notifications = $this->notificationService->getNotifications($accountId, 50, $unreadOnly);

        if ($type) {
            $notifications = $notifications->filter(fn ($n) => $n->type === $type);
        }

        $unreadCount = $this->notificationService->getUnreadCount($accountId);

        if ($request->wantsJson()) {
            return response()->json([
                'notifications' => $notifications,
                'unread_count' => $unreadCount,
            ]);
        }

        return view('clients.notifications.index', compact('notifications', 'unreadCount'));
    }

    /**
     * Đánh dấu đã đọc
     */
    public function markAsRead(int $id): JsonResponse
    {
        $accountId = Auth::id();
        $success = $this->notificationService->markAsRead($id, $accountId);

        if ($success) {
            $unreadCount = $this->notificationService->getUnreadCount($accountId);

            return response()->json([
                'success' => true,
                'unread_count' => $unreadCount,
            ]);
        }

        return response()->json(['success' => false], 403);
    }

    /**
     * Đánh dấu tất cả đã đọc
     */
    public function markAllAsRead(): JsonResponse
    {
        $accountId = Auth::id();
        $count = $this->notificationService->markAllAsRead($accountId);

        return response()->json([
            'success' => true,
            'count' => $count,
            'unread_count' => 0,
        ]);
    }

    /**
     * Xóa thông báo
     */
    public function destroy(int $id): JsonResponse|RedirectResponse
    {
        $accountId = Auth::id();
        $success = $this->notificationService->delete($id, $accountId);

        if (request()->wantsJson()) {
            return response()->json(['success' => $success]);
        }

        return redirect()->back()->with($success ? 'success' : 'error', $success ? 'Đã xóa thông báo' : 'Không thể xóa thông báo');
    }

    /**
     * Xóa tất cả thông báo đã đọc
     */
    public function deleteRead(): JsonResponse|RedirectResponse
    {
        $accountId = Auth::id();
        $count = $this->notificationService->deleteRead($accountId);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'count' => $count,
            ]);
        }

        return redirect()->back()->with('success', "Đã xóa {$count} thông báo đã đọc");
    }

    /**
     * Lấy số lượng thông báo chưa đọc (API)
     */
    public function unreadCount(): JsonResponse
    {
        $accountId = Auth::id();
        $count = $this->notificationService->getUnreadCount($accountId);

        return response()->json(['count' => $count]);
    }

    /**
     * Lấy danh sách thông báo mới nhất (API)
     */
    public function latest(): JsonResponse
    {
        $accountId = Auth::id();
        $notifications = $this->notificationService->getNotifications($accountId, 10, true);

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $this->notificationService->getUnreadCount($accountId),
        ]);
    }
}
