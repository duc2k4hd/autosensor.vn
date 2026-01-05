@extends('clients.layouts.master')

@section('title', 'Thanh toán không thành công')

@push('js_page')
    <script src="{{ asset('clients/assets/js/main.js') }}"></script>
@endpush

@section('head')
    <link rel="stylesheet" href="{{ asset('clients/assets/css/main.css') }}">
    <style>
        .payment-cancel-page {
            background: #fef2f2;
            padding: 40px 0 80px;
            min-height: 60vh;
        }
        .payment-cancel-container {
            width: min(800px, 94vw);
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 26px;
        }
        .payment-cancel-hero {
            background: radial-gradient(circle at top right, rgba(239, 68, 68, 0.2), transparent 60%),
                linear-gradient(135deg, #7f1d1d, #991b1b);
            border-radius: 32px;
            color: #fff;
            padding: 48px 36px;
            text-align: center;
            box-shadow: 0 30px 70px rgba(127, 29, 29, 0.25);
            position: relative;
            overflow: hidden;
        }
        .payment-cancel-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(239, 68, 68, 0.15), transparent 70%);
            border-radius: 50%;
        }
        .payment-cancel-hero h1 {
            margin: 0 0 16px;
            font-size: clamp(32px, 4vw, 48px);
            font-weight: 700;
            position: relative;
            z-index: 1;
        }
        .payment-cancel-hero .icon-wrapper {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        .payment-cancel-hero p {
            margin: 0;
            font-size: 16px;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.6;
            position: relative;
            z-index: 1;
        }
        .order-code-badge {
            display: inline-block;
            margin: 16px 0;
            padding: 8px 20px;
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 999px;
            font-size: 18px;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }
        .payment-cancel-card {
            background: #fff;
            border-radius: 24px;
            padding: 32px;
            box-shadow: 0 20px 60px rgba(127, 29, 29, 0.08);
            text-align: center;
        }
        .payment-cancel-card h3 {
            margin: 0 0 16px;
            font-size: 20px;
            color: #1f2937;
            font-weight: 600;
        }
        .payment-cancel-card p {
            margin: 0 0 24px;
            font-size: 15px;
            color: #6b7280;
            line-height: 1.6;
        }
        .action-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 24px;
        }
        .action-btn {
            padding: 14px 28px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        .action-btn-primary {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: #fff;
            box-shadow: 0 4px 14px rgba(239, 68, 68, 0.3);
        }
        .action-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
        }
        .action-btn-secondary {
            background: #fff;
            color: #374151;
            border: 2px solid #e5e7eb;
        }
        .action-btn-secondary:hover {
            background: #f9fafb;
            border-color: #d1d5db;
            transform: translateY(-2px);
        }
        .info-box {
            background: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 16px 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
        }
        .info-box p {
            margin: 0;
            font-size: 14px;
            color: #7f1d1d;
            line-height: 1.6;
        }
        @media (max-width: 640px) {
            .payment-cancel-hero {
                padding: 36px 24px;
            }
            .payment-cancel-card {
                padding: 24px 20px;
            }
            .action-buttons {
                flex-direction: column;
            }
            .action-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
@endsection

@section('content')
<div class="payment-cancel-page">
    <div class="payment-cancel-container">
        <div class="payment-cancel-hero">
            <div class="icon-wrapper">❌</div>
            <h1>Thanh toán không thành công</h1>
            <p>Đơn hàng của bạn đã được hủy do thanh toán không hoàn tất</p>
            @if(isset($orderCode) && $orderCode !== 'N/A')
                <div class="order-code-badge">
                    Mã đơn: {{ $orderCode }}
                </div>
            @endif
        </div>

        <div class="payment-cancel-card">
            <h3>Điều gì đã xảy ra?</h3>
            <p>{{ $message ?? 'Bạn đã hủy thanh toán hoặc có lỗi xảy ra trong quá trình thanh toán. Đơn hàng đã được tự động hủy để tránh rác dữ liệu.' }}</p>
            
            @if(isset($order))
                <div class="info-box">
                    <p><strong>📦 Thông tin đơn hàng:</strong></p>
                    <p>Mã đơn: <strong>{{ $order->code }}</strong></p>
                    <p>Tổng tiền: <strong>{{ number_format($order->final_price ?? 0) }} đ</strong></p>
                    <p>Trạng thái: <strong>Đã hủy</strong></p>
                </div>
            @endif

            <div class="action-buttons">
                <a href="{{ route('client.checkout.index') }}" class="action-btn action-btn-primary">
                    🔄 Thử lại thanh toán
                </a>
                <a href="{{ route('client.home.index') }}" class="action-btn action-btn-secondary">
                    🏠 Về trang chủ
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
