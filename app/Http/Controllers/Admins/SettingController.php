<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingStoreRequest;
use App\Http\Requests\Admin\SettingUpdateRequest;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SettingController extends Controller
{
    protected array $protectedKeys = [
        'site_name',
        'site_logo',
        'site_url',
        'site_title',
    ];

    public function index(Request $request)
    {
        $query = Setting::query();

        if ($keyword = $request->get('keyword')) {
            $query->where(function ($q) use ($keyword) {
                $q->where('key', 'like', "%{$keyword}%")
                    ->orWhere('label', 'like', "%{$keyword}%");
            });
        }

        if ($group = $request->get('group')) {
            $query->where('group', $group);
        }

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        if (($public = $request->get('is_public')) !== null && $public !== '') {
            $query->where('is_public', (bool) $public);
        }

        $settings_all = $query->orderBy('group')
            ->orderBy('key')
            ->paginate(20)
            ->appends($request->query());

        $groups = Setting::select('group')->distinct()->pluck('group')->filter();
        $types = $this->allowedTypes();

        return view('admins.settings.index', compact('settings_all', 'groups', 'types'));
    }

    public function create()
    {
        $setting = new Setting;
        $groups = Setting::select('group')->distinct()->pluck('group')->filter();
        $types = $this->allowedTypes();

        return view('admins.settings.create', compact('setting', 'groups', 'types'));
    }

    public function store(SettingStoreRequest $request)
    {
        $data = $request->validated();

        // Xử lý upload file nếu type = image
        if (($data['type'] ?? null) === Setting::TYPE_IMAGE && $request->hasFile('value_file')) {
            $data['value'] = $this->handleImageUpload($request->file('value_file'), $data['key']);
        }

        $data = $this->normalizeValue($data);

        Setting::create($data);

        return redirect()->route('admin.settings.index')
            ->with('success', 'Đã tạo setting thành công.');
    }

    public function edit(Setting $setting)
    {
        $groups = Setting::select('group')->distinct()->pluck('group')->filter();
        $types = $this->allowedTypes();

        return view('admins.settings.edit', compact('setting', 'groups', 'types'));
    }

    public function update(SettingUpdateRequest $request, Setting $setting)
    {
        if (in_array($setting->key, $this->protectedKeys, true) && $request->key !== $setting->key) {
            throw ValidationException::withMessages([
                'key' => 'Không thể thay đổi key của setting hệ thống.',
            ]);
        }

        $data = $request->validated();

        // Xử lý upload file nếu type = image
        if (($data['type'] ?? null) === Setting::TYPE_IMAGE && $request->hasFile('value_file')) {
            $data['value'] = $this->handleImageUpload($request->file('value_file'), $setting->key);
        }

        $data = $this->normalizeValue($data);

        // giữ nguyên key khi bị khoá
        if (in_array($setting->key, $this->protectedKeys, true)) {
            $data['key'] = $setting->key;
        }

        $setting->update($data);

        return redirect()->route('admin.settings.edit', $setting)
            ->with('success', 'Đã cập nhật setting.');
    }

    public function destroy(Setting $setting)
    {
        if (in_array($setting->key, $this->protectedKeys, true)) {
            return back()->with('error', 'Không thể xoá setting hệ thống.');
        }

        $setting->delete();

        return back()->with('success', 'Đã xoá setting.');
    }

    private function normalizeValue(array $data): array
    {
        $value = $data['value'] ?? null;

        switch ($data['type']) {
            case 'boolean':
                $data['value'] = $value ? '1' : '0';
                break;
            case 'integer':
                $data['value'] = (string) (int) $value;
                break;
            case 'float':
            case 'number':
                $data['value'] = (string) (float) $value;
                break;
            case 'json':
                $decoded = is_array($value) ? $value : json_decode($value, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw ValidationException::withMessages([
                        'value' => 'JSON không hợp lệ.',
                    ]);
                }
                $data['value'] = json_encode($decoded, JSON_UNESCAPED_UNICODE);
                break;
            case Setting::TYPE_IMAGE:
                // Với image, value đã là tên file (nếu upload), giữ nguyên
                $data['value'] = $value ?? '';
                break;
            default:
                $data['value'] = $value ?? '';
        }

        return $data;
    }

    /**
     * Upload file image cho setting (type = image) vào thư mục business.
     * Trả về tên file được lưu để ghi vào cột value.
     */
    private function handleImageUpload(\Illuminate\Http\UploadedFile $file, string $key): string
    {
        $destination = public_path('clients/assets/img/business');
        File::ensureDirectoryExists($destination, 0755, true);

        $extension = strtolower($file->getClientOriginalExtension() ?: 'webp');
        $baseName = Str::slug($key) ?: 'setting-image';
        $fileName = $baseName.'-'.time().'.'.$extension;

        $file->move($destination, $fileName);

        return $fileName;
    }

    private function allowedTypes(): array
    {
        return Setting::TYPES;
    }
}
