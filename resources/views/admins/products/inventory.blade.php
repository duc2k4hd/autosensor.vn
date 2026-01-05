@extends('admins.layouts.master')

@section('title', 'Lịch sử kho - '.$product->name)
@section('page-title', '📦 Lịch sử kho - '.$product->name)

@section('content')
    <div class="card">
        <div class="card-body">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                <div>
                    <h2 style="margin:0;font-size:18px;">{{ $product->name }}</h2>
                    <p style="margin:4px 0 0;font-size:13px;color:#64748b;">
                        SKU: <strong>{{ $product->sku }}</strong> • Tồn hiện tại:
                        <strong>{{ $product->stock_quantity ?? 0 }}</strong>
                    </p>
                </div>
                <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">← Quay lại danh sách</a>
            </div>

            <form method="POST" action="{{ route('admin.products.inventory-adjust', $product) }}" class="mb-3">
                @csrf
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Hành động</label>
                        <select name="action" class="form-select" required>
                            <option value="increase">+ Nhập thêm vào kho</option>
                            <option value="decrease">- Xuất/bớt khỏi kho</option>
                            <option value="set">Đặt lại số lượng chính xác</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Số lượng</label>
                        <input type="number" name="quantity" class="form-control" min="1" value="1" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Ghi chú</label>
                        <input type="text" name="note" class="form-control" placeholder="Ví dụ: kiểm kê kho, nhập hàng đợt 1...">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Cập nhật kho</button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                    <tr>
                        <th>Thời gian</th>
                        <th>Loại</th>
                        <th>Thay đổi</th>
                        <th>Tồn trước</th>
                        <th>Tồn sau</th>
                        <th>Tham chiếu</th>
                        <th>Người thao tác</th>
                        <th>Ghi chú</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($movements as $move)
                        <tr>
                            <td>{{ $move->created_at?->format('d/m/Y H:i') }}</td>
                            <td>
                                @php
                                    $typeLabels = [
                                        'order' => 'Đặt hàng',
                                        'order_cancel' => 'Hủy đơn',
                                        'import' => 'Nhập kho',
                                        'export' => 'Xuất kho',
                                        'adjust' => 'Điều chỉnh',
                                        'system' => 'Hệ thống',
                                    ];
                                @endphp
                                <span class="badge bg-light text-dark">
                                    {{ $typeLabels[$move->type] ?? $move->type }}
                                </span>
                            </td>
                            <td>
                                @if($move->quantity_change > 0)
                                    <span class="text-success">+{{ $move->quantity_change }}</span>
                                @else
                                    <span class="text-danger">{{ $move->quantity_change }}</span>
                                @endif
                            </td>
                            <td>{{ $move->stock_before }}</td>
                            <td>{{ $move->stock_after }}</td>
                            <td>
                                @if($move->reference_type === \App\Models\Order::class && $move->reference_id)
                                    <a href="{{ route('admin.orders.show', $move->reference_id) }}">
                                        Đơn #{{ $move->reference_id }}
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $move->account?->name ?? 'Hệ thống' }}</td>
                            <td style="max-width:260px;">
                                <span title="{{ $move->note }}">{{ \Illuminate\Support\Str::limit($move->note, 60) }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted" style="padding:24px 0;">
                                Chưa có lịch sử kho cho sản phẩm này.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $movements->links() }}
            </div>
        </div>
    </div>
@endsection


