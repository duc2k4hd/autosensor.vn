<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AccountStoreRequest;
use App\Http\Requests\Admin\AccountUpdateRequest;
use App\Mail\AccountCreatedMail;
use App\Models\Account;
use App\Services\AccountLogService;
use App\Services\AccountService;
use App\Services\ActivityLogService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AccountController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private AccountService $accountService,
        private AccountLogService $logService,
        private ActivityLogService $activityLogService
    ) {
        //
    }

    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Account::class);

        $filters = [
            'search' => $request->get('search'),
            'status' => $request->get('status'),
            'role' => $request->get('role'),
            'email_verified' => $request->get('email_verified'),
            'sort_by' => $request->get('sort_by', 'created_at'),
            'sort_dir' => $request->get('sort_dir', 'desc'),
        ];

        $perPage = (int) $request->get('per_page', 20);
        $perPage = in_array($perPage, [20, 50, 100]) ? $perPage : 20;

        $accounts = $this->accountService->list($filters, $perPage);

        if ($request->wantsJson()) {
            return response()->json($accounts);
        }

        $statusLabels = [
            Account::STATUS_ACTIVE => 'Hoạt động',
            Account::STATUS_INACTIVE => 'Không hoạt động',
            Account::STATUS_BANNED => 'Đã cấm',
            Account::STATUS_LOCKED => 'Đã khóa',
            Account::STATUS_SUSPENDED => 'Tạm ngưng',
        ];

        $stats = $this->accountService->getStats();

        return view('admins.accounts.index', [
            'accounts' => $accounts,
            'filters' => $filters,
            'perPage' => $perPage,
            'roles' => Account::roles(),
            'accountRoles' => Account::roles(),
            'accountStatuses' => Account::statuses(),
            'accountStatusLabels' => $statusLabels,
            'stats' => $stats,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Account::class);

        return view('admins.accounts.create', [
            'roles' => Account::roles(),
            'statuses' => Account::statuses(),
        ]);
    }

    public function store(AccountStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', Account::class);

        /** @var Account $admin */
        $admin = Auth::user();
        $data = $request->validated();

        $result = $this->accountService->create($data, $admin);
        $newAccount = $result['account'];
        $plainPassword = $result['plain_password'];

        // Log activity
        $this->activityLogService->logCreate($newAccount, 'Tạo tài khoản mới: '.$newAccount->name);

        if ($request->boolean('send_email')) {
            try {
                // Use URL directly to avoid route name issues
                $forgotPasswordUrl = url('/xac-thuc/quen-mat-khau');

                // Ensure we're sending to the newly created account, not the admin
                $recipientEmail = $newAccount->email;

                // Log for debugging
                Log::info('Sending account creation email', [
                    'new_account_id' => $newAccount->id,
                    'new_account_email' => $newAccount->email,
                    'new_account_name' => $newAccount->name,
                    'password_length' => strlen($plainPassword),
                    'admin_id' => $admin->id,
                    'admin_email' => $admin->email,
                ]);

                Mail::to($recipientEmail)->send(
                    new AccountCreatedMail($newAccount, $plainPassword, $forgotPasswordUrl)
                );
            } catch (\Exception $e) {
                // Log error but don't fail the request
                Log::error('Failed to send account creation email', [
                    'new_account_id' => $newAccount->id,
                    'new_account_email' => $newAccount->email,
                    'admin_id' => $admin->id,
                    'admin_email' => $admin->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return redirect()
            ->route('admin.accounts.show', $newAccount)
            ->with('success', 'Tài khoản đã được tạo thành công.');
    }

    public function show(Account $account): View
    {
        if (! $account) {
            return view('clients.pages.errors.404');
        }
        $this->authorize('view', $account);

        $account = $this->accountService->find($account->id);

        $logFilters = [
            'type' => request()->get('log_type'),
            'date_from' => request()->get('date_from'),
            'date_to' => request()->get('date_to'),
        ];

        $logs = $this->logService->getLogs($account, $logFilters);

        return view('admins.accounts.show', [
            'account' => $account,
            'logs' => $logs,
            'logTypes' => $this->logService->getLogTypes(),
        ]);
    }

    public function edit(Account $account): View
    {
        $this->authorize('update', $account);

        $account = $this->accountService->find($account->id);

        $statusLabels = [
            Account::STATUS_ACTIVE => 'Hoạt động',
            Account::STATUS_INACTIVE => 'Không hoạt động',
            Account::STATUS_BANNED => 'Đã cấm',
            Account::STATUS_LOCKED => 'Đã khóa',
            Account::STATUS_SUSPENDED => 'Tạm ngưng',
        ];

        return view('admins.accounts.edit', [
            'account' => $account,
            'roles' => Account::roles(),
            'statuses' => Account::statuses(),
            'accountStatuses' => Account::statuses(),
            'accountStatusLabels' => $statusLabels,
        ]);
    }

    public function update(AccountUpdateRequest $request, Account $account): RedirectResponse
    {
        $this->authorize('update', $account);

        /** @var Account $admin */
        $admin = Auth::user();
        $data = $request->validated();

        // Remove password if empty
        if (empty($data['password'])) {
            unset($data['password']);
        }

        // Prevent admin from changing their own role
        if (isset($data['role']) && $admin->id === $account->id) {
            unset($data['role']);
        }

        // Prevent admin from locking/banning themselves
        if (isset($data['status']) && $admin->id === $account->id) {
            $restrictedStatuses = [Account::STATUS_LOCKED, Account::STATUS_BANNED];
            if (in_array($data['status'], $restrictedStatuses)) {
                return redirect()
                    ->back()
                    ->withErrors(['status' => 'Bạn không thể khóa hoặc cấm chính tài khoản của mình']);
            }
        }

        $oldData = $account->toArray();
        $this->accountService->update($account, $data, $admin);

        // Log activity
        $this->activityLogService->logUpdate($account->fresh(), $oldData, 'Cập nhật tài khoản: '.$account->name);

        return redirect()
            ->route('admin.accounts.show', $account)
            ->with('success', 'Tài khoản đã được cập nhật thành công.');
    }

    public function destroy(Account $account): RedirectResponse
    {
        $this->authorize('delete', $account);

        /** @var Account $admin */
        $admin = Auth::user();

        // Log activity before delete
        $this->activityLogService->logDelete($account, 'Xóa tài khoản: '.$account->name);

        $this->accountService->delete($account, $admin);

        return redirect()
            ->route('admin.accounts.index')
            ->with('success', 'Tài khoản đã được xóa thành công.');
    }

    public function lock(Account $account): RedirectResponse
    {
        $this->authorize('lock', $account);

        /** @var Account $admin */
        $admin = Auth::user();
        $this->accountService->lock($account, $admin);

        return redirect()
            ->back()
            ->with('success', 'Tài khoản đã được khóa.');
    }

    public function unlock(Account $account): RedirectResponse
    {
        $this->authorize('unlock', $account);

        /** @var Account $admin */
        $admin = Auth::user();
        $this->accountService->unlock($account, $admin);

        return redirect()
            ->back()
            ->with('success', 'Tài khoản đã được mở khóa.');
    }

    public function ban(Account $account): RedirectResponse
    {
        $this->authorize('ban', $account);

        /** @var Account $admin */
        $admin = Auth::user();
        $this->accountService->ban($account, $admin);

        return redirect()
            ->back()
            ->with('success', 'Tài khoản đã bị cấm.');
    }

    public function unban(Account $account): RedirectResponse
    {
        $this->authorize('unban', $account);

        /** @var Account $admin */
        $admin = Auth::user();
        $this->accountService->unban($account, $admin);

        return redirect()
            ->back()
            ->with('success', 'Tài khoản đã được gỡ cấm.');
    }

    public function toggle(Account $account): RedirectResponse
    {
        $this->authorize('update', $account);

        /** @var Account $admin */
        $admin = Auth::user();

        if ($account->status === Account::STATUS_LOCKED) {
            $this->accountService->unlock($account, $admin);

            return redirect()
                ->back()
                ->with('success', 'Tài khoản đã được mở khóa.');
        }

        $this->accountService->lock($account, $admin);

        return redirect()
            ->back()
            ->with('success', 'Tài khoản đã được khóa.');
    }

    public function resetPassword(Account $account): RedirectResponse
    {
        $this->authorize('resetPassword', $account);

        /** @var Account $admin */
        $admin = Auth::user();
        $newPassword = $this->accountService->resetPassword($account, $admin);

        // TODO: Send email with new password

        return redirect()
            ->back()
            ->with('success', 'Mật khẩu đã được reset. Mật khẩu mới: '.$newPassword);
    }

    public function verifyEmail(Account $account): RedirectResponse
    {
        $this->authorize('update', $account);

        /** @var Account $admin */
        $admin = Auth::user();
        $this->accountService->verifyEmail($account, $admin);

        return redirect()
            ->back()
            ->with('success', 'Email đã được xác minh.');
    }

    public function resetLoginAttempts(Account $account): RedirectResponse
    {
        $this->authorize('update', $account);

        /** @var Account $admin */
        $admin = Auth::user();
        $this->accountService->resetLoginAttempts($account, $admin);

        return redirect()
            ->back()
            ->with('success', 'Số lần đăng nhập thất bại đã được reset.');
    }

    public function restore(int $id): RedirectResponse
    {
        /** @var Account $admin */
        $admin = Auth::user();
        $account = $this->accountService->restore($id, $admin);

        return redirect()
            ->route('admin.accounts.show', $account)
            ->with('success', 'Tài khoản đã được khôi phục.');
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $request->validate([
            'selected' => ['required', 'array'],
            'selected.*' => ['exists:accounts,id'],
            'bulk_action' => ['required', 'string', 'in:activate,deactivate'],
        ]);

        $accountIds = $request->input('selected');
        $action = $request->input('bulk_action');

        /** @var Account $admin */
        $admin = Auth::user();

        $accounts = Account::whereIn('id', $accountIds)->get();

        foreach ($accounts as $account) {
            $this->authorize('update', $account);

            if ($action === 'activate') {
                if ($account->status === Account::STATUS_LOCKED) {
                    $this->accountService->unlock($account, $admin);
                }
            } else {
                if ($account->status === Account::STATUS_ACTIVE) {
                    $this->accountService->lock($account, $admin);
                }
            }
        }

        return redirect()
            ->back()
            ->with('success', 'Đã cập nhật '.count($accountIds).' tài khoản.');
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $filters = [
            'search' => $request->get('search'),
            'status' => $request->get('status'),
            'role' => $request->get('role'),
            'email_verified' => $request->get('email_verified'),
        ];

        $query = Account::with(['profile', 'addresses']);

        // Apply filters
        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('email', 'like', "%{$filters['search']}%")
                    ->orWhere('phone', 'like', "%{$filters['search']}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if ($filters['email_verified'] !== null) {
            if ($filters['email_verified']) {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        $accounts = $query->orderBy('created_at', 'desc')->get();

        // Create Excel file
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Accounts');

        // Headers
        $headers = [
            'ID', 'Tên', 'Email', 'Số điện thoại', 'Vai trò', 'Trạng thái',
            'Email đã xác thực', 'Ngày xác thực email', 'Số đơn hàng', 'Tổng chi tiêu',
            'Địa chỉ', 'Ghi chú admin', 'Ngày tạo', 'Ngày cập nhật',
        ];
        $sheet->fromArray($headers, null, 'A1');

        // Style header
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E0E0E0'],
            ],
        ];
        $sheet->getStyle('A1:N1')->applyFromArray($headerStyle);

        // Data
        $row = 2;
        foreach ($accounts as $account) {
            $orderCount = $account->orders()->count();
            $totalSpent = (float) ($account->orders()
                ->where('status', '!=', 'cancelled')
                ->sum('final_price') ?? 0);

            $addresses = $account->addresses->map(function ($addr) {
                return trim($addr->full_address ?? '');
            })->filter()->implode('; ');

            $sheet->fromArray([
                $account->id,
                $account->name,
                $account->email,
                $account->phone,
                $account->role,
                $account->status,
                $account->email_verified_at ? 'Có' : 'Không',
                $account->email_verified_at?->format('Y-m-d H:i:s'),
                $orderCount,
                number_format($totalSpent, 0, ',', '.'),
                $addresses ?: '',
                $account->admin_note ?? '',
                $account->created_at?->format('Y-m-d H:i:s'),
                $account->updated_at?->format('Y-m-d H:i:s'),
            ], null, 'A'.$row);
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = 'accounts_export_'.now()->format('Y-m-d_H-i-s').'.xlsx';
        $tempDir = storage_path('app/tmp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $fullPath = $tempDir.'/'.$fileName;

        $writer = new Xlsx($spreadsheet);
        $writer->save($fullPath);

        return response()->download($fullPath, $fileName)->deleteFileAfterSend(true);
    }

    public function dashboard(): View
    {
        $this->authorize('viewAny', Account::class);

        $stats = $this->accountService->getDashboardStats();

        return view('admins.accounts.dashboard', [
            'stats' => $stats,
        ]);
    }

    // API Methods for Edit Page

    public function apiShow(Account $account): JsonResponse
    {
        $this->authorize('view', $account);

        $account = $this->accountService->find($account->id);

        return response()->json([
            'data' => [
                'id' => $account->id,
                'name' => $account->name,
                'email' => $account->email,
                'phone' => $account->phone,
                'role' => $account->role,
                'status' => $account->status,
                'account_status' => $account->status,
                'is_active' => $account->isActive(),
                'email_verified_at' => $account->email_verified_at?->toIso8601String(),
                'login_attempts' => $account->login_attempts ?? 0,
                'login_history' => $account->login_history?->toIso8601String(),
                'last_password_changed_at' => $account->last_password_changed_at?->toIso8601String(),
                'security_flags' => $account->security_flags ?? [],
                'created_at' => $account->created_at->toIso8601String(),
                'updated_at' => $account->updated_at->toIso8601String(),
                'profile' => $account->profile ? [
                    'full_name' => $account->profile->fullname ?? null,
                    'nickname' => $account->profile->extra['nickname'] ?? null,
                    'phone' => $account->profile->phone ?? null,
                    'gender' => $account->profile->gender ?? null,
                    'birthday' => $account->profile->birthday?->format('Y-m-d'),
                    'location' => $account->profile->extra['location'] ?? null,
                    'bio' => $account->profile->extra['bio'] ?? null,
                    'is_public' => $account->profile->extra['is_public'] ?? false,
                    'avatar' => $account->profile->avatar ?? null,
                    'sub_avatar' => $account->profile->extra['sub_avatar'] ?? null,
                    'avatar_history' => $account->profile->extra['avatar_history'] ?? [],
                    'sub_avatar_history' => $account->profile->extra['sub_avatar_history'] ?? [],
                ] : null,
            ],
        ]);
    }

    public function apiUpdate(Request $request, Account $account): JsonResponse
    {
        $this->authorize('update', $account);

        /** @var Account $admin */
        $admin = Auth::user();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:accounts,email,'.$account->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['sometimes', 'string', 'in:'.implode(',', Account::roles())],
            'status' => ['sometimes', 'string', 'in:'.implode(',', Account::statuses())],
            'account_status' => ['sometimes', 'string', 'in:'.implode(',', Account::statuses())],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        // Ensure status is a valid value if provided
        if (isset($validated['status']) && ! in_array($validated['status'], Account::statuses(), true)) {
            unset($validated['status']);
        }
        if (isset($validated['account_status']) && ! in_array($validated['account_status'], Account::statuses(), true)) {
            unset($validated['account_status']);
        }

        // Prevent admin from changing their own role
        if (isset($validated['role']) && $admin->id === $account->id) {
            unset($validated['role']);
        }

        // Convert is_active to status if provided
        if (isset($validated['is_active'])) {
            $validated['status'] = $validated['is_active'] ? Account::STATUS_ACTIVE : Account::STATUS_INACTIVE;
            unset($validated['is_active']);
        }

        // Use account_status if provided
        if (isset($validated['account_status'])) {
            $validated['status'] = $validated['account_status'];
            unset($validated['account_status']);
        }

        // Prevent admin from locking/banning themselves (check after all conversions)
        if (isset($validated['status']) && $admin->id === $account->id) {
            $restrictedStatuses = [Account::STATUS_LOCKED, Account::STATUS_BANNED];
            if (in_array($validated['status'], $restrictedStatuses)) {
                return response()->json([
                    'message' => 'Bạn không thể khóa hoặc cấm chính tài khoản của mình',
                ], 403);
            }
        }

        $this->accountService->update($account, $validated, $admin);

        return response()->json([
            'message' => 'Cập nhật thành công',
            'data' => $account->fresh(),
        ]);
    }

    public function apiToggle(Account $account): JsonResponse
    {
        $this->authorize('update', $account);

        /** @var Account $admin */
        $admin = Auth::user();

        // Prevent admin from locking themselves
        if ($admin->id === $account->id) {
            return response()->json([
                'message' => 'Bạn không thể khóa chính tài khoản của mình',
            ], 403);
        }

        if ($account->status === Account::STATUS_LOCKED) {
            $this->accountService->unlock($account, $admin);
        } else {
            $this->accountService->lock($account, $admin);
        }

        return response()->json([
            'message' => 'Đã cập nhật trạng thái',
            'data' => $account->fresh(),
        ]);
    }

    public function apiChangeRole(Request $request, Account $account): JsonResponse
    {
        $this->authorize('changeRole', $account);

        /** @var Account $admin */
        $admin = Auth::user();

        // Double check: cannot change own role
        if ($admin->id === $account->id) {
            return response()->json([
                'message' => 'Bạn không thể đổi role của chính mình',
            ], 403);
        }

        $validated = $request->validate([
            'role' => ['required', 'string', 'in:'.implode(',', Account::roles())],
        ]);

        $this->accountService->update($account, $validated, $admin);

        return response()->json([
            'message' => 'Đã đổi role',
            'data' => $account->fresh(),
        ]);
    }

    public function apiResetPassword(Request $request, Account $account): JsonResponse
    {
        $this->authorize('resetPassword', $account);

        /** @var Account $admin */
        $admin = Auth::user();

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'force_logout' => ['sometimes', 'boolean'],
        ]);

        $account->update([
            'password' => $validated['password'],
            'last_password_changed_at' => now(),
        ]);

        $this->logService->log($account, 'password_reset', [
            'admin_id' => $admin->id,
            'force_logout' => $validated['force_logout'] ?? false,
        ], $admin);

        return response()->json([
            'message' => 'Đã reset mật khẩu',
        ]);
    }

    public function apiForceLogout(Account $account): JsonResponse
    {
        $this->authorize('update', $account);

        /** @var Account $admin */
        $admin = Auth::user();

        // TODO: Implement force logout logic (revoke all tokens, clear sessions, etc.)

        $this->logService->log($account, 'force_logout', [
            'admin_id' => $admin->id,
        ], $admin);

        return response()->json([
            'message' => 'Đã force logout',
        ]);
    }

    public function apiVerifyEmail(Account $account): JsonResponse
    {
        $this->authorize('update', $account);

        /** @var Account $admin */
        $admin = Auth::user();

        $this->accountService->verifyEmail($account, $admin);

        return response()->json([
            'message' => 'Đã xác minh email',
            'data' => $account->fresh(),
        ]);
    }

    public function apiProfileShow(Account $account): JsonResponse
    {
        $this->authorize('view', $account);

        $profile = $account->profile;

        return response()->json([
            'data' => $profile ? [
                'full_name' => $profile->fullname ?? null,
                'nickname' => $profile->extra['nickname'] ?? null,
                'phone' => $profile->phone ?? null,
                'gender' => $profile->gender ?? null,
                'birthday' => $profile->birthday?->format('Y-m-d'),
                'location' => $profile->extra['location'] ?? null,
                'bio' => $profile->extra['bio'] ?? null,
                'is_public' => $profile->extra['is_public'] ?? false,
                'avatar' => $profile->avatar ?? null,
                'sub_avatar' => $profile->extra['sub_avatar'] ?? null,
            ] : null,
        ]);
    }

    public function apiProfileUpdate(Request $request, Account $account): JsonResponse
    {
        $this->authorize('update', $account);

        /** @var Account $admin */
        $admin = Auth::user();

        $validated = $request->validate([
            'full_name' => ['nullable', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'birthday' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'is_public' => ['sometimes', 'boolean'],
        ]);

        $profile = $account->profile;

        $profileData = [
            'fullname' => $validated['full_name'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'birthday' => $validated['birthday'] ?? null,
        ];

        $extra = $profile?->extra ?? [];
        if (isset($validated['nickname'])) {
            $extra['nickname'] = $validated['nickname'];
        }
        if (isset($validated['location'])) {
            $extra['location'] = $validated['location'];
        }
        if (isset($validated['bio'])) {
            $extra['bio'] = $validated['bio'];
        }
        if (isset($validated['is_public'])) {
            $extra['is_public'] = $validated['is_public'];
        }
        $profileData['extra'] = $extra;

        if (! $profile) {
            $profileData['account_id'] = $account->id;
            $profile = $account->profile()->create($profileData);
        } else {
            $profile->update($profileData);
        }

        $this->logService->log($account, 'profile_updated', [
            'admin_id' => $admin->id,
            'data' => $validated,
        ], $admin);

        return response()->json([
            'message' => 'Đã cập nhật profile',
            'data' => $profile->fresh(),
        ]);
    }

    public function apiProfileVisibility(Request $request, Account $account): JsonResponse
    {
        $this->authorize('update', $account);

        /** @var Account $admin */
        $admin = Auth::user();

        $validated = $request->validate([
            'is_public' => ['required', 'boolean'],
        ]);

        $profile = $account->profile;

        $extra = $profile?->extra ?? [];
        $oldIsPublic = $extra['is_public'] ?? false;
        $extra['is_public'] = $validated['is_public'];

        if (! $profile) {
            $profile = $account->profile()->create([
                'account_id' => $account->id,
                'extra' => $extra,
            ]);
        } else {
            $profile->update(['extra' => $extra]);
        }

        $this->logService->log($account, 'profile_visibility_updated', [
            'admin_id' => $admin->id,
            'old_is_public' => $oldIsPublic,
            'new_is_public' => $validated['is_public'],
        ], $admin);

        return response()->json([
            'message' => 'Đã cập nhật visibility',
            'data' => $profile->fresh(),
        ]);
    }

    public function apiProfileAvatar(Request $request, Account $account): JsonResponse
    {
        $this->authorize('update', $account);

        /** @var Account $admin */
        $admin = Auth::user();

        $validated = $request->validate([
            'avatar' => ['nullable', 'image', 'max:2048'],
            'sub_avatar' => ['nullable', 'image', 'max:2048'],
            'remove_avatar' => ['sometimes', 'boolean'],
            'remove_sub_avatar' => ['sometimes', 'boolean'],
            'history_restore' => ['nullable', 'string', 'in:avatar,sub_avatar'],
        ]);

        $profile = $account->profile;

        if (! $profile) {
            $profile = $account->profile()->create([]);
        }

        // TODO: Implement avatar upload logic
        // For now, just return success

        $this->logService->log($account, 'avatar_updated', [
            'admin_id' => $admin->id,
        ], $admin);

        return response()->json([
            'message' => 'Đã cập nhật avatar',
            'data' => $profile->fresh(),
        ]);
    }

    public function apiLogsIndex(Request $request, Account $account): JsonResponse
    {
        $this->authorize('view', $account);

        $filters = [
            'type' => $request->get('type'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ];

        $logs = $this->logService->getLogs($account, $filters, 20);

        $logData = collect($logs->items())->map(function ($log) {
            return [
                'id' => $log->id,
                'type' => $log->type,
                'payload' => $log->payload,
                'ip' => $log->ip,
                'user_agent' => $log->user_agent,
                'created_at' => $log->created_at->toIso8601String(),
                'admin_name' => $log->admin?->name ?? 'System',
            ];
        })->values()->all();

        return response()->json([
            'data' => $logData,
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'prev_page_url' => $logs->previousPageUrl(),
                'next_page_url' => $logs->nextPageUrl(),
            ],
        ]);
    }

    public function apiLogsExport(Request $request, Account $account): JsonResponse
    {
        $this->authorize('view', $account);

        $filters = [
            'type' => $request->get('type'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ];

        $logs = $this->logService->getLogs($account, $filters, 1000);

        // TODO: Implement CSV export
        // For now, return JSON response

        return response()->json([
            'message' => 'Export chưa được triển khai',
            'data' => $logs->items(),
        ]);
    }
}
