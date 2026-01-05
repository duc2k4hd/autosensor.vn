@extends('admins.layouts.master')

@section('title', 'Chi tiết địa chỉ')
@section('page-title', '📍 Chi tiết địa chỉ giao hàng')

@section('content')
    <div class="mb-3 d-flex justify-content-between align-items-center">
        <a href="{{ route('admin.addresses.index') }}" class="btn btn-link p-0">
            ← Quay lại danh sách
        </a>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.addresses.edit', $address) }}" class="btn btn-sm btn-secondary">
                Chỉnh sửa
            </a>
            <form action="{{ route('admin.addresses.set-default', $address) }}" method="POST"
                  onsubmit="return confirm('Đặt địa chỉ này làm mặc định?');">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-success" @disabled($address->is_default)>
                    Đặt làm mặc định
                </button>
            </form>
            <form action="{{ route('admin.addresses.destroy', $address) }}" method="POST"
                  onsubmit="return confirm('Bạn chắc chắn muốn xoá địa chỉ này?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger">
                    Xoá
                </button>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-md-7">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Thông tin địa chỉ</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Tài khoản</dt>
                        <dd class="col-sm-8">
                            @if($address->account)
                                <div>{{ $address->account->name }}</div>
                                <div class="text-muted" style="font-size: 12px;">{{ $address->account->email }}</div>
                            @else
                                <span class="text-muted">Không rõ</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4">Người nhận</dt>
                        <dd class="col-sm-8">{{ $address->full_name }}</dd>

                        <dt class="col-sm-4">Số điện thoại</dt>
                        <dd class="col-sm-8">{{ $address->phone_number }}</dd>

                        <dt class="col-sm-4">Địa chỉ chi tiết</dt>
                        <dd class="col-sm-8">
                            <div>{{ $address->detail_address }}</div>
                            <div class="text-muted" style="font-size: 12px;">
                                {{ $address->ward ? $address->ward . ', ' : '' }}
                                {{ $address->district }}, {{ $address->province }}
                            </div>
                        </dd>

                        <dt class="col-sm-4">Mã bưu chính</dt>
                        <dd class="col-sm-8">{{ $address->postal_code }}</dd>

                        <dt class="col-sm-4">Quốc gia</dt>
                        <dd class="col-sm-8">{{ $address->country }}</dd>

                        <dt class="col-sm-4">Loại địa chỉ</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-secondary">
                                {{ $address->address_type === 'work' ? 'Cơ quan' : 'Nhà riêng' }}
                            </span>
                        </dd>

                        <dt class="col-sm-4">Mặc định</dt>
                        <dd class="col-sm-8">
                            @if($address->is_default)
                                <span class="badge bg-success">Địa chỉ mặc định</span>
                            @else
                                <span class="badge bg-light text-muted">Không</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4">Tạo lúc</dt>
                        <dd class="col-sm-8">{{ $address->created_at?->format('d/m/Y H:i') }}</dd>

                        <dt class="col-sm-4">Cập nhật</dt>
                        <dd class="col-sm-8">{{ $address->updated_at?->format('d/m/Y H:i') }}</dd>
                    </dl>
                </div>
            </div>

            @if($address->notes)
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Ghi chú</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0" style="white-space: pre-wrap;">{{ $address->notes }}</p>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-md-5">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Thông tin mã địa lý</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Mã tỉnh</dt>
                        <dd class="col-sm-8">{{ $address->province_code ?? '-' }}</dd>

                        <dt class="col-sm-4">Mã huyện</dt>
                        <dd class="col-sm-8">{{ $address->district_code ?? '-' }}</dd>

                        <dt class="col-sm-4">Mã phường</dt>
                        <dd class="col-sm-8">{{ $address->ward_code ?? '-' }}</dd>

                        <dt class="col-sm-4">Toạ độ</dt>
                        <dd class="col-sm-8">
                            @if($address->latitude && $address->longitude)
                                {{ $address->latitude }}, {{ $address->longitude }}
                            @else
                                <span class="text-muted">Chưa thiết lập</span>
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Lịch sử thao tác</h5>
        </div>
        <div class="card-body">
            @if($audits->isEmpty())
                <p class="text-muted mb-0">Chưa có lịch sử nào.</p>
            @else
                <div class="list-group list-group-flush">
                    @foreach($audits as $audit)
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <strong>
                                    {{ $audit->action === 'update' ? 'Cập nhật' : ($audit->action === 'set_default' ? 'Đặt mặc định' : 'Xoá') }}
                                </strong>
                                <small class="text-muted">{{ $audit->created_at?->format('d/m/Y H:i') }}</small>
                            </div>
                            <div class="text-muted" style="font-size: 12px;">
                                @if($audit->performer)
                                    Bởi: {{ $audit->performer->name }} ({{ $audit->performer->email }})
                                @else
                                    Hệ thống
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
        </div>
    </div>
@endsection

