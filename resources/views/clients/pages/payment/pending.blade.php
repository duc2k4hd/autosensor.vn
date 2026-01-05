@extends('clients.layouts.master')



@section('title', 'Thanh toán đơn hàng đang chờ | ' . $settings->site_name ?? ($settings->subname ?? 'AutoSensor Việt Nam'))



@section('head')
    <link rel="stylesheet" href="{{ asset('clients/assets/css/main.css') }}">
    <meta name="robots" content="follow, noindex"/>
@endsection

@push('js_page')
    <script src="{{ asset('clients/assets/js/main.js') }}"></script>
@endpush


@section('content')

<div class="xanhworld_order_wrapper" style="max-width:800px;margin:20px auto;padding:16px;background:#fff;border-radius:8px;">

    <h1 style="font-size:20px;margin-bottom:12px;">Đơn hĂ ng Ä'ang chờ thanh toĂ¡n</h1>

    <div style="padding:12px;border:1px solid #eee;border-radius:8px;">

        <p><strong>MĂ£ đơn:</strong> {{ $order->id }}</p>

        <p><strong>Tổng tiền:</strong> {{ number_format($order->final_price, 0, ',', '.') }}đ</p>

        <p><strong>Phương thức:</strong> {{ strtoupper($order->payment_method) }}</p>

        <p><strong>Trạng thĂ¡i thanh toĂ¡n:</strong> {{ $order->payment_status }}</p>

    </div>



    <div style="display:flex;gap:10px;margin-top:16px;flex-wrap:wrap;">

        @if($checkoutUrl)

            <!-- NĂºt chuyển đến link thanh toĂ¡n Ä'Ă£ tạo -->

            <a href="{{ $checkoutUrl }}" 

               target="_blank"

               style="padding:12px 20px;background:#10b981;color:#fff;border:none;border-radius:6px;cursor:pointer;text-decoration:none;display:inline-block;font-weight:500;">

               đŸ'³ Thanh toĂ¡n ngay

            </a>

        @endif

        

        <!-- Thay thế form bằng link trực tiếp -->

        <a href="{{ route('payment.pending.retry.get') }}" 

           onclick="return confirm('Bạn cĂ³ muốn tạo link thanh toĂ¡n má»›i?')"

           style="padding:10px 16px;background:#2563eb;color:#fff;border:none;border-radius:6px;cursor:pointer;text-decoration:none;display:inline-block;">

           đŸ"" Tạo link thanh toĂ¡n má»›i

        </a>

        

        <!-- Form backup (ẩn) -->

        <form action="{{ route('payment.pending.retry') }}" method="POST" id="retry-form" style="display:none;">

            @csrf

            <button type="submit" id="retry-btn">Thanh toĂ¡n lại</button>

        </form>

        <form action="{{ route('payment.pending.cancel') }}" method="POST" onsubmit="return confirm('Bạn cĂ³ chắc muốn há»§y đơn nĂ y?');">

            @csrf

            <button type="submit" style="padding:10px 16px;background:#ef4444;color:#fff;border:none;border-radius:6px;cursor:pointer;">❌ Há»§y đơn hĂ ng</button>

        </form>

        <a href="{{ route('client.cart.index') }}" style="padding:10px 16px;border:1px solid #ddd;border-radius:6px;text-decoration:none;color:#333;">đŸ›' Về giỏ hĂ ng</a>

    </div>

    

    <!-- Debug info -->

    <div id="debug-info" style="margin-top:20px;padding:10px;background:#f5f5f5;border-radius:6px;display:none;">

        <p><strong>Debug Info:</strong></p>

        <p id="debug-text"></p>

    </div>

</div>



<script>

document.addEventListener('DOMContentLoaded', function() {

    const retryForm = document.getElementById('retry-form');

    const retryBtn = document.getElementById('retry-btn');

    const debugInfo = document.getElementById('debug-info');

    const debugText = document.getElementById('debug-text');

    

    retryForm.addEventListener('submit', function(e) {

        console.log('Form submitted');

        debugText.textContent = 'Đang xá»­ lĂ½ thanh toĂ¡n...';

        debugInfo.style.display = 'block';

        retryBtn.textContent = 'Đang xá»­ lĂ½...';

        retryBtn.disabled = true;

        

        // Log form data

        const formData = new FormData(this);

        console.log('Form data:', Object.fromEntries(formData));

        console.log('Form action:', this.action);

        

        // Let form submit normally - Laravel will handle redirect

        // No preventDefault() - let the browser handle the redirect

        

        // Add a small delay to show the loading state

        setTimeout(() => {

            console.log('Form should be submitting now...');

        }, 100);

    });

    

    // Show any flash messages

    @if(session('success'))

        alert('{{ session('success') }}');

    @endif

    

    @if(session('warning'))

        alert('{{ session('warning') }}');

    @endif

    

    @if(session('info'))

        alert('{{ session('info') }}');

    @endif

});

</script>

@endsection