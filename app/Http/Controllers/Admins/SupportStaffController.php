<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\SupportStaff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class SupportStaffController extends Controller
{
    public function index()
    {
        $staffs = SupportStaff::orderBy('sort_order')->get();
        return view('admins.support_staff.index', compact('staffs'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'   => ['required', 'string', 'max:255'],
            'role'   => ['nullable', 'string', 'max:255'],
            'phone'  => ['nullable', 'string', 'max:50'],
            'zalo'   => ['nullable', 'string', 'max:50'],
            'color'  => ['nullable', 'string', 'max:20'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ], [
            'name.required'  => 'Vui lòng nhập họ tên.',
            'name.string'    => 'Họ tên không hợp lệ.',
            'name.max'       => 'Họ tên không được vượt quá 255 ký tự.',
        
            'role.max'       => 'Vai trò không được vượt quá 255 ký tự.',
        
            'phone.max'      => 'Số điện thoại không được vượt quá 50 ký tự.',
            'zalo.max'       => 'Zalo không được vượt quá 50 ký tự.',
        
            'color.max'      => 'Mã màu không được vượt quá 20 ký tự.',
        
            'avatar.image'   => 'Ảnh đại diện phải là file hình ảnh.',
            'avatar.max'     => 'Ảnh đại diện không được vượt quá 2MB.',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Thông tin nhập vào chưa hợp lệ.',
                'errors'  => $validator->errors()
            ], 422);
        }
        
        $data = $validator->validated();

        $maxOrder = SupportStaff::max('sort_order') ?? 0;
        $data['sort_order'] = $maxOrder + 1;
        $data['is_active'] = $request->has('is_active');

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $this->storeAvatar($request->file('avatar'), $data['name'], $data['phone'] ?? null);
        }

        SupportStaff::create($data);

        // Clear cache để cập nhật CSKH ở client
        Cache::forget('support_staff_active');

        return redirect()->route('admin.support-staff.index')->with('success', 'Đã thêm CSKH.');
    }

    public function update(Request $request, SupportStaff $supportStaff)
    {
        $validator = Validator::make($request->all(), [
            'name'   => ['required', 'string', 'max:255'],
            'role'   => ['nullable', 'string', 'max:255'],
            'phone'  => ['nullable', 'string', 'max:50'],
            'zalo'   => ['nullable', 'string', 'max:50'],
            'color'  => ['nullable', 'string', 'max:20'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ], [
            'name.required'  => 'Vui lòng nhập họ tên.',
            'name.string'    => 'Họ tên không hợp lệ.',
            'name.max'       => 'Họ tên không được vượt quá 255 ký tự.',
        
            'role.max'       => 'Vai trò không được vượt quá 255 ký tự.',
        
            'phone.max'      => 'Số điện thoại không được vượt quá 50 ký tự.',
            'zalo.max'       => 'Zalo không được vượt quá 50 ký tự.',
        
            'color.max'      => 'Mã màu không được vượt quá 20 ký tự.',
        
            'avatar.image'   => 'Ảnh đại diện phải là file hình ảnh.',
            'avatar.max'     => 'Ảnh đại diện không được vượt quá 2MB.',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput()->with('error', 'Thông tin nhập vào chưa hợp lệ.');
        }
        
        $data = $validator->validated();

        $data['is_active'] = $request->has('is_active');

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $this->storeAvatar($request->file('avatar'), $data['name'], $data['phone'] ?? null);
        }

        $supportStaff->update($data);

        // Clear cache để cập nhật CSKH ở client
        Cache::forget('support_staff_active');

        return redirect()->route('admin.support-staff.index')->with('success', 'Đã cập nhật CSKH.');
    }

    public function destroy(SupportStaff $supportStaff)
    {
        $supportStaff->delete();

        // Clear cache để cập nhật CSKH ở client
        Cache::forget('support_staff_active');

        return redirect()->route('admin.support-staff.index')->with('success', 'Đã xóa CSKH.');
    }

    public function reorder(Request $request)
    {
        $data = $request->validate([
            'orders' => ['required', 'array'],
            'orders.*.id' => ['required', 'integer', 'exists:support_staff,id'],
            'orders.*.sort_order' => ['required', 'integer'],
        ]);

        foreach ($data['orders'] as $item) {
            SupportStaff::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        // Clear cache để cập nhật thứ tự CSKH ở client
        Cache::forget('support_staff_active');

        return response()->json(['success' => true]);
    }

    private function storeAvatar($file, string $name, ?string $phone): string
    {
        $safeName = \Str::slug($name);
        $safePhone = $phone ? preg_replace('/\D+/', '', $phone) : null;
        $base = $safeName ?: 'staff';
        if ($safePhone) {
            $base .= '-'.$safePhone;
        }
        $ext = $file->getClientOriginalExtension() ?: 'png';
        $filename = $base.'.'.$ext;

        $dest = public_path('clients/assets/img/avatars');
        if (! is_dir($dest)) {
            mkdir($dest, 0755, true);
        }

        // Nếu trùng tên, thêm hậu tố
        $counter = 1;
        $finalName = $filename;
        while (file_exists($dest.'/'.$finalName)) {
            $finalName = $base.'-'.$counter.'.'.$ext;
            $counter++;
        }

        $file->move($dest, $finalName);

        return $finalName;
    }
}

