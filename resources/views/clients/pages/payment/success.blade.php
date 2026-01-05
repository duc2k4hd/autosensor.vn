@php
    use Illuminate\Support\Str;

    $shipping = $order->shippingAddress;
    $items = $order->items ?? collect();
    $maskedPhone = $shipping?->phone_number 
        ? Str::mask($shipping->phone_number, '*', 4, 3) 
        : ($order->receiver_phone ? Str::mask($order->receiver_phone, '*', 4, 3) : null);
    $maskedEmail = $order->receiver_email 
        ? Str::mask($order->receiver_email, '*', 3, 5) 
        : ($order->account?->email ? Str::mask($order->account->email, '*', 3, 5) : null);
@endphp

@extends('clients.layouts.master')

@section('title', 'Thanh toán thành công - ' . ($settings->site_name ?? 'AutoSensor Việt Nam'))

@push('js_page')
    <script src="{{ asset('clients/assets/js/main.js') }}"></script>
@endpush

@section('head')
    <link rel="stylesheet" href="{{ asset('clients/assets/css/main.css') }}">
    <style>
        .payment-success-page {
            background: #f4f7f5;
            padding: 40px 0 80px;
        }
        .payment-success-container {
            width: min(1100px, 94vw);
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 26px;
        }
        .payment-hero {
            background: radial-gradient(circle at top right, rgba(31, 227, 168, 0.25), transparent 60%),
                linear-gradient(135deg, #0b2c1e, #0f5132);
            border-radius: 32px;
            color: #f6fff9;
            padding: 36px;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 30px 70px rgba(11, 44, 30, 0.35);
        }
        .payment-hero h1 {
            margin: 0 0 10px;
            font-size: clamp(32px, 3vw, 42px);
        }
        .payment-hero p {
            margin: 0;
            font-size: 15px;
            color: rgba(255, 255, 255, 0.8);
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: rgba(31, 227, 168, 0.2);
            border: 1px solid rgba(31, 227, 168, 0.4);
            color: #9ff4d3;
            border-radius: 999px;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.08em;
        }
        .payment-card {
            background: #fff;
            border-radius: 24px;
            padding: 28px 30px;
            box-shadow: 0 20px 60px rgba(15, 81, 50, 0.08);
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 18px;
        }
        .summary-chip {
            padding: 16px;
            border-radius: 18px;
            background: #f2fff8;
            border: 1px solid rgba(15, 81, 50, 0.1);
        }
        .summary-chip span {
            display: block;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #6b7280;
            margin-bottom: 6px;
        }
        .summary-chip strong {
            font-size: 20px;
            color: #0f5132;
        }
        .shipping-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 18px;
            margin-top: 18px;
        }
        .shipping-info-card {
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            padding: 18px;
            background: #fbfcfb;
        }
        .shipping-info-card h4 {
            margin: 0 0 8px;
            font-size: 16px;
            color: #0f5132;
        }
        .shipping-info-card p {
            margin: 0;
            color: #4b5563;
            font-size: 14px;
            line-height: 1.6;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .items-table th,
        .items-table td {
            padding: 14px 12px;
            text-align: left;
            border-bottom: 1px solid #f1f5f9;
        }
        .items-table th {
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 12px;
            color: #94a3b8;
        }
        .items-table td strong {
            color: #0f172a;
        }
        .totals {
            margin-top: 20px;
            display: grid;
            gap: 10px;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            color: #475569;
        }
        .totals-row.total {
            font-size: 20px;
            font-weight: 700;
            color: #0f5132;
            border-top: 1px solid #e2e8f0;
            padding-top: 12px;
        }
        .receipt-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 24px;
        }
        .receipt-actions button,
        .receipt-actions a {
            flex: 1;
            min-width: 180px;
            border-radius: 12px;
            border: none;
            padding: 14px 18px;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
        }
        .btn-primary {
            background: linear-gradient(135deg, #0f5132, #20c997);
            color: #fff;
        }
        .btn-outline {
            background: #fff;
            border: 1px solid #0f5132;
            color: #0f5132;
        }
        .timeline {
            margin-top: 10px;
            display: flex;
            gap: 18px;
            flex-wrap: wrap;
        }
        .timeline-step {
            flex: 1;
            min-width: 200px;
            padding: 12px 16px;
            border-radius: 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }
        .timeline-step.active {
            border-color: #0f5132;
            background: #f0fdf4;
        }
        .timeline-step h5 {
            margin: 0 0 4px;
            font-size: 14px;
            color: #0f5132;
        }
        .timeline-step p {
            margin: 0;
            font-size: 13px;
            color: #64748b;
        }
        @media print {
            .receipt-actions {
                display: none !important;
            }
            body {
                background: #fff;
            }
            .payment-success-page {
                padding: 0;
            }
            .payment-hero,
            .payment-card {
                box-shadow: none;
            }
        }
    </style>
@endsection

@section('content')
    <section class="payment-success-page">
        <div class="payment-success-container">
            <div class="payment-hero">
                <div>
                    <div class="status-badge">
                        <span>Thanh toán thành công</span>
                    </div>
                    <h1>Xin chúc mừng! Đơn hàng đã được xác nhận</h1>
                    <p>{{ $message }}</p>
                </div>
                <div class="summary-chip" style="background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.3); color:#fff;">
                    <span>MÃ ĐƠN HÀNG</span>
                    <strong style="color:#fff;">{{ $order->code }}</strong>
                    <small style="display:block;margin-top:4px;color:rgba(255,255,255,0.7);">
                        Đặt lúc {{ $order->created_at?->format('H:i d/m/Y') }}
                    </small>
                </div>
            </div>

            <div class="payment-card">
                <div class="summary-grid">
                    <div class="summary-chip">
                        <span>Tổng thanh toán</span>
                        <strong>{{ number_format($order->final_price, 0, ',', '.') }}₫</strong>
                    </div>
                    <div class="summary-chip">
                        <span>Phương thức thanh toán</span>
                        <strong>{{ $order->payment_method === 'bank_transfer' ? 'Chuyển khoản PayOS' : 'COD' }}</strong>
                    </div>
                    <div class="summary-chip">
                        <span>Trạng thái đơn hàng</span>
                        <strong>{{ ucfirst($order->status) }}</strong>
                    </div>
                    <div class="summary-chip">
                        <span>Trạng thái thanh toán</span>
                        <strong>{{ ucfirst($payment->status ?? 'pending') }}</strong>
                    </div>
                </div>

                <div class="timeline">
                    <div class="timeline-step active">
                        <h5>1. Đặt hàng thành công</h5>
                        <p>AutoSensor Việt Nam đã tiếp nhận yêu cầu.</p>
                    </div>
                    <div class="timeline-step {{ $order->status !== 'pending' ? 'active' : '' }}">
                        <h5>2. Chuẩn bị hàng</h5>
                        <p>Chuyên viên đang kiểm tra thiết bị, đóng gói cẩn thận.</p>
                    </div>
                    <div class="timeline-step {{ in_array($order->status, ['shipping','completed']) ? 'active' : '' }}">
                        <h5>3. Giao hàng</h5>
                        <p>Đơn vị GHN giao tận nơi theo lịch hẹn.</p>
                    </div>
                    <div class="timeline-step {{ $order->status === 'completed' ? 'active' : '' }}">
                        <h5>4. Hoàn tất</h5>
                        <p>Đơn hàng bàn giao thành công.</p>
                    </div>
                </div>
            </div>

            <div class="payment-card">
                <h3 style="margin-top:0;">Thông tin giao nhận</h3>
                <div class="shipping-info">
                    <div class="shipping-info-card">
                        <h4>Người nhận</h4>
                        <p>
                            <strong>{{ $shipping?->full_name ?? $order->receiver_name ?? 'Chưa cập nhật' }}</strong><br>
                            {{ $maskedPhone ?? ($order->receiver_phone ? Str::mask($order->receiver_phone, '*', 4, 3) : '***') }}<br>
                            {{ $maskedEmail ?? ($order->receiver_email ? Str::mask($order->receiver_email, '*', 3, 5) : '***') }}
                        </p>
                    </div>
                    <div class="shipping-info-card">
                        <h4>Địa chỉ giao hàng</h4>
                        <p>
                            @if($shipping)
                                {{ $shipping->detail_address }}<br>
                                {{ $shipping->ward }}, {{ $shipping->district }}, {{ $shipping->province }}<br>
                                {{ $shipping->country ?? 'Việt Nam' }}
                            @elseif($order->shipping_address)
                                {{ $order->shipping_address }}<br>
                                @php
                                    $addressParts = array_filter([
                                        $addressNames['ward'] ?? null,
                                        $addressNames['district'] ?? null,
                                        $addressNames['province'] ?? null,
                                    ]);
                                @endphp
                                @if(!empty($addressParts))
                                    {{ implode(', ', $addressParts) }}<br>
                                @endif
                                Việt Nam
                            @else
                                Chưa có địa chỉ giao hàng
                            @endif
                        </p>
                    </div>
                    <div class="shipping-info-card">
                        <h4>Ghi chú cho chuyên viên</h4>
                        <p>{{ $order->customer_note ?? $order->note ?? 'Không có ghi chú.' }}</p>
                    </div>
                </div>
            </div>

            <div class="payment-card">
                <h3 style="margin-top:0;">Chi tiết sản phẩm</h3>
                <div class="table-responsive">
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th>Đơn giá</th>
                                <th>Số lượng</th>
                                <th>Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                <tr>
                                    <td>
                                        <strong>{{ $item->product?->name ?? 'Sản phẩm đã xóa' }}</strong>
                                        @if($item->variant)
                                            <div style="color:#059669;font-weight:600;font-size:14px;margin-top:4px;">
                                                {{ $item->variant->name }}
                                            </div>
                                        @endif
                                        @if($item->options)
                                            <div style="color:#94a3b8;font-size:13px;">
                                                {{ collect((array) $item->options)->map(fn($value, $key) => ucfirst($key).': '.$value)->join(', ') }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ number_format($item->price, 0, ',', '.') }}₫</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>{{ number_format($item->total, 0, ',', '.') }}₫</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="totals">
                    <div class="totals-row">
                        <span>Tạm tính</span>
                        <strong>{{ number_format($order->total_price, 0, ',', '.') }}₫</strong>
                    </div>
                    <div class="totals-row">
                        <span>Giảm giá</span>
                        <strong>-{{ number_format(($order->discount ?? 0) + ($order->voucher_discount ?? 0), 0, ',', '.') }}₫</strong>
                    </div>
                    <div class="totals-row">
                        <span>Phí vận chuyển</span>
                        <strong>{{ number_format($order->shipping_fee, 0, ',', '.') }}₫</strong>
                    </div>
                    <div class="totals-row total">
                        <span>Tổng thanh toán</span>
                        <strong>{{ number_format($order->final_price, 0, ',', '.') }}₫</strong>
                    </div>
                </div>

                <div class="receipt-actions">
                    <button type="button" class="btn-primary" id="printReceiptBtn">
                        In biên nhận / Tải PDF
                    </button>
                    <a href="{{ route('client.orders.show', $order->code) }}" class="btn-outline">
                        Xem chi tiết đơn hàng
                    </a>
                    <a href="{{ route('client.home.index') }}" class="btn-outline" style="border-color:#0ea5e9;color:#0ea5e9;">
                        Tiếp tục mua sắm
                    </a>
                </div>
                <p style="margin-top:20px;font-size:13px;color:#94a3b8;">
                    * Khi chọn “In biên nhận”, bạn có thể lưu file PDF trực tiếp bằng cách chọn “Save as PDF” trong hộp thoại In của trình duyệt.
                </p>
            </div>

            <div class="payment-card" style="background:#fdfaf4;border:1px solid #fcd34d;">
                <h4 style="margin-top:0;color:#92400e;">Bạn cần hỗ trợ thêm?</h4>
                <p style="margin:6px 0;color:#92400e;">Hotline: <strong>{{ $settings->contact_phone ?? '1900 988 889' }}</strong></p>
                <p style="margin:6px 0;color:#92400e;">Email: <strong>{{ $settings->contact_email ?? 'info@autosensor.vn' }}</strong></p>
                <p style="margin:6px 0;color:#92400e;">Showroom: {{ $settings->contact_address ?? 'Số 88 Nguyễn Cơ Thạch, Nam Từ Liêm, Hà Nội' }}</p>
            </div>
        </div>
    </section>

    <script>
        document.getElementById('printReceiptBtn')?.addEventListener('click', () => {
            window.print();
        });
    </script>
@endsection