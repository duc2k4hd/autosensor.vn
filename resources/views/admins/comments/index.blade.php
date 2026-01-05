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
                <div class="col-md-2">
                    <label class="form-label">ID đối tượng</label>
                    <input type="number" name="object_id" value="{{ $filters['object_id'] ?? '' }}" class="form-control"
                           placeholder="ID sản phẩm/bài viết">
                </div>
                <div class="col-md-2">
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
                <div class="col-md-3">
                    <label class="form-label">Tìm kiếm</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control"
                           placeholder="Tên, email, nội dung...">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Lọc</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Comments List --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">📝 Danh sách bình luận</h5>
            <span class="badge bg-secondary">{{ $comments->total() }} bình luận</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
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
                            <tr>
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
                                <td colspan="9" class="text-center text-muted py-4">
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
