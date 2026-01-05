<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            /** @var Account $user */
            $user = Auth::user();
            if (in_array($user->role, [Account::ROLE_ADMIN, Account::ROLE_WRITER])) {
                return redirect()->route('admin.dashboard');
            }
        }

        return view('admins.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $account = Account::where('email', $credentials['email'])->first();

        if (! $account) {
            return back()->withErrors([
                'email' => 'Email hoặc mật khẩu không đúng.',
            ])->onlyInput('email');
        }

        // Check if account is admin or writer
        if (! in_array($account->role, [Account::ROLE_ADMIN, Account::ROLE_WRITER])) {
            return back()->withErrors([
                'email' => 'Bạn không có quyền truy cập trang quản trị.',
            ])->onlyInput('email');
        }

        // Check if account is active
        if ($account->status !== Account::STATUS_ACTIVE) {
            return back()->withErrors([
                'email' => 'Tài khoản của bạn đã bị khóa hoặc chưa được kích hoạt.',
            ])->onlyInput('email');
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors([
            'email' => 'Email hoặc mật khẩu không đúng.',
        ])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
