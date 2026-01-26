@extends('admins.layouts.master')

@section('title', 'Tạo setting mới')
@section('page-title', '⚙️ Tạo setting')

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
        .checkbox-row {
            display:flex;
            align-items:center;
            gap:8px;
            height:38px;
        }
        .checkbox-row > label {
            margin:0;
        }
    </style>
@endpush

@section('content')
    <form action="{{ route('admin.settings.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div style="display:flex;justify-content:flex-end;gap:10px;margin-bottom:16px;">
            <a href="{{ route('admin.settings.index') }}" class="btn btn-secondary">↩️ Quay lại danh sách</a>
            <button type="submit" class="btn btn-primary">💾 Lưu setting</button>
        </div>

        <div class="card">
            <h3>Thông tin cơ bản</h3>
            <div class="grid-3">
                <div>
                    <label>Label hiển thị</label>
                    <input type="text" name="label" class="form-control" value="{{ old('label') }}">
                </div>
                <div>
                    <label>Key (unique)</label>
                    <input type="text" name="key" class="form-control" value="{{ old('key') }}" required>
                </div>
                <div>
                    <label>Nhóm</label>
                    <input type="text" name="group" list="setting-groups" class="form-control" value="{{ old('group') }}">
                    <datalist id="setting-groups">
                        @foreach($groups as $group)
                            <option value="{{ $group }}">{{ $group }}</option>
                        @endforeach
                    </datalist>
                </div>
                <div>
                    <label>Kiểu dữ liệu</label>
                    <select name="type" class="form-control" id="setting-type" required>
                        @foreach($types as $type)
                            <option value="{{ $type }}" {{ old('type') === $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Public</label>
                    <div class="checkbox-row">
                        <input type="hidden" name="is_public" value="0">
                        <input type="checkbox" id="setting-is-public" name="is_public" value="1"
                               {{ old('is_public', true) ? 'checked' : '' }}>
                        <label for="setting-is-public">Hiển thị</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <h3>Giá trị & mô tả</h3>
            <div class="grid-3">
                <div style="grid-column: span 2;">
                    <label>Giá trị</label>

                    {{-- boolean input --}}
                    <div class="checkbox-row" id="setting-value-boolean-wrap" style="display:none;">
                        <input type="hidden" name="value" value="0" id="setting-value-boolean-hidden" disabled>
                        <input type="checkbox" id="setting-value-boolean" name="value" value="1" disabled>
                        <label for="setting-value-boolean">Bật</label>
                    </div>

                    {{-- default textarea --}}
                    <textarea name="value" rows="6" class="form-control" id="setting-value-textarea">{{ old('value') }}</textarea>

                    <small style="color:#94a3b8;">
                        Tùy vào kiểu dữ liệu, vui lòng nhập đúng định dạng.
                        Nếu chọn kiểu <strong>image</strong>, bạn có thể upload file bên dưới, hệ thống sẽ tự lưu tên file trong thư mục
                        <code>public/clients/assets/img/business/</code>.
                    </small>
                    <div style="margin-top:8px;">
                        <label>Upload file (chỉ dùng khi kiểu = image)</label>
                        <input type="file" name="value_file" class="form-control" accept="image/*">
                    </div>
                </div>
                <div>
                    <label>Mô tả</label>
                    <textarea name="description" rows="6" class="form-control">{{ old('description') }}</textarea>
                </div>
            </div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:10px;margin-bottom:16px;">
            <a href="{{ route('admin.settings.index') }}" class="btn btn-secondary">↩️ Quay lại danh sách</a>
            <button type="submit" class="btn btn-primary">💾 Lưu setting</button>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            const typeSelect = document.getElementById('setting-type');
            const boolWrap = document.getElementById('setting-value-boolean-wrap');
            const boolCheckbox = document.getElementById('setting-value-boolean');
            const boolHidden = document.getElementById('setting-value-boolean-hidden');
            const textarea = document.getElementById('setting-value-textarea');

            if (!typeSelect || !boolWrap || !boolCheckbox || !boolHidden || !textarea) return;

            function sync() {
                const isBool = typeSelect.value === 'boolean';
                boolWrap.style.display = isBool ? 'flex' : 'none';
                textarea.style.display = isBool ? 'none' : 'block';
                textarea.disabled = isBool;

                boolCheckbox.disabled = !isBool;
                boolHidden.disabled = !isBool;

                if (isBool) {
                    const raw = (textarea.value || '').trim().toLowerCase();
                    boolCheckbox.checked = (raw === '1' || raw === 'true');
                }
            }

            typeSelect.addEventListener('change', sync);
            sync();
        })();
    </script>
@endpush

