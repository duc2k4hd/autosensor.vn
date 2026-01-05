@extends('admins.layouts.master')

@section('title', 'Báo cáo & Thống kê')

@section('content')
<div class="container-fluid">
    <h2><i class="fas fa-chart-bar"></i> Báo cáo & Thống kê</h2>

    <div class="row mt-4">
        <div class="col-md-3">
            <a href="{{ route('admin.reports.revenue') }}" class="card text-decoration-none text-dark">
                <div class="card-body text-center">
                    <i class="fas fa-dollar-sign fa-3x text-success mb-3"></i>
                    <h5>Báo cáo Doanh thu</h5>
                    <p class="text-muted">Xem doanh thu theo thời gian</p>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('admin.reports.products') }}" class="card text-decoration-none text-dark">
                <div class="card-body text-center">
                    <i class="fas fa-box fa-3x text-primary mb-3"></i>
                    <h5>Báo cáo Sản phẩm</h5>
                    <p class="text-muted">Sản phẩm bán chạy</p>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('admin.reports.customers') }}" class="card text-decoration-none text-dark">
                <div class="card-body text-center">
                    <i class="fas fa-users fa-3x text-info mb-3"></i>
                    <h5>Báo cáo Khách hàng</h5>
                    <p class="text-muted">Khách hàng mua nhiều nhất</p>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('admin.reports.inventory') }}" class="card text-decoration-none text-dark">
                <div class="card-body text-center">
                    <i class="fas fa-warehouse fa-3x text-warning mb-3"></i>
                    <h5>Báo cáo Tồn kho</h5>
                    <p class="text-muted">Xuất nhập tồn</p>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection

