@extends('clients.layouts.master')



@section('title', 'Đơn hàng của tôi | ' . renderMeta($settings->site_name ?? ($settings->subname ?? 'AutoSensor Việt Nam')))



@section('head')

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <link rel="stylesheet" href="{{ asset('clients/assets/css/order.css') }}">

    <meta name="robots" content="follow, noindex"/>

@endsection

@push('js_page')
    <script defer src="{{ asset('clients/assets/js/main.js') }}"></script>
@endpush


@section('content')

    <div class="autosensor_order_wrapper">

        <!-- Breadcrumb -->

        <section>

            <div class="autosensor_order_breadcrumb">

                <a href="{{ route('client.home.index') }}">Trang chá»§</a>

                <span class="separator">>></span>

                <span class="breadcrumb-current">Đơn hĂ ng cá»§a tĂ´i</span>

            </div>

        </section>



        <section class="autosensor_order_list">

            <div class="autosensor_order_list_container">

                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">

                    <h1 class="autosensor_order_list_title mb-0">Đơn hàng của tôi</h1>

                    <a href="{{ route('client.order.track') }}" class="autosensor_order_item_btn autosensor_order_item_btn_view" style="text-decoration:none;">

                        Tra cứu vận đơn GHN

                    </a>

                </div>



                <!-- Filters -->

                <div class="autosensor_order_filters">

                    <form method="GET" action="{{ route('client.order.index') }}" class="autosensor_order_filter_form">

                        <div class="autosensor_order_filter_group">

                            <label>Trạng thái đơn hàng:</label>

                            <select name="status" class="autosensor_order_filter_select">

                                <option value="">Tất cả</option>

                                <option value="pending" {{ $filters['status'] === 'pending' ? 'selected' : '' }}>Chờ xử lý</option>

                                <option value="processing" {{ $filters['status'] === 'processing' ? 'selected' : '' }}>Đang xử lý</option>

                                <option value="completed" {{ $filters['status'] === 'completed' ? 'selected' : '' }}>Hoàn thành</option>

                                <option value="cancelled" {{ $filters['status'] === 'cancelled' ? 'selected' : '' }}>Đã hủy</option>

                            </select>

                        </div>

                        <div class="autosensor_order_filter_group">

                            <label>Trạng thái thanh toán:</label>

                            <select name="payment_status" class="autosensor_order_filter_select">

                                <option value="">Tất cả</option>

                                <option value="pending" {{ $filters['payment_status'] === 'pending' ? 'selected' : '' }}>Chờ thanh toán</option>

                                <option value="paid" {{ $filters['payment_status'] === 'paid' ? 'selected' : '' }}>Đã thanh toán</option>

                                <option value="failed" {{ $filters['payment_status'] === 'failed' ? 'selected' : '' }}>Thất bại</option>

                            </select>

                        </div>

                        <button type="submit" class="autosensor_order_filter_btn">Lọc</button>

                        <a href="{{ route('client.order.index') }}" class="autosensor_order_filter_reset">Xóa bộ lọc</a>

                    </form>

                </div>



                <!-- Orders List -->

                @if($orders->count() > 0)

                    <div class="autosensor_order_items">

                        @foreach($orders as $order)

                            <div class="autosensor_order_item">

                                <div class="autosensor_order_item_header">

                                    <div class="autosensor_order_item_info">

                                        <h3 class="autosensor_order_item_code">

                                            Đơn hàng: <strong>{{ $order->code }}</strong>

                                        </h3>

                                        <div class="autosensor_order_item_meta">

                                            <span class="autosensor_order_item_date">

                                                {{ $order->created_at->format('d/m/Y H:i') }}

                                            </span>

                                            <span class="autosensor_order_item_status status-{{ $order->status }}">

                                                @if($order->status === 'pending')

                                                    ⏳ Chờ xử lý

                                                @elseif($order->status === 'processing')

                                                    Đang xử lý

                                                @elseif($order->status === 'completed')

                                                    ✅ Hoàn thành

                                                @else

                                                    ❌ Đã hủy

                                                @endif

                                            </span>

                                            <span class="autosensor_order_item_payment payment-{{ $order->payment_status }}">

                                                @if($order->payment_status === 'pending')

                                                    Chờ thanh toán

                                                @elseif($order->payment_status === 'paid')

                                                    ✅ Đã thanh toán

                                                @else

                                                    ❌ Thanh toán thất bại

                                                @endif

                                            </span>

                                        </div>

                                    </div>

                                    <div class="autosensor_order_item_total">

                                        <strong>{{ number_format($order->final_price, 0, ',', '.') }} đ</strong>

                                    </div>

                                </div>



                                <div class="autosensor_order_item_products">

                                    @foreach($order->items->take(3) as $item)

                                        <div class="autosensor_order_item_product">

                                            @php

                                                $imageUrl = $item->variant?->primaryVariantImage

                                                    ? asset('clients/assets/img/clothes/' . $item->variant->primaryVariantImage->url)

                                                    : ($item->product->primaryImage

                                                        ? asset('clients/assets/img/clothes/' . $item->product->primaryImage->url)

                                                        : asset('clients/assets/img/clothes/no-image.webp'));

                                            @endphp

                                            <img src="{{ $imageUrl }}" alt="{{ $item->product->name }}" class="autosensor_order_item_product_img">

                                            <div class="autosensor_order_item_product_info">

                                                <div class="autosensor_order_item_product_name">{{ $item->product->name }}</div>

                                                @if($item->variant)

                                                    @php

                                                        $attrs = is_string($item->variant->attributes) 

                                                            ? json_decode($item->variant->attributes, true) 

                                                            : $item->variant->attributes;

                                                    @endphp

                                                    @if($attrs && is_array($attrs))

                                                        <div class="autosensor_order_item_product_attrs">

                                                            {{ collect($attrs)->map(fn($val, $key) => ucfirst($key) . ': ' . $val)->join(', ') }}

                                                        </div>

                                                    @endif

                                                @endif

                                                <div class="autosensor_order_item_product_qty">

                                                    Số lượng: {{ $item->quantity }} x {{ number_format($item->price, 0, ',', '.') }} đ

                                                </div>

                                            </div>

                                        </div>

                                    @endforeach

                                    @if($order->items->count() > 3)

                                        <div class="autosensor_order_item_product_more">

                                            + {{ $order->items->count() - 3 }} sản phẩm khác

                                        </div>

                                    @endif

                                </div>



                                <div class="autosensor_order_item_actions">

                                    <a href="{{ route('client.order.show', $order->code) }}" class="autosensor_order_item_btn autosensor_order_item_btn_view">

                                        Xem chi tiết

                                    </a>

                                    @if($order->shipping_partner === 'ghn' && $order->shipping_tracking_code)

                                        <a href="{{ route('client.order.track', ['tracking_code' => $order->shipping_tracking_code]) }}" class="autosensor_order_item_btn autosensor_order_item_btn_secondary">

                                            Tra cứu vận đơn GHN

                                        </a>

                                    @endif

                                    @if($order->status === 'pending' && $order->payment_status !== 'paid')

                                        <form action="{{ route('client.order.cancel', $order->code) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('Bạn có chắc chắn muốn hủy đơn hàng này?');">
                                            @csrf
                                            @method('POST')
                                            <button type="submit" class="autosensor_order_item_btn autosensor_order_item_btn_danger">

                                                Hủy đơn hàng

                                            </button>
                                        </form>

                                    @endif

                                    @if(in_array($order->payment_status, ['pending', 'failed']) && $order->status !== 'cancelled')

                                        <a href="{{ route('client.checkout.index', ['order_code' => $order->code]) }}" class="autosensor_order_item_btn autosensor_order_item_btn_primary">

                                            Thanh toán lại

                                        </a>

                                    @endif

                                    @if($order->status === 'completed')

                                        <form action="{{ route('client.order.reorder', $order->code) }}" method="POST" style="display: inline-block;">
                                            @csrf
                                            <button type="submit" class="autosensor_order_item_btn autosensor_order_item_btn_success">

                                                Mua lại

                                            </button>
                                        </form>

                                    @endif

                                    <a href="{{ route('client.contact.index') }}?order_code={{ $order->code }}" class="autosensor_order_item_btn autosensor_order_item_btn_info">

                                        Liên hệ hỗ trợ

                                    </a>

                                    <a href="{{ route('client.order.invoice', $order->code) }}" target="_blank" class="autosensor_order_item_btn autosensor_order_item_btn_secondary">

                                        In hóa đơn

                                    </a>

                                </div>

                            </div>

                        @endforeach

                    </div>



                    <!-- Pagination -->

                    <div class="autosensor_order_pagination">

                        {{ $orders->links() }}

                    </div>

                @else

                    <div class="autosensor_order_empty">

                        <div class="autosensor_order_empty_icon"></div>

                        <h2>Chưa có đơn hàng nào</h2>

                        <p>Bạn chưa có đơn hàng nào. Hãy mua sắm ngay để có đơn hàng đầu tiên!</p>

                        <a href="{{ route('client.shop.index') }}" class="autosensor_order_empty_btn">Mua sắm ngay</a>

                    </div>

                @endif

            </div>

        </section>

    </div>



    @include('clients.templates.chat')

@endsection
