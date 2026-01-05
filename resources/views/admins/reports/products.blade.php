@extends('admins.layouts.master')

@section('title', 'Báo cáo Sản phẩm')

@section('content')
<div class="container-fluid">
    <h2><i class="fas fa-box"></i> Báo cáo Sản phẩm</h2>

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
                <a href="{{ route('admin.reports.export', ['type' => 'products', 'format' => 'excel', 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="btn btn-success">
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
                            <th>SKU</th>
                            <th>Tên sản phẩm</th>
                            <th>Số lượng bán</th>
                            <th>Doanh thu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr>
                                <td>{{ $loop->iteration + ($products->currentPage() - 1) * $products->perPage() }}</td>
                                <td><code>{{ $product->product->sku ?? 'N/A' }}</code></td>
                                <td>{{ $product->product->name ?? 'N/A' }}</td>
                                <td>{{ number_format($product->total_sold) }}</td>
                                <td>{{ number_format($product->total_revenue, 0, ',', '.') }} đ</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">Không có dữ liệu</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $products->links() }}
        </div>
    </div>
</div>
@endsection

