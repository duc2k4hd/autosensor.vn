@extends('admins.layouts.master')

@section('title', 'Quản lý CSKH')

@push('styles')
<style>
    .cskh-card {
        border: 1px solid #e6e6e6;
        border-radius: 10px;
        padding: 12px;
        background: #fff;
        box-shadow: 0 4px 8px rgba(0,0,0,0.03);
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }
    .cskh-card:hover {
        box-shadow: 0 8px 16px rgba(0,0,0,0.08);
        transform: translateY(-1px);
    }
    .cskh-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 8px;
    }
    .cskh-drag {
        cursor: grab;
        color: #888;
    }
    .cskh-name {
        font-weight: 700;
        margin: 0;
    }
    .cskh-role {
        color: #555;
        margin-bottom: 6px;
        font-size: 13px;
    }
    .cskh-contact {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        font-size: 13px;
    }
    .cskh-contact a {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 8px;
        border-radius: 6px;
        text-decoration: none;
        color: #fff;
    }
    .cskh-phone { background: #d9252a; }
    .cskh-zalo { background: #0068ff; }
    .cskh-status {
        font-size: 12px;
        font-weight: 600;
        padding: 2px 6px;
        border-radius: 6px;
        color: #fff;
    }
    .cskh-status.on { background: #00b894; }
    .cskh-status.off { background: #b2bec3; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const list = document.getElementById('cskh-sortable');
    if (list) {
        new Sortable(list, {
            animation: 150,
            handle: '.cskh-drag',
            onEnd: function () {
                const orders = [];
                list.querySelectorAll('[data-id]').forEach((el, index) => {
                    orders.push({ id: el.dataset.id, sort_order: index + 1 });
                });
                fetch('{{ route('admin.support-staff.reorder') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ orders })
                }).then(r => r.json()).then(() => {
                    // optional toast
                });
            }
        });
    }
});
</script>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Thêm CSKH</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.support-staff.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label>Tên *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Vai trò</label>
                            <select name="role" class="form-control">
                                <option value="">-- Chọn vai trò --</option>
                                <option value="Tư vấn & Báo giá">Tư vấn & Báo giá</option>
                                <option value="Kỹ thuật & Giải pháp">Kỹ thuật & Giải pháp</option>
                                <option value="Vận hành & Hóa đơn">Vận hành & Hóa đơn</option>
                                <option value="CSKH tổng đài">CSKH tổng đài</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Điện thoại</label>
                            <input type="text" name="phone" class="form-control" placeholder="0827786198">
                        </div>
                        <div class="form-group">
                            <label>Zalo</label>
                            <input type="text" name="zalo" class="form-control" placeholder="0827786198">
                        </div>
                        <div class="form-group">
                            <label>Màu nền (hex)</label>
                            <select name="color" class="form-control">
                                <option value="">-- Chọn màu --</option>
                                <option value="#fbe9e7">Hồng nhạt (#fbe9e7)</option>
                                <option value="#e8f5e9">Xanh lá nhạt (#e8f5e9)</option>
                                <option value="#e3f2fd">Xanh dương nhạt (#e3f2fd)</option>
                                <option value="#fff8e1">Vàng nhạt (#fff8e1)</option>
                                <option value="#f3e5f5">Tím nhạt (#f3e5f5)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Ảnh đại diện</label>
                            <input type="file" name="avatar" class="form-control" accept="image/*">
                        </div>
                        <div class="form-group form-check">
                            <input type="checkbox" name="is_active" id="is_active" class="form-check-input" checked>
                            <label for="is_active" class="form-check-label">Kích hoạt</label>
                        </div>
                        <button class="btn btn-primary" type="submit">Lưu</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Danh sách CSKH (kéo thả để sắp xếp)</h3>
                </div>
                <div class="card-body">
                    <div id="cskh-sortable" class="row">
                        @foreach($staffs as $staff)
                            <div class="col-md-6 mb-3" data-id="{{ $staff->id }}">
                                <div class="cskh-card" style="background: {{ $staff->color ?: '#f9f9f9' }};">
                                    <div class="cskh-header">
                                        <span class="cskh-drag">☰</span>
                                        <span class="cskh-status {{ $staff->is_active ? 'on' : 'off' }}">{{ $staff->is_active ? 'Đang bật' : 'Tắt' }}</span>
                                    </div>
                                    <p class="cskh-name">{{ $staff->name }}</p>
                                    <p class="cskh-role">{{ $staff->role }}</p>
                                    <div class="cskh-contact">
                                        @if($staff->phone)
                                            <a class="cskh-phone" href="tel:{{ $staff->phone }}">📞 {{ $staff->phone }}</a>
                                        @endif
                                        @if($staff->zalo)
                                            <a class="cskh-zalo" href="https://zalo.me/{{ $staff->zalo }}" target="_blank">Zalo</a>
                                        @endif
                                    </div>
                                    <form action="{{ route('admin.support-staff.update', $staff) }}" method="POST" class="mt-2" enctype="multipart/form-data">
                                        @csrf
                                        @method('PUT')
                                        <div class="form-group mb-1">
                                            <input type="text" name="name" class="form-control form-control-sm" value="{{ $staff->name }}" placeholder="Tên">
                                        </div>
                                        <div class="form-group mb-1">
                                            @php $roles = ['Tư vấn & Báo giá','Kỹ thuật & Giải pháp','Vận hành & Hóa đơn','CSKH tổng đài']; @endphp
                                            <select name="role" class="form-control form-control-sm">
                                                <option value="">-- Chọn vai trò --</option>
                                                @foreach($roles as $r)
                                                    <option value="{{ $r }}" {{ $staff->role === $r ? 'selected' : '' }}>{{ $r }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group mb-1">
                                            <input type="text" name="phone" class="form-control form-control-sm" value="{{ $staff->phone }}" placeholder="Điện thoại">
                                        </div>
                                        <div class="form-group mb-1">
                                            <input type="text" name="zalo" class="form-control form-control-sm" value="{{ $staff->zalo }}" placeholder="Zalo">
                                        </div>
                                        <div class="form-group mb-1">
                                            <select name="color" class="form-control form-control-sm">
                                                <option value="">-- Chọn màu --</option>
                                                <option value="#fbe9e7" {{ $staff->color === '#fbe9e7' ? 'selected' : '' }}>Hồng nhạt (#fbe9e7)</option>
                                                <option value="#e8f5e9" {{ $staff->color === '#e8f5e9' ? 'selected' : '' }}>Xanh lá nhạt (#e8f5e9)</option>
                                                <option value="#e3f2fd" {{ $staff->color === '#e3f2fd' ? 'selected' : '' }}>Xanh dương nhạt (#e3f2fd)</option>
                                                <option value="#fff8e1" {{ $staff->color === '#fff8e1' ? 'selected' : '' }}>Vàng nhạt (#fff8e1)</option>
                                                <option value="#f3e5f5" {{ $staff->color === '#f3e5f5' ? 'selected' : '' }}>Tím nhạt (#f3e5f5)</option>
                                            </select>
                                        </div>
                                        <div class="form-group mb-1">
                                            <label>Ảnh đại diện (upload mới để thay)</label>
                                            <input type="file" name="avatar" class="form-control form-control-sm" accept="image/*">
                                            @if($staff->avatar)
                                                <div class="mt-1">
                                                    <img src="{{ asset('clients/assets/img/avatars/' . $staff->avatar) }}" alt="avatar" style="width:48px; height:48px; border-radius:50%; object-fit:cover;">
                                                </div>
                                            @endif
                                        </div>
                                        <div class="form-group form-check mb-1">
                                            <input type="checkbox" name="is_active" class="form-check-input" id="active-{{ $staff->id }}" {{ $staff->is_active ? 'checked' : '' }}>
                                            <label for="active-{{ $staff->id }}" class="form-check-label">Kích hoạt</label>
                                        </div>
                                        <button class="btn btn-sm btn-primary" type="submit">Cập nhật</button>
                                        <a href="{{ route('admin.support-staff.destroy', $staff) }}" class="btn btn-sm btn-danger" onclick="event.preventDefault(); if(confirm('Xóa CSKH này?')) document.getElementById('del-{{ $staff->id }}').submit();">Xóa</a>
                                    </form>
                                    <form id="del-{{ $staff->id }}" action="{{ route('admin.support-staff.destroy', $staff) }}" method="POST" style="display:none;">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

