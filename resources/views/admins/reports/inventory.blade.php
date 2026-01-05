@extends('admins.layouts.master')

@section('title', 'Báo cáo Tồn kho')

@section('content')
<div class="container-fluid">
    <h2><i class="fas fa-warehouse"></i> Báo cáo Tồn kho</h2>

    <form method="GET" class="row g-3 mb-4 mt-3">
        <div class="col-md-4">
            <label class="form-label">Từ ngày</label>
            <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Đến ngày</label>
            <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">&nbsp;</label>
            <div>
                <button type="submit" class="btn btn-primary">Lọc</button>
                <a href="{{ route('admin.reports.export', ['type' => 'inventory', 'format' => 'excel', 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Export Excel
                </a>
            </div>
        </div>
    </form>

    <!-- Summary -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Tổng nhập kho</h6>
                    <h3>{{ number_format($summary['total_imports']) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Tổng xuất kho</h6>
                    <h3>{{ number_format($summary['total_exports']) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Ngày giờ</th>
                            <th>Sản phẩm</th>
                            <th>Loại</th>
                            <th>Thay đổi</th>
                            <th>Tồn trước</th>
                            <th>Tồn sau</th>
                            <th>Người thao tác</th>
                            <th>Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movements as $movement)
                            <tr>
                                <td>{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('admin.products.inventory', $movement->product_id) }}">
                                        {{ $movement->product->name ?? 'N/A' }}
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-{{ match($movement->type) {
                                        'import' => 'success',
                                        'export' => 'warning',
                                        'order' => 'info',
                                        'order_cancel' => 'secondary',
                                        'adjust' => 'primary',
                                        default => 'dark'
                                    } }}">
                                        {{ match($movement->type) {
                                            'import' => 'Nhập kho',
                                            'export' => 'Xuất kho',
                                            'order' => 'Đơn hàng',
                                            'order_cancel' => 'Hủy đơn',
                                            'adjust' => 'Điều chỉnh',
                                            default => $movement->type
                                        } }}
                                    </span>
                                </td>
                                <td>
                                    <span class="{{ $movement->quantity_change >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $movement->quantity_change >= 0 ? '+' : '' }}{{ $movement->quantity_change }}
                                    </span>
                                </td>
                                <td>{{ $movement->stock_before ?? 'N/A' }}</td>
                                <td>{{ $movement->stock_after ?? 'N/A' }}</td>
                                <td>{{ $movement->account->name ?? 'System' }}</td>
                                <td>{{ $movement->note ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">Không có dữ liệu</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $movements->links() }}
        </div>
    </div>
</div>
@endsection

