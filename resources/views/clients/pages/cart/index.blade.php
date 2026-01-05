@extends('clients.layouts.master')

@php
    $cartItems = collect($cartItems ?? ($cart->cartItems ?? $cart->items ?? []))
        ->filter(fn ($item) => ! is_null($item));
    $cartSubtotal = $cartTotal
        ?? $cartItems->sum(function ($item) {
            $price = (float) ($item->price ?? 0);
            $quantity = max((int) ($item->quantity ?? 0), 1);

            return $price * $quantity;
        });
@endphp

@section('title',
    $account?->name
        ? 'Giỏ hàng của ' . $account->name
        : 'Giỏ hàng - ' . data_get($settings ?? [], 'site_name', data_get($settings ?? [], 'subname', 'Bạn')))

@section('head')
    <link rel="stylesheet" href="{{ asset('clients/assets/css/cart.css') }}">
    <meta name="robots" content="follow, noindex"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('foot')
    <script src="{{ asset('clients/assets/js/cart.js') }}"></script>
@endsection

@section('content')
    <section>
        <div class="xanhworld_cart_breadcrumb">
            <a href="{{ route('client.home.index') }}">Trang chủ</a>
            <span class="separator">>></span>
            <span>Giỏ hàng</span>
        </div>
    </section>
    @if ((isset($settings) && (data_get($settings, 'enable_cart', 'true') === 'true')))
        <div id="cart" class="xanhworld_cart_container">
            <div class="xanhworld_cart_header">
                <p style="font-size: 13px; color: red; font-style: italic">* Xem lại và kiểm tra các mặt hàng của bạn</p>
            </div>

            @if ($cartItems->isNotEmpty())
                <div class="xanhworld_cart_layout">
                    <div class="xanhworld_cart_items">
                        <table class="xanhworld_cart_table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Sản phẩm</th>
                                    <th>Đơn giá</th>
                                    <th>Số lượng</th>
                                    <th>Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($cartItems as $item)
                                    @php
                                        $product = $item->product;
                                    @endphp
                                    @if (! $product)
                                        @continue
                                    @endif
                                    @php
                                        $variant = $item->variant;
                                        // Lấy giá và tồn kho từ variant hoặc product
                                        if ($variant && $variant->is_active) {
                                            $unitPrice = (float) $variant->display_price;
                                            $stockQuantity = $variant->stock_quantity;
                                        } else {
                                            $unitPrice = (float) ($item->price ?? $product->resolveCartPrice());
                                            $stockQuantity = $product->stock_quantity;
                                        }
                                        $quantity = max((int) ($item->quantity ?? 1), 1);
                                        $lineTotal = (float) ($item->subtotal ?? ($unitPrice * $quantity));
                                        $maxPerUser = $product->flashSaleLimitPerUser();
                                        $inputMax = $stockQuantity;
                                        if ($maxPerUser) {
                                            $inputMax = is_null($inputMax) ? $maxPerUser : min($inputMax, $maxPerUser);
                                        }
                                        if (is_null($inputMax) || $inputMax < $quantity) {
                                            $inputMax = $quantity;
                                        }
                                        $remainingStock = ! is_null($stockQuantity) ? max($stockQuantity - $quantity, 0) : null;
                                        $isFlashSale = (bool) ($product->currentFlashSaleItem ?? false);
                                    @endphp
                                        <tr data-cart-item-id="{{ $item->id }}" data-unit-price="{{ $unitPrice }}" class="xanhworld_cart_item">
                                            <!-- Xóa -->
                                            <td class="xanhworld_cart_item_remove" style="text-align: center;">
                                                <form action="{{ route('client.cart.remove.item', $item->id) }}"
                                                    method="post">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button
                                                        onclick="return confirm('Bạn có chắc chắn muốn xóa {{ $product->name ?? '' }}?')"
                                                        class="xanhworld_cart_item_remove_btn"
                                                        aria-label="Xóa sản phẩm">
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                                            <path
                                                                d="M22 5a1 1 0 0 1-1 1H3a1 1 0 0 1 0-2h5V3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v1h5a1 1 0 0 1 1 1zM4.934 21.071 4 8h16l-.934 13.071a1 1 0 0 1-1 .929H5.931a1 1 0 0 1-.997-.929zM15 18a1 1 0 0 0 2 0v-6a1 1 0 0 0-2 0zm-4 0a1 1 0 0 0 2 0v-6a1 1 0 0 0-2 0zm-4 0a1 1 0 0 0 2 0v-6a1 1 0 0 0-2 0z" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            </td>

                                            <!-- Sản phẩm -->
                                            <td class="xanhworld_cart_item_product">
                                                <div class="xanhworld_cart_item_product_wrapper">
                                                    <img src="{{ asset('clients/assets/img/clothes/' . ($product?->primaryImage?->url ?? 'no-image.webp')) }}"
                                                        alt="Ảnh sản phẩm"
                                                        class="xanhworld_cart_item_product_image" />
                                                    <div class="xanhworld_cart_item_product_info">
                                                        <p class="xanhworld_cart_item_product_name">
                                                            <strong>{{ $product->name ?? '' }}</strong>
                                                        </p>
                                                        <p class="xanhworld_cart_item_product_variant">
                                                            <span class="xanhworld_cart_item_specifications">
                                                                @if($item->variant)
                                                                    <span class="spec-attr variant-name" style="font-weight: 600; color: #059669;">{{ $item->variant->name }}</span>
                                                                    <span class="spec-separator"> - </span>
                                                                @endif
                                                                @if($product->sku)
                                                                    <span class="spec-attr">SKU: {{ $product->sku }}</span>
                                                                    <span class="spec-separator"> - </span>
                                                                @endif
                                                                <span class="spec-stock {{ (! is_null($stockQuantity) && $remainingStock <= 0) ? 'out-of-stock' : 'in-stock' }}">
                                                                    @if (is_null($stockQuantity))
                                                                        <span style="color: #008000;">(Còn hàng)</span>
                                                                    @elseif ($stockQuantity <= 0)
                                                                        <span style="color: red; font-size: 12px;">(Hết hàng trong kho)</span>
                                                                    @else
                                                                        (Tồn kho {{ $stockQuantity }} - Còn <span style="font-size: 13px;" class="xanhworld_cart_item_stock_notice">{{ $remainingStock }}</span> sản phẩm)
                                                                    @endif
                                                                </span>
                                                            </span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Đơn giá và Số lượng cùng hàng -->
                                            <td class="xanhworld_cart_item_price" style="text-align: center;">
                                                <div style="display: flex; flex-direction: row; align-items: center; justify-content: space-between; gap: 10px;">
                                                    <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 2px;">
                                                        @if($isFlashSale)
                                                            <span style="color: #dc3545; font-weight: bold; font-size: 15px;">
                                                                {{ number_format($unitPrice, 0, ',', '.') }}₫
                                                            </span>
                                                            <span style="font-size: 10px; color: #dc3545; background: #ffe6e6; padding: 1px 4px; border-radius: 3px;">
                                                                🔥 Flash Sale
                                                            </span>
                                                        @else
                                                            <span style="font-size: 15px; font-weight: 700; color: #e74c3c;">{{ number_format($unitPrice, 0, ',', '.') }}₫</span>
                                                        @endif
                                                    </div>
                                                    <div class="xanhworld_cart_item_quantity_wrapper">
                                                        <button type="button" class="xanhworld_cart_item_quantity_decrease" data-item-id="{{ $item->id }}">-</button>
                                                        <input data-max-quantity="{{ ! is_null($stockQuantity ?? $maxPerUser) ? $inputMax : '' }}"
                                                            type="number" class="xanhworld_cart_item_quantity_input"
                                                            name="items[{{ $item->id }}]"
                                                            value="{{ $quantity }}" min="0" step="1" form="cart-update-form"
                                                            @if(! is_null($stockQuantity ?? $maxPerUser)) max="{{ $inputMax }}" @endif
                                                            data-item-id="{{ $item->id }}" />
                                                        <button type="button" class="xanhworld_cart_item_quantity_increase" data-item-id="{{ $item->id }}">+</button>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Số lượng - Ẩn trên mobile vì đã gộp với giá -->
                                            <td class="xanhworld_cart_item_quantity" style="text-align: center; display: none;">
                                                <div class="xanhworld_cart_item_quantity_wrapper">
                                                    <button type="button" class="xanhworld_cart_item_quantity_decrease" data-item-id="{{ $item->id }}">-</button>
                                                    <input data-max-quantity="{{ ! is_null($stockQuantity ?? $maxPerUser) ? $inputMax : '' }}"
                                                        type="number" class="xanhworld_cart_item_quantity_input"
                                                        name="items[{{ $item->id }}]"
                                                        value="{{ $quantity }}" min="0" step="1" form="cart-update-form"
                                                        @if(! is_null($stockQuantity ?? $maxPerUser)) max="{{ $inputMax }}" @endif
                                                        data-item-id="{{ $item->id }}" />
                                                    <button type="button" class="xanhworld_cart_item_quantity_increase" data-item-id="{{ $item->id }}">+</button>
                                                </div>
                                            </td>

                                            <!-- Thành tiền -->
                                            <td class="xanhworld_cart_item_total" style="text-align: center;">
                                                {{ number_format($lineTotal, 0, ',', '.') }}₫
                                            </td>
                                        </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div style="width: 100%; text-align: right;"><em
                                style="font-size: 13px; color: red; text-align: right;">*
                                Sau khi điều chỉnh số lượng hãy bấm nút "Cập nhật giỏ hàng".</em></div>
                        <div class="xanhworld_cart_actions">
                            <a href="{{ route('client.home.index') }}" class="xanhworld_cart_continue">Tiếp tục mua
                                sắm</a>
                            <form id="cart-update-form" action="{{ route('client.cart.update') }}" method="POST" class="xanhworld_cart_update_form">
                                @csrf
                                <button type="submit" class="xanhworld_cart_update">Cập nhật giỏ hàng</button>
                            </form>
                            <form class="xanhworld_cart_remove_form"
                                action="{{ route('client.cart.remove.all') }}" method="post">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="cart_id" value="{{ $cart?->id }}">
                                <button
                                    onclick="return confirm('Bạn có chắc chắn muốn xóa tất cả sản phẩm trong giỏ hàng?')"
                                    class="xanhworld_cart_remove_all">
                                    Xóa tất cả
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="xanhworld_cart_summary">
                        <h3 class="xanhworld_cart_summary_title">Tóm tắt đơn hàng <p
                                style="font-size: 13px; color: red; text-align: start; font-style: italic;">* Chưa bao gồm
                                phí vận chuyển</p>
                        </h3>

                        <div class="xanhworld_cart_summary_row">
                            <span class="xanhworld_cart_summary_subtotal_label">Tổng phụ ({{ $cartItems->count() }} sản phẩm)</span>
                            <span class="xanhworld_cart_summary_row_subtotal">
                                {{ number_format($cartSubtotal, 0, ',', '.') }} đ
                            </span>
                        </div>
                        <div class="xanhworld_cart_summary_row">
                            <span class="xanhworld_cart_summary_total">Tổng tiền</span>
                            <span data-amount="{{ $cartSubtotal }}"
                                class="xanhworld_cart_summary_amount">{{ number_format($cartSubtotal, 0, ',', '.') }}
                                đ</span>
                        </div>
                        <button
                            onclick="if(confirm('Thanh toán toàn bộ giỏ hàng?')) { window.location.href = '{{ route('client.checkout.index') }}'; }"
                            class="xanhworld_cart_checkout" style="margin-top: 8px;">
                            Thanh toán toàn bộ giỏ hàng
                        </button>
                    </div>
                </div>
            @else
                <div class="xanhworld_no_cart">
                    <div class="xanhworld_no_cart_icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
                            <defs>
                                <style>
                                    .cls-2 {
                                        fill: #f16d8f
                                    }

                                    .cls-3 {
                                        fill: #f280a0
                                    }

                                    .cls-4 {
                                        fill: #f89bae
                                    }

                                    .cls-6 {
                                        fill: #bcc0ff
                                    }

                                    .cls-7 {
                                        fill: #98d7de
                                    }

                                    .cls-8 {
                                        fill: #fac8fc
                                    }

                                    .cls-10 {
                                        fill: #81c8d9
                                    }

                                    .cls-11 {
                                        fill: #a499d6
                                    }

                                    .cls-13 {
                                        fill: #f6fafd
                                    }
                                </style>
                            </defs>
                            <g id="_16-cart" data-name="16-cart">
                                <path style="fill:#ffdddf" d="m1 21 2 16h32.864L39.5 21H1z" />
                                <path class="cls-2" d="M31 1a2 2 0 0 1 2 2v2h-4V3a2 2 0 0 1 2-2z" />
                                <path class="cls-3"
                                    d="M17.192 19.353c-.565.571-9.612 4-14.7-1.142a4.685 4.685 0 0 1 0-6.852c1.7-1.713 3.958-1.713 6.219-.571C7.58 8.5 7.58 6.22 9.277 4.506a4.579 4.579 0 0 1 6.785 0c5.088 5.14 2.261 13.705 1.13 14.847z" />
                                <path class="cls-4"
                                    d="M33 3s0-2 3-2h2a3.942 3.942 0 0 1-3.636 3.956C33.954 4.984 33.5 5 33 5zM29 3s0-2-3-2h-2a3.942 3.942 0 0 0 3.636 3.956C28.046 4.984 28.5 5 29 5z" />
                                <path style="fill:#ffbafe" d="M18 11h2v10h-4V11h2z" />
                                <path class="cls-6" d="M16 11v10h-5V11h5zM25 11v10h-5V11h5z" />
                                <path class="cls-7" d="M29 5v16h-4V5h4zM33 5h4v16h-4z" />
                                <path class="cls-3" d="M29 5h4v16h-4z" />
                                <circle class="cls-7" cx="7" cy="45" r="2" />
                                <circle class="cls-7" cx="33" cy="45" r="2" />
                                <path class="cls-8" d="M18 9c0-1.66-1.79-2-4-2l1 2-1 2h4" />
                                <path class="cls-8"
                                    d="M18.32 7.98C18.93 7.19 20.35 7 22 7l-1 2 1 2h-4V9a1.547 1.547 0 0 1 .32-1.01" />
                                <path style="fill:#fcf1ed" d="M35.864 37 39.5 21H6l16 16h13.864z" />
                                <path class="cls-10" d="M25 17h4v4h-4zM33 17h4v4h-4z" />
                                <path class="cls-2" d="M29 17h4v4h-4z" />
                                <path class="cls-11" d="M11 17h5v4h-5z" />
                                <path style="fill:#faaafe" d="M16 17h4v4h-4z" />
                                <path class="cls-3"
                                    d="M22.5 24c1.75 0 3.5 1 3.5 3 0 4.5-5.83 7-7 7-.58 0-7-2.5-7-7 0-2 1.75-3 3.5-3a3.6 3.6 0 0 1 3.5 2.5 3.6 3.6 0 0 1 3.5-2.5z" />
                                <path class="cls-2"
                                    d="M15 27a2.824 2.824 0 0 1 1.963-2.713A3.763 3.763 0 0 0 15.5 24c-1.75 0-3.5 1-3.5 3 0 4.5 6.42 7 7 7a5.072 5.072 0 0 0 1.625-.478C18.569 32.6 15 30.356 15 27z" />
                                <path class="cls-4"
                                    d="M22.5 24a3.491 3.491 0 0 0-2.577 1.03A3.058 3.058 0 0 1 23 28c0 2.8-2.252 4.82-4.238 5.952A1.167 1.167 0 0 0 19 34c1.17 0 7-2.5 7-7 0-2-1.75-3-3.5-3z" />
                                <ellipse class="cls-13" cx="24" cy="27" rx=".825" ry="1.148"
                                    transform="rotate(-45.02 24 27)" />
                                <ellipse class="cls-13" cx="23.746" cy="28.5" rx=".413" ry=".574"
                                    transform="rotate(-45.02 23.745 28.5)" />
                                <path class="cls-2"
                                    d="M11 20.957V11H9v9.959a15.161 15.161 0 0 0 2-.002zM14.085 7.169A10.771 10.771 0 0 0 12 7l1 2-1 2h2l1-2z" />
                                <path class="cls-13" d="M34 8h2v2h-2zM34 11h2v2h-2zM9 34h2v2H9zM6 34h2v2H6z" />
                                <path class="cls-11" d="M23 11v6h-3v4h5V11h-2z" />
                                <circle class="cls-10" cx="33" cy="45" r="1" />
                                <circle class="cls-10" cx="7" cy="45" r="1" />
                                <path
                                    d="M35 42H3v-2h31.2l7.823-35.217A1 1 0 0 1 43 4h4v2h-3.2l-7.823 35.217A1 1 0 0 1 35 42z"
                                    style="fill:#7d649c" />
                            </g>
                        </svg>
                    </div>
                    <h2 class="xanhworld_no_cart_title">Giỏ hàng của bạn đang trống</h2>
                    <p class="xanhworld_no_cart_text">Hãy khám phá sản phẩm của chúng tôi và thêm vào giỏ ngay nhé!
                    </p>
                    <a href="{{ route('client.home.index') }}" class="xanhworld_no_cart_button">Tiếp tục mua
                        sắm</a>
                </div>
                @include('clients.templates.product_new')
            @endif
            @include('clients.templates.loding_form')
        </div>
        {{-- @include('clients.templates.chat') --}}
    @else
        <div class="xanhworld_cart_disabled">
            <h1>Giỏ hàng hiện đang bị tắt</h1>
            <p>Vui lòng liên hệ quản trị viên để biết thêm chi tiết.</p>
        </div>
    @endif
@endsection
