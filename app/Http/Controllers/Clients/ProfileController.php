<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\ProductViewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function __construct(
        private ProductViewService $productViewService
    ) {}

    /**
     * Hiển thị trang profile
     */
    public function index()
    {
        $account = Auth::guard('web')->user();

        if (! $account) {
            return redirect()->route('client.auth.login');
        }

        $orders = Order::query()
            ->withCount('items')
            ->where('account_id', $account->id)
            ->latest('created_at')
            ->limit(10)
            ->get();

        $recentViews = $this->productViewService->getRecentProducts(10);

        return view('clients.pages.profile.index', compact('account', 'orders', 'recentViews'));
    }

    /**
     * Cập nhật thông tin profile
     */
    public function update(Request $request)
    {
        $account = Auth::guard('web')->user();

        if (! $account) {
            return redirect()->route('client.auth.login');
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚĂĐĨŨƠàáâãèéêìíòóôõùúăđĩũơƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂưăạảấầẩẫậắằẳẵặẹẻẽềềểỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụủứừỬỮỰỲỴÝỶỸửữựỳỵýỷỹ\s]+$/'],
            'email' => ['required', 'email', 'max:255', 'unique:accounts,email,'.$account->id],
        ], [
            'name.required' => 'Vui lòng nhập họ và tên',
            'name.regex' => 'Tên không được chứa số hoặc ký tự đặc biệt',
            'email.required' => 'Vui lòng nhập email',
            'email.email' => 'Email không hợp lệ',
            'email.unique' => 'Email này đã được sử dụng',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $account->name = $request->name;
            $account->email = $request->email;
            $account->save();

            return redirect()->route('client.profile.index')
                ->with('success', 'Cập nhật thông tin thành công!');
        } catch (\Exception $e) {
            Log::error('Profile update error: '.$e->getMessage());

            return back()
                ->withInput()
                ->withErrors(['error' => 'Đã có lỗi xảy ra. Vui lòng thử lại sau.']);
        }
    }

    /**
     * Đổi mật khẩu
     */
    public function changePassword(Request $request)
    {
        $account = Auth::guard('web')->user();

        if (! $account) {
            return redirect()->route('client.auth.login');
        }

        $validator = Validator::make($request->all(), [
            'current_password' => ['required'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại',
            'password.required' => 'Vui lòng nhập mật khẩu mới',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp',
        ]);

        // Kiểm tra mật khẩu hiện tại
        if (! Hash::check($request->current_password, $account->password)) {
            return back()
                ->withErrors(['current_password' => 'Mật khẩu hiện tại không đúng']);
        }

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $account->password = $request->password; // Model sẽ tự động hash qua setPasswordAttribute
            $account->last_password_changed_at = now();
            $account->save();

            return redirect()->route('client.profile.index')
                ->with('success', 'Đổi mật khẩu thành công!');
        } catch (\Exception $e) {
            Log::error('Change password error: '.$e->getMessage());

            return back()
                ->withInput()
                ->withErrors(['error' => 'Đã có lỗi xảy ra. Vui lòng thử lại sau.']);
        }
    }
}
