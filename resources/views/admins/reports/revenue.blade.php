@extends('admins.layouts.master')

@section('title', 'Báo cáo Doanh thu')

@section('content')
<div class="container-fluid">
    <h2><i class="fas fa-dollar-sign"></i> Báo cáo Doanh thu</h2>

    <form method="GET" class="row g-3 mb-4 mt-3">
        <div class="col-md-3">
            <label class="form-label">Từ ngày</label>
            <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Đến ngày</label>
            <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Nhóm theo</label>
            <select name="group_by" class="form-select">
                <option value="day" {{ $groupBy === 'day' ? 'selected' : '' }}>Ngày</option>
                <option value="week" {{ $groupBy === 'week' ? 'selected' : '' }}>Tuần</option>
                <option value="month" {{ $groupBy === 'month' ? 'selected' : '' }}>Tháng</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">&nbsp;</label>
            <div>
                <button type="submit" class="btn btn-primary">Lọc</button>
                <a href="{{ route('admin.reports.export', ['type' => 'revenue', 'format' => 'excel', 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Export Excel
                </a>
            </div>
        </div>
    </form>

    <!-- Summary -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Tổng doanh thu</h6>
                    <h3>{{ number_format($summary['total_revenue'], 0, ',', '.') }} đ</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Tổng đơn hàng</h6>
                    <h3>{{ $summary['total_orders'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Giá trị đơn trung bình</h6>
                    <h3>{{ number_format($summary['average_order_value'], 0, ',', '.') }} đ</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart Data -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Thời gian</th>
                            <th>Doanh thu</th>
                            <th>Số đơn</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $item)
                            <tr>
                                <td>{{ $item->date ?? $item->week ?? $item->month ?? 'N/A' }}</td>
                                <td>{{ number_format($item->revenue, 0, ',', '.') }} đ</td>
                                <td>{{ $item->orders }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center">Không có dữ liệu</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

