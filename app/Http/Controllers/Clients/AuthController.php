<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Mail\EmailVerificationMail;
use App\Mail\PasswordResetMail;
use App\Models\Account;
use App\Models\AccountEmailVerification;
use App\Services\AccountLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(
        private AccountLogService $logService
    ) {}

    /**
     * Hiển thị form đăng nhập
     */
    public function showLoginForm()
    {
        if (auth('web')->check()) {
            return redirect()->route('client.home.index')->with('warning', 'Bạn đã đăng nhập trước đó!');
        }

        // Không cần check auth, cho phép truy cập
        return view('clients.pages.auth.login.index');
    }

    /**
     * Xử lý đăng nhập
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ], [
            'email.required' => 'Vui lòng nhập email',
            'email.email' => 'Email không hợp lệ',
            'password.required' => 'Vui lòng nhập mật khẩu',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự',
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->has('remember');

        // Tìm account
        $account = Account::where('email', $credentials['email'])->first();

        if (! $account) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Email hoặc mật khẩu không đúng']);
        }

        if ($account->email_verified_at === null) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Tài khoản của bạn chưa được kích hoạt. Vui lòng kiểm tra lại email để thực hiện kích hoạt.']);
        }

        // Kiểm tra trạng thái tài khoản
        if ($account->status !== 'active') {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Tài khoản của bạn đã bị khóa hoặc tạm ngưng. Vui lòng liên hệ admin để được hỗ trợ.']);
        }

        // Kiểm tra email đã được xác thực chưa
        if (! $account->email_verified_at) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Email của bạn chưa được xác thực. Vui lòng kiểm tra email và xác thực tài khoản trước khi đăng nhập.']);
        }

        // Thử đăng nhập
        if (Auth::guard('web')->attempt($credentials, $remember)) {
            $request->session()->regenerate();

            // Cập nhật login history
            $account->login_history = now();
            $account->login_attempts = 0;
            $account->save();

            // Redirect về trang trước hoặc home
            return redirect()->intended(route('client.home.index'))->with('success', 'Đăng nhập thành công!');
        }

        // Tăng số lần thử đăng nhập sai
        if ($account) {
            $account->login_attempts = ($account->login_attempts ?? 0) + 1;
            $account->save();
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'Email hoặc mật khẩu không đúng']);
    }

    /**
     * Hiển thị form đăng ký
     */
    public function showRegisterForm()
    {
        if (auth('web')->check()) {
            return redirect()->route('client.home.index')->with('warning', 'Bạn đã đăng nhập trước đó! Vui lòng đăng xuất!');
        }

        // Không cần check auth, cho phép truy cập
        return view('clients.pages.auth.register.index');
    }

    /**
     * Xử lý đăng ký
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚĂĐĨŨƠàáâãèéêìíòóôõùúăđĩũơƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂưăạảấầẩẫậắằẳẵặẹẻẽềềểỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụủứừỬỮỰỲỴÝỶỸửữựỳỵýỷỹ\s]+$/'],
            'email' => 'required|email|max:255|unique:accounts,email',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'name.required' => 'Vui lòng nhập họ và tên',
            'name.regex' => 'Họ và tên không được chứa ký tự đặc biệt hoặc số',
            'email.required' => 'Vui lòng nhập email',
            'email.email' => 'Email không hợp lệ',
            'email.unique' => 'Email này đã được sử dụng',
            'password.required' => 'Vui lòng nhập mật khẩu',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp',
        ]);

        try {
            $account = Account::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password, // Model sẽ tự động hash qua setPasswordAttribute
                'role' => Account::ROLE_USER,
                'status' => 'inactive', // Chưa active cho đến khi xác thực email
                'last_password_changed_at' => now(),
                // email_verified_at sẽ null cho đến khi user xác thực
            ]);

            // Refresh account để đảm bảo có đầy đủ dữ liệu
            $account->refresh();

            // Log account creation
            $this->logService->log($account, 'account_created', [
                'source' => 'user_registration',
                'email_verified' => false,
            ], null);

            // Tạo token xác thực email
            $token = Str::random(80);
            $expiresAt = now()->addHours(24);

            // Xóa token cũ nếu có
            AccountEmailVerification::where('account_id', $account->id)->delete();

            // Lưu token mới
            AccountEmailVerification::create([
                'account_id' => $account->id,
                'token' => $token,
                'expires_at' => $expiresAt,
                'created_at' => now(),
            ]);

            // Tạo URL xác thực
            $verificationUrl = route('client.auth.verify-email', ['token' => $token]);

            // Đảm bảo account có đầy đủ dữ liệu
            $account->refresh();
            Log::info('Sending email verification', [
                'account_id' => $account->id,
                'name' => $account->name,
                'email' => $account->email,
            ]);

            // Gửi email xác thực
            try {
                Mail::to($account->email)->send(new EmailVerificationMail($account, $verificationUrl));
                Log::info('Email verification sent successfully to: '.$account->email);
            } catch (\Exception $e) {
                Log::error('Error sending email verification: '.$e->getMessage());
                // Vẫn tiếp tục, không fail đăng ký nếu gửi email lỗi
            }

            return redirect()->route('client.auth.login')
                ->with('status', 'Đăng ký thành công! Vui lòng kiểm tra email để xác thực tài khoản trước khi đăng nhập.');
        } catch (\Exception $e) {
            Log::error('Register error: '.$e->getMessage());

            return back()
                ->withInput()
                ->withErrors(['email' => 'Đã có lỗi xảy ra. Vui lòng thử lại sau.']);
        }
    }

    /**
     * Xử lý đăng xuất
     */
    public function logout(Request $request)
    {
        $accountId = auth('web')->id();
        $sessionId = session()->getId();

        Auth::guard('web')->logout();

        // Xóa cache view payload (nếu có)
        if ($accountId) {
            Cache::forget('view_payload_'.$accountId.'_'.$sessionId);
        }
        Cache::forget('view_payload_guest_'.$sessionId);

        // Invalidate và regenerate session để đảm bảo session mới
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('client.auth.login')->with('success', 'Đăng xuất thành công!');
    }

    /**
     * Hiển thị form quên mật khẩu
     */
    public function showForgotPasswordForm()
    {
        // Không cần check auth, cho phép truy cập
        return view('clients.pages.auth.forgot-password.index');
    }

    /**
     * Gửi link đặt lại mật khẩu
     */
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'Vui lòng nhập email',
            'email.email' => 'Email không hợp lệ',
        ]);

        $email = $request->email;
        $account = Account::where('email', $email)->first();

        // Kiểm tra email có tồn tại không
        if (! $account) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'Email này không tồn tại trong hệ thống.']);
        }

        try {
            // Tạo token
            $token = Str::random(64);

            // Lưu token vào database (xóa token cũ nếu có)
            DB::table('password_reset_tokens')
                ->where('email', $email)
                ->delete();

            DB::table('password_reset_tokens')->insert([
                'email' => $email,
                'token' => Hash::make($token),
                'created_at' => now(),
            ]);

            // Tạo reset URL
            $resetUrl = route('client.auth.reset-password', ['token' => $token]);

            Log::info('Sending password reset email to: '.$email);

            // Gửi email
            try {
                Mail::to($account->email)->send(new PasswordResetMail($account, $resetUrl));
                Log::info('Password reset email sent successfully to: '.$email);
            } catch (\Exception $e) {
                Log::error('Error sending password reset email: '.$e->getMessage());
                throw $e; // Re-throw để catch ở ngoài xử lý
            }

            return back()->with('status', 'Chúng tôi đã gửi link đặt lại mật khẩu tới email của bạn. Vui lòng kiểm tra hộp thư.');
        } catch (\Exception $e) {
            Log::error('Error sending password reset email: '.$e->getMessage());

            return back()
                ->withInput()
                ->withErrors(['email' => 'Đã có lỗi xảy ra khi gửi email. Vui lòng thử lại sau.']);
        }
    }

    /**
     * Hiển thị form đặt lại mật khẩu
     */
    public function showResetPasswordForm($token)
    {
        // Không cần check auth, cho phép truy cập
        // Validate token
        $tokenExists = DB::table('password_reset_tokens')
            ->where('created_at', '>', now()->subHours(24))
            ->get()
            ->first(function ($record) use ($token) {
                return Hash::check($token, $record->token);
            });

        if (! $tokenExists) {
            return redirect()->route('client.auth.forgot-password')
                ->with('error', 'Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu lại.');
        }

        return view('clients.pages.auth.reset-password.index', [
            'token' => $token,
        ]);
    }

    /**
     * Xử lý đặt lại mật khẩu
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'token.required' => 'Token không hợp lệ',
            'password.required' => 'Vui lòng nhập mật khẩu mới',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp',
        ]);

        // Tìm token hợp lệ
        $tokenRecord = DB::table('password_reset_tokens')
            ->where('created_at', '>', now()->subHours(24))
            ->get()
            ->first(function ($record) use ($request) {
                return Hash::check($request->token, $record->token);
            });

        if (! $tokenRecord) {
            return back()
                ->withInput()
                ->withErrors(['token' => 'Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.']);
        }

        // Tìm account
        $account = Account::where('email', $tokenRecord->email)->first();

        if (! $account) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'Tài khoản không tồn tại.']);
        }

        // Kiểm tra giới hạn đổi mật khẩu (1 lần/ngày)
        // if ($account->last_password_changed_at && $account->last_password_changed_at->isToday()) {
        //     return back()
        //         ->withInput()
        //         ->withErrors(['password' => 'Bạn chỉ có thể đổi mật khẩu một lần mỗi ngày. Vui lòng thử lại vào ngày mai.']);
        // }

        try {
            // Cập nhật mật khẩu (Model sẽ tự động hash qua setPasswordAttribute)
            $account->password = $request->password;
            $account->last_password_changed_at = now();
            $account->save();

            // Log password reset
            $this->logService->log($account, 'password_reset', [
                'source' => 'user_forgot_password',
            ], null);

            // Xóa token
            DB::table('password_reset_tokens')
                ->where('email', $tokenRecord->email)
                ->delete();

            return redirect()->route('client.auth.login')
                ->with('success', 'Đặt lại mật khẩu thành công! Vui lòng đăng nhập với mật khẩu mới.');
        } catch (\Exception $e) {
            Log::error('Error resetting password: '.$e->getMessage());

            return back()
                ->withInput()
                ->withErrors(['password' => 'Đã có lỗi xảy ra. Vui lòng thử lại sau.']);
        }
    }

    /**
     * Xác thực email
     */
    public function verifyEmail($token)
    {
        try {
            // Tìm token hợp lệ
            $verification = AccountEmailVerification::where('token', $token)
                ->where('expires_at', '>', now())
                ->first();

            if (! $verification) {
                return redirect()->route('client.auth.login')
                    ->with('error', 'Link xác thực không hợp lệ hoặc đã hết hạn. Vui lòng đăng ký lại.');
            }

            $account = $verification->account;

            if (! $account) {
                return redirect()->route('client.auth.login')
                    ->with('error', 'Tài khoản không tồn tại.');
            }

            // Kiểm tra email đã được xác thực chưa
            if ($account->email_verified_at) {
                // Xóa token đã sử dụng
                $verification->delete();

                return redirect()->route('client.auth.login')
                    ->with('status', 'Email của bạn đã được xác thực trước đó. Vui lòng đăng nhập.');
            }

            // Xác thực email và kích hoạt tài khoản
            $oldStatus = $account->status;
            $account->email_verified_at = now();
            $account->status = 'active'; // Chuyển từ inactive sang active
            $account->save();

            // Log email verification
            $this->logService->log($account, 'email_verified', [
                'source' => 'user_verification',
                'old_status' => $oldStatus,
                'new_status' => 'active',
            ], null);

            // Xóa token đã sử dụng
            $verification->delete();

            Log::info('Email verified successfully for account: '.$account->email);

            return redirect()->route('client.auth.login')
                ->with('success', 'Xác thực email thành công! Bạn có thể đăng nhập ngay bây giờ.');
        } catch (\Exception $e) {
            Log::error('Error verifying email: '.$e->getMessage());

            return redirect()->route('client.auth.login')
                ->with('error', 'Đã có lỗi xảy ra khi xác thực email. Vui lòng thử lại sau.');
        }
    }
}
