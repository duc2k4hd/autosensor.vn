@extends('clients.layouts.master')

@php
    $formatCurrency = fn ($value) => number_format((float) ($value ?? 0), 0, ',', '.') . '₫';
    $formatAddress = function ($address) {
        if (! $address) {
            return null;
        }

        return implode(', ', array_filter([
            $address->detail_address,
            $address->ward,
            $address->district,
            $address->province,
        ]));
    };

    $statusLabels = [
        'pending' => 'Chờ xác nhận',
        'confirmed' => 'Đang xử lý',
        'shipping' => 'Đang vận chuyển',
        'completed' => 'Hoàn tất',
        'cancelled' => 'Đã hủy',
    ];
@endphp

@section('title', 'Chi tiết đơn hàng ' . $order->code . ' - ' . ($settings->site_name ?? 'AutoSensor Việt Nam'))

@section('head')
    <meta name="robots" content="noindex,follow">
    <style>
        .order-detail-page {
            padding: 30px 0 60px;
            background: #f5f5f5;
        }

        .order-container {
            width: min(1100px, 94vw);
            margin: 0 auto;
        }

        .order-breadcrumb {
            margin-bottom: 16px;
            font-size: 14px;
            color: #6b7280;
        }

        .order-breadcrumb a {
            color: #198754;
            text-decoration: none;
        }

        .order-breadcrumb .separator {
            margin: 0 10px;
            color: #9ca3af;
        }

        .order-card {
            background: #fff;
            border-radius: 24px;
            padding: 28px 32px;
            box-shadow: 0 25px 60px rgba(15, 81, 50, 0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .order-card h1 {
            font-size: 28px;
            margin: 0 0 8px;
            color: #0f5132;
        }

        .order-meta {
            color: #6b7280;
            font-size: 15px;
        }

        .status-badge {
            padding: 10px 18px;
            border-radius: 999px;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-badge.pending {
            background: rgba(249, 115, 22, 0.15);
            color: #b45309;
        }

        .status-badge.confirmed {
            background: rgba(59, 130, 246, 0.15);
            color: #1d4ed8;
        }

        .status-badge.shipping {
            background: rgba(14, 165, 233, 0.15);
            color: #0369a1;
        }

        .status-badge.completed {
            background: rgba(34, 197, 94, 0.15);
            color: #15803d;
        }

        .status-badge.cancelled {
            background: rgba(248, 113, 113, 0.15);
            color: #b91c1c;
        }

        .order-timeline {
            background: #fff;
            border-radius: 24px;
            padding: 28px;
            box-shadow: 0 20px 45px rgba(15, 81, 50, 0.05);
            margin-bottom: 28px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 22px;
        }

        .timeline-step {
            position: relative;
            padding-left: 26px;
        }

        .timeline-step::before {
            content: '';
            position: absolute;
            left: 0;
            top: 12px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 2px solid #d1d5db;
            background: #fff;
        }

        .timeline-step.active::before {
            border-color: #0f5132;
            background: #0f5132;
            box-shadow: 0 0 0 4px rgba(15, 81, 50, 0.15);
        }

        .timeline-step h4 {
            margin: 0;
            font-size: 16px;
            color: #111827;
        }

        .timeline-step p {
            margin: 6px 0 0;
            font-size: 14px;
            color: #6b7280;
            line-height: 1.5;
        }

        .order-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
            margin-bottom: 28px;
        }

        .info-card {
            background: #fff;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 18px 40px rgba(15, 81, 50, 0.05);
        }

        .info-card h3 {
            font-size: 18px;
            margin: 0 0 12px;
            color: #0f5132;
        }

        .info-card p,
        .info-card span {
            margin: 4px 0;
            color: #374151;
            line-height: 1.6;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-top: 6px;
            color: #4b5563;
        }

        .summary-row strong {
            color: #0f172a;
        }

        .summary-row.total {
            font-size: 18px;
            font-weight: 700;
            color: #0f5132;
            margin-top: 18px;
        }

        .order-items {
            background: #fff;
            border-radius: 24px;
            padding: 28px;
            box-shadow: 0 20px 45px rgba(15, 81, 50, 0.05);
            margin-bottom: 28px;
        }

        .order-items table {
            width: 100%;
            border-collapse: collapse;
        }

        .order-items th {
            text-align: left;
            font-size: 14px;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #9ca3af;
            padding-bottom: 12px;
        }

        .order-items td {
            padding: 14px 0;
            border-top: 1px solid #eef2f7;
            vertical-align: middle;
        }

        .item-product {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .item-product img {
            width: 70px;
            height: 70px;
            border-radius: 16px;
            object-fit: cover;
            background: #f3f4f6;
        }

        .item-product h4 {
            margin: 0;
            font-size: 16px;
            color: #111827;
        }

        .item-product span {
            font-size: 14px;
            color: #6b7280;
        }

        .payment-history {
            background: #fff;
            border-radius: 24px;
            padding: 24px;
            box-shadow: 0 20px 45px rgba(15, 81, 50, 0.05);
        }

        .payment-row {
            display: flex;
            justify-content: space-between;
            padding: 14px 0;
            border-top: 1px solid #eef2f7;
        }

        .payment-row:first-child {
            border-top: none;
        }

        .payment-row strong {
            color: #0f172a;
        }

        @media (max-width: 640px) {
            .order-card {
                padding: 20px;
            }

            .order-card h1 {
                font-size: 22px;
            }

            .order-items table,
            .order-items thead,
            .order-items tbody,
            .order-items tr,
            .order-items td,
            .order-items th {
                display: block;
            }

            .order-items th {
                display: none;
            }

            .order-items td {
                border: none;
                padding: 12px 0;
            }

            .summary-row {
                flex-direction: column;
                gap: 4px;
            }
        }
    </style>
@endsection

@push('js_page')
    <script defer src="{{ asset('clients/assets/js/main.js') }}"></script>
@endpush

@section('content')
    <section class="order-detail-page">
        <div class="order-container">
            <div class="order-breadcrumb">
                <a href="{{ route('client.home.index') }}">Trang chủ</a>
                <span class="separator">>></span>
                <a href="{{ route('client.profile.index') }}">Tài khoản</a>
                <span class="separator">>></span>
                <span>Đơn hàng {{ $order->code }}</span>
            </div>

            <div class="order-card">
                <div>
                    <h1>Đơn hàng #{{ $order->code }}</h1>
                    <div class="order-meta">
                        Tạo lúc {{ optional($order->created_at)->format('d/m/Y H:i') ?? '—' }} ·
                        {{ $order->items->count() }} sản phẩm
                    </div>
                </div>
                <span class="status-badge {{ $normalizedStatus }}">
                    {{ $statusLabels[$order->status] ?? ucfirst($order->status) }}
                </span>
            </div>

            <div class="order-timeline">
                @foreach ($statusFlow as $index => $step)
                    <div class="timeline-step {{ $index <= $currentStatusIndex ? 'active' : '' }}">
                        <h4>{{ $step['label'] }}</h4>
                        <p>{{ $step['description'] }}</p>
                    </div>
                @endforeach

                @if ($order->status === 'cancelled')
                    <div class="timeline-step active">
                        <h4>Đơn hàng đã hủy</h4>
                        <p>Đơn hàng bị hủy bởi khách hàng hoặc hệ thống.</p>
                    </div>
                @endif
            </div>

            <div class="order-info-grid">
                <div class="info-card">
                    <h3>Thông tin giao hàng</h3>
                    <p><strong>{{ $order->shippingAddress->full_name ?? $order->receiver_name ?? $order->account?->name ?? 'Không xác định' }}</strong></p>
                    <p>Số điện thoại: {{ $order->shippingAddress->phone_number ?? $order->receiver_phone ?? $order->account?->phone ?? '—' }}</p>
                    <p>
                        @if($order->shippingAddress)
                            {{ $formatAddress($order->shippingAddress) }}
                        @elseif($order->shipping_address)
                            {{ $order->shipping_address }}
                            @php
                                $addressParts = array_filter([
                                    $addressNames['ward'] ?? null,
                                    $addressNames['district'] ?? null,
                                    $addressNames['province'] ?? null,
                                ]);
                            @endphp
                            @if(!empty($addressParts))
                                <br>{{ implode(', ', $addressParts) }}
                            @endif
                        @else
                            Chưa có địa chỉ giao hàng
                        @endif
                    </p>
                    @if ($order->receiver_email)
                        <p>Email: {{ $order->receiver_email }}</p>
                    @endif
                    @if ($order->customer_note || $order->note)
                        <p>Ghi chú: {{ $order->customer_note ?? $order->note }}</p>
                    @endif
                </div>

                <div class="info-card">
                    <h3>Thông tin thanh toán</h3>
                    <p>Phương thức: {{ $order->payment_method ? strtoupper($order->payment_method) : 'Không xác định' }}</p>
                    <p>Trạng thái: 
                        <span class="status-badge {{ $order->payment_status === 'paid' ? 'confirmed' : 'pending' }}">
                            {{ $order->payment_status === 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán' }}
                        </span>
                    </p>
                    @if($checkoutUrl && $order->payment_method === 'bank_transfer' && $order->payment_status === 'pending')
                        <div style="margin-top: 16px; padding: 16px; background: #e7f3ff; border-radius: 12px; border-left: 4px solid #007bff;">
                            <p style="margin: 0 0 12px; font-weight: 600; color: #0056b3;">💳 Thanh toán đơn hàng</p>
                            <p style="margin: 0 0 16px; color: #374151; font-size: 14px;">Vui lòng hoàn tất thanh toán để đơn hàng được xử lý nhanh chóng.</p>
                            <a href="{{ $checkoutUrl }}" target="_blank" style="display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600; transition: transform 0.2s;">
                                Thanh toán ngay →
                            </a>
                        </div>
                    @endif
                    <p>Vận chuyển: {{ $order->shipping_partner ? strtoupper($order->shipping_partner) : 'Chưa chọn' }}</p>
                    <div class="summary-row">
                        <span>Tạm tính</span>
                        <strong>{{ $formatCurrency($order->total_price) }}</strong>
                    </div>
                    <div class="summary-row">
                        <span>Giảm giá</span>
                        <strong>-{{ $formatCurrency(($order->discount ?? 0) + ($order->voucher_discount ?? 0)) }}</strong>
                    </div>
                    <div class="summary-row">
                        <span>Phí vận chuyển</span>
                        <strong>{{ $formatCurrency($order->shipping_fee) }}</strong>
                    </div>
                    <div class="summary-row total">
                        <span>Tổng cộng</span>
                        <span>{{ $formatCurrency($order->final_price) }}</span>
                    </div>
                </div>

                <div class="info-card">
                    <h3>Địa chỉ thanh toán</h3>
                    @if ($order->billingAddress)
                        <p><strong>{{ $order->billingAddress->full_name }}</strong></p>
                        <p>Số điện thoại: {{ $order->billingAddress->phone_number ?? '—' }}</p>
                        <p>{{ $formatAddress($order->billingAddress) }}</p>
                    @else
                        <p>Hệ thống sử dụng chung địa chỉ giao hàng.</p>
                    @endif
                </div>
            </div>

            <div class="order-items">
                <table>
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th>Đơn giá</th>
                            <th>Số lượng</th>
                            <th>Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($order->items as $item)
                            <tr>
                                <td>
                                    <div class="item-product">
                                        @php
                                            $imageUrl = $item->variant?->primaryVariantImage
                                                ? asset('clients/assets/img/clothes/' . $item->variant->primaryVariantImage->url)
                                                : ($item->product?->primaryImage
                                                    ? asset('clients/assets/img/clothes/' . $item->product->primaryImage->url)
                                                    : asset('clients/assets/img/clothes/no-image.webp'));
                                        @endphp
                                        <img
                                            src="{{ $imageUrl }}"
                                            alt="{{ $item->product?->name ?? 'Sản phẩm' }}">
                                        <div>
                                            <h4>{{ $item->product?->name ?? 'Sản phẩm đã xóa' }}</h4>
                                            @if($item->variant)
                                                <span class="spec-attr variant-name" style="display:block;font-weight: 600; color: #059669; margin: 4px 0;">{{ $item->variant->name }}</span>
                                            @endif
                                            <span>Mã SKU: {{ $item->product?->sku ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $formatCurrency($item->price) }}</td>
                                <td>x{{ $item->quantity }}</td>
                                <td><strong>{{ $formatCurrency($item->price * $item->quantity) }}</strong></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">Đơn hàng chưa có sản phẩm.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($order->payments->isNotEmpty())
                <div class="payment-history">
                    <h3>Lịch sử thanh toán</h3>
                    @foreach ($order->payments as $payment)
                        <div class="payment-row">
                            <div>
                                <strong>{{ strtoupper($payment->method) }}</strong>
                                <p style="margin: 4px 0 0; color: #6b7280;">
                                    {{ optional($payment->created_at)->format('d/m/Y H:i') ?? '—' }}
                                    · Mã GD: {{ $payment->transaction_code ?? $payment->transaction_id ?? 'Chưa cập nhật' }}
                                </p>
                            </div>
                            <div>
                                <strong>{{ $formatCurrency($payment->amount) }}</strong>
                                <p style="margin: 4px 0 0; color: #6b7280; text-align: right;">
                                    Trạng thái: {{ ucfirst($payment->status ?? 'pending') }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection

