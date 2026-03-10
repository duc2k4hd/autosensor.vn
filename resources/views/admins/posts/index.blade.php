@extends('admins.layouts.master')

@section('page-title', 'Quản lý bài viết')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/posts-icon.png') }}" type="image/x-icon">
@endpush

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">📚 Quản lý bài viết</h2>
            <p class="text-muted mb-0">Theo dõi, lọc và xuất bản nội dung như một mini CMS</p>
        </div>
        <div class="d-flex gap-2">
            @if(Route::has('admin.posts.import'))
                <a href="{{ route('admin.posts.import') }}" class="menu-item {{ request()->routeIs('admin.posts.import*') ? 'active' : '' }}">
                    <span class="menu-item-icon">📥</span>
                    Import Bài viết
                </a>
            @endif
            <a href="{{ route('admin.posts.create') }}" class="btn btn-primary">
                ✍️ Viết bài mới
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form action="{{ route('admin.posts.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label text-uppercase text-muted small">Trạng thái</label>
                    <select name="status" class="form-select">
                        <option value="">Tất cả</option>
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-uppercase text-muted small">Danh mục</label>
                    <select name="category_id" class="form-select">
                        <option value="">Tất cả</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? '') == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-uppercase text-muted small">Tag</label>
                    <select name="tag_id" class="form-select">
                        <option value="">Tất cả</option>
                        @foreach($tags as $tag)
                            <option value="{{ $tag->id }}" @selected(($filters['tag_id'] ?? '') == $tag->id)>
                                {{ $tag->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-uppercase text-muted small">Tác giả</label>
                    <select name="author_id" class="form-select">
                        <option value="">Tất cả</option>
                        @foreach($authors as $author)
                            <option value="{{ $author->id }}" @selected(($filters['author_id'] ?? '') == $author->id)>
                                {{ $author->name ?? $author->email }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-uppercase text-muted small">Ngày từ</label>
                    <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label text-uppercase text-muted small">Ngày đến</label>
                    <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label text-uppercase text-muted small">Nổi bật</label>
                    <select name="is_featured" class="form-select">
                        <option value="">Tất cả</option>
                        <option value="1" @selected(($filters['is_featured'] ?? '') === '1')>Chỉ nổi bật</option>
                        <option value="0" @selected(($filters['is_featured'] ?? '') === '0')>Không nổi bật</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-uppercase text-muted small">Thiếu ảnh</label>
                    <select name="without_images" class="form-select">
                        <option value="">Không lọc</option>
                        <option value="1" @selected(($filters['without_images'] ?? '') === '1')>Chỉ bài chưa có ảnh</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-uppercase text-muted small">Số lượng</label>
                    <select name="per_page" class="form-select" onchange="this.form.submit()">
                        @foreach([20, 100, 500, 2000, 5000] as $val)
                            <option value="{{ $val }}" @selected(($filters['per_page'] ?? 20) == $val)>{{ $val }} bài / trang</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 text-end ms-auto">
                    <button type="submit" class="btn btn-dark w-100">Lọc kết quả</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.posts.index') }}" class="btn btn-outline-secondary w-100">Xóa lọc</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                    <tr>
                        <th style="width:40px">
                            <input type="checkbox" id="selectAll" class="form-check-input">
                        </th>
                        <th style="width:40px">ID</th>
                        <th>Tiêu đề</th>
                        <th>Danh mục</th>
                        <th>Trạng thái</th>
                        <th>Nổi bật</th>
                        <th>Lượt xem</th>
                        <th>Tác giả</th>
                        <th>Xuất bản</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                    </thead>
                    <tbody>
                    @php
                        $statusBadge = [
                            'draft' => 'secondary',
                            'pending' => 'warning',
                            'published' => 'success',
                            'archived' => 'dark',
                            'trashed' => 'danger',
                        ];
                    @endphp
                    @forelse($posts as $post)
                        <tr data-id="{{ $post->id }}">
                            <td>
                                <input type="checkbox" name="selected[]" value="{{ $post->id }}" class="form-check-input item-checkbox">
                            </td>
                            <td>#{{ $post->id }}</td>
                            <td>
                                <div class="fw-semibold">{{ $post->title }}</div>
                                <div class="text-muted small">{{ $post->slug }}</div>
                                @php
                                    $postTagIds = $post->tag_ids ?? [];
                                    $tagNames = null;
                                    if (!empty($postTagIds) && is_array($postTagIds)) {
                                        $tagNames = $tags->whereIn('id', $postTagIds)->pluck('name')->implode(', ');
                                    }
                                @endphp
                                <div class="small text-muted">Tags: {{ $tagNames ?: '—' }}</div>
                            </td>
                            <td>{{ $post->category?->name ?? '—' }}</td>
                            <td>
                                @if($post->trashed())
                                    <span class="badge bg-danger">Đã xóa</span>
                                @else
                                    <span class="badge bg-{{ $statusBadge[$post->status] ?? 'secondary' }}">
                                        {{ $statusOptions[$post->status] ?? ucfirst($post->status) }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($post->is_featured)
                                    <span class="badge bg-gradient text-uppercase">⭐</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ number_format($post->views) }}</td>
                            <td>{{ $post->author?->name ?? $post->author?->email ?? '—' }}</td>
                            <td>
                                @if($post->published_at)
                                    {{ $post->published_at->translatedFormat('d/m/Y H:i') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <a href="{{ route('admin.posts.edit', $post) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown"></button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        @if($post->trashed())
                                            <form action="{{ route('admin.posts.restore', $post->id) }}" method="POST" class="dropdown-item p-0">
                                                @csrf
                                                <button class="btn btn-link dropdown-item text-success text-start" type="submit">Khôi phục</button>
                                            </form>
                                            <form action="{{ route('admin.posts.force-delete', $post->id) }}" method="POST"
                                                  onsubmit="return confirm('XÓA VĨNH VIỄN bài viết này? Thao tác không thể hoàn tác!')" class="dropdown-item p-0">
                                                @csrf
                                                <button class="btn btn-link dropdown-item text-danger text-start" type="submit">Xóa vĩnh viễn</button>
                                            </form>
                                        @else
                                            <a class="dropdown-item" href="{{ route('client.blog.show', $post) }}" target="_blank">Xem ngoài site</a>
                                            <form action="{{ route('admin.posts.duplicate', $post) }}" method="POST" class="dropdown-item p-0">
                                                @csrf
                                                <button class="btn btn-link dropdown-item text-start" type="submit">Nhân bản</button>
                                            </form>
                                            @if(!$post->is_featured)
                                                <form action="{{ route('admin.posts.feature', $post) }}" method="POST" class="dropdown-item p-0">
                                                    @csrf
                                                    <button class="btn btn-link dropdown-item text-start" type="submit">Đánh dấu nổi bật</button>
                                                </form>
                                            @else
                                                <form action="{{ route('admin.posts.unfeature', $post) }}" method="POST" class="dropdown-item p-0">
                                                    @csrf
                                                    <button class="btn btn-link dropdown-item text-start" type="submit">Bỏ nổi bật</button>
                                                </form>
                                            @endif
                                            <div class="dropdown-divider"></div>
                                            <form action="{{ route('admin.posts.destroy', $post) }}" method="POST"
                                                  onsubmit="return confirm('Xóa bài viết này?')" class="dropdown-item p-0">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-link dropdown-item text-danger text-start" type="submit">Xóa</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                Chưa có bài viết nào khớp bộ lọc.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-0 d-flex justify-content-between align-items-center">
            <div class="text-muted small">
                Hiển thị {{ $posts->firstItem() }} đến {{ $posts->lastItem() }} trong tổng số {{ $posts->total() }} bài viết
            </div>
            {{ $posts->links('pagination::bootstrap-5') }}
        </div>
    </div>

    <!-- Thanh hành động hàng loạt (Sticky Bottom) -->
    <div id="bulkActionContainer" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 shadow-lg p-3 bg-white rounded-pill border" 
         style="display:none; z-index: 1050; min-width: 400px;">
        <div class="d-flex align-items-center justify-content-between px-3">
            <div class="fw-bold me-4">
                🚀 Đã chọn <span id="selectedCount">0</span> bài viết
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="submitBulk('delete')">🗑️ Xóa</button>
                <button type="button" class="btn btn-sm btn-danger" onclick="submitBulk('force_delete')">🔥 Xóa vĩnh viễn</button>
                <button type="button" class="btn btn-sm btn-outline-success" onclick="submitBulk('restore')">🔄 Khôi phục</button>
                <button type="button" class="btn btn-sm btn-link text-muted" onclick="deselectAll()">Hủy</button>
            </div>
        </div>
    </div>

    <!-- Form ẩn để gửi hành động hàng loạt -->
    <form id="bulkActionForm" action="{{ route('admin.posts.bulk-action') }}" method="POST" style="display:none;">
        @csrf
        <input type="hidden" name="bulk_action" id="bulkActionInput">
        <input type="hidden" name="selected_ids" id="selectedIdsInput">
    </form>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAll = document.getElementById('selectAll');
        const itemCheckboxes = document.querySelectorAll('.item-checkbox');
        const bulkActionContainer = document.getElementById('bulkActionContainer');
        const selectedCountEl = document.getElementById('selectedCount');
        const selectedIdsInput = document.getElementById('selectedIdsInput');
        const bulkActionInput = document.getElementById('bulkActionInput');
        const bulkActionForm = document.getElementById('bulkActionForm');

        function updateBulkUI() {
            const selected = Array.from(itemCheckboxes).filter(cb => cb.checked);
            selectedCountEl.textContent = selected.length;
            
            if (selected.length > 0) {
                bulkActionContainer.style.display = 'block';
            } else {
                bulkActionContainer.style.display = 'none';
            }
        }

        selectAll.addEventListener('change', function() {
            itemCheckboxes.forEach(cb => {
                cb.checked = selectAll.checked;
            });
            updateBulkUI();
        });

        itemCheckboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                updateBulkUI();
                if (!this.checked) selectAll.checked = false;
                if (Array.from(itemCheckboxes).every(cb => cb.checked)) selectAll.checked = true;
            });
        });

        window.submitBulk = function(action) {
            const selectedIds = Array.from(itemCheckboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.value)
                .join(',');
            
            if (!selectedIds) return;

            let confirmMsg = 'Bạn có chắc chắn muốn thực hiện hành động này?';
            if (action === 'delete') confirmMsg = 'Xóa tạm các bài viết đã chọn?';
            if (action === 'force_delete') confirmMsg = 'XÓA VĨNH VIỄN các bài viết đã chọn? Thao tác này không thể khôi phục!';
            
            if (!confirm(confirmMsg)) return;

            bulkActionInput.value = action;
            selectedIdsInput.value = selectedIds;
            bulkActionForm.submit();
        };

        window.deselectAll = function() {
            selectAll.checked = false;
            itemCheckboxes.forEach(cb => cb.checked = false);
            updateBulkUI();
        };
    });
    </script>
    @endpush
@endsection

