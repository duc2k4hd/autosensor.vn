@extends('admins.layouts.master')

@section('title', 'Chỉnh sửa setting')
@section('page-title', '⚙️ Chỉnh sửa setting')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/settings-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .card {
            background:#fff;
            border-radius:10px;
            padding:16px;
            box-shadow:0 1px 6px rgba(15,23,42,0.06);
            margin-bottom:16px;
        }
        .card > h3 {
            margin:0 0 8px;
            font-size:16px;
            font-weight:600;
            color:#0f172a;
        }
        .grid-3 {
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
            gap:12px 16px;
        }
        .form-control, textarea, select {
            width:100%;
            padding:8px 10px;
            border:1px solid #cbd5f5;
            border-radius:6px;
            font-size:13px;
        }
        label {
            display:block;
            font-size:13px;
            font-weight:500;
            margin-bottom:4px;
            color:#111827;
        }
    </style>
@endpush

@section('content')
    <form action="{{ route('admin.settings.update', $setting) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div style="display:flex;justify-content:flex-end;gap:10px;margin-bottom:16px;">
            <a href="{{ route('admin.settings.index') }}" class="btn btn-secondary">↩️ Quay lại danh sách</a>
            <button type="submit" class="btn btn-primary">💾 Cập nhật</button>
        </div>

        <div class="card">
            <h3>Thông tin cơ bản</h3>
            <div class="grid-3">
                <div>
                    <label>Label hiển thị</label>
                    <input type="text" name="label" class="form-control"
                           value="{{ old('label', $setting->label) }}">
                </div>
                <div>
                    <label>Key (unique)</label>
                    <input type="text" name="key" class="form-control"
                           value="{{ old('key', $setting->key) }}" required>
                </div>
                <div>
                    <label>Nhóm</label>
                    <input type="text" name="group" list="setting-groups" class="form-control"
                           value="{{ old('group', $setting->group) }}">
                    <datalist id="setting-groups">
                        @foreach($groups as $group)
                            <option value="{{ $group }}">{{ $group }}</option>
                        @endforeach
                    </datalist>
                </div>
                <div>
                    <label>Kiểu dữ liệu</label>
                    <select name="type" class="form-control" required>
                        @foreach($types as $type)
                            <option value="{{ $type }}" {{ old('type', $setting->type) === $type ? 'selected' : '' }}>
                                {{ ucfirst($type) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Public</label>
                    <select name="is_public" class="form-control">
                        <option value="1" {{ old('is_public', $setting->is_public) ? 'selected' : '' }}>Hiển thị</option>
                        <option value="0" {{ old('is_public', $setting->is_public) ? '' : 'selected' }}>Ẩn</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card">
            <h3>Giá trị & mô tả</h3>
            <div class="grid-3">
                <div style="grid-column: span 2;">
                    <label>Giá trị</label>
                    <textarea name="value" rows="6" class="form-control">{{ old('value', $setting->value) }}</textarea>
                    <small style="color:#94a3b8;">
                        Nhập đúng định dạng theo kiểu dữ liệu.
                        Nếu kiểu là <strong>image</strong>, bạn có thể upload file bên dưới, hệ thống sẽ tự lưu tên file trong thư mục
                        <code>public/clients/assets/img/business/</code>.
                    </small>
                    <div style="margin-top:8px;">
                        <label>Upload file (chỉ dùng khi kiểu = image)</label>
                        <input type="file" name="value_file" class="form-control" accept="image/*">
                        @if($setting->type === \App\Models\Setting::TYPE_IMAGE && $setting->value)
                            <div style="margin-top:6px;">
                                <span style="font-size:12px;color:#64748b;">File hiện tại:</span>
                                <a href="{{ asset('clients/assets/img/business/' . $setting->value) }}" target="_blank">
                                    {{ $setting->value }}
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
                <div>
                    <label>Mô tả</label>
                    <textarea name="description" rows="6" class="form-control">{{ old('description', $setting->description) }}</textarea>
                </div>
            </div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:10px;margin-bottom:16px;">
            <a href="{{ route('admin.settings.index') }}" class="btn btn-secondary">↩️ Quay lại danh sách</a>
            <button type="submit" class="btn btn-primary">💾 Cập nhật</button>
        </div>
    </form>
@endsection


