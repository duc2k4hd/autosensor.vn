<?php

namespace App\Http\Middleware;

use App\Models\Account;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // Nếu chưa đăng nhập, redirect đến admin login
        if (! Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('admin.login');
        }

        /** @var Account $user */
        $user = Auth::user();

        // Check if user is admin or writer
        if (! in_array($user->role, [Account::ROLE_ADMIN, Account::ROLE_WRITER])) {
            Auth::logout();

            return redirect()
                ->route('admin.login')
                ->with('error', 'Bạn không có quyền truy cập trang quản trị.');
        }

        // Check if account is active
        if ($user->status !== Account::STATUS_ACTIVE) {
            Auth::logout();

            return redirect()
                ->route('admin.login')
                ->with('error', 'Tài khoản của bạn đã bị khóa hoặc chưa được kích hoạt.');
        }

        return $next($request);
    }
}
