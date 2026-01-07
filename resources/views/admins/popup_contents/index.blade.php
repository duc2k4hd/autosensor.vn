@extends('admins.layouts.master')

@section('title', 'Quản lý Popup')

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const list = document.getElementById('popup-sortable');
    if (list) {
        new Sortable(list, {
            animation: 150,
            handle: '.popup-drag',
            onEnd: function () {
                const orders = [];
                list.querySelectorAll('[data-id]').forEach((el, index) => {
                    orders.push({ id: el.dataset.id, sort_order: index + 1 });
                });
                fetch('{{ route('admin.popup-contents.reorder') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ orders })
                }).then(r => r.json()).then(() => {});
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
                    <h3 class="card-title">Thêm popup</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.popup-contents.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label>Tiêu đề *</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Nội dung (HTML)</label>
                            <textarea name="content" class="form-control" rows="4">{{ old('content') }}</textarea>
                        </div>
                        <div class="form-group">
                            <label>Ảnh (hiển thị trong popup)</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Button text</label>
                                <input type="text" name="button_text" class="form-control" placeholder="Xem ngay">
                            </div>
                            <div class="form-group col-md-6">
                                <label>Button link</label>
                                <input type="text" name="button_link" class="form-control" placeholder="https://...">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Bắt đầu</label>
                                <input type="datetime-local" name="starts_at" class="form-control">
                            </div>
                            <div class="form-group col-md-6">
                                <label>Kết thúc</label>
                                <input type="datetime-local" name="ends_at" class="form-control">
                            </div>
                        </div>
                        <div class="form-group form-check">
                            <input type="checkbox" name="is_active" id="popup_active" class="form-check-input" checked>
                            <label for="popup_active" class="form-check-label">Kích hoạt</label>
                        </div>
                        <button class="btn btn-primary" type="submit">Lưu</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Danh sách popup (kéo thả để sắp xếp)</h3>
                </div>
                <div class="card-body">
                    <div id="popup-sortable" class="row">
                        @foreach($items as $item)
                            <div class="col-md-6 mb-3" data-id="{{ $item->id }}">
                                <div class="card h-100" style="border:1px solid #e6e6e6;">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <span class="popup-drag" style="cursor:grab;">☰</span>
                                        <span class="badge badge-{{ $item->is_active ? 'success' : 'secondary' }}">
                                            {{ $item->is_active ? 'Đang bật' : 'Tắt' }}
                                        </span>
                                    </div>
                                    <div class="card-body">
                                        <h5>{{ $item->title }}</h5>
                                        @if($item->image)
                                            <div class="mb-2">
                                                <img src="{{ asset('clients/assets/img/popup/' . $item->image) }}" alt="popup" style="width:100%;height:auto;max-height:160px;object-fit:cover;">
                                            </div>
                                        @endif
                                        <div style="font-size:13px; color:#444;">{{ Str::limit(strip_tags($item->content), 150) }}</div>
                                        @if($item->button_text)
                                            <div class="mt-2">
                                                <span class="badge badge-info">{{ $item->button_text }}</span>
                                                @if($item->button_link)
                                                    <small>{{ $item->button_link }}</small>
                                                @endif
                                            </div>
                                        @endif
                                        <form action="{{ route('admin.popup-contents.update', $item) }}" method="POST" enctype="multipart/form-data" class="mt-2">
                                            @csrf
                                            @method('PUT')
                                            <div class="form-group mb-1">
                                                <input type="text" name="title" class="form-control form-control-sm" value="{{ $item->title }}" placeholder="Tiêu đề">
                                            </div>
                                            <div class="form-group mb-1">
                                                <textarea name="content" class="form-control form-control-sm" rows="2" placeholder="Nội dung HTML">{{ old('content', $item->content) }}</textarea>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group col-6 mb-1">
                                                    <input type="text" name="button_text" class="form-control form-control-sm" value="{{ $item->button_text }}" placeholder="Button text">
                                                </div>
                                                <div class="form-group col-6 mb-1">
                                                    <input type="text" name="button_link" class="form-control form-control-sm" value="{{ $item->button_link }}" placeholder="Button link">
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group col-6 mb-1">
                                                    <input type="datetime-local" name="starts_at" class="form-control form-control-sm" value="{{ $item->starts_at ? $item->starts_at->format('Y-m-d\TH:i') : '' }}">
                                                </div>
                                                <div class="form-group col-6 mb-1">
                                                    <input type="datetime-local" name="ends_at" class="form-control form-control-sm" value="{{ $item->ends_at ? $item->ends_at->format('Y-m-d\TH:i') : '' }}">
                                                </div>
                                            </div>
                                            <div class="form-group mb-1">
                                                <input type="file" name="image" class="form-control form-control-sm" accept="image/*">
                                            </div>
                                            <div class="form-group form-check mb-1">
                                                <input type="checkbox" name="is_active" class="form-check-input" id="active-{{ $item->id }}" {{ $item->is_active ? 'checked' : '' }}>
                                                <label for="active-{{ $item->id }}" class="form-check-label">Kích hoạt</label>
                                            </div>
                                            <button class="btn btn-sm btn-primary" type="submit">Cập nhật</button>
                                            <a href="{{ route('admin.popup-contents.destroy', $item) }}" class="btn btn-sm btn-danger" onclick="event.preventDefault(); if(confirm('Xóa popup này?')) document.getElementById('del-popup-{{ $item->id }}').submit();">Xóa</a>
                                        </form>
                                        <form id="del-popup-{{ $item->id }}" action="{{ route('admin.popup-contents.destroy', $item) }}" method="POST" style="display:none;">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </div>
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

