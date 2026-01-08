<?php

namespace App\Http\Controllers\Admins\APIs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AccountStoreRequest;
use App\Http\Requests\Admin\AccountUpdateRequest;
use App\Http\Resources\Admin\AccountResource;
use App\Models\Account;
use App\Services\AccountLogService;
use App\Services\AccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AccountApiController extends Controller
{
    public function __construct(
        protected AccountService $accountService,
        protected AccountLogService $accountLogService
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Account::class);

        $filters = $this->buildFilters($request);

        $query = Account::query()->with('profile');

        // Apply search filter
        if (!empty($filters['keyword'])) {
            $query->search($filters['keyword']);
        }

        // Apply role filter
        if (!empty($filters['role'])) {
            $query->byRole($filters['role']);
        }

        // Apply status filter
        if (isset($filters['is_active'])) {
            if ($filters['is_active']) {
                $query->byStatus(Account::STATUS_ACTIVE);
            } else {
                $query->byStatus(Account::STATUS_INACTIVE);
            }
        }

        // Apply account_status filter
        if (!empty($filters['account_status'])) {
            $query->byStatus($filters['account_status']);
        }

        // Apply email verified filter
        if (isset($filters['email_verified'])) {
            if ($filters['email_verified'] === 'yes') {
                $query->verified();
            } elseif ($filters['email_verified'] === 'no') {
                $query->unverified();
            }
        }

        // Apply gender filter (from profile)
        if (!empty($filters['gender'])) {
            $query->whereHas('profile', function ($q) use ($filters) {
                $q->where('gender', $filters['gender']);
            });
        }

        // Apply location filter (from profile extra)
        if (!empty($filters['location'])) {
            $query->whereHas('profile', function ($q) use ($filters) {
                $q->whereJsonContains('extra->location', $filters['location']);
            });
        }

        // Apply last login date range filter
        if (!empty($filters['last_login_from']) || !empty($filters['last_login_to'])) {
            $query->where(function ($q) use ($filters) {
                if (!empty($filters['last_login_from'])) {
                    $q->where('login_history', '>=', $filters['last_login_from']);
                }
                if (!empty($filters['last_login_to'])) {
                    $q->where('login_history', '<=', $filters['last_login_to']);
                }
            });
        }

        $perPage = min(100, max(1, (int) ($filters['per_page'] ?? $request->integer('per_page', 20))));
        $accounts = $query->orderByDesc('id')->paginate($perPage);

        return AccountResource::collection($accounts);
    }

    public function show(Account $account)
    {
        $this->authorize('view', $account);

        return new AccountResource($account->load('profile'));
    }

    public function store(AccountStoreRequest $request)
    {
        $this->authorize('create', Account::class);

        $validated = $request->validated();
        $profileData = $validated['profile'] ?? [];
        unset($validated['profile']);

        $account = DB::transaction(function () use ($validated, $profileData) {
            $account = Account::create($validated);
            $account->profile()->create($profileData);

            return $account->load('profile');
        });

        $this->accountLogService->record('account.created', $account->id, null, [
            'account' => $account->only(['name', 'email', 'role', 'is_active', 'account_status']),
            'profile' => $account->profile?->toArray(),
        ]);

        return new AccountResource($account);
    }

    public function update(AccountUpdateRequest $request, Account $account)
    {
        $this->authorize('update', $account);

        $validated = $request->validated();
        $profileData = $validated['profile'] ?? [];
        unset($validated['profile']);

        $changes = [];

        DB::transaction(function () use ($account, $validated, $profileData, &$changes) {
            if (! empty($validated)) {
                $beforeAccount = $account->getOriginal();
                $account->fill($validated);
                $dirtyAccount = $account->getDirty();

                if (! empty($dirtyAccount)) {
                    $account->save();
                    $changes['account'] = [
                        'before' => array_intersect_key($beforeAccount, $dirtyAccount),
                        'after' => $dirtyAccount,
                    ];
                }
            }

            if (! empty($profileData)) {
                $profile = $account->profile()->firstOrNew([]);
                $beforeProfile = $profile->getOriginal();
                $profile->fill($profileData);
                $dirtyProfile = $profile->getDirty();

                if (! empty($dirtyProfile)) {
                    $profile->save();
                    $changes['profile'] = [
                        'before' => array_intersect_key($beforeProfile, $dirtyProfile),
                        'after' => $dirtyProfile,
                    ];
                }
            }
        });

        if (! empty($changes)) {
            $this->accountLogService->record('account.updated', $account->id, null, $changes);
        }

        return new AccountResource($account->load('profile'));
    }

    public function destroy(Account $account)
    {
        $this->authorize('delete', $account);

        $accountId = $account->id;
        $account->delete();

        $this->accountLogService->record('account.deleted', $accountId);

        return response()->noContent();
    }

    public function toggle(Request $request, Account $account)
    {
        $this->authorize('update', $account);

        $target = $request->has('is_active')
            ? $request->boolean('is_active')
            : ! $account->isActive();

        $account->forceFill([
            'status' => $target ? Account::STATUS_ACTIVE : Account::STATUS_INACTIVE,
        ])->save();

        if (! $target) {
            $this->terminateSessions($account);
        }

        $this->accountLogService->record('account.state_toggled', $account->id, null, [
            'is_active' => $target,
            'status' => $account->status,
        ]);

        return new AccountResource($account->refresh()->load('profile'));
    }

    public function changeRole(Request $request, Account $account)
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
            'role' => ['required', 'string', Rule::in(Account::roles())],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $previous = $account->role;
        $this->accountService->update($account, ['role' => $validated['role']], $admin);

        $this->accountLogService->record('account.role_changed', $account->id, null, [
            'before' => $previous,
            'after' => $validated['role'],
            'note' => $validated['note'] ?? null,
        ]);

        return new AccountResource($account->refresh()->load('profile'));
    }

    public function resetPassword(Request $request, Account $account): JsonResponse
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

        $sessions = 0;
        if ($request->boolean('force_logout')) {
            $sessions = $this->terminateSessions($account);
        }

        $this->accountLogService->record('account.password_reset', $account->id, null, [
            'force_logout' => $request->boolean('force_logout'),
            'sessions_terminated' => $sessions,
        ]);

        return response()->json([
            'message' => 'Đã reset mật khẩu thành công.',
            'sessions_terminated' => $sessions,
        ]);
    }

    public function forceLogout(Account $account): JsonResponse
    {
        $this->authorize('forceLogout', $account);

        $sessions = $this->terminateSessions($account);

        $this->accountLogService->record('account.force_logout', $account->id, null, [
            'sessions_terminated' => $sessions,
        ]);

        return response()->json([
            'message' => 'Sessions terminated.',
            'sessions_terminated' => $sessions,
        ]);
    }

    public function verifyEmail(Account $account): JsonResponse
    {
        $this->authorize('update', $account);

        /** @var Account $admin */
        $admin = Auth::user();

        if ($account->email_verified_at) {
            return response()->json([
                'message' => 'Email này đã được xác minh trước đó.',
            ]);
        }

        $this->accountService->verifyEmail($account, $admin);

        return response()->json([
            'message' => 'Đã xác minh email thành công.',
            'data' => new AccountResource($account->refresh()->load('profile')),
        ]);
    }

    protected function terminateSessions(Account $account): int
    {
        $driver = config('session.driver');
        $deleted = 0;

        if ($driver === 'database') {
            $deleted = DB::table(config('session.table', 'sessions'))
                ->where('user_id', $account->id)
                ->delete();
        }

        $account->forceFill([
            'remember_token' => Str::random(60),
        ])->saveQuietly();

        return $deleted;
    }

    protected function buildFilters(Request $request): array
    {
        $validated = $request->validate([
            'keyword' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', Rule::in(Account::roles())],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'account_status' => ['nullable', Rule::in(Account::statuses())],
            'email_verified' => ['nullable', Rule::in(['yes', 'no'])],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'location' => ['nullable', 'string', 'max:255'],
            'last_login_from' => ['nullable', 'date'],
            'last_login_to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if (isset($validated['status'])) {
            $validated['is_active'] = $validated['status'] === 'active';
            unset($validated['status']);
        }

        return $validated;
    }
}
