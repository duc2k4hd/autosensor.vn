<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NewsletterFilterRequest;
use App\Http\Requests\Admin\NewsletterSendBulkRequest;
use App\Http\Requests\Admin\NewsletterStatusUpdateRequest;
use App\Models\Account;
use App\Models\AccountLog;
use App\Models\Newsletter;
use App\Models\NewsletterCampaign;
use App\Services\AccountLogService;
use App\Services\NewsletterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class AdminNewsletterController extends Controller
{
    public function __construct(
        protected NewsletterService $newsletterService,
        protected AccountLogService $accountLogService
    ) {}

    /**
     * Danh sách newsletter subscriptions
     */
    public function index(NewsletterFilterRequest $request): View
    {
        $query = Newsletter::query();

        $query->filterStatus($request->input('status'))
            ->filterSource($request->input('source'))
            ->dateRange($request->input('date_from'), $request->input('date_to'))
            ->search($request->input('search'));

        $subscriptions = $query->latest()->paginate(20);

        // Thống kê
        $stats = [
            'total' => Newsletter::count(),
            'subscribed' => Newsletter::subscribed()->count(),
            'pending' => Newsletter::pending()->count(),
            'unsubscribed' => Newsletter::unsubscribed()->count(),
        ];

        // Danh sách sources
        $sources = Newsletter::select('source')
            ->whereNotNull('source')
            ->distinct()
            ->pluck('source')
            ->filter();

        return view('admins.newsletters.index', compact('subscriptions', 'stats', 'sources'));
    }

    /**
     * Chi tiết subscription
     */
    public function show($id): View
    {
        $subscription = Newsletter::findOrFail($id);

        // Lấy logs liên quan từ account_logs nếu có email giống nhau
        $relatedLogs = AccountLog::where('payload->email', $subscription->email)
            ->orWhere(function ($query) use ($subscription) {
                $query->where('type', 'like', 'newsletter.%')
                    ->whereJsonContains('payload->meta->email', $subscription->email);
            })
            ->latest()
            ->limit(50)
            ->get();

        return view('admins.newsletters.show', compact('subscription', 'relatedLogs'));
    }

    /**
     * Xóa subscription
     */
    public function destroy($id): JsonResponse|RedirectResponse
    {
        $subscription = Newsletter::findOrFail($id);
        $email = $subscription->email;

        $subscription->delete();

        // Log - chỉ log nếu có account tương ứng với email
        $account = Account::where('email', $email)->first();
        if ($account) {
        $this->accountLogService->record(
            'newsletter.deleted',
                $account->id,
            null,
            [],
            ['email' => $email],
            false
        );
        }

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã xóa đăng ký newsletter thành công.',
            ]);
        }

        return redirect()->route('admin.newsletters.index')
            ->with('success', 'Đã xóa đăng ký newsletter thành công.');
    }

    /**
     * Thay đổi trạng thái
     */
    public function changeStatus(
        $id,
        NewsletterStatusUpdateRequest $request
    ): JsonResponse|RedirectResponse {
        $subscription = Newsletter::findOrFail($id);
        $oldStatus = $subscription->status;
        $newStatus = $request->input('status');

        $subscription->update([
            'status' => $newStatus,
            'note' => $request->input('note'),
        ]);

        // Nếu chuyển sang subscribed và chưa verify, đánh dấu đã verify
        if ($newStatus === 'subscribed' && ! $subscription->verified_at) {
            $subscription->update(['verified_at' => now()]);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật trạng thái thành công.',
                'subscription' => $subscription->fresh(),
            ]);
        }

        return redirect()->back()
            ->with('success', 'Đã cập nhật trạng thái thành công.');
    }

    /**
     * Gửi lại email xác nhận
     */
    public function resendVerifyEmail($id): JsonResponse|RedirectResponse
    {
        // Rate limiting: 5 phút
        $key = 'newsletter_resend_'.$id;
        if (RateLimiter::tooManyAttempts($key, 1)) {
            $seconds = RateLimiter::availableIn($key);
            $minutes = ceil($seconds / 60);
            $message = "Vui lòng đợi {$minutes} phút trước khi gửi lại email.";

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 429);
            }

            return redirect()->back()->with('error', $message);
        }

        RateLimiter::hit($key, 300); // 5 phút (300 giây)

        $subscription = Newsletter::findOrFail($id);

        if ($subscription->status === 'unsubscribed') {
            $message = 'Không thể gửi email xác nhận cho người đã hủy đăng ký.';

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 400);
            }

            return redirect()->back()->with('error', $message);
        }

        // Tạo token mới nếu chưa có
        if (! $subscription->verify_token) {
            $subscription->generateVerifyToken();
        }

        // Gửi email
        try {
            $this->newsletterService->sendVerifyEmail($subscription);

            $message = 'Đã gửi lại email xác nhận thành công.';

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            $message = 'Có lỗi xảy ra khi gửi email: '.$e->getMessage();

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 500);
            }

            return redirect()->back()->with('error', $message);
        }
    }

    /**
     * Gửi email hàng loạt
     */
    public function sendBulkEmail(NewsletterSendBulkRequest $request): JsonResponse|RedirectResponse
    {
        // Rate limiting cho chiến dịch
        $key = 'newsletter_campaign_'.auth('admin')->id();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $message = "Bạn đã gửi quá nhiều chiến dịch. Vui lòng đợi {$seconds} giây.";

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 429);
            }

            return redirect()->back()->with('error', $message);
        }

        RateLimiter::hit($key, 3600); // 1 giờ

        $query = Newsletter::subscribed();

        // Filter theo status
        if ($request->filled('filter_status') && $request->input('filter_status') !== 'all') {
            $query->where('status', $request->input('filter_status'));
        }

        // Filter theo source
        if ($request->filled('filter_source')) {
            $query->where('source', $request->input('filter_source'));
        }

        // Filter theo date range
        if ($request->filled('filter_date_from')) {
            $query->whereDate('created_at', '>=', $request->input('filter_date_from'));
        }
        if ($request->filled('filter_date_to')) {
            $query->whereDate('created_at', '<=', $request->input('filter_date_to'));
        }

        // Nếu có subscription_ids cụ thể
        if ($request->filled('subscription_ids') && is_array($request->input('subscription_ids'))) {
            $query->whereIn('id', $request->input('subscription_ids'));
        }

        $subscriptions = $query->get();
        $subscriptionIds = $subscriptions->pluck('id')->toArray();

        if (empty($subscriptionIds)) {
            $message = 'Không có người đăng ký nào phù hợp với bộ lọc.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 400);
            }

            return redirect()->back()->with('error', $message);
        }

        // Tạo bản ghi chiến dịch để lưu lịch sử
        $campaign = NewsletterCampaign::create([
            'name' => $request->input('campaign_name') ?: null,
            'subject' => $request->input('subject'),
            'content' => $request->input('content'),
            'cta_url' => $request->input('cta_url'),
            'cta_text' => $request->input('cta_text'),
            'footer' => $request->input('footer'),
            'filter_status' => $request->input('filter_status'),
            'filter_source' => $request->input('filter_source'),
            'filter_date_from' => $request->input('filter_date_from'),
            'filter_date_to' => $request->input('filter_date_to'),
            'total_target' => count($subscriptionIds),
            'sent_success' => 0,
            'sent_failed' => 0,
            'status' => 'sending',
            'created_by' => auth('admin')->id(),
        ]);

        // Gửi email
        try {
            // Prepare data for email template
            $emailData = [];
            if ($request->filled('content')) {
                $emailData['content'] = $request->input('content');
            }
            if ($request->filled('cta_url')) {
                $emailData['cta_url'] = $request->input('cta_url');
            }
            if ($request->filled('cta_text')) {
                $emailData['cta_text'] = $request->input('cta_text');
            }
            if ($request->filled('footer')) {
                $emailData['footer'] = $request->input('footer');
            }
            
            // Set email mặc định nếu không chọn từ form
            // Lưu ý: giá trị "0" vẫn là hợp lệ (sử dụng cấu hình .env), nên không dùng toán tử ?: ở đây
            if ($request->has('email_account_id') && $request->input('email_account_id') !== '') {
                $emailAccountId = (int) $request->input('email_account_id');
            } else {
                $emailAccountId = (int) (config('email_defaults.newsletter_marketing') ?? 0);
            }
            
            $results = $this->newsletterService->sendMarketingEmail(
                $subscriptionIds,
                $request->input('subject'),
                $request->input('template'),
                $emailData,
                $emailAccountId
            );

            // Cập nhật thống kê cho chiến dịch
            $campaign->update([
                'sent_success' => count($results['success']),
                'sent_failed' => count($results['failed']),
                'status' => 'completed',
            ]);

            $message = sprintf(
                'Đã gửi email thành công tới %d/%d người đăng ký.',
                count($results['success']),
                $results['total']
            );

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'results' => $results,
                    'campaign_id' => $campaign->id,
                ]);
            }

            return redirect()
                ->route('admin.newsletters.campaigns.show', $campaign->id)
                ->with('success', $message);
        } catch (\Exception $e) {
            $campaign->update(['status' => 'failed']);

            $message = 'Có lỗi xảy ra khi gửi email: '.$e->getMessage();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 500);
            }

            return redirect()->back()->with('error', $message);
        }
    }

    /**
     * Form gửi chiến dịch
     */
    public function showCampaignForm(): View
    {
        $stats = [
            'subscribed' => Newsletter::subscribed()->count(),
        ];

        $sources = Newsletter::select('source')
            ->whereNotNull('source')
            ->distinct()
            ->pluck('source')
            ->filter();

        return view('admins.newsletters.campaign', compact('stats', 'sources'));
    }

    /**
     * Danh sách chiến dịch đã gửi
     */
    public function campaignsIndex(): View
    {
        $campaigns = NewsletterCampaign::query()
            ->latest()
            ->paginate(20);

        return view('admins.newsletters.campaigns.index', compact('campaigns'));
    }

    /**
     * Chi tiết chiến dịch
     */
    public function campaignsShow(int $id): View
    {
        $campaign = NewsletterCampaign::findOrFail($id);

        return view('admins.newsletters.campaigns.show', compact('campaign'));
    }
}
