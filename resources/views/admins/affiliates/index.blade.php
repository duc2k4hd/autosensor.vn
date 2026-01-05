@extends('admins.layouts.master')

@section('title', 'Quản lý Affiliate')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-link"></i> Quản lý Affiliate</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAffiliateModal">
            <i class="fas fa-plus"></i> Tạo Affiliate
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Tổng Affiliate</h6>
                    <h3>{{ $stats['total'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Đang hoạt động</h6>
                    <h3>{{ $stats['active'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Tổng Clicks</h6>
                    <h3>{{ number_format($stats['total_clicks']) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Tổng Hoa hồng</h6>
                    <h3>{{ number_format($stats['total_commission'], 0, ',', '.') }} đ</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Khách hàng</th>
                            <th>Code</th>
                            <th>Clicks</th>
                            <th>Conversions</th>
                            <th>Hoa hồng (%)</th>
                            <th>Tổng hoa hồng</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($affiliates as $affiliate)
                            <tr>
                                <td>{{ $affiliate->id }}</td>
                                <td>{{ $affiliate->account->name ?? 'N/A' }}</td>
                                <td><code>{{ $affiliate->code }}</code></td>
                                <td>{{ $affiliate->clicks }}</td>
                                <td>{{ $affiliate->conversions }}</td>
                                <td>{{ $affiliate->commission_rate }}%</td>
                                <td>{{ number_format($affiliate->total_commission, 0, ',', '.') }} đ</td>
                                <td>
                                    <span class="badge bg-{{ $affiliate->status === 'active' ? 'success' : 'secondary' }}">
                                        {{ $affiliate->status === 'active' ? 'Hoạt động' : 'Không hoạt động' }}
                                    </span>
                                </td>
                                <td>
                                    <form action="{{ route('admin.affiliates.destroy', $affiliate) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">Chưa có affiliate nào</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $affiliates->links() }}
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createAffiliateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.affiliates.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tạo Affiliate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tài khoản</label>
                        <select name="account_id" class="form-select" required>
                            <option value="">Chọn tài khoản...</option>
                            @foreach(\App\Models\Account::where('role', 'user')->get() as $account)
                                <option value="{{ $account->id }}">{{ $account->name }} ({{ $account->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Hoa hồng (%)</label>
                        <input type="number" name="commission_rate" class="form-control" value="5" min="0" max="100" step="0.1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Tạo</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

