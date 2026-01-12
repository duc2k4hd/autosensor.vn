@extends('clients.layouts.master')

@php
    $account = auth('web')->user();
    $cartItems = $cartItems ?? collect();
    $cartSubtotal = $cartSubtotal ?? $cartItems->sum(fn ($item) => $item->subtotal);
    $shippingOptions = [
        'standard' => [
            'label' => 'Giao tiêu chuẩn (2-4 ngày)',
            'description' => 'Miễn phí cho đơn từ 500.000đ',
            'fee' => 0,
        ],
        'express' => [
            'label' => 'Giao nhanh 24-48h',
            'description' => 'Phụ thu 35.000đ toàn quốc',
            'fee' => 35000,
        ],
    ];
    $selectedShipping = old('shipping_method', 'standard');
@endphp

@section('title', 'Thanh toán - ' . ($settings->site_name ?? $settings->subname ?? 'AutoSensor Việt Nam'))

@section('head')
    <meta name="description"
          content="Hoàn tất đơn hàng tại AutoSensor Việt Nam: xác nhận thông tin giao hàng, lựa chọn thanh toán và nhận thiết bị tự động hóa chỉ trong một bước.">
    <meta name="robots" content="noindex, follow">
    <style>
        :root {
            --checkout-green: #0f5132;
            --checkout-light: #f4f7f5;
            --checkout-border: #e4e7ec;
            --checkout-dark: #1f2937;
        }

        .checkout-page {
            background: var(--checkout-light);
            padding: 30px 0 50px;
        }

        .checkout-container {
            width: min(1200px, 94vw);
            margin: 0 auto;
        }

        .checkout-breadcrumb {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 18px;
        }

        .checkout-breadcrumb a {
            color: var(--checkout-green);
            text-decoration: none;
        }

        .checkout-breadcrumb .separator {
            margin: 0 10px;
        }

        .checkout-header {
            margin-bottom: 28px;
        }

        .checkout-header h1 {
            font-size: clamp(26px, 3vw, 34px);
            color: var(--checkout-green);
            margin: 0 0 8px;
        }

        .checkout-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(0, 0.9fr);
            gap: 26px;
        }

        .checkout-card {
            background: #fff;
            border-radius: 24px;
            padding: 28px 30px;
            box-shadow: 0 25px 65px rgba(15, 81, 50, 0.08);
            margin: 10px 0
        }

        .checkout-card h2 {
            font-size: 20px;
            margin-bottom: 18px;
            color: var(--checkout-dark);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group label {
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            border: 1px solid var(--checkout-border);
            border-radius: 14px;
            padding: 12px 14px;
            font-size: 15px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--checkout-green);
            box-shadow: 0 0 0 3px rgba(15, 81, 50, 0.15);
        }

        .voucher-form {
            display: flex;
            gap: 10px;
            margin-top: 6px;
        }

        .voucher-form input {
            flex: 1;
            border: 1px solid var(--checkout-border);
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 15px;
        }

        .voucher-form button {
            border: none;
            border-radius: 12px;
            padding: 0 18px;
            background: var(--checkout-green);
            color: #fff;
            font-weight: 600;
            cursor: pointer;
        }

        .voucher-form button.loading {
            opacity: 0.7;
            pointer-events: none;
        }

        .voucher-remove {
            margin-top: 10px;
            border: none;
            background: transparent;
            color: var(--checkout-green);
            font-weight: 600;
            cursor: pointer;
            padding: 0;
        }

        #voucher_result {
            margin-top: 10px;
            font-size: 14px;
        }

        #voucher_suggestions .voucher-suggestion-item {
            border: 1px dashed var(--checkout-border);
            border-radius: 12px;
            padding: 10px 14px;
            background: #f7fdf9;
        }

        .voucher-hint {
            margin-top: 10px;
            font-size: 13px;
            color: #6b7280;
        }

        .voucher-guest {
            text-align: center;
        }

        .voucher-guest p {
            color: #4b5563;
            margin-bottom: 12px;
        }

        .voucher-guest a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 18px;
            border-radius: 999px;
            background: var(--checkout-green);
            color: #fff;
            font-weight: 600;
            text-decoration: none;
        }

        .saved-address-list {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin: 18px 0;
        }

        .saved-address-list button {
            border: 1px solid var(--checkout-border);
            border-radius: 12px;
            padding: 10px 14px;
            background: #fff;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 4px;
        }

        .saved-address-list button strong {
            color: var(--checkout-green);
        }

        .saved-address-list button.active {
            border-color: var(--checkout-green);
            background: #f0fdf4;
            box-shadow: 0 0 0 2px rgba(15, 81, 50, 0.12);
        }

        .radio-card {
            border: 1px solid var(--checkout-border);
            border-radius: 18px;
            padding: 14px 16px;
            display: flex;
            gap: 12px;
            align-items: flex-start;
            cursor: pointer;
            transition: border-color 0.2s, background 0.2s;
        }

        .radio-card input {
            margin-top: 4px;
        }

        .radio-card.active {
            border-color: var(--checkout-green);
            background: rgba(15, 81, 50, 0.04);
        }

        .summary-items {
            display: flex;
            flex-direction: column;
            gap: 18px;
            margin-bottom: 22px;
        }

        .summary-item {
            display: flex;
            gap: 14px;
            align-items: center;
        }

        .summary-item img {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            object-fit: cover;
            background: #f3f4f6;
        }

        .summary-item h4 {
            margin: 0 0 4px;
            font-size: 15px;
            color: var(--checkout-dark);
        }

        .summary-item span {
            font-size: 14px;
            color: #6b7280;
        }

        .summary-line {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            color: #4b5563;
        }

        .summary-line.total {
            font-size: 20px;
            font-weight: 700;
            color: var(--checkout-green);
            margin-top: 12px;
        }

        .checkout-submit {
            width: 100%;
            border: none;
            border-radius: 16px;
            padding: 16px;
            font-size: 17px;
            font-weight: 600;
            color: #fff;
            background: linear-gradient(135deg, #0f5132 0%, #198754 60%, #20c997 100%);
            cursor: pointer;
            margin-top: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .checkout-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 18px 40px rgba(15, 81, 50, 0.2);
        }

        .checkout-submit:disabled {
            opacity: 0.55;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .note-counter {
            text-align: right;
            font-size: 13px;
            color: #6b7280;
        }

        .field-error {
            font-size: 13px;
            color: #c62828;
        }

        @media (max-width: 960px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }

            .checkout-card {
                padding: 20px;
            }
        }

        .page-loader-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.3s;
            opacity: 1;
        }

        .page-loader-overlay[hidden] {
            opacity: 0;
            pointer-events: none;
        }

        .loader-spinner {
            border: 5px solid #f3f3f3; /* Light grey */
            border-top: 5px solid var(--checkout-green);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
@endsection

@section('foot')
    @parent
    <script>
        window.checkoutConfig = {
            canUseVoucher: {{ Auth::check() ? 'true' : 'false' }},
        };
    </script>
    <script src="{{ asset('clients/assets/js/order.js') }}?v={{ filemtime(public_path('clients/assets/js/order.js')) }}"></script>
@endsection

@section('content')
    <div class="page-loader-overlay" id="page_loader_overlay" hidden>
        <div class="loader-spinner"></div>
    </div>
    <section class="checkout-page">
        <div class="checkout-container">
            <div class="checkout-breadcrumb">
                <a href="{{ route('client.home.index') }}">Trang chủ</a>
                    <span class="separator">>></span>
                <a href="{{ route('client.cart.index') }}">Giỏ hàng</a>
                    <span class="separator">>></span>
                <span>Thanh toán</span>
            </div>

            {{-- <header class="checkout-header">
                <h1>Hoàn tất đơn hàng</h1>
                <p style="color: #6b7280; max-width: 640px;">
                    Xác nhận thông tin giao nhận và phương thức thanh toán. Đội ngũ AutoSensor Việt Nam sẽ liên hệ và chăm sóc đơn hàng của bạn ngay khi nhận được.
                </p>
            </header> --}}
            @if ((isset($settings) && (data_get($settings, 'enable_checkout', true) === true)) && $cartItems->isNotEmpty())

                @if ($errors->any())
                    <div style="background:#fdecea;border-radius:16px;padding:14px 18px;margin-bottom:20px;color:#b91c1c;">
                        <strong>Có lỗi xảy ra:</strong>
                        <ul style="margin:10px 0 0 18px;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('client.checkout.store') }}" method="POST" id="checkout-form" autocomplete="off">
                            @csrf
                    @php
                        $activeVoucherCode = old('voucher_code', $appliedVoucher['code'] ?? null);
                        $activeVoucherDiscount = (float) old('voucher_discount', $appliedVoucher['discount'] ?? 0);
                    @endphp
                    <div class="checkout-grid">
                        <div class="checkout-left">
                            <div class="checkout-card">
                                <h2>Thông tin giao nhận</h2>
                                        @auth
                                    @if(($addresses ?? collect())->isNotEmpty())
                                        <div class="saved-address-list">
                                            @foreach ($addresses as $address)
                                                @php
                                                    $addressPayload = [
                                                        'id' => $address->id,
                                                        'full_name' => $address->full_name,
                                                        'phone_number' => $address->phone_number,
                                                        'detail_address' => $address->detail_address,
                                                        'province' => $address->province,
                                                        'district' => $address->district,
                                                        'ward' => $address->ward,
                                                        'province_code' => $address->province_code,
                                                        'district_code' => $address->district_code,
                                                        'ward_code' => $address->ward_code,
                                                        'postal_code' => $address->postal_code,
                                                        'country' => $address->country,
                                                        'is_default' => (bool) $address->is_default,
                                                    ];
                                                @endphp
                                                <button onclick="showCustomToast('Quý khách vui lòng chờ chút để hệ thống sử lý thông tin!')" type="button"
                                                        class="saved-address"
                                                        data-address='@json($addressPayload)'>
                                                    <strong>{{ $address->full_name }}</strong>
                                                    <span>{{ $address->detail_address }}</span>
                                                    <span style="color:#6b7280; font-size:13px;">{{ $address->district }}, {{ $address->province }}</span>
                                                </button>
                                            @endforeach
                                                </div>
                                        @endif
                                    @endauth
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="fullname">Họ và tên *</label>
                                        <input type="text" id="fullname" name="fullname"
                                            value="{{ old('fullname', $defaultAddress->full_name ?? $account?->name) }}"
                                            autocomplete="off" autocapitalize="none" spellcheck="false"
                                            inputmode="text" required>
                                        @error('fullname')
                                            <span class="field-error">{{ $message }}</span>
                                        @enderror
                                        </div>
                                    <div class="form-group">
                                        <label for="email">Email *</label>
                                        <input type="email" id="email" name="email"
                                            value="{{ old('email', $account?->email) }}"
                                            autocomplete="off" autocapitalize="none" spellcheck="false"
                                            inputmode="email" required>
                                        @error('email')
                                            <span class="field-error">{{ $message }}</span>
                                        @enderror
                                        </div>
                                    <div class="form-group">
                                        <label for="phone">Số điện thoại *</label>
                                        <input type="tel" id="phone" name="phone"
                                            value="{{ old('phone', $defaultAddress->phone_number ?? '') }}"
                                            autocomplete="off" inputmode="tel" pattern="[0-9]*"
                                            autocapitalize="none" spellcheck="false" required>
                                        @error('phone')
                                            <span class="field-error">{{ $message }}</span>
                                        @enderror
                                        </div>
                                    <div class="form-group" style="grid-column: span 1;">
                                        <label for="address">Địa chỉ chi tiết *</label>
                                        <input type="text" id="address" name="address"
                                            value="{{ old('address', $defaultAddress->detail_address ?? '') }}"
                                            autocomplete="new-password" autocapitalize="none" spellcheck="false"
                                            inputmode="text" aria-autocomplete="none"
                                            data-lpignore="true" data-1p-ignore="true" required>
                                        @error('address')
                                            <span class="field-error">{{ $message }}</span>
                                        @enderror
                                        </div>
                                    <div class="form-group">
                                        <label>Tỉnh/Thành phố *</label>
                                        <select name="provinceId"
                                                class="autosensor_main_checkout_flex_province"
                                                onchange="onProvinceChange(this)"
                                                data-selected="{{ old('provinceId', $defaultAddress->province_code ?? '') }}"
                                                required>
                                            <option value="">{{ old('provinceId', $defaultAddress->province_code ?? '') ? 'Đang tải...' : 'Chọn Tỉnh/Thành Phố' }}</option>
                                            </select>
                                        <input type="hidden" name="province" id="checkout_province_name"
                                            value="{{ old('province', $defaultAddress->province ?? '') }}">
                                        @error('province')
                                            <span class="field-error">{{ $message }}</span>
                                        @enderror
                                        @error('provinceId')
                                            <span class="field-error">{{ $message }}</span>
                                        @enderror
                                        </div>
                                    <div class="form-group">
                                        <label>Quận/Huyện *</label>
                                        <select name="districtId"
                                                class="autosensor_main_checkout_flex_district"
                                                onchange="onDistrictChange(this)"
                                                data-selected="{{ old('districtId', $defaultAddress->district_code ?? '') }}"
                                                required>
                                            <option value="">{{ old('districtId', $defaultAddress->district_code ?? '') ? 'Đang tải...' : 'Chọn Quận/Huyện' }}</option>
                                            </select>
                                        <input type="hidden" name="district" id="checkout_district_name"
                                            value="{{ old('district', $defaultAddress->district ?? '') }}">
                                        @error('district')
                                            <span class="field-error">{{ $message }}</span>
                                        @enderror
                                        @error('districtId')
                                            <span class="field-error">{{ $message }}</span>
                                        @enderror
                                        </div>
                                    <div class="form-group">
                                        <label>Phường/Xã *</label>
                                        <select name="wardId"
                                                class="autosensor_main_checkout_flex_ward"
                                                onchange="onWardChange(this)"
                                                data-selected="{{ old('wardId', $defaultAddress->ward_code ?? '') }}"
                                                required>
                                            <option value="">{{ old('wardId', $defaultAddress->ward_code ?? '') ? 'Đang tải...' : 'Chọn Phường/Xã' }}</option>
                                            </select>
                                        <input type="hidden" name="ward" id="checkout_ward_name"
                                            value="{{ old('ward', $defaultAddress->ward ?? '') }}">
                                        @error('ward')
                                            <span class="field-error">{{ $message }}</span>
                                        @enderror
                                        @error('wardId')
                                            <span class="field-error">{{ $message }}</span>
                                        @enderror
                                        </div>
                                            </div>
                                        </div>

                            <div class="checkout-card">
                                <h2>Phương thức giao hàng</h2>
                                <p style="color:#6b7280; margin-bottom:18px;">Chọn tỉnh/thành, quận/huyện và phường/xã để hệ thống lấy phí vận chuyển từ GHN. Kết quả sẽ tự động cập nhật tại đây.</p>
                                <div class="autosensor_main_checkout_options">
                                    <div style="
                                                padding: 16px;
                                                border: 1px dashed #d0d0d0;
                                        border-radius: 12px;
                                                background: #fafafa;
                                                text-align: center;
                                        color: #0f5132;
                                                font-size: 15px;
                                                line-height: 1.6;
                                    ">
                                        🚚 <strong>Chưa có phương thức giao hàng</strong><br>
                                            <span style="color:#666;">
                                            Vui lòng chọn đủ <b>Tỉnh/Thành</b>, <b>Quận/Huyện</b> và <b>Phường/Xã</b> để hiển thị các lựa chọn giao hàng phù hợp.
                                            </span>
                                        </div>
                                    </div>
                                </div>

                            <div class="checkout-card">
                                <h2>Phương thức thanh toán</h2>
                                <div style="display: flex; flex-direction: column; gap: 12px;">
                                    <label class="radio-card {{ old('payment', 'cod') === 'cod' ? 'active' : '' }}">
                                        <input type="radio" name="payment" value="cod" {{ old('payment', 'cod') === 'cod' ? 'checked' : '' }}>
                                        <div>
                                            <strong>Thanh toán khi nhận hàng (COD)</strong>
                                            <p style="margin: 4px 0 0; color:#6b7280; font-size: 14px;">Kiểm tra thiết bị trước khi thanh toán, miễn phí thu hộ.</p>
                                        </div>
                                    </label>
                                    <label class="radio-card {{ old('payment') === 'bank_transfer' ? 'active' : '' }}">
                                        <input type="radio" name="payment" value="bank_transfer" {{ old('payment') === 'bank_transfer' ? 'checked' : '' }}>
                                        <div>
                                            <strong>Chuyển khoản ngân hàng</strong>
                                            <p style="margin: 4px 0 0; color:#6b7280; font-size: 14px;">Nhận thông tin tài khoản AutoSensor Việt Nam sau khi đặt đơn.</p>
                                        </div>
                                    </label>
                                            </div>
                                        </div>

                            <div class="checkout-card">
                                <h2>Ghi chú cho chuyên viên AutoSensor Việt Nam (tuỳ chọn)</h2>
                                <div class="form-group">
                                    <textarea name="customer_note" rows="4" maxlength="500" autocomplete="off"
                                            autocapitalize="none" spellcheck="false"
                                            placeholder="Ví dụ: Giao giờ hành chính, đặt thiết bị tại sảnh, cần hóa đơn VAT...">{{ old('customer_note') }}</textarea>
                                    <div class="note-counter"><span id="note-counter">0</span>/500 ký tự</div>
                                        </div>
                                    </div>

                            <div class="checkout-hidden-fields" style="display:none;">
                                <input type="hidden" id="checkout_province_id" value="{{ old('provinceId', $defaultAddress->province_code ?? '') }}">
                                <input type="hidden" id="checkout_district_id" value="{{ old('districtId', $defaultAddress->district_code ?? '') }}">
                                <input type="hidden" id="checkout_ward_id" value="{{ old('wardId', $defaultAddress->ward_code ?? '') }}">

                                <input type="hidden" name="serviceId" id="checkout_service_id" value="{{ old('serviceId') }}">
                                <input type="hidden" name="serviceTypeId" id="checkout_service_type_id" value="{{ old('serviceTypeId') }}">

                                <input type="hidden" name="shipping_fee_original" id="checkout_shipping_fee_original" value="{{ old('shipping_fee_original', $appliedVoucher['original_shipping_fee'] ?? 0) }}">
                                <input type="hidden" name="shipping" id="checkout_shipping_value" value="{{ old('shipping', $appliedVoucher['shipping_fee'] ?? 0) }}">
                                <input type="hidden" name="shipping_fee" id="checkout_shipping_fee_value" value="{{ old('shipping_fee', $appliedVoucher['shipping_fee'] ?? 0) }}">
                                <input type="hidden" name="shipping_label" id="checkout_shipping_label" value="{{ old('shipping_label', 'GHN') }}">

                                <input type="hidden" name="postal_code" value="{{ old('postal_code', $defaultAddress->postal_code ?? '00000') }}">
                                <input type="hidden" name="country" value="{{ old('country', $defaultAddress->country ?? 'Việt Nam') }}">

                                <input type="hidden" name="subtotal" id="checkout_subtotal_value" value="{{ old('subtotal', $cartSubtotal) }}">
                                <input type="hidden" name="total" id="checkout_total_value" value="{{ old('total', $appliedVoucher['total'] ?? $cartSubtotal) }}">

                                <input type="hidden" name="voucher_code" id="voucher_code_input" value="{{ $activeVoucherCode }}">
                                <input type="hidden" name="voucher_discount" id="voucher_discount_input" value="{{ $activeVoucherDiscount }}">
                                </div>
                                    </div>

                        <div class="checkout-right">
                            <div class="checkout-card">
                                <h2>Đơn hàng của bạn</h2>
                                <div class="summary-items">
                                    @foreach ($cartItems as $item)
                                        @php
                                            $optionsArray = $item->options;
                                            if (is_string($optionsArray)) {
                                                $decoded = json_decode($optionsArray, true);
                                                $optionsArray = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
                                            }
                                                                @endphp
                                        <div
                                            class="summary-item"
                                            data-product-id="{{ $item->product_id }}"
                                            data-category-id="{{ $item->product?->primary_category_id }}"
                                            data-item-name="{{ $item->product?->name ?? 'Sản phẩm đã xóa' }}"
                                            data-item-price="{{ $item->price }}"
                                            data-item-qty="{{ $item->quantity }}"
                                            data-item-total="{{ $item->subtotal }}"
                                            data-item-options='@json($optionsArray ?? [])'>
                                            @php
                                                $imageUrl = $item->variant?->primaryVariantImage
                                                    ? asset('clients/assets/img/clothes/' . $item->variant->primaryVariantImage->url)
                                                    : ($item->product?->primaryImage
                                                        ? asset('clients/assets/img/clothes/' . $item->product->primaryImage->url)
                                                        : asset('clients/assets/img/clothes/no-image.webp'));
                                            @endphp
                                            <img src="{{ $imageUrl }}" alt="{{ $item->product?->name }}">
                                            <div style="flex:1;">
                                                <h4>{{ $item->product?->name ?? 'Sản phẩm đã xóa' }}</h4>
                                                @if($item->variant)
                                                    <span class="spec-attr variant-name" style="display:block;font-weight: 600; color: #059669; margin-bottom: 4px;">{{ $item->variant->name }}</span>
                                                @endif
                                        <span>{{ number_format($item->price, 0, ',', '.') }}₫ x {{ $item->quantity }}</span>
                                                @if($item->options)
                                                    <small style="display:block;color:#9ca3af;font-size:13px;">
                                                        {{ is_array($item->options) ? collect($item->options)->map(fn($value, $key) => ucfirst($key).': '.$value)->join(', ') : $item->options }}
                                                    </small>
                                                                @endif
                                                        </div>
                                            <strong>{{ number_format($item->subtotal, 0, ',', '.') }}₫</strong>
                                                        </div>
                                    @endforeach
                                                    </div>
                                <div class="summary-line">
                                    <span>Tạm tính</span>
                                    <strong id="summary-subtotal">{{ number_format(old('subtotal', $cartSubtotal), 0, ',', '.') }}₫</strong>
                                                            </div>
                                <div class="summary-line">
                                    <span>Phí vận chuyển</span>
                                    <strong id="summary-shipping">{{ number_format(old('shipping_fee', $appliedVoucher['shipping_fee'] ?? 0), 0, ',', '.') }}₫</strong>
                                                            </div>
                                <div class="summary-line" id="voucher_discount_row" style="display: {{ $activeVoucherDiscount > 0 ? 'flex' : 'none' }}">
                                    <span>Voucher</span>
                                    <strong id="checkout_voucher_discount">
                                        {{ $activeVoucherDiscount > 0 ? '-'.number_format($activeVoucherDiscount, 0, ',', '.').'₫' : '0₫' }}
                                    </strong>
                                                            </div>
                                <div class="summary-line total">
                                    <span>Tổng thanh toán</span>
                                    <span id="summary-total">{{ number_format(old('total', $appliedVoucher['total'] ?? $cartSubtotal), 0, ',', '.') }}₫</span>
                                </div>
                                <button type="submit" class="checkout-submit">
                                    Đặt hàng ngay
                                </button>
                                                        </div>

                                        @auth
                                <div class="checkout-card">
                                    <h2>Mã giảm giá</h2>
                                @php
                                    $hasShippingFee = old('shipping_fee_original', $appliedVoucher['original_shipping_fee'] ?? 0) > 0;
                                @endphp
                                <div class="voucher-form">
                                        <input type="text" id="voucher_code" placeholder="Nhập mã voucher (VD: SALE10)" autocomplete="off">
                                    <button type="button" id="apply_voucher_btn">Áp dụng</button>
                            </div>
                                    <p class="voucher-hint" id="voucher_hint">Chọn địa chỉ để hệ thống lấy phí ship trước khi nhập mã.</p>
                                    <button type="button" id="remove_voucher_btn" class="voucher-remove" style="{{ $activeVoucherCode ? '' : 'display:none;' }}">Hủy mã</button>
                                    <div id="voucher_result" style="display:none;">
                                        <p class="voucher_success" style="display:none;color:#15803d;font-weight:600;margin-top:8px;"></p>
                                        <p class="voucher_error" style="display:none;color:#c2410c;font-weight:600;margin-top:8px;"></p>
                        </div>
                                    <div id="voucher_info" style="display: {{ $activeVoucherCode ? 'flex' : 'none' }};justify-content:space-between;align-items:center;margin-top:12px;padding:10px 12px;border:1px solid #c6f6d5;border-radius:8px;background:#f0fdf4;">
                                        <div>
                                            <div class="voucher_name" style="font-weight:600;color:#065f46;">{{ $activeVoucherCode }}</div>
                                            <div class="voucher_discount" style="font-size:13px;color:#047857;">
                                                {{ $activeVoucherDiscount > 0 ? '-'.number_format($activeVoucherDiscount, 0, ',', '.').'₫' : '' }}
                    </div>
            </div>
                                        <button type="button" class="voucher-remove" id="voucher_info_remove">Xóa</button>
                                </div>
                                    <div id="voucher_suggestions" style="display:none;margin-top:12px;">
                                        <div style="font-weight:600;color:#0f5132;margin-bottom:6px;">Gợi ý cho bạn</div>
                                        <div id="voucher_suggestions_list" style="display:flex;flex-direction:column;gap:8px;"></div>
                                                </div>
                                            </div>
                            @else
                                <div class="checkout-card voucher-guest">
                                    <h2>Mã giảm giá</h2>
                                    <p>Đăng nhập để sử dụng mã giảm giá và theo dõi lịch sử voucher.</p>
                                    <a href="{{ route('client.auth.login') }}">Đăng nhập ngay</a>
                                        </div>
                            @endauth

                            <div class="checkout-card">
                                <h2>Cam kết từ AutoSensor Việt Nam</h2>
                                <ul style="margin:0;padding-left:20px;color:#4b5563;line-height:1.8;font-size:14px;">
                                    <li>Thiết bị chính hãng, đúng model và thông số kỹ thuật miêu tả.</li>
                                    <li>Hỗ trợ đổi trả trong 30 ngày nếu lỗi kỹ thuật.</li>
                                    <li>Có hướng dẫn lắp đặt và vận hành chi tiết cho từng thiết bị.</li>
                                    <li>Hoàn tiền nếu thiết bị bị hư hại do vận chuyển.</li>
                                </ul>
                                </div>
                            </div>
                        </div>
                </form>
            @else
                <div class="checkout-card">
                    <h2>Chức năng này hiện đang bảo trì hoặc đang có lỗi xảy ra</h2>
                    <p>Vui lòng liên hệ quản trị viên để biết thêm chi tiết.</p>
                    <a href="{{ route('client.home.contact') }}">Liên hệ quản trị viên để mua hàng trực tiếp. Mong quý khách thông cảm.</a>
                </div>
            @endif
        </div>
    </section>
@endsection
