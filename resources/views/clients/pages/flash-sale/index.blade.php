@extends('clients.layouts.master')

@php
    $formatCurrency = fn ($value) => number_format((float) ($value ?? 0), 0, ',', '.') . '₫';
    $pageTitle = $flashSale && $flashSale->title
        ? 'Flash Sale: ' . $flashSale->title . ' - ' . ($settings->site_name ?? 'Xanh World')
        : 'Flash Sale cực sốc - ' . ($settings->site_name ?? 'Xanh World');
@endphp

@section('title', $pageTitle)

@section('head')
    <link rel="stylesheet" href="{{ asset('clients/assets/css/flash-sale.css') }}">
@endsection

@section('foot')
    <script>
        window.flashSaleMeta = {
            hasFlashSale: @json((bool) $flashSale),
            endTime: @json(optional($flashSale?->end_time)->toIso8601String()),
            startTime: @json(optional($flashSale?->start_time)->toIso8601String()),
        };
    </script>
    <script src="{{ asset('clients/assets/js/flash-sale.js') }}"></script>
@endsection

@section('content')
    <section class="flash-sale-page">
        <div class="flash-sale-container">
            <div class="flash-sale-hero" data-countdown-end="{{ optional($flashSale?->end_time)->toIso8601String() }}"
                data-countdown-start="{{ optional($flashSale?->start_time)->toIso8601String() }}">
                <div class="flash-sale-hero__text">
                    <p class="flash-sale-hero__eyebrow">
                        {{ $flashSale?->tag ?? 'Deal độc quyền trong ngày' }}
                    </p>
                    <h1>
                        {{ $flashSale?->title ?? 'Flash Sale siêu tốc' }}
                    </h1>
                    <p class="flash-sale-hero__desc">
                        {{ $flashSale?->description ?? 'Săn những deal giảm sâu với giá tốt nhất chỉ trong khung giờ vàng hôm nay. Số lượng giới hạn, nhanh tay kẻo lỡ!' }}
                    </p>
                    <div class="flash-sale-countdown">
                        <div class="countdown-chip">
                            <span>Ngày</span>
                            <strong data-countdown-part="days">00</strong>
                        </div>
                        <div class="countdown-chip">
                            <span>Giờ</span>
                            <strong data-countdown-part="hours">00</strong>
                        </div>
                        <div class="countdown-chip">
                            <span>Phút</span>
                            <strong data-countdown-part="minutes">00</strong>
                        </div>
                        <div class="countdown-chip">
                            <span>Giây</span>
                            <strong data-countdown-part="seconds">00</strong>
                        </div>
                    </div>
                    <div class="flash-sale-hero__actions">
                        <a href="#flash-sale-grid" class="hero-btn hero-btn--primary">Săn deal ngay</a>
                        <button class="hero-btn hero-btn--ghost" data-scroll="#flash-sale-upcoming">Nhắc lịch đợt sau</button>
                    </div>
                </div>
                <div class="flash-sale-hero__stats">
                    <div class="stat-card">
                        <p>Deal đang diễn ra</p>
                        <strong>{{ $stats['totalProducts'] }}</strong>
                    </div>
                    <div class="stat-card">
                        <p>% giảm cao nhất</p>
                        <strong>{{ $stats['maxDiscount'] }}%</strong>
                    </div>
                    <div class="stat-card">
                        <p>Đã bán</p>
                        <strong>{{ number_format($stats['totalSold']) }}</strong>
                    </div>
                    <div class="stat-card">
                        <p>Tồn kho</p>
                        <strong>{{ number_format($stats['totalStock']) }}</strong>
                    </div>
                </div>
            </div>

            <div class="flash-sale-toolbar">
                <div class="toolbar-left">
                    <div class="toolbar-search">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path
                                d="M16.5 14h-.79l-.28-.27A6 6 0 1 0 14 16.5l.27.28v.79l4.25 4.25L20.75 18.25 16.5 14zm-6 0a4.5 4.5 0 1 1 0-9 4.5 4.5 0 0 1 0 9z" />
                        </svg>
                        <input type="search" id="flash-search" placeholder="Tìm nhanh sản phẩm flash sale...">
                    </div>
                    <div class="toolbar-filters">
                        <button type="button" class="filter-pill is-active" data-filter="all">Tất cả</button>
                        <button type="button" class="filter-pill" data-filter="hot">Giảm &gt; 40%</button>
                        <button type="button" class="filter-pill" data-filter="low-stock">Sắp hết hàng</button>
                        <button type="button" class="filter-pill" data-filter="budget">&lt; 500K</button>
                    </div>
                </div>
                <div class="toolbar-right">
                    <select id="flash-sort">
                        <option value="featured">Nổi bật</option>
                        <option value="discount_desc">Giảm giá cao nhất</option>
                        <option value="price_asc">Giá thấp đến cao</option>
                        <option value="price_desc">Giá cao đến thấp</option>
                        <option value="stock_desc">Còn nhiều hàng</option>
                    </select>
                    <button type="button" class="toolbar-refresh" id="flash-refresh">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path
                                d="M17.65 6.35a7.95 7.95 0 0 0-11.3 0l-1.3 1.3V4H3v6h6V8.65H5.35l1.3-1.3a5.96 5.96 0 0 1 8.48 0 5.96 5.96 0 0 1 0 8.48 5.96 5.96 0 0 1-8.48 0L5 16.83a7.95 7.95 0 0 0 11.3 0 7.95 7.95 0 0 0 0-11.3z" />
                        </svg>
                        Làm mới
                    </button>
                </div>
            </div>

            @if ($flashSaleItems->isEmpty())
                <div class="flash-sale-empty">
                    <div class="flash-sale-empty__icon">🔥</div>
                    <h3>Hiện chưa có chương trình flash sale</h3>
                    <p>Chúng tôi sẽ sớm trở lại với những ưu đãi cực khủng. Đừng quên xem trước các đợt sắp tới bên
                        dưới nhé!</p>
                </div>
            @else
                <div class="flash-sale-grid" id="flash-sale-grid">
                    @foreach ($flashSaleItems as $entry)
                        @php
                            $product = $entry['product'];
                            $salePrice = $entry['sale_price'];
                            $originalPrice = $entry['original_price'];
                            $stock = $entry['stock'];
                            $sold = $entry['sold'];
                            $remaining = max($stock - $sold, 0);
                            $progress = $entry['progress'];
                        @endphp
                        <article class="flash-card"
                            data-name="{{ \Illuminate\Support\Str::lower($product?->name ?? '') }}"
                            data-discount="{{ $entry['discount_percent'] }}"
                            data-price="{{ (int) $salePrice }}"
                            data-stock="{{ $stock }}"
                            data-remaining="{{ $remaining }}"
                            data-budget="{{ $salePrice < 500000 ? '1' : '0' }}"
                            data-low-stock="{{ $remaining <= max(3, (int) floor($stock * 0.1)) ? '1' : '0' }}">
                            <div class="flash-card__badge">
                                <span>-{{ $entry['discount_percent'] }}%</span>
                                @if (!empty($entry['badges']))
                                    @foreach ($entry['badges'] as $badge)
                                        <span>{{ $badge }}</span>
                                    @endforeach
                                @endif
                            </div>
                            <div class="flash-card__image">
                                <img
                                    src="{{ asset('clients/assets/img/clothes/' . ($product?->primaryImage?->url ?? 'no-image.webp')) }}"
                                    alt="{{ $product?->name }}">
                                <div class="flash-card__timer">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path
                                            d="M15 1H9v2h6V1zm1 10h-2V9h-4v4h4v4h2v-4h4V9h-4v2zm-4 11a9 9 0 1 1 0-18 9 9 0 0 1 0 18zm0-2a7 7 0 1 0 0-14 7 7 0 0 0 0 14z" />
                                    </svg>
                                    Còn {{ max($remaining, 0) }} sản phẩm
                                </div>
                            </div>
                            <div class="flash-card__body">
                                <div class="flash-card__category">
                                    {{ $product?->primaryCategory?->name ?? 'Flash sale' }}
                                </div>
                                <h3>{{ $product?->name ?? 'Sản phẩm' }}</h3>
                                <p class="flash-card__sku">SKU: {{ $product?->sku ?? 'N/A' }}</p>

                                <div class="flash-card__price">
                                    <strong>{{ $formatCurrency($salePrice) }}</strong>
                                    @if ($originalPrice > $salePrice)
                                        <span>{{ $formatCurrency($originalPrice) }}</span>
                                    @endif
                                </div>

                                <div class="flash-card__progress">
                                    <div class="progress-track">
                                        <span class="progress-fill" style="width: {{ $progress }}%"></span>
                                    </div>
                                    <div class="progress-info">
                                        <span>Đã bán {{ $sold }}/{{ $stock }}</span>
                                        <span>Còn lại {{ $remaining }}</span>
                                    </div>
                                </div>

                                <div class="flash-card__actions">
                                    <form action="{{ route('client.cart.store') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $product?->id }}">
                                        <button type="submit" class="flash-btn flash-btn--primary"
                                            {{ $remaining < 1 ? 'disabled' : '' }}>
                                            {{ $remaining < 1 ? 'Đã hết hàng' : 'Thêm vào giỏ' }}
                                        </button>
                                    </form>
                                    <a class="flash-btn flash-btn--ghost"
                                        href="{{ $product ? route('client.product.detail', $product->slug) : '#' }}">
                                        Xem chi tiết
                                    </a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif

            <div class="flash-sale-banner">
                <div>
                    <h3>Shipping hỏa tốc toàn quốc</h3>
                    <p>Đơn hàng từ 499K được miễn phí vận chuyển hoặc nhận trong ngày tại HCM.</p>
                </div>
                <button type="button" data-scroll="#flash-sale-grid">Săn deal ngay</button>
            </div>

            <div class="flash-sale-upcoming" id="flash-sale-upcoming">
                <div class="section-head">
                    <h2>Flash sale sắp diễn ra</h2>
                    <p>Đặt nhắc nhở để không bỏ lỡ khung giờ vàng kế tiếp.</p>
                </div>
                @if ($upcomingFlashSales->isEmpty())
                    <div class="flash-sale-empty mini">
                        <p>Hiện chưa có lịch flash sale mới. Vui lòng quay lại sau!</p>
                    </div>
                @else
                    <div class="upcoming-grid">
                        @foreach ($upcomingFlashSales as $sale)
                            <div class="upcoming-card">
                                <div class="upcoming-card__time">
                                    {{ optional($sale->start_time)->format('d/m H:i') }} →
                                    {{ optional($sale->end_time)->format('H:i') }}
                                </div>
                                <h3>{{ $sale->title }}</h3>
                                <p>{{ $sale->description ?? 'Deal giới hạn, số lượng cực ít, nhớ bật thông báo!' }}</p>
                                <div class="upcoming-card__meta">
                                    <span>{{ $sale->display_limit ? ($sale->display_limit . ' sản phẩm') : 'Không giới hạn' }}</span>
                                    <span>{{ $sale->max_per_user ? ('Tối đa ' . $sale->max_per_user . '/người') : 'Mua thoải mái' }}</span>
                                </div>
                                <button class="flash-btn flash-btn--primary upcoming-remind"
                                    data-title="{{ $sale->title }}"
                                    data-time="{{ optional($sale->start_time)->toIso8601String() }}">
                                    Nhắc tôi
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="flash-sale-spotlight">
                <div class="section-head">
                    <h2>Sản phẩm gợi ý hôm nay</h2>
                    <p>Trong lúc chờ deal, bạn có thể tham khảo thêm những sản phẩm bán chạy khác.</p>
                </div>
                <div class="spotlight-grid">
                    @forelse ($spotlightProducts as $product)
                        <a href="{{ route('client.product.detail', $product->slug) }}" class="spotlight-card">
                            <div class="spotlight-card__image">
                                <img
                                    src="{{ asset('clients/assets/img/clothes/' . ($product?->primaryImage?->url ?? 'no-image.webp')) }}"
                                    alt="{{ $product->name }}">
                            </div>
                            <div>
                                <p class="spotlight-card__category">
                                    {{ $product->primaryCategory?->name ?? 'Sản phẩm nổi bật' }}
                                </p>
                                <h3>{{ $product->name }}</h3>
                                <div class="spotlight-card__price">
                                    <strong>{{ $formatCurrency($product->sale_price && $product->sale_price < $product->price ? $product->sale_price : $product->price) }}</strong>
                                    @if ($product->sale_price && $product->sale_price < $product->price)
                                        <span>{{ $formatCurrency($product->price) }}</span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="flash-sale-empty mini">
                            <p>Đang cập nhật danh sách đề xuất.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="flash-sale-sticky">
            <div class="sticky-countdown">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M12 1.75a10.25 10.25 0 1 0 0 20.5 10.25 10.25 0 0 0 0-20.5zm0 18.5a8.25 8.25 0 1 1 0-16.5 8.25 8.25 0 0 1 0 16.5zm.75-13h-1.5v5l4.25 2.55.75-1.23-3.5-2.07V7.25z" />
                </svg>
                <span class="sticky-label">Flash sale</span>
                <strong data-countdown-part="sticky">00:00:00</strong>
            </div>
            <button type="button" data-scroll="#flash-sale-grid">Xem tất cả</button>
        </div>
    </section>
@endsection

