@extends('admins.layouts.master')

@section('title', 'Import Bài viết từ Excel')
@section('page-title', '📥 Import Bài viết')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/imports-excel.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .container { max-width: 900px; margin: 0 auto; background: white; border-radius:8px; padding:30px; }
        .subtitle { color:#666; margin-bottom:20px; }
        input[type=file] { width:100%; padding:10px; border:2px dashed #ddd; border-radius:6px; background:#fafafa }
        .btn { padding:10px 18px; border-radius:6px; font-weight:600 }
        .info-box { background:#e7f3ff; border-left:4px solid #007bff; padding:12px; border-radius:6px }
    </style>
@endpush

@section('content')
    <div class="container">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h1>📥 Import / Export Bài viết</h1>
                <p class="subtitle">Xuất hoặc nhập file Excel cho bài viết. File mẫu và cấu trúc phía dưới.</p>
            </div>
            <div style="display:flex; gap:10px;">
                <a href="{{ route('admin.posts.index') }}" class="btn btn-secondary">↩️ Quản lý bài viết</a>
                <a href="{{ route('admin.posts.export-template') }}" class="btn btn-primary">⬇️ Tải file mẫu</a>
                <a href="{{ route('admin.posts.export') }}" class="btn btn-primary">⬇️ Export tất cả</a>
            </div>
        </div>

        @if(session('success'))
            <div style="margin-top:12px; padding:12px; background:#d4edda; border-radius:6px">✅ {!! session('success') !!}
                @if(session('log_file'))
                    <div style="margin-top:8px">📄 File log: <code>{{ session('log_file') }}</code></div>
                @endif
            </div>
        @endif

        @if(session('error'))
            <div style="margin-top:12px; padding:12px; background:#f8d7da; border-radius:6px">❌ {!! session('error') !!}
                @if(session('log_file'))
                    <div style="margin-top:8px">📄 File log: <code>{{ session('log_file') }}</code></div>
                @endif
            </div>
        @endif

        <div class="info-box" style="margin-top:16px">
            <strong>📋 Cấu trúc file (1 sheet):</strong>
            <div style="margin-top:8px">
                <code>title, slug, status, category_slug, tags, excerpt, content, image_paths, published_at, created_by, meta_title, meta_description, meta_keywords</code>
            </div>
            <div style="margin-top:8px; color:#444; font-size:13px">- `tags` và `meta_keywords` là danh sách cách nhau bởi dấu phẩy. Nếu tag chưa tồn tại hệ thống sẽ tự tạo.</div>
        </div>

        <form action="{{ route('admin.posts.import.process') }}" method="POST" enctype="multipart/form-data" style="margin-top:18px">
            @csrf
            <div style="margin-bottom:12px">
                <label for="excel_file">Chọn file Excel (.xlsx)</label>
                <input type="file" name="excel_file" id="excel_file" accept=".xlsx,.xls" required>
                @error('excel_file')<div style="color:#dc3545">{{ $message }}</div>@enderror
            </div>
            <button class="btn btn-primary">🚀 Bắt đầu Import</button>
        </form>
    </div>
@endsection
