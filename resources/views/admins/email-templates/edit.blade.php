@extends('admins.layouts.master')

@section('title', $emailTemplate->id ? 'Sửa Email Template' : 'Tạo Email Template')

@section('content')
<div class="container-fluid">
    <h2><i class="fas fa-envelope"></i> {{ $emailTemplate->id ? 'Sửa' : 'Tạo' }} Email Template</h2>

    <form action="{{ $emailTemplate->id ? route('admin.email-templates.update', $emailTemplate) : route('admin.email-templates.store') }}" method="POST" class="mt-4">
        @csrf
        @if($emailTemplate->id)
            @method('PUT')
        @endif

        <div class="card">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Key <span class="text-danger">*</span></label>
                    <input type="text" name="key" class="form-control" value="{{ old('key', $emailTemplate->key) }}" required {{ $emailTemplate->id ? 'readonly' : '' }}>
                    <small class="text-muted">Unique key: order_confirmation, password_reset, etc.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tên <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $emailTemplate->name) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Subject <span class="text-danger">*</span></label>
                    <input type="text" name="subject" class="form-control" value="{{ old('subject', $emailTemplate->subject) }}" required>
                    <small class="text-muted">Có thể dùng biến: @{{name}}, @{{email}}, etc.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Body (HTML) <span class="text-danger">*</span></label>
                    <textarea name="body" class="form-control" rows="15" required>{{ old('body', $emailTemplate->body) }}</textarea>
                    <small class="text-muted">Có thể dùng biến: @{{name}}, @{{email}}, etc.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $emailTemplate->is_active) ? 'checked' : '' }}>
                        Hoạt động
                    </label>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Lưu</button>
                    <a href="{{ route('admin.email-templates.index') }}" class="btn btn-secondary">Hủy</a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

