@extends('clients.layouts.master')

@section('title', $pageTitle ?? 'Kết quả tư vấn')

@section('head')
    <link rel="stylesheet" href="{{ asset('clients/assets/css/wizard.css') }}">
    <meta name="robots" content="noindex, follow" />
@endsection

@section('content')
    <div class="autosensor_wizard_result_container">
        <div class="autosensor_wizard_result_header">
            <h1 class="autosensor_wizard_result_title">{{ $pageTitle }}</h1>
            <p class="autosensor_wizard_result_subtitle">Dựa trên câu trả lời của bạn, chúng tôi đã tìm thấy {{ $products->count() }} sản phẩm phù hợp</p>
        </div>

        @if($products->isEmpty())
            <div class="autosensor_wizard_result_empty">
                <p>Không tìm thấy sản phẩm phù hợp với yêu cầu của bạn.</p>
                <a href="{{ route('client.wizard.index', ['type' => $session->product_type]) }}" class="autosensor_wizard_btn">
                    Thử lại với tiêu chí khác
                </a>
            </div>
        @else
            <div class="autosensor_wizard_result_products">
                @foreach($products as $product)
                    <div class="autosensor_wizard_result_product_card">
                        <a href="/{{ $product->slug }}" class="autosensor_wizard_result_product_image">
                            <img src="{{ asset('clients/assets/img/clothes/resize/300x300/' . ($product->primaryImage->url ?? 'no-image.webp')) }}" 
                                 alt="{{ $product->name }}"
                                 onerror="this.onerror=null;this.src='{{ asset('clients/assets/img/clothes/no-image.webp') }}'">
                        </a>
                        <div class="autosensor_wizard_result_product_info">
                            <h3 class="autosensor_wizard_result_product_name">
                                <a href="/{{ $product->slug }}">{{ $product->name }}</a>
                            </h3>
                            <div class="autosensor_wizard_result_product_price">
                                {{ number_format($product->sale_price ?? $product->price ?? 0, 0, ',', '.') }}đ
                            </div>
                            <div class="autosensor_wizard_result_product_actions">
                                <a href="/{{ $product->slug }}" class="autosensor_wizard_result_product_btn">
                                    Xem chi tiết
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="autosensor_wizard_result_actions">
                <a href="{{ route('client.wizard.index', ['type' => $session->product_type]) }}" class="autosensor_wizard_btn">
                    Tư vấn lại
                </a>
                <a href="{{ route('client.shop.index') }}" class="autosensor_wizard_btn autosensor_wizard_btn_secondary">
                    Xem tất cả sản phẩm
                </a>
            </div>
        @endif
    </div>
@endsection
