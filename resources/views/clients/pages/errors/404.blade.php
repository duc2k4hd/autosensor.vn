@extends('clients.layouts.master')


@section('title', '404 - Rất Tiếc, Không Tìm Thấy Trang | ' . ($settings_site_name ?? 'AutoSensor Việt Nam'))


@section('head')

<link rel="stylesheet" href="{{ asset('clients/assets/css/404.css') }}">

<meta name="theme-color" content="#ffffff">

<meta name="robots" content="nofollow, noindex" />

<style>
    /* Thiết lập chung */

    :root {
        /* Đồng bộ với palette dự án (xanh dương + xám trung tính) */
        --primary-color: #2563EB;       /* Xanh dương chủ đạo */
        --secondary-color: #1D4ED8;     /* Xanh dương đậm hơn */
        --accent-color: #DBEAFE;        /* Xanh dương rất nhạt cho viền / glow */
        --background-color: #F9FAFB;    /* Nền xám rất nhạt */
        --text-color: #111827;          /* Chữ xám đậm */
        --font-heading: 'Montserrat', sans-serif;
        --font-body: 'Poppins', sans-serif;
    }


    * {

        margin: 0;

        padding: 0;

        box-sizing: border-box;

    }


    body {

        font-family: var(--font-body);

        background-color: var(--background-color);

        color: var(--text-color);

    }


    .container {

        text-align: center;

        position: relative;

        padding: 40px 20px;

        z-index: 10;

        width: 1200px;

        background: rgba(255, 255, 255, 0.98);

        border-radius: 15px;

        box-shadow: 0 5px 25px rgba(15, 23, 42, 0.08);

        border: 1px solid var(--accent-color);
        margin: 10px auto;

    }


    /* --- Tiêu đề Lỗi 404 --- */

    .error-header {

        margin-bottom: 30px;

    }


    .error-code {

        font-size: 10rem;

        font-family: var(--font-heading);

        color: var(--secondary-color);

        text-shadow: 0 0 10px var(--accent-color);
        /* Shadow nhẹ nhàng */

        position: relative;

        line-height: 1;

        font-weight: 800;

    }


    /* Hiệu ứng Glow (Rung nhẹ) cho số 0 - Tinh tế hơn */

    /* Hiệu ứng Glow nâng cấp */
    .glow {
        position: relative;
        display: inline-block;
        font-weight: 900;
        color: var(--secondary-color);
        text-shadow:
            0 0 12px rgba(52, 180, 48, 0.5),
            0 0 22px rgba(15, 143, 83, 0.6),
            0 0 35px rgba(77, 168, 82, 0.4);
        animation: glow-breath 2.2s ease-in-out infinite;
        font-size: 1em;
    }

    /* Lớp neon thứ 2 tạo chiều sâu */
    .glow::before {
        content: attr(data-text);
        position: absolute;
        inset: 0;
        color: var(--primary-color);
        filter: blur(12px);
        opacity: 0.75;
        z-index: -1;
        text-shadow:
            0 0 20px var(--primary-color),
            0 0 40px var(--primary-color),
            0 0 60px var(--primary-color);
        animation: glow-pulse 1.8s ease-in-out infinite alternate;
    }

    /* Layer glow thứ 3 (siêu mờ) */
    .glow::after {
        content: attr(data-text);
        position: absolute;
        inset: 0;
        color: var(--accent-color);
        filter: blur(25px);
        opacity: 0.5;
        z-index: -2;
        animation: glow-wave 3.3s ease-in-out infinite;
    }

    /* Animation 1: Nhịp thở */
    @keyframes glow-breath {
        0% {
            transform: scale(1);
            opacity: 0.9;
        }

        50% {
            transform: scale(1.03);
            opacity: 1;
        }

        100% {
            transform: scale(1);
            opacity: 0.9;
        }
    }

    /* Animation 2: Độ sáng lên xuống */
    @keyframes glow-pulse {
        0% {
            opacity: 0.5;
            transform: scale(1);
        }

        100% {
            opacity: 0.95;
            transform: scale(1.06);
        }
    }

    /* Animation 3: Sóng ánh sáng lan dần */
    @keyframes glow-wave {
        0% {
            opacity: 0.3;
            transform: scale(1);
        }

        50% {
            opacity: 0.55;
            transform: scale(1.07);
        }

        100% {
            opacity: 0.3;
            transform: scale(1);
        }
    }


    /* --- Nội dung Lỗi --- */

    .error-title {

        font-size: 2rem;

        font-family: var(--font-heading);

        color: var(--primary-color);

        margin-bottom: 15px;

    }


    .error-message {

        font-size: 1.1rem;

        margin-bottom: 30px;

        line-height: 1.6;

        color: var(--text-color);

    }


    /* --- Nút hành động --- */

    .action-buttons {

        display: flex;

        justify-content: center;

        gap: 20px;

        margin-bottom: 40px;

    }


    .btn {

        display: inline-flex;

        align-items: center;

        padding: 12px 25px;

        border-radius: 50px;
        /* Bo tròn hiện đại */

        text-decoration: none;

        font-weight: 600;

        transition: all 0.3s ease;

        text-transform: uppercase;

        letter-spacing: 0.5px;

        border: 2px solid transparent;

        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);

    }


    .btn .icon {

        margin-right: 8px;

    }


    .primary-btn {

        background-color: var(--primary-color);

        color: white;

        border-color: var(--primary-color);

    }


    .primary-btn:hover {

        background-color: var(--secondary-color);

        border-color: var(--secondary-color);

        transform: translateY(-2px);

        box-shadow: 0 6px 10px rgba(255, 51, 102, 0.3);

    }


    .secondary-btn {

        background-color: transparent;

        color: var(--primary-color);

        border-color: var(--primary-color);

    }


    .secondary-btn:hover {

        background-color: var(--primary-color);

        color: white;

        transform: translateY(-2px);

    }


    /* --- Vùng gợi ý sản phẩm --- */

    .suggestion-area {

        text-align: left;

        margin-top: 30px;

        padding-top: 20px;

        border-top: 1px solid var(--accent-color);

    }


    .suggestion-area h3 {

        font-size: 1.5rem;

        font-family: var(--font-heading);

        margin-bottom: 20px;

        color: var(--secondary-color);

        font-weight: 700;

    }


    .product-list {

        display: flex;

        justify-content: space-around;

        flex-wrap: wrap;

        gap: 20px;

    }


    .product-card {

        background: #FAFAFA;

        border-radius: 10px;

        overflow: hidden;

        width: 100%;

        max-width: 200px;

        text-align: center;

        padding-bottom: 10px;

        border: 1px solid #EEEEEE;

        transition: transform 0.3s ease, box-shadow 0.3s ease;

    }


    .product-card:hover {

        transform: translateY(-5px);

        box-shadow: 0 8px 20px rgba(255, 51, 102, 0.2);

    }


    .product-card img {

        width: 100%;

        height: 150px;

        object-fit: cover;

        display: block;

    }


    .product-card a {

        text-decoration: none;

        color: var(--text-color);

        font-weight: 500;

        display: block;

        margin-top: 10px;

        font-size: 0.95rem;

    }


    .product-card span {

        color: var(--primary-color);

        font-weight: bold;

        display: block;

        margin-top: 5px;

        font-size: 1.1rem;

    }




    /* --- Animation Background (Bong bóng/Ngôi sao bay lên) --- */

    .background-animation {

        position: fixed;

        top: 0;

        left: 0;

        width: 100%;

        height: 100%;

        z-index: 1;

    }
    
    .autosensor_header {
        z-index: 9999;
        position: relative;
    }


    .background-animation span {

        position: absolute;

        display: block;

        width: 20px;

        height: 20px;

        background: rgba(255, 51, 102, 0.1);
        /* Hồng nhạt mờ */

        animation: animate 20s linear infinite;

        bottom: -150px;

        border-radius: 50%;

    }


    .background-animation span:nth-child(even) {

        background: rgba(255, 153, 179, 0.2);
        /* Màu hồng sáng hơn */

    }


    /* Kích thước và vị trí ngẫu nhiên */

    /* (Giữ nguyên phần vị trí ngẫu nhiên như CSS trước, chỉ đổi animation) */

    .background-animation span:nth-child(1) {
        left: 25%;
        width: 80px;
        height: 80px;
        animation-delay: 0s;
        animation-duration: 15s;
    }

    .background-animation span:nth-child(2) {
        left: 10%;
        width: 20px;
        height: 20px;
        animation-delay: 2s;
        animation-duration: 12s;
    }

    /* ... (các child còn lại) ... */


    @keyframes animate {

        0% {

            transform: translateY(0) rotate(0deg);

            opacity: 0.8;

        }


        100% {

            transform: translateY(-1000px) rotate(720deg);

            opacity: 0;

        }

    }


    /* --- Footer --- */

    footer {

        margin-top: 30px;

        padding-top: 20px;

        border-top: 1px solid var(--accent-color);

        font-size: 0.85rem;

        opacity: 0.8;

        color: #666;

    }


    /* --- Media Queries (Responsive) --- */

    @media (max-width: 768px) {

        .error-code {

            font-size: 6rem;

        }



        .error-title {

            font-size: 1.5rem;

        }



        .action-buttons {

            flex-direction: column;

            gap: 15px;

        }



        .btn {

            width: 100%;

            justify-content: center;

        }


        .product-card {

            max-width: 150px;

        }

    }


    @media (max-width: 480px) {

        .container {
            width: 100%;

            padding: 20px 15px;

        }



        .error-code {

            font-size: 4.5rem;

        }

    }

</style>

<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

@endsection

@push('js_page')
    <script defer src="{{ asset('clients/assets/js/main.js') }}"></script>
@endpush

@section('content')

@php

$productPage404 = \App\Models\Product::active()->inRandomOrder()->limit(20)->get();
\App\Models\Product::preloadImages($productPage404);

@endphp

<div class="background-animation">

    <span style="--i:11;"></span>

    <span style="--i:17;"></span>

    <span style="--i:12;"></span>

    <span style="--i:24;"></span>

    <span style="--i:10;"></span>

    <span style="--i:14;"></span>

    <span style="--i:23;"></span>

    <span style="--i:18;"></span>

    <span style="--i:16;"></span>

    <span style="--i:19;"></span>

    <span style="--i:20;"></span>

    <span style="--i:22;"></span>

    <span style="--i:25;"></span>

    <span style="--i:15;"></span>

    <span style="--i:13;"></span>

    <span style="--i:21;"></span>

</div>



<div class="container">

    <header class="error-header">

        <h1 class="error-code">4<span class="glow" data-text="0">0</span>4</h1>

    </header>


    <section class="error-content">

        <h2 class="error-title">Sản Phẩm Đang Lạc Lối, Hay Là...?</h2>

        <p class="error-message">

            Đường dẫn bạn truy cập hiện không khả dụng. Đừng lo lắng, hãy quay lại khám phá những bộ sưu tập mới nhất!

        </p>



        <div class="action-buttons">

            <a href="{{ route('client.home.index') }}" class="btn primary-btn">

                <i class="icon fas fa-home"></i> Trở Về Trang Chủ

            </a>

            <a href="{{ route('client.shop.index') }}" class="btn secondary-btn">

                <i class="icon fas fa-gift"></i> Khám Phá Hàng Mới

            </a>

            <a href="{{ route('client.contact.index') }}" class="btn secondary-btn">

                <i class="icon fas fa-gift"></i> Liên hệ hỗ trợ

            </a>

        </div>



        <div class="suggestion-area">

            <h3>✨ Gợi Ý Dành Cho Bạn</h3>

            <div id="product-suggestions" class="product-list">

            </div>

        </div>

    </section>


    <footer>

        <p>&copy; {{ date('Y') }} - {{ $settings->site_name ?? 'AutoSensor Việt Nam' }}.</p>

    </footer>

</div>



<script src="https://kit.fontawesome.com/your-font-awesome-kit.js" crossorigin="anonymous"></script>

@section('scripts')

<script>
    document.addEventListener('DOMContentLoaded', function() {

        const suggestionArea = document.getElementById('product-suggestions');

        const container = document.querySelector('.container');


        // Dữ liệu gợi ý sản phẩm (Giả định)

        const products = [

            @foreach($productPage404 as $product)

            {
                name: "{{ $product->name }}"
                , price: "{{ number_format((float) ($product->price ?? 0), 0, ',', '.') }}₫"
                , image: "{{ $product->primaryImage->url ?? 'default_image.jpg' }}"
                , url: "{{ route('client.product.detail', ['slug' => $product->slug]) }}"
            },

            @endforeach

        ];


        // Chèn sản phẩm vào HTML

        products.forEach(product => {

            const card = document.createElement('div');

            card.className = 'product-card';



            card.innerHTML = `

                        <a href="${product.url}">

                            <img src="{{ asset('clients/assets/img/clothes') }}/${product.image}" alt="${product.name}">

                        </a>

                        <a href="${product.url}">${product.name}</a>

                        <span>${product.price}</span>

                    `;



            suggestionArea.appendChild(card);

        });



        // Hiệu ứng Fade-in khi tải trang

        container.style.opacity = '0';

        container.style.transform = 'translateY(20px)';



        setTimeout(() => {

            container.style.transition = 'opacity 0.8s ease-out, transform 0.8s ease-out';

            container.style.opacity = '1';

            container.style.transform = 'translateY(0)';

        }, 100);

    });

</script>

@endsection

@endsection
