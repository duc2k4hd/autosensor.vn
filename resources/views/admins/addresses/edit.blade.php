@extends('admins.layouts.master')

@section('title', 'Chỉnh sửa địa chỉ')
@section('page-title', '📍 Chỉnh sửa địa chỉ giao hàng')

@section('content')
    <div class="mb-3">
        <a href="{{ route('admin.addresses.show', $address) }}" class="btn btn-link p-0">
            ← Quay lại chi tiết
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Thông tin địa chỉ</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.addresses.update', $address) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Tài khoản</label>
                    <select name="account_id" class="form-select" disabled>
                        @if($address->account)
                            <option value="{{ $address->account->id }}" selected>
                                {{ $address->account->name }} ({{ $address->account->email }})
                            </option>
                        @else
                            <option>Không rõ</option>
                        @endif
                    </select>
                    <small class="text-muted">Địa chỉ hiện chỉ cho phép chỉnh sửa, không đổi chủ sở hữu.</small>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Họ tên người nhận *</label>
                        <input type="text" name="full_name" value="{{ old('full_name', $address->full_name) }}"
                               class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Số điện thoại *</label>
                        <input type="text" name="phone_number" value="{{ old('phone_number', $address->phone_number) }}"
                               class="form-control" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Địa chỉ chi tiết *</label>
                    <input type="text" name="detail_address" value="{{ old('detail_address', $address->detail_address) }}"
                           class="form-control" required>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Phường / Xã</label>
                        <input type="text" name="ward" value="{{ old('ward', $address->ward) }}" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Quận / Huyện *</label>
                        <input type="text" name="district" value="{{ old('district', $address->district) }}"
                               class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tỉnh / Thành *</label>
                        <input type="text" name="province" value="{{ old('province', $address->province) }}"
                               class="form-control" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Mã bưu chính *</label>
                        <input type="text" name="postal_code" value="{{ old('postal_code', $address->postal_code) }}"
                               class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Quốc gia</label>
                        <input type="text" name="country" value="{{ old('country', $address->country) }}"
                               class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Loại địa chỉ</label>
                        <select name="address_type" class="form-select">
                            <option value="">Không xác định</option>
                            <option value="home" @selected(old('address_type', $address->address_type) === 'home')>Nhà riêng</option>
                            <option value="work" @selected(old('address_type', $address->address_type) === 'work')>Cơ quan</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Vĩ độ</label>
                        <input type="text" name="latitude" value="{{ old('latitude', $address->latitude) }}"
                               class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Kinh độ</label>
                        <input type="text" name="longitude" value="{{ old('longitude', $address->longitude) }}"
                               class="form-control">
                    </div>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" name="is_default" value="1" id="is_default"
                           class="form-check-input" @checked(old('is_default', $address->is_default))>
                    <label for="is_default" class="form-check-label">Đặt làm địa chỉ mặc định</label>
                </div>

                <div class="mb-3">
                    <label class="form-label">Ghi chú</label>
                    <textarea name="notes" class="form-control" rows="3">{{ old('notes', $address->notes) }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                    Lưu thay đổi
                </button>
                <a href="{{ route('admin.addresses.show', $address) }}" class="btn btn-secondary ms-2">
                    Huỷ
                </a>
            </form>
        </div>
    </div>
@endsection

