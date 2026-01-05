@extends('admins.layouts.master')

@php
    $isEditing = $isEditing ?? false;
    $order = $order ?? null;
@endphp

@php
    $taxPercentValue = old('tax');
    if ($taxPercentValue === null) {
        $taxPercentValue = $order && ($order->total_price ?? 0) > 0
            ? round(($order->tax ?? 0) / max($order->total_price, 1) * 100, 2)
            : 0;
    }
@endphp

@section('title', $isEditing ? 'Chỉnh sửa đơn hàng' : 'Tạo đơn hàng')
@section('page-title', $isEditing ? '✏️ Chỉnh sửa đơn hàng' : '📦 Tạo đơn hàng')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/order-icon.png') }}" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css">
@endpush

@push('styles')
    <style>
        .card {
            background:#fff;
            border-radius:10px;
            padding:16px;
            box-shadow:0 1px 6px rgba(15,23,42,0.06);
            margin-bottom:16px;
        }
        .card > h3 {
            margin:0 0 12px;
            font-size:16px;
            font-weight:600;
            color:#0f172a;
        }
        .grid-2 {
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(300px,1fr));
            gap:12px 16px;
        }
        .grid-3 {
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
            gap:12px 16px;
        }
        .form-control, textarea, select {
            width:100%;
            padding:8px 10px;
            border:1px solid #cbd5f5;
            border-radius:6px;
            font-size:13px;
        }
        label {
            display:block;
            font-size:13px;
            font-weight:500;
            margin-bottom:4px;
            color:#111827;
        }
        .item-row {
            background:#f8fafc;
            padding:12px;
            border-radius:8px;
            margin-bottom:12px;
            border:1px solid #eef2f7;
        }
        .item-row-header {
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:8px;
        }
        .item-row-title {
            font-weight:600;
            color:#0f172a;
        }
        .btn-remove-item {
            background:#fee2e2;
            color:#b91c1c;
            border:none;
            padding:4px 8px;
            border-radius:4px;
            cursor:pointer;
            font-size:12px;
        }
        .btn-remove-item:hover {
            background:#fecaca;
        }
        .summary-item {
            display:flex;
            justify-content:space-between;
            padding:8px 0;
            border-bottom:1px solid #eef2f7;
        }
        .summary-item.total {
            font-weight:600;
            font-size:16px;
            border-top:2px solid #eef2f7;
            margin-top:8px;
            padding-top:12px;
        }
    </style>
@endpush

@section('content')
    <form action="{{ $isEditing ? route('admin.orders.update', $order) : route('admin.orders.store') }}" method="POST" id="order-form">
        @csrf
        @if($isEditing)
            @method('PUT')
        @endif

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h2 style="margin:0;">{{ $isEditing ? 'Chỉnh sửa đơn hàng' : 'Tạo đơn hàng mới' }}</h2>
            <a href="{{ $isEditing ? route('admin.orders.show', $order) : route('admin.orders.index') }}" class="btn btn-secondary">↩️ Quay lại</a>
        </div>

        <div class="order-form-layout">
            <!-- Cột trái: Form chính -->
            <div class="order-form-main">

        @if($isEditing && $order)
            <div class="card">
                <h3>Thông tin đơn hàng</h3>
                <div class="grid-3">
                    <div>
                        <label>Mã đơn hàng</label>
                        <div class="readonly-field">{{ $order->code }}</div>
                    </div>
                    <div>
                        <label>Ngày tạo</label>
                        <div class="readonly-field">{{ optional($order->created_at)->format('d/m/Y H:i') }}</div>
                    </div>
                    <div>
                        <label>Trạng thái hiện tại</label>
                        <div class="readonly-field">{{ strtoupper($order->status) }} / {{ strtoupper($order->payment_status) }}</div>
                    </div>
                </div>
            </div>
        @endif

        <div class="card">
            <h3>Thông tin người đặt</h3>
            <div class="grid-2">
                <div>
                    <label>Người dùng (tùy chọn)</label>
                    <select name="account_id" class="form-control" id="account-select">
                        <option value="">-- Chọn người dùng --</option>
                        @foreach($accounts as $account)
                            @php
                                $accountLabel = trim(($account->name ? $account->name . ' - ' : '') . ($account->email ?? '') . ' - ' . ($account->phone ?? ''));
                            @endphp
                            <option value="{{ $account->id }}" {{ old('account_id', $order->account_id ?? '') == $account->id ? 'selected' : '' }}>
                                {{ $accountLabel }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Tìm kiếm theo tên, email hoặc số điện thoại.</small>
                </div>
                <div>
                    <label>Session ID (nếu là khách)</label>
                    <input type="text" name="session_id" class="form-control" value="{{ old('session_id', $order->session_id ?? '') }}" placeholder="session_id">
                </div>
            </div>
        </div>

        <div class="card">
            <h3>Thông tin người nhận</h3>
            <div class="grid-2">
                <div>
                    <label>Họ tên <span style="color:red;">*</span></label>
                    <input type="text" name="receiver_name" class="form-control" value="{{ old('receiver_name', $order->receiver_name ?? '') }}" required>
                </div>
                <div>
                    <label>Số điện thoại <span style="color:red;">*</span></label>
                    <input type="text" name="receiver_phone" class="form-control" value="{{ old('receiver_phone', $order->receiver_phone ?? '') }}" required>
                </div>
                <div>
                    <label>Email</label>
                    <input type="email" name="receiver_email" class="form-control" value="{{ old('receiver_email', $order->receiver_email ?? '') }}">
                </div>
            </div>
        </div>

        <div class="card">
            <h3>Địa chỉ giao hàng</h3>
            <div class="grid-2">
                <div>
                    <label>Địa chỉ chi tiết <span style="color:red;">*</span></label>
                    <textarea name="shipping_address" rows="3" class="form-control" required>{{ old('shipping_address', $order->shipping_address ?? '') }}</textarea>
                </div>
                <div>
                    <label>Tỉnh/Thành phố <span style="color:red;">*</span></label>
                    <select name="shipping_province_id" id="shipping-province-select" class="form-control" required>
                        <option value="">{{ old('shipping_province_id', $order->shipping_province_id ?? '') ? 'Đang tải...' : '-- Chọn tỉnh/thành --' }}</option>
                    </select>
                    @error('shipping_province_id')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
                <div>
                    <label>Quận/Huyện <span style="color:red;">*</span></label>
                    <select name="shipping_district_id" id="shipping-district-select" class="form-control" required disabled>
                        <option value="">{{ old('shipping_district_id', $order->shipping_district_id ?? '') ? 'Đang tải...' : '-- Chọn quận/huyện --' }}</option>
                    </select>
                    @error('shipping_district_id')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
                <div>
                    <label>Phường/Xã <span style="color:red;">*</span></label>
                    <select name="shipping_ward_id" id="shipping-ward-select" class="form-control" required disabled>
                        <option value="">{{ old('shipping_ward_id', $order->shipping_ward_id ?? '') ? 'Đang tải...' : '-- Chọn phường/xã --' }}</option>
                    </select>
                    @error('shipping_ward_id')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
            </div>
        </div>

        <div class="card">
            <h3>Thanh toán & Vận chuyển</h3>
            <div class="grid-3">
                <div>
                    <label>Phương thức thanh toán <span style="color:red;">*</span></label>
                    <select name="payment_method" class="form-control" required>
                        <option value="cod" {{ old('payment_method', $order->payment_method ?? 'cod') === 'cod' ? 'selected' : '' }}>COD</option>
                        <option value="bank_transfer" {{ old('payment_method', $order->payment_method ?? '') === 'bank_transfer' ? 'selected' : '' }}>Chuyển khoản</option>
                        <option value="qr" {{ old('payment_method', $order->payment_method ?? '') === 'qr' ? 'selected' : '' }}>QR Code</option>
                        <option value="momo" {{ old('payment_method', $order->payment_method ?? '') === 'momo' ? 'selected' : '' }}>MoMo</option>
                        <option value="zalopay" {{ old('payment_method', $order->payment_method ?? '') === 'zalopay' ? 'selected' : '' }}>ZaloPay</option>
                        <option value="payos" {{ old('payment_method', $order->payment_method ?? '') === 'payos' ? 'selected' : '' }}>PayOS</option>
                    </select>
                </div>
                <div>
                    <label>Trạng thái thanh toán</label>
                    <select name="payment_status" class="form-control">
                        <option value="pending" {{ old('payment_status', $order->payment_status ?? 'pending') === 'pending' ? 'selected' : '' }}>Chờ thanh toán</option>
                        <option value="paid" {{ old('payment_status', $order->payment_status ?? '') === 'paid' ? 'selected' : '' }}>Đã thanh toán</option>
                        <option value="failed" {{ old('payment_status', $order->payment_status ?? '') === 'failed' ? 'selected' : '' }}>Thất bại</option>
                    </select>
                </div>
                <div>
                    <label>Mã giao dịch</label>
                    <input type="text" name="transaction_code" class="form-control" value="{{ old('transaction_code', $order->transaction_code ?? '') }}">
                </div>
                <div>
                    <label>Đơn vị vận chuyển</label>
                    <select name="shipping_partner" class="form-control">
                        <option value="ghn" {{ old('shipping_partner', $order->shipping_partner ?? 'ghn') === 'ghn' ? 'selected' : '' }}>GHN</option>
                        <option value="viettelpost" {{ old('shipping_partner', $order->shipping_partner ?? '') === 'viettelpost' ? 'selected' : '' }}>ViettelPost</option>
                        <option value="ghtk" {{ old('shipping_partner', $order->shipping_partner ?? '') === 'ghtk' ? 'selected' : '' }}>GHTK</option>
                    </select>
                </div>
                <div>
                    <label>Dịch vụ GHN <span style="color:red;">*</span></label>
                    <select id="ghn-service-select" class="form-control" name="ghn_service_id" data-service-type="">
                        <option value="">{{ old('shipping_partner', $order->shipping_partner ?? 'ghn') === 'ghn' ? 'Đang tải...' : '-- Chọn dịch vụ --' }}</option>
                    </select>
                    <input type="hidden" name="ghn_service_type_id" id="ghn-service-type-id" value="{{ old('ghn_service_type_id', $order->ghn_service_type_id ?? '') }}">
                </div>
                <div>
                    <label>Mã vận đơn</label>
                    <input type="text" name="shipping_tracking_code" class="form-control" value="{{ old('shipping_tracking_code', $order->shipping_tracking_code ?? '') }}">
                </div>
                <div>
                    <label>Trạng thái vận chuyển</label>
                    <select name="delivery_status" class="form-control">
                        <option value="pending" {{ old('delivery_status', $order->delivery_status ?? 'pending') === 'pending' ? 'selected' : '' }}>Chờ giao</option>
                        <option value="shipped" {{ old('delivery_status', $order->delivery_status ?? '') === 'shipped' ? 'selected' : '' }}>Đang giao</option>
                        <option value="delivered" {{ old('delivery_status', $order->delivery_status ?? '') === 'delivered' ? 'selected' : '' }}>Đã giao</option>
                        <option value="returned" {{ old('delivery_status', $order->delivery_status ?? '') === 'returned' ? 'selected' : '' }}>Đã trả</option>
                    </select>
                </div>
                <div>
                    <label>Trạng thái đơn hàng</label>
                    <select name="status" class="form-control">
                        <option value="pending" {{ old('status', $order->status ?? 'pending') === 'pending' ? 'selected' : '' }}>Chờ xử lý</option>
                        <option value="processing" {{ old('status', $order->status ?? '') === 'processing' ? 'selected' : '' }}>Đang xử lý</option>
                        <option value="completed" {{ old('status', $order->status ?? '') === 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                        <option value="cancelled" {{ old('status', $order->status ?? '') === 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card">
            <h3>Chi phí & Khuyến mãi</h3>
            <div class="grid-3">
                <div>
                    <label>Phí vận chuyển <small style="color:#64748b;">(tự tính từ GHN)</small></label>
                    <input type="number" name="shipping_fee" class="form-control shipping-fee" value="{{ old('shipping_fee', $order->shipping_fee ?? 0) }}" min="0" step="1000" readonly>
                </div>
                <div>
                    <label>Thuế (%)</label>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <input type="number" name="tax" class="form-control tax" value="{{ $taxPercentValue }}" min="0" max="100" step="0.1">
                        <span>%</span>
                    </div>
                </div>
                <div>
                    <label>Giảm giá</label>
                    <input type="number" name="discount" class="form-control discount" value="{{ old('discount', $order->discount ?? 0) }}" min="0" step="1000">
                </div>
                <div>
                    <label>Mã voucher</label>
                    <div style="display:flex;gap:8px;">
                        <input type="text" name="voucher_code" id="voucher-code-input" class="form-control" value="{{ old('voucher_code', $order->voucher_code ?? '') }}" placeholder="Nhập mã voucher">
                        <button type="button" class="btn btn-secondary" id="apply-voucher-btn" onclick="applyVoucherFromInput()">Áp dụng</button>
                    </div>
                </div>
                <div>
                    <label>Giảm giá từ voucher</label>
                    <input type="number" name="voucher_discount" class="form-control voucher-discount" value="{{ old('voucher_discount', $order->voucher_discount ?? 0) }}" min="0" step="1000">
                </div>
            </div>
        </div>

        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                <h3 style="margin:0;">Sản phẩm trong đơn</h3>
                <button type="button" class="btn btn-primary" onclick="addItemRow()">➕ Thêm sản phẩm</button>
            </div>
            <div id="items-container">
                @php
                    $initialItems = old('items');
                    if (!$initialItems && $order) {
                        $order->loadMissing('items');
                    }
                    if (!$initialItems && $order && $order->items) {
                        $initialItems = $order->items->map(function($item) {
                            return [
                                'product_id' => $item->product_id,
                                'product_variant_id' => $item->product_variant_id,
                                'quantity' => $item->quantity,
                                'price' => $item->price,
                            ];
                        })->toArray();
                    }
                    $itemRowCount = $initialItems ? count($initialItems) : 1;
                @endphp
                @if($initialItems)
                    @foreach($initialItems as $index => $item)
                        @include('admins.orders.partials.item-row', ['index' => $index, 'item' => $item])
                    @endforeach
                @else
                    @include('admins.orders.partials.item-row', ['index' => 0])
                @endif
            </div>
        </div>

        <div class="card">
            <h3>Tóm tắt đơn hàng</h3>
            <div>
                <div class="summary-item">
                    <span>Tổng tiền sản phẩm:</span>
                    <strong id="total-price-display">{{ number_format(old('total_price', $order->total_price ?? 0)) }} đ</strong>
                </div>
                <div class="summary-item">
                    <span>Phí vận chuyển:</span>
                    <span id="shipping-fee-display">{{ number_format(old('shipping_fee', $order->shipping_fee ?? 0)) }} đ</span>
                </div>
                <div class="summary-item">
                    <span>Thuế:</span>
                    <span id="tax-display">{{ number_format($order->tax ?? 0) }} đ</span>
                </div>
                <div class="summary-item">
                    <span>Giảm giá:</span>
                    <span id="discount-display">{{ number_format(old('discount', $order->discount ?? 0)) }} đ</span>
                </div>
                <div class="summary-item">
                    <span>Giảm giá voucher:</span>
                    <span id="voucher-discount-display">{{ number_format(old('voucher_discount', $order->voucher_discount ?? 0)) }} đ</span>
                </div>
                <div class="summary-item total">
                    <span>Thành tiền:</span>
                    <strong id="final-price-display">{{ number_format(old('final_price', $order->final_price ?? 0)) }} đ</strong>
                </div>
            </div>
        </div>

        <div class="card">
            <h3>Ghi chú</h3>
            <div>
                <label>Ghi chú khách hàng</label>
                <textarea name="customer_note" rows="3" class="form-control">{{ old('customer_note', $order->customer_note ?? '') }}</textarea>
            </div>
            <div style="margin-top:12px;">
                <label>Ghi chú nội bộ</label>
                <textarea name="admin_note" rows="3" class="form-control">{{ old('admin_note', $order->admin_note ?? '') }}</textarea>
            </div>
        </div>

            </div>

            <!-- Cột phải: Sidebar với actions và quick info -->
            <div class="order-form-sidebar">
                <!-- Quick Info -->
                @if($isEditing && $order)
                <div class="sidebar-card">
                    <h4>Thông tin nhanh</h4>
                    <div class="sidebar-info-item">
                        <span class="sidebar-info-label">Mã đơn:</span>
                        <span class="sidebar-info-value">{{ $order->code }}</span>
                    </div>
                    <div class="sidebar-info-item">
                        <span class="sidebar-info-label">Ngày tạo:</span>
                        <span class="sidebar-info-value">{{ optional($order->created_at)->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="sidebar-info-item">
                        <span class="sidebar-info-label">Trạng thái:</span>
                        <span class="sidebar-info-value">{{ strtoupper($order->status) }}</span>
                    </div>
                    <div class="sidebar-info-item">
                        <span class="sidebar-info-label">Thanh toán:</span>
                        <span class="sidebar-info-value">{{ strtoupper($order->payment_status) }}</span>
                    </div>
                </div>
                @endif

                <!-- Tóm tắt đơn hàng -->
                <div class="sidebar-card">
                    <h4>Tóm tắt</h4>
                    <div class="sidebar-info-item">
                        <span class="sidebar-info-label">Tổng sản phẩm:</span>
                        <span class="sidebar-info-value" id="sidebar-total-price">{{ number_format(old('total_price', $order->total_price ?? 0)) }} đ</span>
                    </div>
                    <div class="sidebar-info-item">
                        <span class="sidebar-info-label">Phí vận chuyển:</span>
                        <span class="sidebar-info-value" id="sidebar-shipping-fee">{{ number_format(old('shipping_fee', $order->shipping_fee ?? 0)) }} đ</span>
                    </div>
                    <div class="sidebar-info-item">
                        <span class="sidebar-info-label">Thuế:</span>
                        <span class="sidebar-info-value" id="sidebar-tax">{{ number_format($order->tax ?? 0) }} đ</span>
                    </div>
                    <div class="sidebar-info-item">
                        <span class="sidebar-info-label">Giảm giá:</span>
                        <span class="sidebar-info-value" id="sidebar-discount">{{ number_format(old('discount', $order->discount ?? 0)) }} đ</span>
                    </div>
                    <div class="sidebar-info-item">
                        <span class="sidebar-info-label">Voucher:</span>
                        <span class="sidebar-info-value" id="sidebar-voucher-discount">{{ number_format(old('voucher_discount', $order->voucher_discount ?? 0)) }} đ</span>
                    </div>
                    <div class="sidebar-info-item" style="border-top: 2px solid #e5e7eb; margin-top: 8px; padding-top: 12px;">
                        <span class="sidebar-info-label" style="font-size: 14px; font-weight: 600;">Thành tiền:</span>
                        <span class="sidebar-info-value" style="font-size: 16px; color: #15803d; font-weight: 700;" id="sidebar-final-price">{{ number_format(old('final_price', $order->final_price ?? 0)) }} đ</span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="sidebar-card">
                    <h4>Thao tác</h4>
                    <div class="sidebar-actions">
                        <button type="submit" form="order-form" class="btn btn-primary">{{ $isEditing ? '💾 Lưu thay đổi' : '💾 Tạo đơn hàng' }}</button>
                        @if($isEditing && $order)
                            <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-outline-primary">👁️ Xem chi tiết</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </form>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script>
        let itemIndex = {{ $itemRowCount }};
        const ghnState = {
            province: '{{ old('shipping_province_id', $order->shipping_province_id ?? '') }}',
            district: '{{ old('shipping_district_id', $order->shipping_district_id ?? '') }}',
            ward: '{{ old('shipping_ward_id', $order->shipping_ward_id ?? '') }}',
            serviceId: '{{ old('ghn_service_id', $order->ghn_service_id ?? '') }}',
            serviceTypeId: '{{ old('ghn_service_type_id', $order->ghn_service_type_id ?? '') }}',
        };
        let totalsState = {
            totalPrice: parseFloat({{ old('total_price', $order->total_price ?? 0) ?? 0 }}),
            shippingFee: parseFloat({{ old('shipping_fee', $order->shipping_fee ?? 0) ?? 0 }}),
            tax: parseFloat({{ $order->tax ?? 0 }}),
            discount: parseFloat({{ old('discount', $order->discount ?? 0) ?? 0 }}),
            voucherDiscount: parseFloat({{ old('voucher_discount', $order->voucher_discount ?? 0) ?? 0 }}),
            finalPrice: parseFloat({{ old('final_price', $order->final_price ?? 0) ?? 0 }}),
        };
        let feeRecalcTimer = null;
        let suppressFeeRecalc = false;
        let serviceTS = null;
        let serviceOptionsCache = [];
        const productPriceMap = {};
        const variantPriceMap = {};
        let voucherLocked = false;
        let voucherLockedCode = null;
        let voucherAdjustsShipping = false;
        let voucherShippingOffset = 0;

        function formatCurrency(value) {
            return new Intl.NumberFormat('vi-VN').format(value) + ' đ';
        }

        function invalidateVoucher(reason = null) {
            const voucherInput = document.querySelector('.voucher-discount');
            const codeInput = document.getElementById('voucher-code-input');
            const applyBtn = document.getElementById('apply-voucher-btn');
            const hadVoucher = voucherLocked || (voucherInput && parseFloat(voucherInput.value || 0) > 0);

            voucherLocked = false;
            voucherLockedCode = null;
            voucherAdjustsShipping = false;
            voucherShippingOffset = 0;

            if (voucherInput) {
                voucherInput.value = 0;
            }
            if (codeInput) {
                codeInput.readOnly = false;
            }
            if (applyBtn) {
                applyBtn.disabled = false;
            }
            updateTotals();

            if (hadVoucher && reason && typeof showCustomToast === 'function') {
                showCustomToast(reason, 'info', 3500);
            }
        }

        function addItemRow() {
            const container = document.getElementById('items-container');
            const row = document.createElement('div');
            row.className = 'item-row';
            row.dataset.index = itemIndex;
            row.innerHTML = `
                <div class="item-row-header">
                    <span class="item-row-title">Sản phẩm #${itemIndex + 1}</span>
                    <button type="button" class="btn-remove-item" onclick="removeItemRow(this)">🗑️ Xóa</button>
                </div>
                <div class="grid-3">
                    <div>
                        <label>Sản phẩm <span style="color:red;">*</span></label>
                        <select name="items[${itemIndex}][product_id]" class="form-control product-select" required onchange="loadVariants(this, ${itemIndex})" id="product-${itemIndex}">
                            <option value="">-- Chọn sản phẩm --</option>
                        </select>
                    </div>
                    <div>
                        <label>Biến thể (tùy chọn)</label>
                        <select name="items[${itemIndex}][product_variant_id]" class="form-control variant-select" id="variant-${itemIndex}">
                            <option value="">-- Không có biến thể --</option>
                        </select>
                    </div>
                    <div>
                        <label>Số lượng <span style="color:red;">*</span></label>
                        <input type="number" name="items[${itemIndex}][quantity]" class="form-control item-quantity" value="1" min="1" required oninput="updateItemTotal(this)">
                    </div>
                    <div>
                        <label>Giá <span style="color:red;">*</span></label>
                        <input type="number" name="items[${itemIndex}][price]" class="form-control item-price" value="0" min="0" step="1000" required oninput="updateItemTotal(this)">
                    </div>
                    <div>
                        <label>Thành tiền</label>
                        <input type="text" class="form-control item-total" value="0 đ" readonly style="background:#f8fafc;">
                    </div>
                </div>
            `;
            container.appendChild(row);
            loadProducts(row.querySelector('.product-select')).then(() => {
                initTomSelectsForRow(itemIndex - 1);
            });
            if (typeof showCustomToast === 'function') {
                showCustomToast('Đã thêm một sản phẩm vào đơn hàng.', 'success', 3000);
            }
            itemIndex++;
        }

        function removeItemRow(btn) {
            const container = document.getElementById('items-container');
            if (container.children.length <= 1) {
                alert('Đơn hàng phải có ít nhất 1 sản phẩm.');
                return;
            }
            btn.closest('.item-row').remove();
            updateTotals();
            if (typeof showCustomToast === 'function') {
                showCustomToast('Đã xóa sản phẩm khỏi đơn hàng.', 'info', 3000);
            }
        }

        function registerProductOption(option) {
            if (option.value) {
                productPriceMap[option.value] = parseFloat(option.dataset.price || option.getAttribute('data-price') || 0) || 0;
            }
        }

        function applyPriceForRow(index, price) {
            const row = document.querySelector(`.item-row[data-index="${index}"]`);
            if (!row) return;
            const priceInput = row.querySelector('.item-price');
            if (!priceInput) return;
            const value = parseFloat(price);
            priceInput.value = isNaN(value) ? 0 : value;
            priceInput.dispatchEvent(new Event('input'));
        }

        function handleVariantChange(index, variantId) {
            const row = document.querySelector(`.item-row[data-index="${index}"]`);
            if (!row) return;
            const productId = row.querySelector('.product-select')?.value;

            if (!variantId) {
                const fallback = productPriceMap[productId] ?? 0;
                applyPriceForRow(index, fallback);
                return;
            }

            const price = variantPriceMap[index]?.[variantId];
            if (typeof price !== 'undefined') {
                applyPriceForRow(index, price);
            } else if (productId && typeof productPriceMap[productId] !== 'undefined') {
                applyPriceForRow(index, productPriceMap[productId]);
            }
            if (typeof showCustomToast === 'function') {
                showCustomToast('Đã cập nhật giá theo biến thể.', 'info', 2500);
            }
        }

        function loadProducts(select) {
            return fetch('{{ route('admin.products.index') }}?format=json')
                .then(res => res.json())
                .then(data => {
                    if (data.products) {
                        data.products.forEach(product => {
                            const option = document.createElement('option');
                            option.value = product.id;
                            option.textContent = product.name + ' (SKU: ' + product.sku + ')';
                             option.dataset.price = product.price ?? 0;
                             if (product.primary_category_id) {
                                 option.dataset.categoryId = product.primary_category_id;
                             }
                            select.appendChild(option);
                             registerProductOption(option);
                        });
                        if (typeof showCustomToast === 'function') {
                            showCustomToast('Đã tải danh sách sản phẩm.', 'success', 3000);
                        }
                    }
                })
                .catch(() => {
                    if (typeof showCustomToast === 'function') {
                        showCustomToast('Không thể tải danh sách sản phẩm. Vui lòng thử lại.', 'error', 4000);
                    }
                });
        }

        function loadVariants(productSelect, index) {
            const productId = productSelect.value;
            const variantSelect = document.getElementById(`variant-${index}`);
            if (!variantSelect) return;

            const variantTS = variantSelect.tomselect || null;
            const resetVariant = () => {
                if (variantTS) {
                    variantTS.clearOptions();
                    variantTS.addOption({ value: '', text: '-- Không có biến thể --' });
                    variantTS.addItem('');
                } else {
                    variantSelect.innerHTML = '<option value="">-- Không có biến thể --</option>';
                }
            };

            resetVariant();
            
            const row = productSelect.closest('.item-row');
            const rowIndex = row?.dataset.index ?? index;

            if (!productId) {
                variantPriceMap[rowIndex] = {};
                handleVariantChange(rowIndex, '');
                return;
            }

            // Load variants via AJAX
            fetch(`/admin/products/${productId}/variants?format=json`)
                .then(res => res.json())
                .then(data => {
                    if (data.product && typeof data.product.price !== 'undefined') {
                        productPriceMap[productId] = data.product.price;
                    }

                    variantPriceMap[rowIndex] = {};

                    if (data.variants && data.variants.length) {
                        if (variantTS) {
                            const options = data.variants.map(variant => {
                                const rawAttrs = variant.attributes ?? {};
                                const attrs = typeof rawAttrs === 'string'
                                    ? (JSON.parse(rawAttrs) || {})
                                    : (rawAttrs || {});
                                const attrText = Object.entries(attrs).map(([k, v]) => `${k}: ${v}`).join(', ');
                                const price = parseFloat(variant.price ?? variant.sale_price ?? variant.compare_at_price ?? productPriceMap[productId] ?? 0) || 0;
                                variantPriceMap[rowIndex][String(variant.id)] = price;
                                return { value: variant.id, text: attrText || `Biến thể #${variant.id}` };
                            });
                            variantTS.addOption(options);
                            variantTS.refreshOptions(false);
                            // Tự động chọn biến thể đầu tiên
                            const firstVariantId = String(data.variants[0].id);
                            variantTS.setValue(firstVariantId, true);
                            handleVariantChange(rowIndex, firstVariantId);
                        } else {
                            data.variants.forEach(variant => {
                                const option = document.createElement('option');
                                option.value = variant.id;
                                const rawAttrs = variant.attributes ?? {};
                                const attrs = typeof rawAttrs === 'string'
                                    ? (JSON.parse(rawAttrs) || {})
                                    : (rawAttrs || {});
                                const attrText = Object.entries(attrs).map(([k, v]) => `${k}: ${v}`).join(', ');
                                option.textContent = attrText || 'Biến thể #' + variant.id;
                                const price = parseFloat(variant.price ?? variant.sale_price ?? variant.compare_at_price ?? productPriceMap[productId] ?? 0) || 0;
                                option.dataset.price = price;
                                variantPriceMap[rowIndex][String(variant.id)] = price;
                                variantSelect.appendChild(option);
                            });
                            // Tự động chọn biến thể đầu tiên
                            if (variantSelect.options.length > 1) {
                                const firstOption = variantSelect.options[1];
                                variantSelect.value = firstOption.value;
                                handleVariantChange(rowIndex, firstOption.value);
                            }
                        }

                        const variantChangeHandler = value => handleVariantChange(rowIndex, value);
                        if (variantTS) {
                            variantTS.off('change');
                            variantTS.on('change', variantChangeHandler);
                        } else {
                            variantSelect.onchange = function () {
                                variantChangeHandler(this.value);
                            };
                        }
                        if (typeof showCustomToast === 'function') {
                            showCustomToast('Đã tải danh sách biến thể sản phẩm.', 'success', 3000);
                        }
                    } else {
                        variantPriceMap[rowIndex] = {};
                        handleVariantChange(rowIndex, '');
                        if (typeof showCustomToast === 'function') {
                            showCustomToast('Sản phẩm này không có biến thể.', 'info', 3000);
                        }
                    }
                })
                .catch(() => {
                    if (typeof showCustomToast === 'function') {
                        showCustomToast('Không thể tải biến thể sản phẩm. Vui lòng thử lại.', 'error', 4000);
                    }
                });
        }

        function updateItemTotal(input) {
            const row = input.closest('.item-row');
            const quantity = parseFloat(row.querySelector('.item-quantity').value) || 0;
            const price = parseFloat(row.querySelector('.item-price').value) || 0;
            const total = quantity * price;
            row.querySelector('.item-total').value = new Intl.NumberFormat('vi-VN').format(total) + ' đ';
            updateTotals();
        }

        function updateTotals() {
            let totalPrice = 0;
            document.querySelectorAll('.item-row').forEach(row => {
                const quantity = parseFloat(row.querySelector('.item-quantity').value) || 0;
                const price = parseFloat(row.querySelector('.item-price').value) || 0;
                totalPrice += quantity * price;
            });

            const shippingFee = parseFloat(document.querySelector('.shipping-fee').value) || 0;
            const taxPercent = parseFloat(document.querySelector('.tax').value) || 0;
            const tax = totalPrice * (taxPercent / 100);
            const discount = parseFloat(document.querySelector('.discount').value) || 0;
            const voucherDiscount = parseFloat(document.querySelector('.voucher-discount').value) || 0;

            const shippingDiscountApplied = voucherAdjustsShipping
                ? Math.min(voucherShippingOffset || voucherDiscount, shippingFee)
                : 0;
            const displayedShipping = Math.max(0, shippingFee - shippingDiscountApplied);

            const finalPrice = totalPrice + shippingFee + tax - discount - voucherDiscount;

            totalsState = {
                totalPrice,
                shippingFee,
                tax,
                discount,
                voucherDiscount,
                finalPrice: Math.max(0, finalPrice),
            };

            document.getElementById('total-price-display').textContent = formatCurrency(totalPrice);
            document.getElementById('shipping-fee-display').textContent = formatCurrency(displayedShipping);
            document.getElementById('tax-display').textContent = formatCurrency(tax);
            document.getElementById('discount-display').textContent = formatCurrency(discount);
            document.getElementById('voucher-discount-display').textContent = formatCurrency(voucherDiscount);
            document.getElementById('final-price-display').textContent = formatCurrency(totalsState.finalPrice);

            // Cập nhật sidebar
            const sidebarTotalPrice = document.getElementById('sidebar-total-price');
            const sidebarShippingFee = document.getElementById('sidebar-shipping-fee');
            const sidebarTax = document.getElementById('sidebar-tax');
            const sidebarDiscount = document.getElementById('sidebar-discount');
            const sidebarVoucherDiscount = document.getElementById('sidebar-voucher-discount');
            const sidebarFinalPrice = document.getElementById('sidebar-final-price');
            
            if (sidebarTotalPrice) sidebarTotalPrice.textContent = formatCurrency(totalsState.totalPrice);
            if (sidebarShippingFee) sidebarShippingFee.textContent = formatCurrency(totalsState.shippingFee);
            if (sidebarTax) sidebarTax.textContent = formatCurrency(totalsState.tax);
            if (sidebarDiscount) sidebarDiscount.textContent = formatCurrency(totalsState.discount);
            if (sidebarVoucherDiscount) sidebarVoucherDiscount.textContent = formatCurrency(totalsState.voucherDiscount);
            if (sidebarFinalPrice) sidebarFinalPrice.textContent = formatCurrency(totalsState.finalPrice);

            if (!suppressFeeRecalc) {
                scheduleFeeRecalc();
            } else {
                suppressFeeRecalc = false;
            }
        }

        // ------------------------------
        // Voucher helpers (dùng đúng logic ở VoucherService)
        // ------------------------------

        function buildOrderDataForVoucher() {
            const items = [];
            document.querySelectorAll('.item-row').forEach(row => {
                const productSelect = row.querySelector('.product-select');
                const productId = parseInt(productSelect?.value || 0, 10) || null;
                const quantity = parseInt(row.querySelector('.item-quantity')?.value || 0, 10) || 0;
                const price = parseFloat(row.querySelector('.item-price')?.value || 0) || 0;
                const total_price = quantity * price;
                const selectedOption = productSelect ? productSelect.options[productSelect.selectedIndex] : null;
                const categoryId = selectedOption?.dataset?.categoryId
                    ? parseInt(selectedOption.dataset.categoryId, 10)
                    : null;

                if (productId && quantity > 0) {
                    items.push({
                        product_id: productId,
                        category_id: categoryId,
                        quantity,
                        total_price
                    });
                }
            });

            return {
                items,
                shipping_fee: parseFloat(document.querySelector('.shipping-fee')?.value || 0) || 0
            };
        }

        function applyVoucherFromInput() {
            const codeInput = document.getElementById('voucher-code-input');
            if (!codeInput) return;
            const voucherCode = (codeInput.value || '').trim();

            if (voucherLocked) {
                if (typeof showCustomToast === 'function') {
                    showCustomToast('Mỗi đơn hàng chỉ áp dụng được 1 voucher. Nếu muốn thay đổi, hãy làm mới trang.', 'info', 4000);
                }
                return;
            }

            if (!voucherCode) {
                if (typeof showCustomToast === 'function') {
                    showCustomToast('Vui lòng nhập mã voucher trước khi áp dụng.', 'warning', 3000);
                }
                return;
            }

            // Bắt buộc phải có phí ship trước khi áp dụng voucher (đặc biệt với loại free_shipping / shipping_*)
            const shippingFeeInput = document.querySelector('.shipping-fee');
            let shippingFee = parseFloat(shippingFeeInput?.value || 0) || 0;
            if (!shippingFeeInput || shippingFee <= 0) {
                if (shippingFeeInput) {
                    // Mặc định phí ship = 30k
                    shippingFeeInput.value = 30000;
                    shippingFee = 30000;
                    suppressFeeRecalc = true;
                    updateTotals();
                }
                if (typeof showCustomToast === 'function') {
                    showCustomToast('Vui lòng xác định phí vận chuyển trước khi áp dụng voucher (đã đặt mặc định 30.000đ).', 'warning', 4500);
                }
                return;
            }

            const orderData = buildOrderDataForVoucher();
            if (!orderData.items.length) {
                if (typeof showCustomToast === 'function') {
                    showCustomToast('Đơn hàng chưa có sản phẩm nên không thể áp dụng voucher.', 'warning', 3500);
                }
                return;
            }

            const csrf = document.querySelector('meta[name="csrf-token"]');
            const headers = {
                'Content-Type': 'application/json',
            };
            if (csrf) {
                headers['X-CSRF-TOKEN'] = csrf.getAttribute('content');
            }

            fetch('/voucher/validate', {
                method: 'POST',
                headers,
                body: JSON.stringify({
                    voucher_code: voucherCode,
                    order_data: orderData
                })
            })
            .then(res => res.json())
            .then(data => {
                const voucherDiscountInput = document.querySelector('.voucher-discount');
                if (!voucherDiscountInput) {
                    return;
                }

                if (data.success) {
                    voucherAdjustsShipping = false;
                    voucherShippingOffset = 0;
                    const discount = parseFloat(data.discount_amount || 0) || 0;
                    voucherDiscountInput.value = discount;
                    const voucherType = data.voucher?.type || null;
                    const shippingVoucherTypes = ['free_shipping', 'shipping_percentage', 'shipping_fixed'];
                    if (shippingVoucherTypes.includes(voucherType)) {
                        const shippingFeeInput = document.querySelector('.shipping-fee');
                        const shippingFeeValue = parseFloat(shippingFeeInput?.value || 0) || 0;
                        voucherAdjustsShipping = true;
                        voucherShippingOffset = Math.min(discount, shippingFeeValue);
                    }
                    const beforeTotals = { ...totalsState };
                    updateTotals();
                    const afterTotals = { ...totalsState };
                    console.log('Voucher apply success', {
                        voucherCode,
                        voucherType,
                        discount,
                        shippingVoucher: voucherAdjustsShipping,
                        totalsBefore: beforeTotals,
                        totalsAfter: afterTotals,
                    });
                    voucherLocked = true;
                    voucherLockedCode = voucherCode;
                    codeInput.readOnly = true;
                    const applyBtn = document.getElementById('apply-voucher-btn');
                    if (applyBtn) {
                        applyBtn.disabled = true;
                    }
                    if (typeof showCustomToast === 'function') {
                        const msg = data.message
                            ? `${data.message} (Giảm: ${formatCurrency(discount)}, Thành tiền mới: ${formatCurrency(afterTotals.finalPrice)})`
                            : `Áp dụng voucher thành công! Giảm ${formatCurrency(discount)}. Thành tiền mới: ${formatCurrency(afterTotals.finalPrice)}`;
                        showCustomToast(msg, 'success', 5000);
                    }
                } else {
                    voucherDiscountInput.value = 0;
                    voucherAdjustsShipping = false;
                    voucherShippingOffset = 0;
                    updateTotals();
                    console.warn('Voucher apply failed', { voucherCode, response: data });
                    if (typeof showCustomToast === 'function') {
                        const msg = data.message || 'Không thể áp dụng voucher.';
                        showCustomToast(msg, 'error', 4000);
                    }
                }
            })
            .catch(err => {
                console.error(err);
                voucherAdjustsShipping = false;
                voucherShippingOffset = 0;
                console.error('Voucher apply error', err);
                if (typeof showCustomToast === 'function') {
                    showCustomToast('Có lỗi xảy ra khi kiểm tra voucher. Vui lòng thử lại.', 'error', 4000);
                }
            });
        }

        // Initialize
        function initTomSelectsForRow(index) {
            const idx = Number(index);
            const productSelect = document.getElementById(`product-${idx}`);
            const variantSelect = document.getElementById(`variant-${idx}`);
            if (productSelect && !productSelect.tomselect) {
                new TomSelect(productSelect, {
                    placeholder: 'Tìm sản phẩm...',
                    allowEmptyOption: true,
                    searchField: ['text']
                });
            }
            if (variantSelect && !variantSelect.tomselect) {
                new TomSelect(variantSelect, {
                    placeholder: 'Chọn biến thể...',
                    allowEmptyOption: true
                }).on('change', value => handleVariantChange(idx, value));
            } else if (variantSelect && variantSelect.tomselect) {
                variantSelect.tomselect.on('change', value => handleVariantChange(idx, value));
            } else if (variantSelect) {
                variantSelect.addEventListener('change', function() {
                    handleVariantChange(idx, this.value);
                });
            }
        }

        function syncServiceSelection(value) {
            const option = serviceOptionsCache.find(opt => opt.value === String(value));
            const serviceTypeInput = document.getElementById('ghn-service-type-id');
            const serviceType = option?.serviceTypeId || '';
            if (serviceTypeInput) {
                serviceTypeInput.value = serviceType;
            }
            ghnState.serviceId = value || '';
            ghnState.serviceTypeId = serviceType;
        }

        function initGhnAddressSelectors(config) {
            const { provinceSelectId, districtSelectId, wardSelectId, oldProvince, oldDistrict, oldWard, oldService } = config;
            const provinceEl = document.getElementById(provinceSelectId);
            const districtEl = document.getElementById(districtSelectId);
            const wardEl = document.getElementById(wardSelectId);
            if (!provinceEl || !districtEl || !wardEl) return;

            let provinceTS = null;
            let districtTS = null;
            let wardTS = null;

            const resetSelect = (select, placeholder, disable = true) => {
                if (!select) return;
                if (select.tomselect) {
                    select.tomselect.destroy();
                    select.tomselect = null;
                }
                select.innerHTML = `<option value="">${placeholder}</option>`;
                select.disabled = disable;
            };

            const recreateTomSelect = (instance, select, placeholder, search = true) => {
                if (instance) {
                    instance.destroy();
                }
                return new TomSelect(select, {
                    placeholder,
                    allowEmptyOption: true,
                    searchField: search ? ['text'] : undefined
                });
            };

            const fetchJson = (url, options = {}) => {
                return fetch(url, options)
                    .then(res => res.json())
                    .then(json => {
                        // Hỗ trợ cả dạng { data: [...] } và dạng [...] thuần
                        console.log('GHN fetchJson response for', url, json);
                        if (Array.isArray(json)) {
                            return json;
                        }
                        if (json && Array.isArray(json.data)) {
                            return json.data;
                        }
                        return [];
                    });
            };

            const loadProvinces = async (selectedValue, cascade = false) => {
                resetSelect(provinceEl, 'Đang tải tỉnh/thành...', true);
                try {
                    const data = await fetchJson('/api/v1/ghn/province');
                    provinceEl.innerHTML = '<option value="">-- Chọn tỉnh/thành --</option>';
                    data.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.provinceId;
                        option.textContent = item.provinceName;
                        provinceEl.appendChild(option);
                    });
                    provinceEl.disabled = false;
                    provinceTS = recreateTomSelect(provinceTS, provinceEl, 'Chọn tỉnh/thành');
                    provinceTS.clearOptions();
                    provinceTS.addOption({value: '', text: '-- Chọn tỉnh/thành --'});
                    data.forEach(item => {
                        provinceTS.addOption({value: String(item.provinceId), text: item.provinceName});
                    });
                    provinceTS.refreshOptions(false);
                    provinceTS.clear(true);
                    provinceTS.on('change', value => {
                        ghnState.province = value;
                        invalidateVoucher('Đã thay đổi Tỉnh/Thành, vui lòng áp lại voucher.');
                        loadDistricts(value);
                    });
                    if (selectedValue) {
                        provinceTS.setValue(String(selectedValue), true);
                        if (cascade) {
                            await loadDistricts(selectedValue, oldDistrict, cascade);
                        }
                    } else {
                        resetSelect(districtEl, '-- Chọn quận/huyện --', true);
                        resetSelect(wardEl, '-- Chọn phường/xã --', true);
                    }
                    if (typeof showCustomToast === 'function') {
                        showCustomToast('Đã tải danh sách Tỉnh/Thành phố từ GHN.', 'success', 3000);
                    }
                } catch (error) {
                    console.error(error);
                    resetSelect(provinceEl, 'Không thể tải tỉnh/thành', true);
                    if (typeof showCustomToast === 'function') {
                        showCustomToast('Không thể tải danh sách Tỉnh/Thành phố từ GHN.', 'error', 4000);
                    }
                }
            };

            const loadDistricts = async (provinceId, selectedValue = null, cascade = false) => {
                resetSelect(districtEl, '-- Chọn quận/huyện --', true);
                resetSelect(wardEl, '-- Chọn phường/xã --', true);
                ghnState.district = '';
                ghnState.ward = '';
                ghnState.serviceId = '';
                ghnState.serviceTypeId = '';
                resetServiceSelect();
                if (!provinceId) {
                    if (districtTS) districtTS.destroy();
                    if (wardTS) wardTS.destroy();
                    return;
                }
                try {
                    const data = await fetchJson(`/api/v1/ghn/district/${provinceId}`, { method: 'POST' });
                    districtEl.innerHTML = '<option value="">-- Chọn quận/huyện --</option>';
                    data.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.districtID;
                        option.textContent = item.districtName;
                        districtEl.appendChild(option);
                    });
                    districtEl.disabled = false;
                    districtTS = recreateTomSelect(districtTS, districtEl, 'Chọn quận/huyện');
                    districtTS.clearOptions();
                    districtTS.addOption({value: '', text: '-- Chọn quận/huyện --'});
                    data.forEach(item => {
                        districtTS.addOption({value: String(item.districtID), text: item.districtName});
                    });
                    districtTS.refreshOptions(false);
                    districtTS.clear(true);
                    districtTS.on('change', value => {
                        ghnState.district = value;
                        invalidateVoucher('Đã thay đổi Quận/Huyện, vui lòng áp lại voucher.');
                        loadWards(value);
                        loadServices(value, ghnState.serviceId);
                    });
                    if (selectedValue) {
                        districtTS.setValue(String(selectedValue), true);
                        if (cascade) {
                            await loadWards(selectedValue, oldWard, cascade);
                            await loadServices(selectedValue, ghnState.serviceId);
                        }
                    }
                    if (typeof showCustomToast === 'function') {
                        showCustomToast('Đã tải danh sách Quận/Huyện từ GHN.', 'success', 3000);
                    }
                } catch (error) {
                    console.error(error);
                    resetSelect(districtEl, 'Không thể tải quận/huyện', true);
                    if (typeof showCustomToast === 'function') {
                        showCustomToast('Không thể tải danh sách Quận/Huyện từ GHN.', 'error', 4000);
                    }
                }
            };

            const loadWards = async (districtId, selectedValue = null, cascade = false) => {
                resetSelect(wardEl, '-- Chọn phường/xã --', true);
                ghnState.ward = '';
                if (!districtId) {
                    if (wardTS) wardTS.destroy();
                    return;
                }
                try {
                    const data = await fetchJson(`/api/v1/ghn/ward/${districtId}`, { method: 'POST' });
                    wardEl.innerHTML = '<option value="">-- Chọn phường/xã --</option>';
                    data.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.wardCode;
                        option.textContent = item.wardName;
                        wardEl.appendChild(option);
                    });
                    wardEl.disabled = false;
                    wardTS = recreateTomSelect(wardTS, wardEl, 'Chọn phường/xã');
                    wardTS.clearOptions();
                    wardTS.addOption({value: '', text: '-- Chọn phường/xã --'});
                    data.forEach(item => {
                        wardTS.addOption({value: String(item.wardCode), text: item.wardName});
                    });
                    wardTS.refreshOptions(false);
                    wardTS.clear(true);
                    wardTS.on('change', value => {
                        ghnState.ward = value;
                        invalidateVoucher('Đã thay đổi Phường/Xã, vui lòng áp lại voucher.');
                        maybeEnableServiceSelect();
                        scheduleFeeRecalc();
                    });
                    if (selectedValue) {
                        wardTS.setValue(String(selectedValue), true);
                        ghnState.ward = selectedValue;
                        maybeEnableServiceSelect();
                        scheduleFeeRecalc();
                    }
                    if (typeof showCustomToast === 'function') {
                        showCustomToast('Đã tải danh sách Phường/Xã từ GHN.', 'success', 3000);
                    }
                } catch (error) {
                    console.error(error);
                    resetSelect(wardEl, 'Không thể tải phường/xã', true);
                    if (typeof showCustomToast === 'function') {
                        showCustomToast('Không thể tải danh sách Phường/Xã từ GHN.', 'error', 4000);
                    }
                }
            };

            const resetServiceSelect = () => {
                serviceOptionsCache = [];
                syncServiceSelection('');
                if (serviceTS) {
                    serviceTS.clear(true);
                    serviceTS.clearOptions();
                    serviceTS.addOption({ value: '', text: '-- Chọn dịch vụ --' });
                    serviceTS.setValue('', true);
                    serviceTS.disable();
                } else {
                    const serviceSelect = document.getElementById('ghn-service-select');
                    if (serviceSelect) {
                        serviceSelect.innerHTML = '<option value="">-- Chọn dịch vụ --</option>';
                        serviceSelect.disabled = true;
                    }
                }
            };

            const maybeEnableServiceSelect = (preferredValue = null) => {
                if (!serviceTS) return;
                if (!ghnState.ward || !serviceOptionsCache.length) {
                    serviceTS.clear(true);
                    serviceTS.disable();
                    syncServiceSelection('');
                    return;
                }

                serviceTS.enable();
                let target = preferredValue || ghnState.serviceId || '';
                if (!serviceOptionsCache.find(opt => opt.value === String(target))) {
                    target = (serviceOptionsCache.find(opt => String(opt.serviceTypeId) === '2')?.value)
                        || serviceOptionsCache[0]?.value
                        || '';
                }

                if (target) {
                    serviceTS.setValue(String(target), true);
                    syncServiceSelection(String(target));
                    scheduleFeeRecalc();
                }
            };

            const loadServices = async (districtId, preferredService = null) => {
                resetServiceSelect();
                if (!districtId) {
                    return;
                }
                const ensureServiceTS = () => {
                    if (!serviceTS) {
                        const serviceSelectEl = document.getElementById('ghn-service-select');
                        if (serviceSelectEl) {
                            serviceTS = new TomSelect(serviceSelectEl, {
                                placeholder: 'Chọn dịch vụ GHN',
                                allowEmptyOption: true,
                                searchField: ['text']
                            });
                            serviceTS.disable();
                            serviceTS.on('change', value => {
                                syncServiceSelection(value);
                                if (value) {
                                    scheduleFeeRecalc();
                                } else {
                                    syncServiceSelection('');
                                }
                            });
                        }
                    }
                };
                ensureServiceTS();
                try {
                    const data = await fetchJson(`/api/v1/ghn/services/${districtId}`);
                    if (!data.length) {
                        serviceTS.clearOptions();
                        serviceTS.addOption({ value: '', text: 'Không có dịch vụ phù hợp' });
                        serviceTS.disable();
                        return;
                    }

                    serviceOptionsCache = data.map(item => ({
                        value: String(item.serviceId),
                        text: `${item.shortName || 'Dịch vụ'} (ID: ${item.serviceId})`,
                        serviceTypeId: item.serviceTypeId ?? ''
                    }));

                    if (serviceTS) {
                        serviceTS.clearOptions();
                        serviceTS.addOption({ value: '', text: '-- Chọn dịch vụ --' });
                        serviceOptionsCache.forEach(opt => {
                            serviceTS.addOption({ value: opt.value, text: opt.text });
                        });
                        serviceTS.refreshOptions(false);
                        serviceTS.disable();
                        serviceTS.clear(true);
                    }

                    maybeEnableServiceSelect(preferredService);
                    if (typeof showCustomToast === 'function') {
                        showCustomToast('Đã tải danh sách dịch vụ GHN.', 'success', 3000);
                    }
                } catch (error) {
                    console.error(error);
                    serviceTS.clearOptions();
                    serviceTS.addOption({ value: '', text: 'Không thể tải dịch vụ GHN' });
                    serviceTS.disable();
                    if (typeof showCustomToast === 'function') {
                        showCustomToast('Không thể tải dịch vụ GHN.', 'error', 4000);
                    }
                }
            };

            loadProvinces(oldProvince, true);
            if (oldDistrict) {
                loadServices(oldDistrict, ghnState.serviceId);
            }
        }

        function scheduleFeeRecalc() {
            if (feeRecalcTimer) clearTimeout(feeRecalcTimer);
            feeRecalcTimer = setTimeout(() => {
                calculateGhnFee();
            }, 600);
        }

        function calculateGhnFee() {
            if (!ghnState.district || !ghnState.ward || !ghnState.serviceId) return;

            const items = [];
            document.querySelectorAll('.item-row').forEach(row => {
                const name = row.querySelector('.product-select')?.tomselect?.getItem(row.querySelector('.product-select').value)?.text || 'Sản phẩm';
                const quantity = parseFloat(row.querySelector('.item-quantity').value) || 1;
                items.push({ name, quantity });
            });

            const body = {
                items,
                districtId: ghnState.district,
                wardId: ghnState.ward,
                serviceId: ghnState.serviceId,
                serviceTypeId: ghnState.serviceTypeId,
                total: totalsState.finalPrice,
            };

            const headers = {
                'Content-Type': 'application/json',
            };
            const csrf = document.querySelector('meta[name="csrf-token"]');
            if (csrf) {
                headers['X-CSRF-TOKEN'] = csrf.getAttribute('content');
            }

            fetch('/api/v1/ghn/calculate-fee', {
                method: 'POST',
                headers,
                body: JSON.stringify(body),
            })
            .then(res => res.json())
            .then(data => {
                if (data.code === 200 && data.data && typeof data.data.total !== 'undefined') {
                    const shippingFeeInput = document.querySelector('.shipping-fee');
                    suppressFeeRecalc = true;
                    shippingFeeInput.value = data.data.total || 0;
                    totalsState.shippingFee = data.data.total || 0;
                    updateTotals();
                    const notice = document.getElementById('ghn-fee-notice') || document.createElement('div');
                    notice.id = 'ghn-fee-notice';
                    notice.style.color = '#16a34a';
                    notice.style.fontSize = '13px';
                    notice.style.marginTop = '6px';
                    notice.textContent = 'Đã cập nhật phí vận chuyển GHN: ' + new Intl.NumberFormat('vi-VN').format(data.data.total || 0) + ' đ';
                    shippingFeeInput.parentElement.appendChild(notice);
                    if (typeof showCustomToast === 'function') {
                        showCustomToast('Đã tính phí GHN thành công.', 'success', 3000);
                    }
                } else {
                    console.warn('Tính phí GHN thất bại', data);
                    if (typeof showCustomToast === 'function') {
                        showCustomToast('Tính phí GHN thất bại. Vui lòng kiểm tra lại thông tin địa chỉ.', 'warning', 4000);
                    }
                }
            })
            .catch(err => {
                console.error(err);
                if (typeof showCustomToast === 'function') {
                    showCustomToast('Lỗi kết nối khi tính phí GHN.', 'error', 4000);
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const accountSelectEl = document.getElementById('account-select');
            if (accountSelectEl) {
                new TomSelect(accountSelectEl, {
                    placeholder: 'Tìm theo tên, email hoặc số điện thoại',
                    allowEmptyOption: true,
                    maxOptions: 500,
                    searchField: ['text']
                });
            }

            document.querySelectorAll('.product-select').forEach(select => {
                if (!select || !select.options) {
                    return;
                }
                const idx = select.id ? select.id.split('-')[1] : select.closest('.item-row')?.dataset.index;
                const options = Array.from(select.options || []);
                options.forEach(registerProductOption);
                initTomSelectsForRow(idx || 0);
            });

            document.querySelectorAll('.variant-select').forEach(select => {
                if (!select || !select.options) {
                    return;
                }
                const idx = select.id.split('-')[1] || 0;
                variantPriceMap[idx] = variantPriceMap[idx] || {};
                const options = Array.from(select.options || []);
                options.forEach(opt => {
                    if (opt.value) {
                        variantPriceMap[idx][opt.value] = parseFloat(opt.dataset.price || 0) || 0;
                    }
                });
                select.addEventListener('change', function() {
                    handleVariantChange(idx, this.value);
                });
            });

            const serviceSelectEl = document.getElementById('ghn-service-select');
            if (serviceSelectEl) {
                serviceTS = new TomSelect(serviceSelectEl, {
                    placeholder: 'Chọn dịch vụ GHN',
                    allowEmptyOption: true,
                    searchField: ['text']
                });
                serviceTS.disable();
                serviceTS.on('change', value => {
                    syncServiceSelection(value);
                    if (value) {
                        scheduleFeeRecalc();
                    } else {
                        syncServiceSelection('');
                    }
                });
            }

            // Update totals on input
            document.querySelectorAll('.shipping-fee, .tax, .discount, .voucher-discount').forEach(input => {
                input.addEventListener('input', updateTotals);
            });
            const shippingFeeInput = document.querySelector('.shipping-fee');
            if (shippingFeeInput) {
                shippingFeeInput.addEventListener('change', () => {
                    invalidateVoucher('Đã thay đổi phí vận chuyển, vui lòng áp lại voucher.');
                });
            }

            updateTotals();

            initGhnAddressSelectors({
                provinceSelectId: 'shipping-province-select',
                districtSelectId: 'shipping-district-select',
                wardSelectId: 'shipping-ward-select',
                oldProvince: '{{ old('shipping_province_id', $order->shipping_province_id ?? '') }}',
                oldDistrict: '{{ old('shipping_district_id', $order->shipping_district_id ?? '') }}',
                oldWard: '{{ old('shipping_ward_id', $order->shipping_ward_id ?? '') }}',
                oldService: '{{ old('ghn_service_id', $order->ghn_service_id ?? '') }}',
            });
        });
    </script>
    @endpush
@endsection

