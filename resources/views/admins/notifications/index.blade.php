@extends('admins.layouts.master')

@section('title', 'Quản lý thông báo')
@section('page-title', '🔔 Quản lý thông báo')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/notifications-icon.png') }}" type="image/x-icon">
@endpush

@section('content')
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">📊 Thống kê</h5>
            <div>
                <span class="badge badge-warning">Chưa đọc: <span id="unreadCount">{{ $unreadCount }}</span></span>
                <button type="button" class="btn btn-sm btn-primary ml-2" id="markAllReadBtn">
                    <i class="fa fa-check"></i> Đánh dấu tất cả đã đọc
                </button>
                <button type="button" class="btn btn-sm btn-danger ml-2" id="deleteReadBtn">
                    <i class="fa fa-trash"></i> Xóa đã đọc
                </button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="mb-0">Danh sách thông báo</h5>
                </div>
                <div class="col-md-6 text-right">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-secondary filter-btn active" data-filter="all">
                            Tất cả
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary filter-btn" data-filter="unread">
                            Chưa đọc
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            @if($notifications->count() > 0)
                <div class="list-group" id="notificationsList">
                    @foreach($notifications as $notification)
                        <div class="list-group-item {{ !$notification->is_read ? 'list-group-item-action' : '' }} notification-item {{ !$notification->is_read ? 'unread' : '' }}" 
                             data-id="{{ $notification->id }}" 
                             data-read="{{ $notification->is_read ? '1' : '0' }}">
                            <div class="d-flex w-100 justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fa {{ $notification->icon }} mr-2 text-{{ $notification->priority === 'urgent' ? 'danger' : ($notification->priority === 'high' ? 'warning' : 'info') }}"></i>
                                        <h6 class="mb-0 {{ !$notification->is_read ? 'font-weight-bold' : '' }}">
                                            {{ $notification->title }}
                                        </h6>
                                        @if(!$notification->is_read)
                                            <span class="badge badge-primary badge-pill ml-2">Mới</span>
                                        @endif
                                        <span class="badge badge-{{ $notification->getPriorityBadgeClass() }} ml-2">
                                            {{ ucfirst($notification->priority) }}
                                        </span>
                                    </div>
                                    <p class="mb-1 text-muted">{{ $notification->message }}</p>
                                    <small class="text-muted">
                                        <i class="fa fa-clock"></i> {{ $notification->created_at->diffForHumans() }}
                                    </small>
                                </div>
                                <div class="ml-3">
                                    <div class="btn-group-vertical btn-group-sm">
                                        @if(!$notification->is_read)
                                            <button type="button" class="btn btn-sm btn-outline-primary mark-read-btn" data-id="{{ $notification->id }}" title="Đánh dấu đã đọc">
                                                <i class="fa fa-check"></i>
                                            </button>
                                        @endif
                                        @if($notification->link)
                                            <a href="{{ $notification->link }}" class="btn btn-sm btn-outline-info" title="Xem chi tiết">
                                                <i class="fa fa-external-link"></i>
                                            </a>
                                        @endif
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-btn" data-id="{{ $notification->id }}" title="Xóa">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fa fa-bell-slash fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Chưa có thông báo nào</p>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const unreadCountEl = document.getElementById('unreadCount');
    const markAllReadBtn = document.getElementById('markAllReadBtn');
    const deleteReadBtn = document.getElementById('deleteReadBtn');
    const filterBtns = document.querySelectorAll('.filter-btn');
    const notificationsList = document.getElementById('notificationsList');

    // Đánh dấu đã đọc
    document.querySelectorAll('.mark-read-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const notificationId = this.dataset.id;
            markAsRead(notificationId);
        });
    });

    // Xóa thông báo
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!confirm('Bạn có chắc muốn xóa thông báo này?')) return;
            
            const notificationId = this.dataset.id;
            deleteNotification(notificationId);
        });
    });

    // Đánh dấu tất cả đã đọc
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function() {
            if (!confirm('Đánh dấu tất cả thông báo đã đọc?')) return;
            
            fetch('{{ route("admin.notifications.read-all") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        });
    }

    // Xóa tất cả đã đọc
    if (deleteReadBtn) {
        deleteReadBtn.addEventListener('click', function() {
            if (!confirm('Xóa tất cả thông báo đã đọc?')) return;
            
            fetch('{{ route("admin.notifications.delete-read") }}', {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        });
    }

    // Filter
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const filter = this.dataset.filter;
            
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const items = document.querySelectorAll('.notification-item');
            items.forEach(item => {
                if (filter === 'all') {
                    item.style.display = '';
                } else if (filter === 'unread') {
                    item.style.display = item.dataset.read === '0' ? '' : 'none';
                }
            });
        });
    });

    function markAsRead(notificationId) {
        fetch(`{{ route("admin.notifications.read", ":id") }}`.replace(':id', notificationId), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const item = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
                if (item) {
                    item.classList.remove('unread', 'list-group-item-action');
                    item.dataset.read = '1';
                    const markReadBtn = item.querySelector('.mark-read-btn');
                    if (markReadBtn) markReadBtn.remove();
                    const badge = item.querySelector('.badge-primary');
                    if (badge) badge.remove();
                    item.querySelector('h6').classList.remove('font-weight-bold');
                }
                if (unreadCountEl) {
                    unreadCountEl.textContent = data.unread_count || 0;
                }
            }
        });
    }

    function deleteNotification(notificationId) {
        fetch(`{{ route("admin.notifications.destroy", ":id") }}`.replace(':id', notificationId), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const item = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
                if (item) {
                    item.remove();
                    if (unreadCountEl && item.dataset.read === '0') {
                        const currentCount = parseInt(unreadCountEl.textContent) || 0;
                        unreadCountEl.textContent = Math.max(0, currentCount - 1);
                    }
                }
            }
        });
    }

    // Auto refresh unread count every 30 seconds
    setInterval(function() {
        fetch('{{ route("admin.notifications.unread-count") }}')
            .then(response => response.json())
            .then(data => {
                if (unreadCountEl && data.count !== undefined) {
                    unreadCountEl.textContent = data.count;
                }
            });
    }, 30000);
});
</script>
@endpush

