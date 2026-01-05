@extends('admins.layouts.master')

@section('title', 'Báo cáo Khách hàng')

@section('content')
<div class="container-fluid">
    <h2><i class="fas fa-users"></i> Báo cáo Khách hàng</h2>

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
                <a href="{{ route('admin.reports.export', ['type' => 'customers', 'format' => 'excel', 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Export Excel
                </a>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Tên</th>
                            <th>Email</th>
                            <th>Số điện thoại</th>
                            <th>Số đơn hàng</th>
                            <th>Tổng chi tiêu</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                            <tr>
                                <td>{{ $loop->iteration + ($customers->currentPage() - 1) * $customers->perPage() }}</td>
                                <td>{{ $customer->name }}</td>
                                <td>{{ $customer->email }}</td>
                                <td>{{ $customer->phone ?? 'N/A' }}</td>
                                <td>{{ $customer->order_count ?? 0 }}</td>
                                <td>{{ number_format($customer->total_spent ?? 0, 0, ',', '.') }} đ</td>
                                <td>
                                    <a href="{{ route('admin.accounts.show', $customer) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">Không có dữ liệu</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $customers->links() }}
        </div>
    </div>
</div>
@endsection

