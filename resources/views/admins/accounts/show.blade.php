@extends('admins.layouts.master')

@section('title', 'Chi tiết tài khoản')

@section('page-title', '👤 Chi tiết tài khoản')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/account-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .account-detail-page {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .account-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            flex-wrap: wrap;
        }

        .account-info-card {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .account-info-card h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
            color: #0f172a;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 8px;
        }

        .info-row {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #475569;
            font-size: 14px;
        }

        .info-value {
            color: #0f172a;
            font-size: 14px;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success {
            background: #dcfce7;
            color: #166534;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #2563eb;
            color: #fff;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .btn-danger {
            background: #dc2626;
            color: #fff;
        }

        .btn-danger:hover {
            background: #b91c1c;
        }

        .btn-warning {
            background: #f59e0b;
            color: #fff;
        }

        .btn-warning:hover {
            background: #d97706;
        }

        .btn-success {
            background: #10b981;
            color: #fff;
        }

        .btn-success:hover {
            background: #059669;
        }

        .logs-section {
            margin-top: 24px;
        }

        .logs-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }

        .logs-table th,
        .logs-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .logs-table th {
            background: #f8fafc;
            font-weight: 600;
            color: #475569;
            font-size: 13px;
        }

        .logs-table td {
            font-size: 13px;
            color: #0f172a;
        }

        .filter-form {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .filter-form select,
        .filter-form input {
            padding: 8px 12px;
            border: 1px solid #d0d7ee;
            border-radius: 6px;
            font-size: 14px;
        }

        .related-data {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
            margin-top: 24px;
        }

        .related-card {
            background: #f8fafc;
            padding: 16px;
            border-radius: 8px;
        }

        .related-card h4 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #475569;
        }

        .related-card .count {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
        }
    </style>
@endpush

@section('content')
    <div class="account-detail-page">
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <div class="account-header">
            <div>
                <h2>{{ $account->name }}</h2>
                <p class="text-muted">ID: #{{ $account->id }} · Email: {{ $account->email }}</p>
            </div>
            <div class="action-buttons">
                <a href="{{ route('admin.accounts.edit', $account) }}" class="btn btn-primary">✏️ Chỉnh sửa</a>
                <a href="{{ route('admin.accounts.index') }}" class="btn">← Quay lại</a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="account-info-card">
                    <h3>Thông tin tài khoản</h3>
                    <div class="info-row">
                        <span class="info-label">ID:</span>
                        <span class="info-value">#{{ $account->id }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Họ tên:</span>
                        <span class="info-value">{{ $account->name }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value">{{ $account->email }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Số điện thoại:</span>
                        <span class="info-value">{{ $account->phone ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Vai trò:</span>
                        <span class="info-value">
                            <span class="badge badge-info">{{ ucfirst($account->role) }}</span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Trạng thái:</span>
                        <span class="info-value">
                            @if($account->status === 'active')
                                <span class="badge badge-success">Hoạt động</span>
                            @elseif($account->status === 'locked')
                                <span class="badge badge-warning">Đã khóa</span>
                            @elseif($account->status === 'banned')
                                <span class="badge badge-danger">Đã cấm</span>
                            @elseif($account->status === 'inactive')
                                <span class="badge badge-warning">Không hoạt động</span>
                            @else
                                <span class="badge badge-warning">Chờ xử lý</span>
                            @endif
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email đã xác minh:</span>
                        <span class="info-value">
                            @if($account->email_verified_at)
                                <span class="badge badge-success">Đã xác minh</span>
                                <small class="text-muted">({{ $account->email_verified_at->format('d/m/Y H:i') }})</small>
                            @else
                                <span class="badge badge-warning">Chưa xác minh</span>
                            @endif
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Số lần đăng nhập thất bại:</span>
                        <span class="info-value">{{ $account->login_attempts ?? 0 }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Ngày tạo:</span>
                        <span class="info-value">{{ $account->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Cập nhật lần cuối:</span>
                        <span class="info-value">{{ $account->updated_at->format('d/m/Y H:i') }}</span>
                    </div>
                    @if($account->admin_note)
                        <div class="info-row">
                            <span class="info-label">Ghi chú nội bộ:</span>
                            <span class="info-value">{{ $account->admin_note }}</span>
                        </div>
                    @endif
                    @if($account->tags && count($account->tags) > 0)
                        <div class="info-row">
                            <span class="info-label">Tags:</span>
                            <span class="info-value">
                                @foreach($account->tags as $tag)
                                    <span class="badge badge-info">{{ $tag }}</span>
                                @endforeach
                            </span>
                        </div>
                    @endif
                </div>

                <div class="account-info-card logs-section">
                    <h3>Lịch sử hoạt động</h3>
                    <form method="GET" action="{{ route('admin.accounts.show', $account) }}" class="filter-form">
                        <select name="log_type" class="form-select">
                            <option value="">Tất cả loại</option>
                            @foreach($logTypes as $type)
                                <option value="{{ $type }}" {{ request('log_type') === $type ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('_', ' ', $type)) }}
                                </option>
                            @endforeach
                        </select>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" placeholder="Từ ngày">
                        <input type="date" name="date_to" value="{{ request('date_to') }}" placeholder="Đến ngày">
                        <button type="submit" class="btn btn-primary">Lọc</button>
                        <a href="{{ route('admin.accounts.show', $account) }}" class="btn">Xóa bộ lọc</a>
                    </form>

                    <table class="logs-table">
                        <thead>
                            <tr>
                                <th>Thời gian</th>
                                <th>Loại</th>
                                <th>IP</th>
                                <th>Admin</th>
                                <th>Chi tiết</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                                <tr>
                                    <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                                    <td>
                                        <span class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $log->type)) }}</span>
                                    </td>
                                    <td>{{ $log->ip }}</td>
                                    <td>{{ $log->admin?->name ?? '—' }}</td>
                                    <td>
                                        @if($log->payload)
                                            <small>{{ json_encode($log->payload, JSON_UNESCAPED_UNICODE) }}</small>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Không có log nào</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    {{ $logs->links() }}
                </div>
            </div>

            <div class="col-md-4">
                <div class="account-info-card">
                    <h3>Thao tác nhanh</h3>
                    <div class="action-buttons" style="flex-direction: column;">
                        @if($account->status === 'locked')
                            <form method="POST" action="{{ route('admin.accounts.unlock', $account) }}" style="width: 100%;">
                                @csrf
                                <button type="submit" class="btn btn-success" style="width: 100%;">🔓 Mở khóa</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.accounts.lock', $account) }}" style="width: 100%;">
                                @csrf
                                <button type="submit" class="btn btn-warning" style="width: 100%;">🔒 Khóa tài khoản</button>
                            </form>
                        @endif

                        @if($account->status === 'banned')
                            <form method="POST" action="{{ route('admin.accounts.unban', $account) }}" style="width: 100%;">
                                @csrf
                                <button type="submit" class="btn btn-success" style="width: 100%;">✅ Gỡ cấm</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.accounts.ban', $account) }}" style="width: 100%;">
                                @csrf
                                <button type="submit" class="btn btn-danger" style="width: 100%;">🚫 Cấm tài khoản</button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route('admin.accounts.reset-password', $account) }}" style="width: 100%;">
                            @csrf
                            <button type="submit" class="btn btn-warning" style="width: 100%;">🔑 Reset mật khẩu</button>
                        </form>

                        @if(!$account->email_verified_at)
                            <form method="POST" action="{{ route('admin.accounts.verify-email', $account) }}" style="width: 100%;">
                                @csrf
                                <button type="submit" class="btn btn-success" style="width: 100%;">✓ Xác minh email</button>
                            </form>
                        @endif

                        @if($account->login_attempts > 0)
                            <form method="POST" action="{{ route('admin.accounts.reset-login-attempts', $account) }}" style="width: 100%;">
                                @csrf
                                <button type="submit" class="btn btn-info" style="width: 100%;">🔄 Reset login attempts</button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="account-info-card">
                    <h3>Dữ liệu liên quan</h3>
                    <div class="related-data">
                        <div class="related-card">
                            <h4>Địa chỉ</h4>
                            <div class="count">{{ $account->addresses->count() }}</div>
                        </div>
                        <div class="related-card">
                            <h4>Đơn hàng</h4>
                            <div class="count">{{ $account->orders->count() }}</div>
                        </div>
                        <div class="related-card">
                            <h4>Yêu thích</h4>
                            <div class="count">{{ $account->favorites->count() }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
