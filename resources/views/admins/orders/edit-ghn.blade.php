@extends('admins.layouts.master')

@section('title', 'Cập nhật vận đơn GHN')
@section('page-title', '🚚 Cập nhật vận đơn GHN')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/order-icon.png') }}" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css">
@endpush

@push('styles')
    <style>
        .ghn-edit-layout {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 20px;
            align-items: start;
        }
        
        .ghn-edit-main {
            min-width: 0;
        }
        
        .ghn-edit-sidebar {
            position: sticky;
            top: 20px;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }
        
        .sidebar-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: 1px solid #e5e7eb;
        }
        
        .sidebar-card h4 {
            margin: 0 0 15px 0;
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            padding-bottom: 10px;
            border-bottom: 2px solid #f3f4f6;
        }
        
        .sidebar-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .sidebar-actions .btn {
            width: 100%;
            justify-content: center;
            font-size: 13px;
            padding: 8px 12px;
        }
        
        .sidebar-info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .sidebar-info-item:last-child {
            border-bottom: none;
        }
        
        .sidebar-info-label {
            font-size: 13px;
            color: #6b7280;
            font-weight: 500;
        }
        
        .sidebar-info-value {
            font-size: 13px;
            color: #1f2937;
            font-weight: 600;
            text-align: right;
            max-width: 60%;
            word-break: break-word;
        }
        
        @media (max-width: 1200px) {
            .ghn-edit-layout {
                grid-template-columns: 1fr;
            }
            
            .ghn-edit-sidebar {
                position: relative;
                top: 0;
                max-height: none;
            }
        }
        
        .ghn-card {
            background:#fff;
            border-radius:10px;
            padding:20px;
            box-shadow:0 1px 6px rgba(15,23,42,0.08);
            margin-bottom:20px;
        }
        .ghn-grid {
            display:grid;
            grid-template-columns:repeat(auto-fit, minmax(260px,1fr));
            gap:16px;
        }
        .form-group label {
            font-weight:600;
            font-size:13px;
            color:#475569;
        }
        .form-control {
            border-radius:8px;
        }
    </style>
@endpush

@section('content')
    <div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <div>
                <h2 style="margin:0;">Cập nhật vận đơn GHN</h2>
                <p style="margin:4px 0 0;color:#64748b;">Đơn hàng: <strong>{{ $order->code }}</strong> | Mã vận đơn: <strong>{{ $order->shipping_tracking_code }}</strong></p>
            </div>
            <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-secondary">↩️ Quay về chi tiết</a>
        </div>

        <div class="ghn-edit-layout">
            <!-- Cột trái: Form chính -->
            <div class="ghn-edit-main">
                <form action="{{ route('admin.orders.update-ghn', $order) }}" method="POST" id="ghn-update-form">
                    @csrf
                    @method('PUT')

            <div class="ghn-card">
                <h4>Thông tin người nhận</h4>
                <div class="ghn-grid">
                    <div class="form-group">
                        <label>Họ tên *</label>
                        <input type="text" name="to_name" class="form-control" value="{{ old('to_name', $order->receiver_name) }}" required>
                        @error('to_name')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label>Số điện thoại *</label>
                        <input type="text" name="to_phone" class="form-control" value="{{ old('to_phone', $order->receiver_phone) }}" required>
                        @error('to_phone')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                </div>
                <div class="form-group">
                    <label>Địa chỉ chi tiết *</label>
                    <input type="text" name="to_address" class="form-control" value="{{ old('to_address', $order->shipping_address) }}" required>
                    @error('to_address')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
                <div class="ghn-grid">
                    <div class="form-group">
                        <label>Tỉnh/Thành phố</label>
                        <select name="to_province_id" id="ghn-province-select" class="form-control">
                            <option value="">{{ old('to_province_id', $order->shipping_province_id) ? 'Đang tải...' : '-- Chọn tỉnh/thành --' }}</option>
                        </select>
                        @error('to_province_id')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label>Quận/Huyện *</label>
                        <select name="to_district_id" id="ghn-district-select" class="form-control" required disabled>
                            <option value="">-- Chọn quận/huyện --</option>
                        </select>
                        @error('to_district_id')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label>Phường/Xã *</label>
                        <select name="to_ward_code" id="ghn-ward-select" class="form-control" required disabled>
                            <option value="">-- Chọn phường/xã --</option>
                        </select>
                        @error('to_ward_code')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                </div>
            </div>

            <div class="ghn-card">
                <h4>Thông tin vận đơn</h4>
                <div class="ghn-grid">
                    <div class="form-group">
                        <label>Người trả phí vận chuyển *</label>
                        <select name="payment_type_id" class="form-control" required>
                            <option value="1" {{ old('payment_type_id', $order->payment_method === 'cod' ? 2 : 1) == 1 ? 'selected' : '' }}>Người bán (Shop)</option>
                            <option value="2" {{ old('payment_type_id', $order->payment_method === 'cod' ? 2 : 1) == 2 ? 'selected' : '' }}>Người nhận</option>
                        </select>
                        @error('payment_type_id')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label>Ghi chú cho shipper</label>
                        <input type="text" name="note" class="form-control" value="{{ old('note', $order->admin_note) }}">
                        @error('note')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label>Yêu cầu khi giao *</label>
                        <select name="required_note" class="form-control" required>
                            @php
                                $requiredNote = old('required_note', 'KHONGCHOXEMHANG');
                            @endphp
                            <option value="KHONGCHOXEMHANG" {{ $requiredNote === 'KHONGCHOXEMHANG' ? 'selected' : '' }}>KHÔNG CHO XEM HÀNG</option>
                            <option value="CHOXEMHANGKHONGTHU" {{ $requiredNote === 'CHOXEMHANGKHONGTHU' ? 'selected' : '' }}>CHO XEM HÀNG (KHÔNG THỬ)</option>
                            <option value="CHOTHUHANG" {{ $requiredNote === 'CHOTHUHANG' ? 'selected' : '' }}>CHO THỬ HÀNG</option>
                        </select>
                        @error('required_note')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label>Thu hộ (COD)</label>
                        <input type="number" name="cod_amount" class="form-control" value="{{ old('cod_amount', $order->payment_method === 'cod' ? (int) $order->final_price : 0) }}">
                        @error('cod_amount')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                </div>
                <div class="ghn-grid">
                    <div class="form-group">
                        <label>Khối lượng (gram)</label>
                        <input type="number" name="weight" class="form-control" value="{{ old('weight') }}">
                        @error('weight')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label>Chiều dài (cm)</label>
                        <input type="number" name="length" class="form-control" value="{{ old('length') }}">
                        @error('length')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label>Chiều rộng (cm)</label>
                        <input type="number" name="width" class="form-control" value="{{ old('width') }}">
                        @error('width')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label>Chiều cao (cm)</label>
                        <input type="number" name="height" class="form-control" value="{{ old('height') }}">
                        @error('height')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                </div>
            </div>

                </form>
            </div>

            <!-- Cột phải: Sidebar với quick info và actions -->
            <div class="ghn-edit-sidebar">
                <!-- Quick Info -->
                <div class="sidebar-card">
                    <h4>Thông tin nhanh</h4>
                    <div class="sidebar-info-item">
                        <span class="sidebar-info-label">Mã đơn:</span>
                        <span class="sidebar-info-value">{{ $order->code }}</span>
                    </div>
                    <div class="sidebar-info-item">
                        <span class="sidebar-info-label">Mã vận đơn:</span>
                        <span class="sidebar-info-value" style="color:#1d4ed8;">{{ $order->shipping_tracking_code }}</span>
                    </div>
                    <div class="sidebar-info-item">
                        <span class="sidebar-info-label">Người nhận:</span>
                        <span class="sidebar-info-value">{{ $order->receiver_name }}</span>
                    </div>
                    <div class="sidebar-info-item">
                        <span class="sidebar-info-label">Số điện thoại:</span>
                        <span class="sidebar-info-value">{{ $order->receiver_phone }}</span>
                    </div>
                    <div class="sidebar-info-item">
                        <span class="sidebar-info-label">Phương thức:</span>
                        <span class="sidebar-info-value">
                            @if($order->payment_method === 'cod') COD
                            @elseif($order->payment_method === 'bank_transfer') Chuyển khoản
                            @else {{ $order->payment_method }}
                            @endif
                        </span>
                    </div>
                    <div class="sidebar-info-item">
                        <span class="sidebar-info-label">Tổng tiền:</span>
                        <span class="sidebar-info-value" style="color:#15803d;font-size:14px;">
                            <strong>{{ number_format($order->final_price) }} đ</strong>
                        </span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="sidebar-card">
                    <h4>Thao tác</h4>
                    <div class="sidebar-actions">
                        <button type="submit" form="ghn-update-form" class="btn btn-primary">💾 Cập nhật GHN</button>
                        <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-secondary">↩️ Quay về chi tiết</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initGhnAddressSelectors({
                provinceSelectId: 'ghn-province-select',
                districtSelectId: 'ghn-district-select',
                wardSelectId: 'ghn-ward-select',
                oldProvince: '{{ old('to_province_id', $order->shipping_province_id) ?? '' }}',
                oldDistrict: '{{ old('to_district_id', $order->shipping_district_id) ?? '' }}',
                oldWard: '{{ old('to_ward_code', $order->shipping_ward_id) ?? '' }}'
            });
        });

        function initGhnAddressSelectors(config) {
            const { provinceSelectId, districtSelectId, wardSelectId, oldProvince, oldDistrict, oldWard } = config;
            const provinceEl = document.getElementById(provinceSelectId);
            const districtEl = document.getElementById(districtSelectId);
            const wardEl = document.getElementById(wardSelectId);
            if (!provinceEl || !districtEl || !wardEl) return;

            let provinceTS = null;
            let districtTS = null;
            let wardTS = null;

            const resetSelect = (select, placeholder, disable = true) => {
                select.innerHTML = `<option value="">${placeholder}</option>`;
                select.disabled = disable;
            };

            const recreateTomSelect = (instance, select, placeholder) => {
                if (instance) instance.destroy();
                return new TomSelect(select, {
                    placeholder,
                    allowEmptyOption: true,
                    searchField: ['text']
                });
            };

            const fetchJson = (url, options = {}) => {
                return fetch(url, options)
                    .then(res => res.json())
                    .then(json => json.data || []);
            };

            const loadProvinces = async (selectedValue) => {
                resetSelect(provinceEl, 'Đang tải tỉnh/thành...', true);
                try {
                    const data = await fetchJson('/api/v1/ghn/province');
                    provinceEl.innerHTML = '<option value="">-- Chọn tỉnh/thành --</option>';
                    data.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.provinceId;
                        option.textContent = item.provinceName;
                        provinceEl.appendChild(option);
                    });
                    provinceEl.disabled = false;
                    provinceTS = recreateTomSelect(provinceTS, provinceEl, 'Chọn tỉnh/thành');
                    provinceTS.on('change', value => loadDistricts(value));
                    if (selectedValue) {
                        provinceTS.setValue(String(selectedValue), true);
                        await loadDistricts(selectedValue, oldDistrict);
                    }
                } catch (error) {
                    console.error(error);
                    resetSelect(provinceEl, 'Không thể tải tỉnh/thành', true);
                }
            };

            const loadDistricts = async (provinceId, selectedValue = null) => {
                resetSelect(districtEl, '-- Chọn quận/huyện --', true);
                resetSelect(wardEl, '-- Chọn phường/xã --', true);
                if (!provinceId) {
                    if (districtTS) districtTS.destroy();
                    if (wardTS) wardTS.destroy();
                    return;
                }
                try {
                    const data = await fetchJson(`/api/v1/ghn/district/${provinceId}`, { method: 'POST' });
                    districtEl.innerHTML = '<option value="">-- Chọn quận/huyện --</option>';
                    data.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.districtID;
                        option.textContent = item.districtName;
                        districtEl.appendChild(option);
                    });
                    districtEl.disabled = false;
                    districtTS = recreateTomSelect(districtTS, districtEl, 'Chọn quận/huyện');
                    districtTS.on('change', value => loadWards(value));
                    if (selectedValue) {
                        districtTS.setValue(String(selectedValue), true);
                        await loadWards(selectedValue, oldWard);
                    }
                } catch (error) {
                    console.error(error);
                    resetSelect(districtEl, 'Không thể tải quận/huyện', true);
                }
            };

            const loadWards = async (districtId, selectedValue = null) => {
                resetSelect(wardEl, '-- Chọn phường/xã --', true);
                if (!districtId) {
                    if (wardTS) wardTS.destroy();
                    return;
                }
                try {
                    const data = await fetchJson(`/api/v1/ghn/ward/${districtId}`, { method: 'POST' });
                    wardEl.innerHTML = '<option value="">-- Chọn phường/xã --</option>';
                    data.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.wardCode;
                        option.textContent = item.wardName;
                        wardEl.appendChild(option);
                    });
                    wardEl.disabled = false;
                    wardTS = recreateTomSelect(wardTS, wardEl, 'Chọn phường/xã');
                    if (selectedValue) {
                        wardTS.setValue(String(selectedValue), true);
                    }
                } catch (error) {
                    console.error(error);
                    resetSelect(wardEl, 'Không thể tải phường/xã', true);
                }
            };

            loadProvinces(oldProvince || '{{ $order->shipping_province_id }}');
        }
    </script>
@endpush

