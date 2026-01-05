<?php

namespace App\Services;

use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AccountService
{
    public function __construct(
        private AccountLogService $logService
    ) {}

    public function list(array $filters = [], int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Account::query()->with(['accountLogs' => function ($q) {
            $q->latest()->limit(1);
        }]);

        // Search
        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Filter by status
        if (! empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        // Filter by role
        if (! empty($filters['role'])) {
            $query->byRole($filters['role']);
        }

        // Filter by email verified
        if (isset($filters['email_verified'])) {
            if ($filters['email_verified'] === '1') {
                $query->verified();
            } else {
                $query->unverified();
            }
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage)->withQueryString();
    }

    public function find(int $id): ?Account
    {
        return Account::with([
            'profile',
            'addresses',
            'favorites',
            'orders',
            'accountLogs' => function ($q) {
                $q->latest()->limit(50);
            },
        ])->findOrFail($id);
    }

    public function create(array $data, ?Account $admin = null): array
    {
        DB::beginTransaction();
        try {
            // Separate account and profile data
            $profileData = [];
            $profileFields = ['phone', 'fullname', 'gender', 'birthday'];
            foreach ($profileFields as $field) {
                if (isset($data[$field])) {
                    $profileData[$field] = $data[$field];
                    unset($data[$field]);
                }
            }

            // Handle email verification
            $emailVerified = $data['email_verified'] ?? false;
            unset($data['email_verified']);
            if ($emailVerified) {
                $data['email_verified_at'] = now();
            }

            // Remove password_confirmation
            unset($data['password_confirmation']);

            // Remove send_email flag (handled separately)
            $sendEmail = $data['send_email'] ?? false;
            unset($data['send_email']);

            // Ensure password is set (should be validated in request)
            // IMPORTANT: Get password directly from data array, not from anywhere else
            $plainPassword = $data['password'] ?? Str::random(16);

            // Store plain password IMMEDIATELY before any hashing
            $passwordForEmail = $plainPassword;

            // Log for debugging
            Log::info('AccountService creating account', [
                'email' => $data['email'] ?? 'N/A',
                'password_length' => strlen($plainPassword),
                'password_preview' => substr($plainPassword, 0, 3).'***',
            ]);

            // Set password (will be hashed automatically via setPasswordAttribute)
            $data['password'] = $plainPassword;

            // Set default values if not provided
            $data['status'] = $data['status'] ?? Account::STATUS_ACTIVE;
            $data['role'] = $data['role'] ?? Account::ROLE_USER;
            $data['login_attempts'] = 0;

            // Create account (password will be hashed automatically via setPasswordAttribute)
            $account = Account::create($data);

            // Create profile if profile data exists
            if (! empty($profileData)) {
                $profileData['account_id'] = $account->id;
                \App\Models\Profile::create($profileData);
            }

            // Log action
            $this->logService->log($account, 'account_created', [
                'admin_id' => $admin?->id,
                'email_verified' => $emailVerified,
                'send_email' => $sendEmail,
            ], $admin);

            DB::commit();

            return [
                'account' => $account->fresh(['profile']),
                'plain_password' => $passwordForEmail,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(Account $account, array $data, ?Account $admin = null): Account
    {
        DB::beginTransaction();
        try {
            $oldData = $account->toArray();

            // Ensure status is valid if provided
            if (isset($data['status']) && ! in_array($data['status'], Account::statuses(), true)) {
                unset($data['status']);
            }

            // Remove empty values to prevent issues
            $data = array_filter($data, function ($value) {
                return $value !== null && $value !== '';
            });

            $account->update($data);

            // Log changes
            $this->logService->log($account, 'account_updated', [
                'admin_id' => $admin?->id,
                'old_data' => $oldData,
                'new_data' => $account->toArray(),
            ], $admin);

            DB::commit();

            return $account->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete(Account $account, ?Account $admin = null): bool
    {
        DB::beginTransaction();
        try {
            // Log before delete
            $this->logService->log($account, 'account_deleted', [
                'admin_id' => $admin?->id,
                'account_data' => $account->toArray(),
            ], $admin);

            $account->delete();

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function restore(int $id, ?Account $admin = null): Account
    {
        DB::beginTransaction();
        try {
            $account = Account::withTrashed()->findOrFail($id);
            $account->restore();

            $this->logService->log($account, 'account_restored', [
                'admin_id' => $admin?->id,
            ], $admin);

            DB::commit();

            return $account;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function lock(Account $account, ?Account $admin = null): Account
    {
        DB::beginTransaction();
        try {
            $account->update([
                'status' => Account::STATUS_LOCKED,
                'login_attempts' => 0,
            ]);

            $this->logService->log($account, 'account_locked', [
                'admin_id' => $admin?->id,
            ], $admin);

            DB::commit();

            return $account->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function unlock(Account $account, ?Account $admin = null): Account
    {
        DB::beginTransaction();
        try {
            $account->update([
                'status' => Account::STATUS_ACTIVE,
                'login_attempts' => 0,
            ]);

            $this->logService->log($account, 'account_unlocked', [
                'admin_id' => $admin?->id,
            ], $admin);

            DB::commit();

            return $account->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function ban(Account $account, ?Account $admin = null): Account
    {
        DB::beginTransaction();
        try {
            $account->update([
                'status' => Account::STATUS_BANNED,
            ]);

            $this->logService->log($account, 'account_banned', [
                'admin_id' => $admin?->id,
            ], $admin);

            DB::commit();

            return $account->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function unban(Account $account, ?Account $admin = null): Account
    {
        DB::beginTransaction();
        try {
            $account->update([
                'status' => Account::STATUS_ACTIVE,
            ]);

            $this->logService->log($account, 'account_unbanned', [
                'admin_id' => $admin?->id,
            ], $admin);

            DB::commit();

            return $account->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function resetPassword(Account $account, ?Account $admin = null): string
    {
        DB::beginTransaction();
        try {
            $newPassword = Str::random(16);
            $account->update([
                'password' => $newPassword,
                'last_password_changed_at' => now(),
            ]);

            $this->logService->log($account, 'password_reset', [
                'admin_id' => $admin?->id,
            ], $admin);

            DB::commit();

            return $newPassword;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function verifyEmail(Account $account, ?Account $admin = null): Account
    {
        DB::beginTransaction();
        try {
            $account->update([
                'email_verified_at' => now(),
            ]);

            $this->logService->log($account, 'email_verified', [
                'admin_id' => $admin?->id,
            ], $admin);

            DB::commit();

            return $account->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function resetLoginAttempts(Account $account, ?Account $admin = null): Account
    {
        DB::beginTransaction();
        try {
            $account->update([
                'login_attempts' => 0,
            ]);

            $this->logService->log($account, 'login_attempts_reset', [
                'admin_id' => $admin?->id,
            ], $admin);

            DB::commit();

            return $account->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getStats(): array
    {
        return [
            'total' => Account::count(),
            'active' => Account::active()->count(),
            'inactive' => Account::where('status', Account::STATUS_INACTIVE)->count(),
            'verified' => Account::verified()->count(),
            'locked' => Account::locked()->count(),
            'suspended' => Account::banned()->count(),
        ];
    }

    public function getDashboardStats(): array
    {
        return [
            'total' => Account::count(),
            'new_last_7_days' => Account::where('created_at', '>=', now()->subDays(7))->count(),
            'active' => Account::active()->count(),
            'locked' => Account::locked()->count(),
            'banned' => Account::banned()->count(),
            'unverified' => Account::unverified()->count(),
            'top_login_accounts' => Account::orderByDesc('login_attempts')->limit(10)->get(),
            'new_accounts_by_day' => Account::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
        ];
    }
}
