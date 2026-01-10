@extends('admins.layouts.master')

@section('title', 'Quản lý yêu cầu báo giá')
@section('page-title', '💰 Quản lý yêu cầu báo giá')

@section('content')
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase mb-2">Tổng yêu cầu</h6>
                        <h3 class="mb-0">{{ number_format($stats['total'] ?? 0) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase mb-2">Mới</h6>
                        <h3 class="mb-0 text-primary">{{ number_format($stats['new'] ?? 0) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase mb-2">Đã liên hệ</h6>
                        <h3 class="mb-0 text-info">{{ number_format($stats['contacted'] ?? 0) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase mb-2">Hoàn thành</h6>
                        <h3 class="mb-0 text-success">{{ number_format($stats['done'] ?? 0) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.quotes.index') }}" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Từ khóa</label>
                        <input type="text" name="keyword" value="{{ request('keyword') }}" class="form-control"
                               placeholder="Tên, email, điện thoại...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Trạng thái</label>
                        <select name="status" class="form-select">
                            <option value="">Tất cả</option>
                            <option value="new" @selected(request('status') === 'new')>Mới</option>
                            <option value="contacted" @selected(request('status') === 'contacted')>Đã liên hệ</option>
                            <option value="done" @selected(request('status') === 'done')>Hoàn thành</option>
                            <option value="cancelled" @selected(request('status') === 'cancelled')>Đã hủy</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Từ ngày</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Đến ngày</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Lọc</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <div>
                        <strong>{{ $quotes->total() }}</strong> yêu cầu báo giá
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Khách hàng</th>
                            <th>Thông tin liên hệ</th>
                            <th>Tổng giá trị</th>
                            <th>Số sản phẩm</th>
                            <th>Trạng thái</th>
                            <th>Thời gian</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($quotes as $quote)
                            <tr>
                                <td>
                                    <strong>#{{ $quote->id }}</strong>
                                </td>
                                <td>
                                    <strong>{{ $quote->name }}</strong>
                                    @if($quote->account)
                                        <div class="text-muted small">
                                            TK: {{ $quote->account->name ?? $quote->account->email }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="small">
                                        @if($quote->email)
                                            <div>📧 {{ $quote->email }}</div>
                                        @endif
                                        @if($quote->phone)
                                            <div>📞 {{ $quote->phone }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <strong class="text-primary">{{ number_format($quote->total_amount, 0, ',', '.') }}₫</strong>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ count($quote->cart_snapshot ?? []) }} SP</span>
                                </td>
                                <td>
                                    @php
                                        $statusColors = [
                                            'new' => 'primary',
                                            'contacted' => 'info',
                                            'done' => 'success',
                                            'cancelled' => 'danger',
                                        ];
                                        $statusLabels = [
                                            'new' => 'Mới',
                                            'contacted' => 'Đã liên hệ',
                                            'done' => 'Hoàn thành',
                                            'cancelled' => 'Đã hủy',
                                        ];
                                        $color = $statusColors[$quote->status] ?? 'secondary';
                                        $label = $statusLabels[$quote->status] ?? $quote->status;
                                    @endphp
                                    <span class="badge bg-{{ $color }}">{{ $label }}</span>
                                </td>
                                <td class="small text-muted">
                                    {{ $quote->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.quotes.show', $quote) }}" class="btn btn-outline-primary">
                                            Xem
                                        </a>
                                        @if($quote->pdf_path || $quote->cart_snapshot)
                                            <a href="{{ route('admin.quotes.download-pdf', $quote) }}" class="btn btn-outline-secondary" target="_blank">
                                                PDF
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">
                                    Chưa có yêu cầu báo giá nào.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $quotes->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
