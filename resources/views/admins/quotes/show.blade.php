@extends('admins.layouts.master')

@section('title', 'Chi tiết yêu cầu báo giá #' . $quote->id)
@section('page-title', '💰 Chi tiết yêu cầu báo giá #' . $quote->id)

@section('content')
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Thông tin yêu cầu</h5>
                        <div>
                            <a href="{{ route('admin.quotes.index') }}" class="btn btn-outline-secondary btn-sm">
                                ← Quay lại
                            </a>
                            @if($quote->pdf_path || $quote->cart_snapshot)
                                <a href="{{ route('admin.quotes.download-pdf', $quote) }}" class="btn btn-primary btn-sm" target="_blank">
                                    📄 Tải PDF
                                </a>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-muted mb-3">Thông tin khách hàng</h6>
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="150"><strong>Họ tên:</strong></td>
                                        <td>{{ $quote->name }}</td>
                                    </tr>
                                    @if($quote->email)
                                        <tr>
                                            <td><strong>Email:</strong></td>
                                            <td>{{ $quote->email }}</td>
                                        </tr>
                                    @endif
                                    @if($quote->phone)
                                        <tr>
                                            <td><strong>Điện thoại:</strong></td>
                                            <td>{{ $quote->phone }}</td>
                                        </tr>
                                    @endif
                                    @if($quote->account)
                                        <tr>
                                            <td><strong>Tài khoản:</strong></td>
                                            <td>
                                                <a href="{{ route('admin.accounts.show', $quote->account) }}" target="_blank">
                                                    {{ $quote->account->name ?? $quote->account->email }}
                                                </a>
                                            </td>
                                        </tr>
                                    @endif
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-3">Thông tin yêu cầu</h6>
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="150"><strong>Mã yêu cầu:</strong></td>
                                        <td><strong>#{{ $quote->id }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Trạng thái:</strong></td>
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
                                    </tr>
                                    <tr>
                                        <td><strong>Tổng giá trị:</strong></td>
                                        <td><strong class="text-primary fs-5">{{ number_format($quote->total_amount, 0, ',', '.') }}₫</strong></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Thời gian:</strong></td>
                                        <td>{{ $quote->created_at->format('d/m/Y H:i:s') }}</td>
                                    </tr>
                                    @if($quote->ip)
                                        <tr>
                                            <td><strong>IP:</strong></td>
                                            <td class="small text-muted">{{ $quote->ip }}</td>
                                        </tr>
                                    @endif
                                </table>
                            </div>
                        </div>

                        @if($quote->note)
                            <div class="mt-3">
                                <h6 class="text-muted mb-2">Ghi chú từ khách hàng</h6>
                                <div class="alert alert-light">
                                    {{ $quote->note }}
                                </div>
                            </div>
                        @endif

                        <div class="mt-4">
                            <h6 class="text-muted mb-3">Cập nhật trạng thái</h6>
                            <form method="POST" action="{{ route('admin.quotes.update-status', $quote) }}" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <select name="status" class="form-select" required>
                                            <option value="new" @selected($quote->status === 'new')>Mới</option>
                                            <option value="contacted" @selected($quote->status === 'contacted')>Đã liên hệ</option>
                                            <option value="done" @selected($quote->status === 'done')>Hoàn thành</option>
                                            <option value="cancelled" @selected($quote->status === 'cancelled')>Đã hủy</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary">Cập nhật</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Chi tiết sản phẩm ({{ count($quote->cart_snapshot ?? []) }} sản phẩm)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Sản phẩm</th>
                                    <th>SKU</th>
                                    <th>Biến thể</th>
                                    <th class="text-end">Đơn giá</th>
                                    <th class="text-end">Số lượng</th>
                                    <th class="text-end">Thành tiền</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($quote->cart_snapshot ?? [] as $index => $item)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <strong>{{ $item['product_name'] ?? 'N/A' }}</strong>
                                            @if(isset($item['product_id']))
                                                <div class="small">
                                                    <a href="{{ route('admin.products.show', $item['product_id']) }}" target="_blank" class="text-muted">
                                                        Xem sản phẩm #{{ $item['product_id'] }}
                                                    </a>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="small text-muted">{{ $item['sku'] ?? 'N/A' }}</td>
                                        <td class="small">{{ $item['variant_name'] ?? '-' }}</td>
                                        <td class="text-end">{{ number_format($item['unit_price'] ?? 0, 0, ',', '.') }}₫</td>
                                        <td class="text-end">{{ $item['quantity'] ?? 0 }}</td>
                                        <td class="text-end"><strong>{{ number_format($item['line_total'] ?? 0, 0, ',', '.') }}₫</strong></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">Không có sản phẩm nào.</td>
                                    </tr>
                                @endforelse
                                </tbody>
                                <tfoot>
                                <tr class="table-primary">
                                    <td colspan="6" class="text-end"><strong>Tổng cộng:</strong></td>
                                    <td class="text-end"><strong class="fs-5">{{ number_format($quote->total_amount, 0, ',', '.') }}₫</strong></td>
                                </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
