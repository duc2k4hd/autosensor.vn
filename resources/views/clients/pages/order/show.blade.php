@extends('clients.layouts.master')



@section('title', 'Chi tiết đơn hàng - ' . renderMeta($settings->site_name ?? ($settings->subname ?? 'AutoSensor Việt Nam')))



@section('head')

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <link rel="stylesheet" href="{{ asset('clients/assets/css/order.css') }}">

    <meta name="robots" content="follow, noindex"/>

@endsection



@section('content')

    <div class="xanhworld_order_wrapper">

        <!-- Breadcrumb -->

        <section>

            <div class="xanhworld_order_breadcrumb">

                <a href="{{ route('client.home.index') }}">Trang chá»§</a>

                <span class="separator">>></span>

                <a href="{{ route('client.order.index') }}">Đơn hĂ ng cá»§a tĂ´i</a>

                <span class="separator">>></span>

                <span class="breadcrumb-current">Chi tiết đơn hĂ ng</span>

            </div>

        </section>



        <section class="xanhworld_order_detail">

            <div class="xanhworld_order_detail_container">

                <div class="xanhworld_order_detail_header">

                    <h1 class="xanhworld_order_detail_title">Chi tiết đơn hĂ ng</h1>

                    <a href="{{ route('client.order.index') }}" class="xanhworld_order_detail_back">← Quay lại danh sĂ¡ch</a>

                </div>



                <!-- Order Info -->

                <div class="xanhworld_order_detail_card">

                    <h2 class="xanhworld_order_detail_card_title">ThĂ´ng tin đơn hĂ ng</h2>

                    <div class="xanhworld_order_detail_info">

                        <div class="xanhworld_order_detail_info_item">

                            <span class="xanhworld_order_detail_info_label">MĂ£ đơn hĂ ng:</span>

                            <span class="xanhworld_order_detail_info_value">{{ $order->code }}</span>

                        </div>

                        <div class="xanhworld_order_detail_info_item">

                            <span class="xanhworld_order_detail_info_label">NgĂ y đặt:</span>

                            <span class="xanhworld_order_detail_info_value">{{ $order->created_at->format('d/m/Y H:i') }}</span>

                        </div>

                        <div class="xanhworld_order_detail_info_item">

                            <span class="xanhworld_order_detail_info_label">Trạng thĂ¡i đơn hĂ ng:</span>

                            <span class="xanhworld_order_detail_info_value status-{{ $order->status }}">

                                @if($order->status === 'pending')

                                    ⏳ Chờ xá»­ lĂ½

                                @elseif($order->status === 'processing')

                                    đŸ"" Đang xá»­ lĂ½

                                @elseif($order->status === 'completed')

                                    ✅ HoĂ n thĂ nh

                                @else

                                    ❌ ÄĂ£ há»§y

                                @endif

                            </span>

                        </div>

                        <div class="xanhworld_order_detail_info_item">

                            <span class="xanhworld_order_detail_info_label">Trạng thĂ¡i thanh toĂ¡n:</span>

                            <span class="xanhworld_order_detail_info_value payment-{{ $order->payment_status }}">

                                @if($order->payment_status === 'pending')

                                    đŸ'³ Chờ thanh toĂ¡n

                                @elseif($order->payment_status === 'paid')

                                    ✅ ÄĂ£ thanh toĂ¡n

                                @else

                                    ❌ Thanh toĂ¡n thất bại

                                @endif

                            </span>

                        </div>

                        <div class="xanhworld_order_detail_info_item">

                            <span class="xanhworld_order_detail_info_label">Trạng thĂ¡i giao hĂ ng:</span>

                            <span class="xanhworld_order_detail_info_value delivery-{{ $order->delivery_status }}">

                                @if($order->delivery_status === 'pending')

                                    đŸ"¦ Chờ giao hĂ ng

                                @elseif($order->delivery_status === 'shipped')

                                    đŸšš Đang giao hĂ ng    

                                @elseif($order->delivery_status === 'delivered')

                                    ✅ ÄĂ£ giao hĂ ng

                                @elseif($order->delivery_status === 'returned')

                                    ↩️ ÄĂ£ trả hĂ ng

                                @elseif($order->delivery_status === 'cancelled')

                                    ↩️ ÄĂ£ há»§y hĂ ng

                                @else

                                    ⏸️ Chưa xĂ¡c định

                                @endif

                            </span>

                        </div>

                    </div>

                </div>



                <!-- Receiver Info -->

                <div class="xanhworld_order_detail_card">

                    <h2 class="xanhworld_order_detail_card_title">ThĂ´ng tin người nhận</h2>

                    <div class="xanhworld_order_detail_info">

                        <div class="xanhworld_order_detail_info_item">

                            <span class="xanhworld_order_detail_info_label">Họ tĂªn:</span>

                            <span class="xanhworld_order_detail_info_value">{{ $order->receiver_name }}</span>

                        </div>

                        <div class="xanhworld_order_detail_info_item">

                            <span class="xanhworld_order_detail_info_label">Số điện thoại:</span>

                            <span class="xanhworld_order_detail_info_value">{{ $order->receiver_phone }}</span>

                        </div>

                        @if($order->receiver_email)

                        <div class="xanhworld_order_detail_info_item">

                            <span class="xanhworld_order_detail_info_label">Email:</span>

                            <span class="xanhworld_order_detail_info_value">{{ $order->receiver_email }}</span>

                        </div>

                        @endif

                        <div class="xanhworld_order_detail_info_item">

                            <span class="xanhworld_order_detail_info_label">Địa chỉ:</span>

                            <span class="xanhworld_order_detail_info_value">{{ $order->shipping_address }}</span>

                        </div>

                    </div>

                </div>



                <!-- Order Items -->

                <div class="xanhworld_order_detail_card">

                    <h2 class="xanhworld_order_detail_card_title">Sản phẩm trong đơn</h2>

                    <div class="xanhworld_order_detail_items">

                        <table class="xanhworld_order_detail_table">

                            <thead>

                                <tr>

                                    <th>Sản phẩm</th>

                                    <th>GiĂ¡</th>

                                    <th>Số lượng</th>

                                    <th>Thành tiền</th>

                                </tr>

                            </thead>

                            <tbody>

                                @foreach($order->items as $item)

                                    <tr>

                                        <td>

                                            <div class="xanhworld_order_detail_table_product">

                                                @php

                                                    $imageUrl = $item->variant?->primaryVariantImage

                                                        ? asset('clients/assets/img/clothes/' . $item->variant->primaryVariantImage->url)

                                                        : ($item->product->primaryImage

                                                            ? asset('clients/assets/img/clothes/' . $item->product->primaryImage->url)

                                                            : asset('clients/assets/img/clothes/no-image.webp'));

                                                @endphp

                                                <img src="{{ $imageUrl }}" alt="{{ $item->product->name }}" class="xanhworld_order_detail_table_product_img">

                                                <div class="xanhworld_order_detail_table_product_info">

                                                    <div class="xanhworld_order_detail_table_product_name">{{ $item->product->name }}</div>

                                                    @if($item->variant)

                                                        @php

                                                            $attrs = is_string($item->variant->attributes) 

                                                                ? json_decode($item->variant->attributes, true) 

                                                                : $item->variant->attributes;

                                                        @endphp

                                                        @if($attrs && is_array($attrs))

                                                            <div class="xanhworld_order_detail_table_product_attrs">

                                                                {{ collect($attrs)->map(fn($val, $key) => ucfirst($key) . ': ' . $val)->join(', ') }}

                                                            </div>

                                                        @endif

                                                    @endif

                                                </div>

                                            </div>

                                        </td>

                                        <td>{{ number_format($item->price, 0, ',', '.') }} Ä'</td>

                                        <td>{{ $item->quantity }}</td>

                                        <td><strong>{{ number_format($item->total_price, 0, ',', '.') }} Ä'</strong></td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>

                </div>



                <!-- Order Summary -->

                <div class="xanhworld_order_detail_card">

                    <h2 class="xanhworld_order_detail_card_title">TĂ³m tắt đơn hĂ ng</h2>

                    <div class="xanhworld_order_detail_summary">

                        @php

                            // TĂ­nh lại tạm tĂ­nh từ order_items (đảm bảo Ä'Ăºng vá»›i giĂ¡ Ä'Ă£ lưu)

                            $calculatedSubtotal = $order->items->sum('total_price');

                        @endphp

                        <div class="xanhworld_order_detail_summary_item">

                            <span class="xanhworld_order_detail_summary_label">Tạm tĂ­nh:</span>

                            <span class="xanhworld_order_detail_summary_value">{{ number_format($calculatedSubtotal, 0, ',', '.') }} Ä'</span>

                        </div>

                        <div class="xanhworld_order_detail_summary_item">

                            <span class="xanhworld_order_detail_summary_label">PhĂ­ vận chuyển:</span>

                            <span class="xanhworld_order_detail_summary_value">{{ number_format($order->shipping_fee, 0, ',', '.') }} Ä'</span>

                        </div>

                        @if($order->tax > 0)

                        <div class="xanhworld_order_detail_summary_item">

                            <span class="xanhworld_order_detail_summary_label">Thuế:</span>

                            <span class="xanhworld_order_detail_summary_value">{{ number_format($order->tax, 0, ',', '.') }} Ä'</span>

                        </div>

                        @endif

                        @if($order->discount > 0)

                        <div class="xanhworld_order_detail_summary_item">

                            <span class="xanhworld_order_detail_summary_label">Giảm giĂ¡:</span>

                            <span class="xanhworld_order_detail_summary_value">-{{ number_format($order->discount, 0, ',', '.') }} Ä'</span>

                        </div>

                        @endif

                        @if($order->voucher_discount > 0)

                        <div class="xanhworld_order_detail_summary_item">

                            <span class="xanhworld_order_detail_summary_label">Giảm giĂ¡ voucher ({{ $order->voucher_code }}):</span>

                            <span class="xanhworld_order_detail_summary_value">-{{ number_format($order->voucher_discount, 0, ',', '.') }} Ä'</span>

                        </div>

                        @endif

                        <div class="xanhworld_order_detail_summary_item xanhworld_order_detail_summary_total">

                            <span class="xanhworld_order_detail_summary_label">Tổng cộng:</span>

                            <span class="xanhworld_order_detail_summary_value">{{ number_format($order->final_price, 0, ',', '.') }} Ä'</span>

                        </div>

                    </div>

                </div>



                <!-- Payment & Shipping Info -->

                <div class="xanhworld_order_detail_card">

                    <h2 class="xanhworld_order_detail_card_title">Thanh toĂ¡n & Vận chuyển</h2>

                    <div class="xanhworld_order_detail_info">

                        <div class="xanhworld_order_detail_info_item">

                            <span class="xanhworld_order_detail_info_label">Phương thức thanh toĂ¡n:</span>

                            <span class="xanhworld_order_detail_info_value">

                                @if($order->payment_method === 'cod')

                                    đŸ'µ Thanh toĂ¡n khi nhận hĂ ng (COD)

                                @elseif($order->payment_method === 'bank_transfer')

                                    đŸ¦ Chuyển khoản ngĂ¢n hĂ ng

                                @elseif($order->payment_method === 'momo')

                                    đŸ'œ MoMo

                                @elseif($order->payment_method === 'zalopay')

                                    đŸ'™ ZaloPay

                                @elseif($order->payment_method === 'payos')

                                    đŸ'³ PayOS

                                @else

                                    {{ $order->payment_method }}

                                @endif

                            </span>

                        </div>

                        @if($order->transaction_code)

                        <div class="xanhworld_order_detail_info_item">

                            <span class="xanhworld_order_detail_info_label">MĂ£ giao dịch:</span>

                            <span class="xanhworld_order_detail_info_value">{{ $order->transaction_code }}</span>

                        </div>

                        @endif

                        <div class="xanhworld_order_detail_info_item">

                            <span class="xanhworld_order_detail_info_label">Đơn vị vận chuyển:</span>

                            <span class="xanhworld_order_detail_info_value">

                                @if($order->shipping_partner === 'ghn')

                                    đŸšš Giao HĂ ng Nhanh (GHN)

                                @elseif($order->shipping_partner === 'viettelpost')

                                    đŸ"® ViettelPost

                                @elseif($order->shipping_partner === 'ghtk')

                                    đŸ"¦ GHTK

                                @else

                                    {{ $order->shipping_partner ?? 'Chưa xĂ¡c định' }}

                                @endif

                            </span>

                        </div>

                        @if($order->shipping_tracking_code)

                        <div class="xanhworld_order_detail_info_item">

                            <span class="xanhworld_order_detail_info_label">MĂ£ vận đơn:</span>

                            <span class="xanhworld_order_detail_info_value">{{ $order->shipping_tracking_code }}</span>

                        </div>

                        @endif

                    </div>

                </div>



                @php

                    $ghnStatuses = config('ghn.shipping_statuses', []);

                    $shippingRaw = $order->shipping_raw_response ?? [];

                    $ghnPayload = $shippingRaw['ghn'] ?? $shippingRaw;

                    $clientShippingHistory = collect($shippingRaw['status_history'] ?? [])->sortBy(function ($item) {

                        return $item['created_at'] ?? now();

                    })->values();

                    $currentShippingStatusKey = $shippingRaw['current_status'] ?? null;

                    $currentShippingStatus = $currentShippingStatusKey

                        ? array_merge(['status' => $currentShippingStatusKey], $ghnStatuses[$currentShippingStatusKey] ?? [])

                        : null;

                @endphp



                @if($order->shipping_partner === 'ghn' && ($clientShippingHistory->count() || $currentShippingStatus))

                    <div class="xanhworld_order_detail_card xanhworld_order_tracking_card">

                        <div class="xanhworld_order_detail_card_header">

                            <h2 class="xanhworld_order_detail_card_title">Theo dõi trạng thĂ¡i vận chuyển (GHN)</h2>

                            @if($order->shipping_tracking_code)

                                <div class="d-flex flex-wrap align-items-center gap-2">

                                    <span class="xanhworld_order_tracking_code">MĂ£ vận đơn: {{ $order->shipping_tracking_code }}</span>

                                    <a href="{{ route('client.order.track', ['tracking_code' => $order->shipping_tracking_code]) }}" class="xanhworld_order_detail_btn xanhworld_order_detail_btn_secondary">

                                        đŸ" Tra cứu trá»±c tuyến

                                    </a>

                                </div>

                            @endif

                        </div>



                        <div class="xanhworld_order_tracking_status_current">

                            @if($currentShippingStatus)

                                <div class="xanhworld_order_tracking_status_label">

                                    Trạng thĂ¡i hiện tại:

                                    <span>{{ $currentShippingStatus['label'] ?? strtoupper($currentShippingStatus['status']) }}</span>

                                </div>

                                @if(!empty($currentShippingStatus['description']))

                                    <p>{{ $currentShippingStatus['description'] }}</p>

                                @endif

                            @else

                                <div class="xanhworld_order_tracking_status_label">

                                    Đơn hĂ ng Ä'ang chờ GHN cập nhật trạng thĂ¡i má»›i.

                                </div>

                            @endif

                        </div>



                        <div class="xanhworld_order_tracking_body">

                            <div class="xanhworld_order_tracking_timeline_wrapper">

                                <ul class="xanhworld_order_timeline">

                                    @forelse($clientShippingHistory as $log)

                                        <li class="xanhworld_order_timeline_item {{ $loop->last ? 'is-active' : '' }}">

                                            <div class="xanhworld_order_timeline_point"></div>

                                            <div class="xanhworld_order_timeline_content">

                                                <div class="xanhworld_order_timeline_title">

                                                    {{ $log['label'] ?? strtoupper($log['status'] ?? '') }}

                                                </div>

                                                <div class="xanhworld_order_timeline_meta">

                                                    {{ \Carbon\Carbon::parse($log['created_at'] ?? now())->format('d/m/Y H:i') }}

                                                    @if(!empty($log['created_by']))

                                                        • {{ $log['created_by'] }}

                                                    @endif

                                                </div>

                                                @if(!empty($log['description']))

                                                    <div class="xanhworld_order_timeline_desc">

                                                        {{ $log['description'] }}

                                                    </div>

                                                @endif

                                                @if(!empty($log['note']))

                                                    <div class="xanhworld_order_timeline_note">

                                                        <strong>Ghi chĂº:</strong> {{ $log['note'] }}

                                                    </div>

                                                @endif

                                            </div>

                                        </li>

                                    @empty

                                        <li class="xanhworld_order_timeline_item">

                                            <div class="xanhworld_order_timeline_content">

                                                <div class="xanhworld_order_timeline_desc">

                                                    GHN chưa cĂ³ cập nhật nĂ o cho đơn hĂ ng nĂ y.

                                                </div>

                                            </div>

                                        </li>

                                    @endforelse

                                </ul>

                            </div>

                            @if(!empty($ghnPayload['expected_delivery_time']))

                                <div class="xanhworld_order_tracking_expected">

                                    Dự kiến giao: {{ \Carbon\Carbon::parse($ghnPayload['expected_delivery_time'])->format('d/m/Y H:i') }}

                                </div>

                            @endif

                            @if(!empty($ghnPayload['total_fee']))

                                <div class="xanhworld_order_tracking_expected">

                                    PhĂ­ GHN: {{ number_format($ghnPayload['total_fee'], 0, ',', '.') }} Ä'

                                </div>

                            @endif

                        </div>

                    </div>

                @endif



                @if($order->customer_note)

                <div class="xanhworld_order_detail_card">

                    <h2 class="xanhworld_order_detail_card_title">Ghi chĂº</h2>

                    <div class="xanhworld_order_detail_note">

                        {{ $order->customer_note }}

                    </div>

                </div>

                @endif



                <div class="xanhworld_order_detail_actions">

                    <a href="{{ route('client.order.index') }}" class="xanhworld_order_detail_btn xanhworld_order_detail_btn_back">← Quay lại danh sĂ¡ch</a>

                </div>

            </div>

        </section>

    </div>



    @include('clients.templates.chat')

@endsection











