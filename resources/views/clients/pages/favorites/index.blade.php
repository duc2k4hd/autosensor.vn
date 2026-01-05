@extends('clients.layouts.master')

@section('title', 'Sản phẩm yêu thích')

@section('head')
    <meta name="robots" content="noindex, nofollow">
@endsection

@push('js_page')
    <script defer src="{{ asset('clients/assets/js/main.js') }}"></script>
@endpush

@section('content')
<style>
    /* Favorite (heart) button) */
    .xanhworld_fav_btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border: 1px solid #ef4444;
        border-radius: 50%;
        background: #fff;
        cursor: pointer;
        transition: all .2s ease;
        padding: 0
    }

    .xanhworld_fav_btn .heart {
        width: 18px;
        height: 18px;
        position: relative;
        display: inline-block
    }

    .xanhworld_fav_btn .heart:before,
    .xanhworld_fav_btn .heart:after {
        content: "";
        position: absolute;
        left: 9px;
        top: 0;
        width: 9px;
        height: 14px;
        background: transparent;
        border: 2px solid #ef4444;
        border-top-left-radius: 9px;
        border-top-right-radius: 9px;
        border-bottom: none;
        transform: rotate(-45deg);
        transform-origin: left bottom
    }

    .xanhworld_fav_btn .heart:after {
        left: 0;
        transform: rotate(45deg);
        transform-origin: right bottom
    }

    .xanhworld_fav_btn.active {
        background: #ef4444;
        border-color: #ef4444
    }

    .xanhworld_fav_btn.active .heart:before,
    .xanhworld_fav_btn.active .heart:after {
        background: #ef4444;
        border-color: #ef4444
    }

    /* Favorites page */
    .xanhworld_favorites {
        max-width: 1100px;
        margin: 28px auto;
        padding: 0 12px
    }

    .xanhworld_favorites .favorites-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 16px
    }

    .xanhworld_favorites .favorites-header h1 {
        font-size: 24px;
        font-weight: 700;
        color: #232629
    }

    .xanhworld_favorites .favorites-empty {
        background: #fff;
        border: 1px solid #eee;
        border-radius: 12px;
        padding: 28px;
        text-align: center;
        box-shadow: 0 4px 16px rgba(0, 0, 0, .04)
    }

    .xanhworld_favorites .favorites-empty img {
        width: 120px;
        height: 120px;
        object-fit: contain;
        margin-bottom: 12px;
        opacity: .9
    }

    .xanhworld_favorites .favorites-empty h3 {
        margin: 6px 0 4px 0;
        color: #ff3366
    }

    .xanhworld_favorites .favorites-empty p {
        color: #555;
        margin-bottom: 12px
    }

    .xanhworld_favorites .favorites-btn {
        display: inline-block;
        padding: 10px 16px;
        background: #ff3366;
        color: #fff;
        border-radius: 8px
    }

    .xanhworld_favorites .favorites-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 16px
    }

    .xanhworld_favorites .favorite-card {
        border: 1px solid #eee;
        border-radius: 10px;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 2px 10px rgba(0, 0, 0, .03)
    }

    .xanhworld_favorites .favorite-card img {
        width: 100%;
        height: 180px;
        object-fit: cover
    }

    .xanhworld_favorites .favorite-card h3 {
        font-size: 15px;
        margin: 10px 12px;
        color: #232629;
        height: 42px;
        overflow: hidden
    }

    .xanhworld_favorites .favorite-card .price {
        margin: 0 12px 10px 12px;
        color: #e11d48;
        font-weight: 700
    }

    .xanhworld_favorites .favorite-card form {
        padding: 0 12px 12px
    }

    .xanhworld_favorites .favorites-remove {
        width: 100%;
        padding: 8px 12px;
        background: #ef4444;
        color: #fff;
        border: none;
        border-radius: 6px;
        cursor: pointer
    }

</style>
<main class="xanhworld_favorites">
    <div class="favorites-header">
        <svg viewBox="0 0 24 24" width="24" height="24" fill="#ff3366" aria-hidden="true">
            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 6 4 4 6.5 4c1.74 0 3.41.81 4.5 2.09C12.09 4.81 13.76 4 15.5 4 18 4 20 6 20 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"></path>
        </svg>
        <h1>Sản phẩm yêu thích</h1>
    </div>

    @if($favorites->isEmpty())
    <div class="favorites-empty">
        <img src="{{ asset('clients/assets/img/other/empty-heart.jpg') }}" alt="Empty wishlist" onerror="this.style.display='none'">
        <h3>Danh sách yêu thích đang trống</h3>
        <p>Hãy thêm những sản phẩm bạn thích để xem lại nhanh khi cần.</p>
        <a href="{{ route('client.home.index') }}" class="favorites-btn">Tiếp tục mua sắm</a>
    </div>
    @else
    <div class="favorites-grid">
        @foreach($favorites as $fav)
        @php $p = $fav->product; @endphp
        @if($p)
        <div class="favorite-card">
            <a href="/san-pham/{{ $p->slug }}">
                <img src="{{ asset('/clients/assets/img/clothes/' . ($p->primaryImage->url ?? 'no-image.webp')) }}" alt="{{ $p->name }}" />
                <h3>{{ $p->name }}</h3>
            </a>
            <p class="price">{{ number_format($p->sale_price ?? $p->price ?? 0, 0, ',', '.') }} đ</p>
            <form action="{{ route('client.product.wishlist.remove') }}" method="POST"
                onsubmit="return confirm('Xóa khỏi yêu thích?')">
                @csrf
                @method('DELETE')

                <input type="hidden" name="product_id" value="{{ $p->id }}">

                <button class="favorites-remove">Xóa</button>
            </form>
        </div>
        @endif
        @endforeach
    </div>
    @endif
</main>
@endsection
