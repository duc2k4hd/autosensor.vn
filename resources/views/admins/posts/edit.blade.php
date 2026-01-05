@extends('admins.layouts.master')

@section('page-title', 'Chỉnh sửa bài viết')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/posts-icon.png') }}" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.css" rel="stylesheet">
@endpush

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-1">📝 Chỉnh sửa: {{ $post->title }}</h2>
            <p class="text-muted mb-0">Slug: {{ $post->slug }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('client.blog.show', $post) }}" target="_blank" class="btn btn-outline-secondary">
                👁 Xem trên site
            </a>
            <form action="{{ route('admin.posts.publish', $post) }}" method="POST">
                @csrf
                <input type="hidden" name="published_at" value="{{ now()->format('Y-m-d\TH:i') }}">
                <button class="btn btn-success" type="submit">🚀 Xuất bản ngay</button>
            </form>
            <form action="{{ route('admin.posts.archive', $post) }}" method="POST">
                @csrf
                <button class="btn btn-outline-warning" type="submit">🗂 Lưu trữ</button>
            </form>
            <form action="{{ route('admin.posts.duplicate', $post) }}" method="POST">
                @csrf
                <button class="btn btn-outline-primary" type="submit">📄 Nhân bản</button>
            </form>
            <form action="{{ route('admin.posts.destroy', $post) }}" method="POST" onsubmit="return confirm('Xóa bài viết này?')">
                @csrf
                @method('DELETE')
                <button class="btn btn-outline-danger" type="submit">🗑 Xóa</button>
            </form>
        </div>
    </div>

    <form action="{{ route('admin.posts.update', $post) }}" method="POST" novalidate>
        @csrf
        @method('PUT')
        @include('admins.posts.partials.form', [
            'post' => $post,
            'categories' => $categories,
            'tags' => $tags,
            'postTags' => $postTags ?? collect(),
            'seoInsights' => $seoInsights ?? ['score' => 0, 'issues' => [], 'suggestions' => []],
            'mediaPicker' => $mediaPicker ?? [],
        ])
        <div class="mt-4 d-flex justify-content-end gap-2">
            <a href="{{ route('admin.posts.index') }}" class="btn btn-outline-secondary">Quay lại</a>
            <button class="btn btn-primary">Cập nhật</button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Khởi tạo TomSelect cho category_id
    if (document.querySelector('select[name="category_id"]')) {
        new TomSelect('select[name="category_id"]', {
            placeholder: '-- Chọn danh mục --',
            allowEmptyOption: true,
            create: false,
            sortField: {
                field: 'text',
                direction: 'asc'
            }
        });
    }

    // Khởi tạo TomSelect cho status
    if (document.querySelector('select[name="status"]')) {
        new TomSelect('select[name="status"]', {
            placeholder: 'Chọn trạng thái',
            allowEmptyOption: false,
            create: false
        });
    }

    // Khởi tạo TomSelect cho tag_ids (multiple select)
    const tagSelectEl = document.querySelector('select[name="tag_ids[]"]');
    if (tagSelectEl) {
        // Lưu tag IDs ban đầu từ database để đảm bảo không mất tags
        const initialTagIds = Array.from(tagSelectEl.selectedOptions).map(opt => parseInt(opt.value));
        
        const tomSelect = new TomSelect('select[name="tag_ids[]"]', {
            placeholder: 'Chọn tags từ danh sách...',
            plugins: ['remove_button'],
            maxItems: null,
            create: false,
            sortField: {
                field: 'text',
                direction: 'asc'
            }
        });

        // Đảm bảo form gửi đủ tags khi submit
        const form = tagSelectEl.closest('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                // Xóa các hidden inputs cũ (tránh duplicate)
                form.querySelectorAll('input[type="hidden"][name="tag_ids[]"]').forEach(input => {
                    if (!input.closest('select')) {
                        input.remove();
                    }
                });

                // Lấy tất cả tags đã chọn từ TomSelect (loại bỏ duplicate và null/undefined)
                const selectedValues = [...new Set(tomSelect.getValue()
                    .map(v => parseInt(v))
                    .filter(v => !isNaN(v) && v > 0))];
                
                // Đảm bảo tất cả tags được gửi (không bị thiếu do TomSelect)
                selectedValues.forEach(tagId => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'tag_ids[]';
                    input.value = tagId;
                    form.appendChild(input);
                });
            });
        }
    }
});
</script>
@endpush


