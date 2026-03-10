@extends('admins.layouts.master')

@section('title', 'Quản lý bình luận')
@section('page-title', '💬 Quản lý bình luận')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/comments-icon.png') }}" type="image/x-icon">
@endpush

@section('content')
    {{-- Rating Statistics --}}
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="mb-0">📊 Thống kê đánh giá</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="text-center">
                        <h3 class="mb-0">{{ $stats['total_comments'] }}</h3>
                        <small class="text-muted">Tổng bình luận</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h3 class="mb-0">{{ number_format($stats['average_rating'], 1) }} ⭐</h3>
                        <small class="text-muted">Đánh giá trung bình</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-around">
                        <div class="text-center">
                            <div class="fw-bold">5 ⭐</div>
                            <div>{{ $stats['star_5_count'] }}</div>
                        </div>
                        <div class="text-center">
                            <div class="fw-bold">4 ⭐</div>
                            <div>{{ $stats['star_4_count'] }}</div>
                        </div>
                        <div class="text-center">
                            <div class="fw-bold">3 ⭐</div>
                            <div>{{ $stats['star_3_count'] }}</div>
                        </div>
                        <div class="text-center">
                            <div class="fw-bold">2 ⭐</div>
                            <div>{{ $stats['star_2_count'] }}</div>
                        </div>
                        <div class="text-center">
                            <div class="fw-bold">1 ⭐</div>
                            <div>{{ $stats['star_1_count'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="mb-0">🔍 Bộ lọc</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.comments.index') }}" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Loại</label>
                    <select name="type" class="form-select">
                        <option value="">Tất cả</option>
                        <option value="product" @selected(($filters['type'] ?? '') === 'product')>Sản phẩm</option>
                        <option value="post" @selected(($filters['type'] ?? '') === 'post')>Bài viết</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">ID</label>
                    <input type="number" name="object_id" value="{{ $filters['object_id'] ?? '' }}" class="form-control"
                           placeholder="ID">
                </div>
                <div class="col-md-1">
                    <label class="form-label">Rating</label>
                    <select name="rating" class="form-select">
                        <option value="">Tất cả</option>
                        @for($i = 5; $i >= 1; $i--)
                            <option value="{{ $i }}" @selected(($filters['rating'] ?? '') == $i)>{{ $i }} sao</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Trạng thái</label>
                    <select name="status" class="form-select">
                        <option value="">Tất cả</option>
                        <option value="approved" @selected(($filters['status'] ?? '') === 'approved')>Đã duyệt</option>
                        <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Chưa duyệt</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tìm kiếm</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control"
                           placeholder="Tên, email, nội dung...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Hiển thị</label>
                    <select name="per_page" class="form-select">
                        <option value="20" @selected(($filters['per_page'] ?? '') == 20)>20 dòng</option>
                        <option value="100" @selected(($filters['per_page'] ?? '') == 100)>100 dòng</option>
                        <option value="500" @selected(($filters['per_page'] ?? '') == 500)>500 dòng</option>
                        <option value="2000" @selected(($filters['per_page'] ?? '') == 2000)>2000 dòng</option>
                        <option value="5000" @selected(($filters['per_page'] ?? '') == 5000)>5000 dòng</option>
                        <option value="10000" @selected(($filters['per_page'] ?? '') == 10000)>10000 dòng</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Lọc</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Checkbox selection info & Progress --}}
    <div id="bulk-delete-progress-container" class="card mb-3" style="display: none;">
        <div class="card-body">
            <h6 id="bulk-delete-status">Đang xóa... (0/0)</h6>
            <div class="progress">
                <div id="bulk-delete-progressbar" class="progress-bar progress-bar-striped progress-bar-animated bg-danger" role="progressbar" style="width: 0%"></div>
            </div>
        </div>
    </div>

    {{-- Comments List --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <h5 class="mb-0">📝 Danh sách bình luận</h5>
                <div id="selected-info" style="display: none;">
                    <span class="badge bg-primary"><span id="selected-count">0</span> đã chọn</span>
                    <button type="button" class="btn btn-danger btn-sm ms-2" id="btn-bulk-delete">
                        🗑️ Xóa đã chọn
                    </button>
                </div>
            </div>
            <span class="badge bg-secondary" title="Tổng số bình luận gốc (Dữ liệu từ cache)">
                ~{{ number_format($stats['total_comments']) }} bình luận
            </span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th width="40">
                                <input type="checkbox" id="check-all" class="form-check-input">
                            </th>
                            <th>ID</th>
                            <th>Người gửi</th>
                            <th>Nội dung</th>
                            <th>Loại</th>
                            <th>Rating</th>
                            <th>Trạng thái</th>
                            <th>Reply</th>
                            <th>Ngày</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($comments as $comment)
                            <tr data-id="{{ $comment->id }}">
                                <td>
                                    <input type="checkbox" class="form-check-input comment-checkbox" value="{{ $comment->id }}">
                                </td>
                                <td>#{{ $comment->id }}</td>
                                <td>
                                    @if($comment->account)
                                        <strong>{{ $comment->account->name }}</strong><br>
                                        <small class="text-muted">{{ $comment->account->email }}</small>
                                    @else
                                        <strong>{{ $comment->name }}</strong><br>
                                        <small class="text-muted">{{ $comment->email }}</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 200px;" title="{{ $comment->content }}">
                                        {{ Str::limit($comment->content, 80) }}
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        {{ $comment->commentable_type === 'product' ? 'Sản phẩm' : 'Bài viết' }}
                                    </span>
                                    @if($comment->commentable)
                                        <br><small>{{ $comment->commentable->name ?? $comment->commentable->title ?? 'N/A' }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($comment->rating)
                                        <div class="d-flex align-items-center">
                                            <span class="me-1">{{ $comment->rating }}</span>
                                            <span>⭐</span>
                                        </div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($comment->is_approved)
                                        <span class="badge bg-success">Đã duyệt</span>
                                    @else
                                        <span class="badge bg-warning">Chưa duyệt</span>
                                    @endif
                                </td>
                                <td>
                                    @if($comment->adminReply)
                                        <span class="badge bg-info">Có reply</span>
                                    @else
                                        <span class="text-muted">Chưa có</span>
                                    @endif
                                </td>
                                <td>
                                    <small>{{ $comment->created_at->format('d/m/Y') }}</small><br>
                                    <small class="text-muted">{{ $comment->created_at->format('H:i') }}</small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.comments.show', $comment->id) }}" class="btn btn-primary">
                                            Chi tiết
                                        </a>
                                        @if(!$comment->is_approved)
                                            <form method="POST" action="{{ route('admin.comments.approve', $comment->id) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-success" title="Duyệt">
                                                    ✓
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.comments.reject', $comment->id) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-warning" title="Hủy duyệt">
                                                    ✗
                                                </button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('admin.comments.destroy', $comment->id) }}" class="d-inline"
                                              onsubmit="return confirm('Bạn có chắc muốn xóa bình luận này?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger" title="Xóa">
                                                🗑️
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    Chưa có bình luận nào.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($comments->hasPages())
                <div class="d-flex justify-content-center mt-3">
                    {{ $comments->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkAll = document.getElementById('check-all');
            const checkboxes = document.querySelectorAll('.comment-checkbox');
            const selectedInfo = document.getElementById('selected-info');
            const selectedCount = document.getElementById('selected-count');
            const btnBulkDelete = document.getElementById('btn-bulk-delete');
            const progressContainer = document.getElementById('bulk-delete-progress-container');
            const progressStatus = document.getElementById('bulk-delete-status');
            const progressBar = document.getElementById('bulk-delete-progressbar');

            const commentTable = document.querySelector('.table');

            function updateSelectedInfo() {
                const checkedCount = commentTable.querySelectorAll('.comment-checkbox:checked').length;
                if (checkedCount > 0) {
                    selectedInfo.style.display = 'block';
                    selectedCount.textContent = checkedCount;
                } else {
                    selectedInfo.style.display = 'none';
                }
            }

            if (checkAll) {
                checkAll.addEventListener('change', function() {
                    checkboxes.forEach(cb => cb.checked = checkAll.checked);
                    updateSelectedInfo();
                });
            }

            checkboxes.forEach(cb => {
                cb.addEventListener('change', updateSelectedInfo);
            });

            if (btnBulkDelete) {
                btnBulkDelete.addEventListener('click', async function() {
                    const selectedIds = Array.from(document.querySelectorAll('.comment-checkbox:checked'))
                        .map(cb => cb.value);

                    if (selectedIds.length === 0) return;

                    if (!confirm(`Bạn có chắc muốn xóa ${selectedIds.length} bình luận đã chọn?`)) {
                        return;
                    }

                    // Setup progress
                    const total = selectedIds.length;
                    let deleted = 0;
                    const chunkSize = 50; // Mỗi lần xóa 50 cái
                    const chunks = [];

                    for (let i = 0; i < selectedIds.length; i += chunkSize) {
                        chunks.push(selectedIds.slice(i, i + chunkSize));
                    }

                    // UI Updates
                    btnBulkDelete.disabled = true;
                    progressContainer.style.display = 'block';
                    updateProgress(0, total);

                    for (const chunk of chunks) {
                        try {
                            const response = await fetch('{{ route("admin.comments.bulk-delete") }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({ ids: chunk })
                            });

                            const result = await response.json();

                            if (result.success) {
                                deleted += chunk.length;
                                updateProgress(deleted, total);
                                
                                // Xóa các dòng tương ứng trên UI (tùy chọn, ở đây ta sẽ reload sau khi xong)
                                chunk.forEach(id => {
                                    const row = document.querySelector(`tr[data-id="${id}"]`);
                                    if (row) row.style.opacity = '0.3';
                                });
                            } else {
                                alert('Lỗi: ' + result.message);
                                break;
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            alert('Có lỗi xảy ra trong quá trình xóa.');
                            break;
                        }
                    }

                    // Hoàn tất
                    showCustomToast(`Đã xóa thành công ${deleted} bình luận.`, 'success');
                    
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                });
            }

            function updateProgress(current, total) {
                const percent = Math.round((current / total) * 100);
                progressStatus.textContent = `Đang xóa... (${current}/${total})`;
                progressBar.style.width = percent + '%';
                progressBar.setAttribute('aria-valuenow', percent);
            }
        });
    </script>
@endpush
