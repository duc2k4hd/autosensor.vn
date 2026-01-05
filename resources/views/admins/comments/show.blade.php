@extends('admins.layouts.master')

@section('title', 'Chi tiết bình luận #' . $comment->id)
@section('page-title', '💬 Chi tiết bình luận #' . $comment->id)

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/comments-icon.png') }}" type="image/x-icon">
@endpush

@section('content')
    <div class="row">
        <div class="col-md-8">
            {{-- Comment Info --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">📝 Thông tin bình luận</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Người gửi</label>
                        <div>
                            @if($comment->account)
                                <strong>{{ $comment->account->name }}</strong> ({{ $comment->account->email }})
                            @else
                                <strong>{{ $comment->name }}</strong> ({{ $comment->email }})
                            @endif
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nội dung</label>
                        <div class="border p-3 bg-light rounded">
                            {{ $comment->content }}
                        </div>
                    </div>
                    @if($comment->rating)
                        <div class="mb-3">
                            <label class="form-label fw-bold">Đánh giá</label>
                            <div>
                                <span class="fs-4">{{ $comment->rating }} ⭐</span>
                            </div>
                        </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label fw-bold">Trạng thái</label>
                        <div>
                            @if($comment->is_approved)
                                <span class="badge bg-success">Đã duyệt</span>
                            @else
                                <span class="badge bg-warning">Chưa duyệt</span>
                            @endif
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        @if(!$comment->is_approved)
                            <form method="POST" action="{{ route('admin.comments.approve', $comment->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-success">✓ Duyệt bình luận</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.comments.reject', $comment->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-warning">✗ Hủy duyệt</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('admin.comments.destroy', $comment->id) }}"
                              onsubmit="return confirm('Bạn có chắc muốn xóa bình luận này?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">🗑️ Xóa</button>
                        </form>
                        <a href="{{ route('admin.comments.index') }}" class="btn btn-secondary">← Quay lại</a>
                    </div>
                </div>
            </div>

            {{-- Reply Section --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">💬 Trả lời bình luận</h5>
                </div>
                <div class="card-body">
                    @if($comment->adminReply)
                        <div class="alert alert-info mb-3">
                            <strong>Reply hiện tại:</strong>
                            <div class="mt-2 p-2 bg-white rounded border">
                                {{ $comment->adminReply->content }}
                            </div>
                            <small class="text-muted">
                                Bởi: {{ $comment->adminReply->account->name ?? 'Admin' }} 
                                - {{ $comment->adminReply->created_at->format('d/m/Y H:i') }}
                            </small>
                        </div>
                        <form method="POST" action="{{ route('admin.comments.replies.update', $comment->adminReply->id) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Cập nhật reply</label>
                                <textarea name="reply_content" class="form-control" rows="4" required>{{ old('reply_content', $comment->adminReply->content) }}</textarea>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Cập nhật reply</button>
                                <form method="POST" action="{{ route('admin.comments.replies.delete', $comment->adminReply->id) }}" class="d-inline"
                                      onsubmit="return confirm('Bạn có chắc muốn xóa reply này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger">Xóa reply</button>
                                </form>
                            </div>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.comments.reply', $comment->id) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Nội dung trả lời</label>
                                <textarea name="reply_content" class="form-control" rows="4" required
                                          placeholder="Nhập nội dung trả lời...">{{ old('reply_content') }}</textarea>
                                @error('reply_content')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-primary">Gửi trả lời</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            {{-- System Info --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">ℹ️ Thông tin hệ thống</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <strong>ID:</strong> #{{ $comment->id }}
                        </li>
                        <li class="mb-2">
                            <strong>Loại:</strong>
                            <span class="badge bg-info">
                                {{ $comment->commentable_type === 'product' ? 'Sản phẩm' : 'Bài viết' }}
                            </span>
                        </li>
                        <li class="mb-2">
                            <strong>Đối tượng:</strong><br>
                            @if($comment->commentable)
                                <a href="{{ '/kinh-nghiem/'. $comment->commentable->slug ?? '#' }}" target="_blank">
                                    {{ $comment->commentable->name ?? $comment->commentable->title ?? 'N/A' }}
                                </a>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </li>
                        <li class="mb-2">
                            <strong>IP:</strong> {{ $comment->ip ?? 'N/A' }}
                        </li>
                        <li class="mb-2">
                            <strong>User Agent:</strong><br>
                            <small class="text-muted">{{ Str::limit($comment->user_agent ?? 'N/A', 50) }}</small>
                        </li>
                        <li class="mb-2">
                            <strong>Ngày tạo:</strong><br>
                            {{ $comment->created_at->format('d/m/Y H:i:s') }}
                        </li>
                        <li class="mb-2">
                            <strong>Cập nhật:</strong><br>
                            {{ $comment->updated_at->format('d/m/Y H:i:s') }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
@endsection
