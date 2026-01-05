@extends('admins.layouts.master')

@section('title', 'Dashboard Tài khoản')

@section('page-title', '📊 Dashboard Tài khoản')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/account-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .dashboard-page {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }

        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .stat-card h4 {
            font-size: 14px;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 8px;
        }

        .stat-card .value {
            font-size: 32px;
            font-weight: 700;
            color: #0f172a;
        }

        .top-accounts {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .top-accounts h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
            color: #0f172a;
        }

        .account-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .account-item:last-child {
            border-bottom: none;
        }

        .account-item-info {
            flex: 1;
        }

        .account-item-name {
            font-weight: 600;
            color: #0f172a;
            font-size: 14px;
        }

        .account-item-email {
            font-size: 12px;
            color: #64748b;
        }

        .account-item-count {
            font-size: 18px;
            font-weight: 700;
            color: #2563eb;
        }
    </style>
@endpush

@section('content')
    <div class="dashboard-page">
        <div class="stats-grid">
            <div class="stat-card">
                <h4>Tổng số tài khoản</h4>
                <div class="value">{{ number_format($stats['total']) }}</div>
            </div>
            <div class="stat-card">
                <h4>Mới trong 7 ngày</h4>
                <div class="value">{{ number_format($stats['new_last_7_days']) }}</div>
            </div>
            <div class="stat-card">
                <h4>Đang hoạt động</h4>
                <div class="value">{{ number_format($stats['active']) }}</div>
            </div>
            <div class="stat-card">
                <h4>Đã khóa</h4>
                <div class="value">{{ number_format($stats['locked']) }}</div>
            </div>
            <div class="stat-card">
                <h4>Đã cấm</h4>
                <div class="value">{{ number_format($stats['banned']) }}</div>
            </div>
            <div class="stat-card">
                <h4>Chưa xác minh email</h4>
                <div class="value">{{ number_format($stats['unverified']) }}</div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="top-accounts">
                    <h3>Top 10 tài khoản đăng nhập nhiều nhất</h3>
                    @forelse($stats['top_login_accounts'] as $acc)
                        <div class="account-item">
                            <div class="account-item-info">
                                <div class="account-item-name">{{ $acc->name }}</div>
                                <div class="account-item-email">{{ $acc->email }}</div>
                            </div>
                            <div class="account-item-count">{{ $acc->login_attempts ?? 0 }}</div>
                        </div>
                    @empty
                        <p class="text-muted">Không có dữ liệu</p>
                    @endforelse
                </div>
            </div>

            <div class="col-md-6">
                <div class="top-accounts">
                    <h3>Tài khoản mới theo ngày (30 ngày gần nhất)</h3>
                    @forelse($stats['new_accounts_by_day'] as $day)
                        <div class="account-item">
                            <div class="account-item-info">
                                <div class="account-item-name">{{ \Carbon\Carbon::parse($day->date)->format('d/m/Y') }}</div>
                            </div>
                            <div class="account-item-count">{{ $day->count }}</div>
                        </div>
                    @empty
                        <p class="text-muted">Không có dữ liệu</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
