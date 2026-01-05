@extends('clients.layouts.master')

@section('title', 'So sánh sản phẩm | ' . ($settings->site_name ?? $settings->site_name ?? 'AutoSensor Việt Nam'))

@push('css_page')
    <link rel="stylesheet" href="{{ asset('clients/assets/css/comparison.css') }}">
@endpush

@push('js_page')
    <script defer src="{{ asset('clients/assets/js/main.js') }}"></script>
@endpush

@section('head')
    <meta robot
    <meta name="robots" content="noindex, nofollow">
@endsection

@section('content')
<div class="autosensor_comparison">
    <div class="autosensor_comparison_container">
        <div class="autosensor_comparison_header">
            <h1 class="autosensor_comparison_title">So sánh sản phẩm</h1>
            <p class="autosensor_comparison_subtitle">So sánh chi tiết các sản phẩm để đưa ra lựa chọn tốt nhất</p>
        </div>

        @if($products->isEmpty())
            <div class="autosensor_comparison_empty">
                <div class="autosensor_comparison_empty_icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="80" height="80">
                        <path d="M320 488c0 9.5-5.6 18.1-14.2 21.9s-18.8 2.3-25.8-4.1l-80-72c-5.1-4.6-7.9-11-7.9-17.8s2.9-13.3 7.9-17.8l80-72c7-6.3 17.2-7.9 25.8-4.1s14.2 12.4 14.2 21.9l0 40 16 0c35.3 0 64-28.7 64-64l0-166.7C371.7 141 352 112.8 352 80c0-44.2 35.8-80 80-80s80 35.8 80 80c0 32.8-19.7 61-48 73.3L464 320c0 70.7-57.3 128-128 128l-16 0 0 40zM456 80a24 24 0 1 0 -48 0 24 24 0 1 0 48 0zM192 24c0-9.5 5.6-18.1 14.2-21.9s18.8-2.3 25.8 4.1l80 72c5.1 4.6 7.9 11 7.9 17.8s-2.9 13.3-7.9 17.8l-80 72c-7 6.3-17.2 7.9-25.8 4.1s-14.2-12.4-14.2-21.9l0-40-16 0c-35.3 0-64 28.7-64 64l0 166.7c28.3 12.3 48 40.5 48 73.3c0 44.2-35.8 80-80 80s-80-35.8-80-80c0-32.8 19.7-61 48-73.3L48 192c0-70.7 57.3-128 128-128l16 0 0-40zM56 432a24 24 0 1 0 48 0 24 24 0 1 0 -48 0z" fill="currentColor"/>
                    </svg>
                </div>
                <h3 class="autosensor_comparison_empty_title">Bạn chưa có sản phẩm nào để so sánh</h3>
                <p class="autosensor_comparison_empty_text">Thêm sản phẩm vào danh sách so sánh để xem chi tiết và so sánh</p>
                <a href="{{ route('client.shop.index') }}" class="autosensor_comparison_empty_btn">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="20" height="20">
                        <path d="M217.9 105.9L340.7 228.7c7.2 7.2 11.3 17.1 11.3 27.3s-4.1 20.1-11.3 27.3L217.9 406.1c-6.4 6.4-15 9.9-24 9.9c-18.7 0-33.9-15.2-33.9-33.9l0-62.1L32 320c-17.7 0-32-14.3-32-32l0-64c0-17.7 14.3-32 32-32l128 0 0-62.1c0-18.7 15.2-33.9 33.9-33.9c9 0 17.6 3.6 24 9.9zM352 416l64 0c17.7 0 32-14.3 32-32l0-256c0-17.7-14.3-32-32-32l-64 0c-17.7 0-32-14.3-32-32s14.3-32 32-32l64 0c53 0 96 43 96 96l0 256c0 53-43 96-96 96l-64 0c-17.7 0-32-14.3-32-32s14.3-32 32-32z"/>
                    </svg>
                    Tiếp tục mua sắm
                </a>
            </div>
        @else
            <div class="autosensor_comparison_wrapper">
                <div class="autosensor_comparison_table_container">
                    <table class="autosensor_comparison_table">
                        <thead>
                            <tr>
                                <th class="autosensor_comparison_table_criteria">Tiêu chí</th>
                                @foreach($products as $product)
                                    <th class="autosensor_comparison_table_product">
                                        <div class="autosensor_comparison_product_card">
                                            <button class="autosensor_comparison_product_remove remove-comparison" data-product-id="{{ $product->id }}" aria-label="Xóa sản phẩm">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" width="16" height="16">
                                                    <path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/>
                                                </svg>
                                            </button>
                                            <div class="autosensor_comparison_product_image">
                                                @if($product->primaryImage && $product->primaryImage->url)
                                                    <img src="{{ asset('clients/assets/img/clothes/resize/230x230/' . $product->primaryImage->url) }}" 
                                                         alt="{{ $product->name }}" 
                                                         loading="lazy">
                                                @else
                                                    <img src="{{ asset('clients/assets/img/clothes/no-image.webp') }}" 
                                                         alt="{{ $product->name }}" 
                                                         loading="lazy">
                                                @endif
                                            </div>
                                            <h3 class="autosensor_comparison_product_name">{{ $product->name }}</h3>
                                            <p class="autosensor_comparison_product_sku">SKU: {{ $product->sku }}</p>
                                            <a href="{{ route('client.product.detail', $product->slug) }}" class="autosensor_comparison_product_link">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="16" height="16">
                                                    <path d="M217.9 105.9L340.7 228.7c7.2 7.2 11.3 17.1 11.3 27.3s-4.1 20.1-11.3 27.3L217.9 406.1c-6.4 6.4-15 9.9-24 9.9c-18.7 0-33.9-15.2-33.9-33.9l0-62.1L32 320c-17.7 0-32-14.3-32-32l0-64c0-17.7 14.3-32 32-32l128 0 0-62.1c0-18.7 15.2-33.9 33.9-33.9c9 0 17.6 3.6 24 9.9zM352 416l64 0c17.7 0 32-14.3 32-32l0-256c0-17.7-14.3-32-32-32l-64 0c-17.7 0-32-14.3-32-32s14.3-32 32-32l64 0c53 0 96 43 96 96l0 256c0 53-43 96-96 96l-64 0c-17.7 0-32-14.3-32-32s14.3-32 32-32z"/>
                                                </svg>
                                                Xem chi tiết
                                            </a>
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="autosensor_comparison_row">
                                <td class="autosensor_comparison_table_criteria">
                                    <div class="autosensor_comparison_criteria_label">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="18" height="18">
                                            <path d="M160 80H512c8.8 0 16 7.2 16 16V320c0 8.8-7.2 16-16 16H490.8L388.1 478.8c-4.4 6.8-12 10.8-20.1 10.8s-15.7-4-20.1-10.8L246.2 336H160c-8.8 0-16-7.2-16-16V96c0-8.8 7.2-16 16-16zM48 96V320c0 35.3 28.7 64 64 64h96.4l32 64H112c-8.8 0-16 7.2-16 16s7.2 16 16 16H352c8.8 0 16-7.2 16-16s-7.2-16-16-16H265.2l-32-64H352c35.3 0 64-28.7 64-64V96c0-35.3-28.7-64-64-64H112C76.7 32 48 60.7 48 96z"/>
                                        </svg>
                                        <span>Giá</span>
                                    </div>
                                </td>
                                @foreach($products as $product)
                                    <td class="autosensor_comparison_table_value">
                                        <div class="autosensor_comparison_price">
                                            @if($product->sale_price && $product->sale_price < $product->price)
                                                <span class="autosensor_comparison_price_sale">{{ number_format($product->sale_price, 0, ',', '.') }} đ</span>
                                                <span class="autosensor_comparison_price_original">{{ number_format($product->price, 0, ',', '.') }} đ</span>
                                            @else
                                                <span class="autosensor_comparison_price_current">{{ number_format($product->price, 0, ',', '.') }} đ</span>
                                            @endif
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                            <tr class="autosensor_comparison_row">
                                <td class="autosensor_comparison_table_criteria">
                                    <div class="autosensor_comparison_criteria_label">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" width="18" height="18">
                                            <path d="M287.9 0c9.2 0 17.6 5.2 21.6 13.5l68.6 141.3 153.2 22.6c9 1.3 16.5 7.6 19.3 16.3s.5 18.1-5.9 24.5L433.6 328.4l26.2 155.6c1.5 9-2.2 18.1-9.6 23.5s-17.3 6-25.3 1.7l-137-73.2L151 509.1c-8.1 4.3-17.9 3.7-25.3-1.7s-11.2-14.5-9.7-23.5l26.2-155.6L31.1 218.2c-6.5-6.4-8.7-15.9-5.9-24.5s10.3-14.9 19.3-16.3l153.2-22.6L266.3 13.5C270.4 5.2 278.7 0 287.9 0zm0 79L235.4 187.2c-3.5 7.1-10.2 12.1-18.1 13.3L99 217.9 184.9 303c5.5 5.5 8.1 13.3 6.8 21L171.4 443.7l105.2-56.2c7.1-3.8 15.6-3.8 22.6 0l105.2 56.2L401.2 324.1c-1.3-7.7 1.2-15.5 6.8-21L494.9 237l-118.5-17.5c-7.8-1.2-14.6-6.1-18.1-13.3L287.9 79z"/>
                                        </svg>
                                        <span>Đánh giá</span>
                                    </div>
                                </td>
                                @foreach($products as $product)
                                    <td class="autosensor_comparison_table_value">
                                        <div class="autosensor_comparison_rating">
                                            <div class="autosensor_comparison_rating_stars">
                                                @for($i = 1; $i <= 5; $i++)
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" width="18" height="18" class="{{ $i <= $product->display_rating_star ? 'active' : '' }}">
                                                        <path d="M287.9 0c9.2 0 17.6 5.2 21.6 13.5l68.6 141.3 153.2 22.6c9 1.3 16.5 7.6 19.3 16.3s.5 18.1-5.9 24.5L433.6 328.4l26.2 155.6c1.5 9-2.2 18.1-9.6 23.5s-17.3 6-25.3 1.7l-137-73.2L151 509.1c-8.1 4.3-17.9 3.7-25.3-1.7s-11.2-14.5-9.7-23.5l26.2-155.6L31.1 218.2c-6.5-6.4-8.7-15.9-5.9-24.5s10.3-14.9 19.3-16.3l153.2-22.6L266.3 13.5C270.4 5.2 278.7 0 287.9 0zm0 79L235.4 187.2c-3.5 7.1-10.2 12.1-18.1 13.3L99 217.9 184.9 303c5.5 5.5 8.1 13.3 6.8 21L171.4 443.7l105.2-56.2c7.1-3.8 15.6-3.8 22.6 0l105.2 56.2L401.2 324.1c-1.3-7.7 1.2-15.5 6.8-21L494.9 237l-118.5-17.5c-7.8-1.2-14.6-6.1-18.1-13.3L287.9 79z"/>
                                                    </svg>
                                                @endfor
                                            </div>
                                            <span class="autosensor_comparison_rating_count">({{ $product->display_review_count }} đánh giá)</span>
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                            <tr class="autosensor_comparison_row">
                                <td class="autosensor_comparison_table_criteria">
                                    <div class="autosensor_comparison_criteria_label">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" width="18" height="18">
                                            <path d="M0 24C0 10.7 10.7 0 24 0H69.5c22 0 41.5 12.8 50.6 32h411c26.3 0 45.5 25 38.6 50.4l-41 152.3c-8.5 31.4-37 53.3-69 53.3H170.7l5.4 28.5c2.2 11.3 12.1 19.5 23.6 19.5H488c13.3 0 24 10.7 24 24s-10.7 24-24 24H199.7c-34.6 0-64.3-24.6-70.7-58.5L77.4 54.5c-.7-3.8-4-6.5-7.9-6.5H24C10.7 48 0 37.3 0 24zM128 464a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zm336-48a48 48 0 1 1 0 96 48 48 0 1 1 0-96z"/>
                                        </svg>
                                        <span>Tồn kho</span>
                                    </div>
                                </td>
                                @foreach($products as $product)
                                    <td class="autosensor_comparison_table_value">
                                        <div class="autosensor_comparison_stock">
                                            @if($product->stock_quantity === null)
                                                <span class="autosensor_comparison_stock_badge in_stock">Còn hàng</span>
                                            @elseif($product->stock_quantity > 0)
                                                <span class="autosensor_comparison_stock_badge in_stock">Còn {{ $product->stock_quantity }} sản phẩm</span>
                                            @else
                                                <span class="autosensor_comparison_stock_badge out_of_stock">Hết hàng</span>
                                            @endif
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                            <tr class="autosensor_comparison_row">
                                <td class="autosensor_comparison_table_criteria">
                                    <div class="autosensor_comparison_criteria_label">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="18" height="18">
                                            <path d="M57.7 193l9.4 16.4c8.3 14.5 21.9 25.1 37.8 29.8l57.2 16c46.4 13 49.6 13 97.9 0l57.2-16c15.9-4.5 29.5-15.3 37.8-29.8l9.4-16.4c20.6-35.9 30.3-55.9 30.3-87.1s-9.7-51.2-30.3-87.1l-9.4-16.4c-8.3-14.5-21.9-25.1-37.8-29.8L231.1 0c-46.4-13-49.6-13-97.9 0L75.9 16c-15.9 4.5-29.5 15.3-37.8 29.8L28.7 62.6C8.1 98.5-1.5 118.5-1.5 149.7s9.7 51.2 30.3 87.1zM432 240c26.5 0 48-21.5 48-48s-21.5-48-48-48s-48 21.5-48 48s21.5 48 48 48z"/>
                                        </svg>
                                        <span>Danh mục</span>
                                    </div>
                                </td>
                                @foreach($products as $product)
                                    <td class="autosensor_comparison_table_value">
                                        <span class="autosensor_comparison_category">{{ $product->primaryCategory->name ?? 'N/A' }}</span>
                                    </td>
                                @endforeach
                            </tr>
                            <tr class="autosensor_comparison_row">
                                <td class="autosensor_comparison_table_criteria">
                                    <div class="autosensor_comparison_criteria_label">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="18" height="18">
                                            <path d="M471.6 21.7c-21.9-21.9-57.3-21.9-79.2 0L362.3 51.7l97.9 97.9 30.1-30.1c21.9-21.9 21.9-57.3 0-79.2L471.6 21.7zm-299.2 220c-6.1 6.1-10.8 13.6-13.5 21.9l-29.6 88.8c-2.9 8.6-.6 18.1 5.8 24.6s15.9 8.7 24.6 5.8l88.8-29.6c8.2-2.7 15.7-7.4 21.9-13.5L437.7 172.3 339.7 74.3 172.4 241.7zM96 64C43 64 0 107 0 160V416c0 53 43 96 96 96H352c53 0 96-43 96-96V320c0-17.7-14.3-32-32-32s-32 14.3-32 32v96c0 17.7-14.3 32-32 32H96c-17.7 0-32-14.3-32-32V160c0-17.7 14.3-32 32-32h96c17.7 0 32-14.3 32-32s-14.3-32-32-32H96z"/>
                                        </svg>
                                        <span>Mô tả</span>
                                    </div>
                                </td>
                                @foreach($products as $product)
                                    <td class="autosensor_comparison_table_value">
                                        <p class="autosensor_comparison_description">{{ Str::limit(strip_tags($product->short_description ?? $product->description ?? ''), 120) }}</p>
                                    </td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="autosensor_comparison_actions">
                    <button class="autosensor_comparison_clear_btn" id="clear-comparison">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="18" height="18">
                            <path d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z"/>
                        </svg>
                        Xóa tất cả
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

