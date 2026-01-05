<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AccountLogService
{
    public function log(
        Account $account,
        string $type,
        array $payload = [],
        ?Account $admin = null,
        ?Request $request = null
    ): AccountLog {
        $request = $request ?? request();

        return AccountLog::create([
            'account_id' => $account->id,
            'admin_id' => $admin?->id,
            'type' => $type,
            'payload' => $payload,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    /**
     * Record a log entry with flexible parameters
     *
     * @param  string  $type  Log type
     * @param  int|null  $accountId  Account ID (null if not applicable)
     * @param  int|null  $adminId  Admin ID (null if not applicable)
     * @param  array  $payload  Main payload data
     * @param  array  $meta  Additional metadata
     * @param  bool  $skipIfNoAccount  Skip if account_id is null/0
     */
    public function record(
        string $type,
        ?int $accountId = null,
        ?int $adminId = null,
        array $payload = [],
        array $meta = [],
        bool $skipIfNoAccount = false
    ): ?AccountLog {
        if ($skipIfNoAccount && (! $accountId || $accountId === 0)) {
            return null;
        }

        $request = request();

        $fullPayload = array_merge($payload, ['meta' => $meta]);

        return AccountLog::create([
            'account_id' => $accountId ?: null,
            'admin_id' => $adminId ?: auth('admin')->id(),
            'type' => $type,
            'payload' => $fullPayload,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    public function getLogs(Account $account, array $filters = [], int $perPage = 50): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $account->accountLogs()->with('admin')->latest();

        // Filter by type
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Filter by date range
        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function getLogTypes(): array
    {
        return [
            'login_fail',
            'login_success',
            'password_reset',
            'update_profile',
            'admin_action',
            'account_created',
            'account_updated',
            'account_deleted',
            'account_restored',
            'account_locked',
            'account_unlocked',
            'account_banned',
            'account_unbanned',
            'email_verified',
            'login_attempts_reset',
        ];
    }
}
