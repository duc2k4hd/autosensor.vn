@extends('clients.layouts.master')

@section('title', '503 - Bảo Trì Hệ Thống | ' . ($settings->site_name ?? 'AutoSensor Việt Nam'))

@section('head')
<meta name="robots" content="noindex, nofollow" />
<style>
    :root {
        --primary-color: #34B430;
        --secondary-color: #0F8F53;
        --accent-color: #4DA852;
        --background-color: #FFFFFF;
        --text-color: #333333;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        color: var(--text-color);
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        padding: 20px;
    }

    .maintenance-container {
        text-align: center;
        background: white;
        padding: 60px 40px;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        max-width: 600px;
        width: 100%;
    }

    .maintenance-icon {
        font-size: 80px;
        margin-bottom: 30px;
        animation: pulse 2s ease-in-out infinite;
    }

    @keyframes pulse {
        0%, 100% {
            transform: scale(1);
            opacity: 1;
        }
        50% {
            transform: scale(1.1);
            opacity: 0.8;
        }
    }

    .maintenance-title {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--primary-color);
        margin-bottom: 20px;
        font-family: 'Montserrat', sans-serif;
    }

    .maintenance-message {
        font-size: 1.1rem;
        line-height: 1.8;
        color: var(--text-color);
        margin-bottom: 30px;
    }

    .maintenance-info {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 30px;
        text-align: left;
    }

    .maintenance-info h3 {
        font-size: 1.2rem;
        color: var(--secondary-color);
        margin-bottom: 10px;
    }

    .maintenance-info p {
        margin-bottom: 5px;
        color: #666;
    }

    .btn {
        display: inline-block;
        padding: 12px 30px;
        background: var(--primary-color);
        color: white;
        text-decoration: none;
        border-radius: 50px;
        font-weight: 600;
        transition: all 0.3s ease;
        margin: 5px;
    }

    .btn:hover {
        background: var(--secondary-color);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(52, 180, 48, 0.3);
    }

    .countdown {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--primary-color);
        margin-top: 20px;
    }

    @media (max-width: 768px) {
        .maintenance-title {
            font-size: 2rem;
        }
        .maintenance-icon {
            font-size: 60px;
        }
    }
</style>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
@endsection

@push('js_page')
    <script defer src="{{ asset('clients/assets/js/main.js') }}"></script>
@endpush

@section('content')
<div class="maintenance-container">
    <div class="maintenance-icon">🔧</div>
    <h1 class="maintenance-title">Đang Bảo Trì Hệ Thống</h1>
    <p class="maintenance-message">
        Chúng tôi đang thực hiện bảo trì hệ thống để cải thiện trải nghiệm của bạn.
        Vui lòng quay lại sau vài phút.
    </p>
    
    <div class="maintenance-info">
        <h3>📋 Thông Tin Bảo Trì</h3>
        <p><strong>Thời gian:</strong> {{ now()->format('d/m/Y H:i') }}</p>
        <p><strong>Lý do:</strong> Nâng cấp hệ thống</p>
        <p><strong>Dự kiến:</strong> Hoàn thành trong vòng 30 phút</p>
    </div>

    <div>
        <a href="{{ route('client.home.index') }}" class="btn">Trang Chủ</a>
        <a href="javascript:location.reload()" class="btn">Tải Lại Trang</a>
    </div>

    <div class="countdown" id="countdown">
        Vui lòng đợi...
    </div>
</div>

<script>
    // Simple countdown (optional)
    let seconds = 300; // 5 minutes
    const countdownEl = document.getElementById('countdown');
    
    const updateCountdown = () => {
        const minutes = Math.floor(seconds / 60);
        const secs = seconds % 60;
        countdownEl.textContent = `Quay lại sau: ${minutes}:${secs.toString().padStart(2, '0')}`;
        
        if (seconds > 0) {
            seconds--;
            setTimeout(updateCountdown, 1000);
        } else {
            countdownEl.textContent = 'Đang kiểm tra lại...';
            setTimeout(() => location.reload(), 2000);
        }
    };
    
    updateCountdown();
</script>
@endsection

