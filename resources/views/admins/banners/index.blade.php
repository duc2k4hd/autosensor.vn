@extends('admins.layouts.master')

@section('title', 'Quản lý banner')
@section('page-title', '🖼️ Banners')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/banners-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .filters {
            display:flex;
            flex-wrap:wrap;
            gap:10px;
            margin-bottom:16px;
        }
        .filters input,
        .filters select {
            padding:7px 10px;
            border:1px solid #cbd5f5;
            border-radius:6px;
            font-size:13px;
        }
        .banners-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .banner-card {
            background:#fff;
            border-radius:12px;
            padding:16px;
            box-shadow:0 2px 10px rgba(15,23,42,0.08);
            display:flex;
            gap:16px;
            transition: all 0.3s;
            cursor: move;
            position: relative;
        }
        .banner-card:hover {
            box-shadow:0 4px 16px rgba(15,23,42,0.12);
            transform: translateY(-2px);
        }
        .banner-card.dragging {
            opacity: 0.5;
            transform: scale(0.95);
        }
        .banner-card .drag-handle {
            position: absolute;
            left: 8px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            cursor: grab;
            font-size: 20px;
            padding: 8px;
        }
        .banner-card .drag-handle:active {
            cursor: grabbing;
        }
        .banner-card img {
            width:200px;
            height:110px;
            object-fit:cover;
            border-radius:8px;
            border:1px solid #e2e8f0;
            flex-shrink: 0;
        }
        .badge {
            padding:3px 8px;
            border-radius:999px;
            font-size:11px;
            font-weight:600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
        }
        .badge-active { background:#dcfce7;color:#15803d; }
        .badge-inactive { background:#fee2e2;color:#b91c1c; }
        .banner-info {
            flex: 1;
            min-width: 0;
        }
        .banner-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 12px;
        }
        .sort-info {
            background: #f1f5f9;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 13px;
            color: #475569;
        }
        .sort-info strong {
            color: #1e40af;
        }
    </style>
@endpush

@section('content')
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <div>
            <h2 style="margin:0;">Danh sách banner</h2>
            <p style="margin:4px 0 0;color:#94a3b8;">Quản lý ảnh hero/homepage.</p>
        </div>
        <a href="{{ route('admin.banners.create') }}" class="btn btn-primary">➕ Thêm banner</a>
    </div>

    <form method="GET" class="filters">
        <input type="text" name="keyword" placeholder="🔍 Tìm theo tiêu đề..." value="{{ request('keyword') }}">
        <select name="position">
            <option value="">-- Vị trí --</option>
            @foreach($positions ?? config('banners.positions', []) as $key => $label)
                <option value="{{ $key }}" {{ request('position') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <select name="status">
            <option value="">-- Trạng thái --</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>✅ Đang hiển thị</option>
            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>❌ Tạm tắt</option>
            <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>⏰ Đã hết hạn</option>
        </select>
        <button type="submit" class="btn btn-secondary">Lọc</button>
    </form>

    @if($banners->count() > 0)
        <div class="sort-info">
            <strong>💡 Mẹo:</strong> Kéo thả các banner để sắp xếp thứ tự hiển thị. Banner ở trên sẽ hiển thị trước.
        </div>
    @endif

    <div class="banners-list" id="banners-list">
        @forelse($banners as $banner)
            <div class="banner-card" data-banner-id="{{ $banner->id }}" data-order="{{ $banner->order ?? 0 }}">
                <div class="drag-handle" title="Kéo để sắp xếp">☰</div>
                <img src="{{ $banner->image_desktop_url }}" alt="{{ $banner->title }}" 
                     onerror="this.src='{{ asset('admins/img/placeholder.png') }}'">
                <div class="banner-info">
                    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:8px;">
                        <h4 style="margin:0;font-size:16px;">{{ $banner->title ?: 'Banner không có tiêu đề' }}</h4>
                        @php
                            $allPositions = $positions ?? config('banners.positions', []);
                            $allBadges = $positionBadges ?? config('banners.position_badges', []);
                            
                            $positionText = $allPositions[$banner->position] ?? 'Vị trí không hợp lệ';
                            $badgeConfig = $allBadges[$banner->position] ?? ['bg' => '#e2e8f0', 'text' => '#64748b'];
                            
                            $status = $banner->status;
                            $statusConfig = [
                                'active' => ['class' => 'badge-active', 'text' => '✅ Đang hiển thị'],
                                'inactive' => ['class' => 'badge-inactive', 'text' => '❌ Đã tắt'],
                                'scheduled' => ['class' => 'badge', 'bg' => '#fef3c7', 'text' => '#92400e', 'label' => '⏰ Đã lên lịch'],
                                'expired' => ['class' => 'badge', 'bg' => '#fee2e2', 'text' => '#991b1b', 'label' => '⏰ Đã hết hạn'],
                            ];
                            $currentStatus = $statusConfig[$status] ?? $statusConfig['inactive'];
                        @endphp

                        <span class="badge" style="background: {{ $badgeConfig['bg'] }}; color: {{ $badgeConfig['text'] }};">📍 {{ $positionText }}</span>
                        @if(isset($currentStatus['bg']))
                            <span class="badge" style="background: {{ $currentStatus['bg'] }}; color: {{ $currentStatus['text'] }};">{{ $currentStatus['label'] }}</span>
                        @else
                            <span class="badge {{ $currentStatus['class'] }}">{{ $currentStatus['text'] }}</span>
                        @endif
                        <span class="badge" style="background:#f1f5f9;color:#475569;">#{{ $banner->order ?? 0 }}</span>
                    </div>
                    @if($banner->description)
                        <p style="margin:6px 0;color:#475569;font-size:13px;">{{ \Illuminate\Support\Str::limit($banner->description, 100) }}</p>
                    @endif
                    <div style="font-size:12px;color:#94a3b8;margin-top:4px;">
                        📅 Bắt đầu: <strong>{{ $banner->start_at?->format('d/m/Y H:i') ?? 'Ngay lập tức' }}</strong> | 
                        Kết thúc: <strong>{{ $banner->end_at?->format('d/m/Y H:i') ?? 'Không giới hạn' }}</strong>
                    </div>
                    <div class="banner-actions">
                        <a href="{{ route('admin.banners.edit', $banner) }}" class="btn btn-secondary btn-sm">✏️ Sửa</a>
                        <form action="{{ route('admin.banners.toggle', $banner) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-primary btn-sm">
                                {{ $banner->is_active ? '👁️ Tắt' : '👁️‍🗨️ Bật' }}
                            </button>
                        </form>
                        <form action="{{ route('admin.banners.destroy', $banner) }}" method="POST" 
                              onsubmit="return confirm('Bạn có chắc muốn xóa banner này?')" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">🗑️ Xóa</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div style="text-align:center;padding:40px;background:#fff;border-radius:12px;box-shadow:0 2px 10px rgba(15,23,42,0.08);">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="#94a3b8" viewBox="0 0 640 640" style="margin:0 auto 16px;">
                    <path d="M144 480C144 408.4 200.4 352 272 352C343.6 352 400 408.4 400 480C400 551.6 343.6 608 272 608C200.4 608 144 551.6 144 480zM272 320C174.8 320 96 398.8 96 496C96 593.2 174.8 672 272 672C369.2 672 448 593.2 448 496C448 398.8 369.2 320 272 320zM272 416C290.3 416 304 429.7 304 448C304 466.3 290.3 480 272 480C253.7 480 240 466.3 240 448C240 429.7 253.7 416 272 416zM512 0C547.3 0 576 28.65 576 64V192H672C707.3 192 736 220.7 736 256V448C736 483.3 707.3 512 672 512H512C476.7 512 448 483.3 448 448V256C448 220.7 476.7 192 512 192H576V64H448V192H512C547.3 192 576 220.7 576 256V448H672V256H512V0z"/>
                </svg>
                <p style="margin:0;color:#64748b;font-size:16px;">Chưa có banner nào.</p>
                <a href="{{ route('admin.banners.create') }}" class="btn btn-primary" style="margin-top:16px;">➕ Tạo banner đầu tiên</a>
            </div>
        @endforelse
    </div>

    <div style="margin-top:16px;">
        {{ $banners->links() }}
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
    const bannersList = document.getElementById('banners-list');
    if (bannersList) {
        const sortable = Sortable.create(bannersList, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'dragging',
            onEnd: function(evt) {
                const items = Array.from(bannersList.children);
                const orders = items.map((item, index) => ({
                    id: item.dataset.bannerId,
                    order: index + 1
                }));

                // Update order via AJAX
                fetch('{{ route("admin.banners.reorder") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ orders: orders })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update order numbers in UI
                        items.forEach((item, index) => {
                            const badge = item.querySelector('.badge[style*="#"]');
                            if (badge) {
                                badge.textContent = '#' + (index + 1);
                            }
                            item.dataset.order = index + 1;
                        });
                        
                        // Show success message
                        const message = document.createElement('div');
                        message.style.cssText = 'position:fixed;top:20px;right:20px;background:#10b981;color:white;padding:12px 20px;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);z-index:9999;';
                        message.textContent = '✅ Đã cập nhật thứ tự banner';
                        document.body.appendChild(message);
                        setTimeout(() => message.remove(), 3000);
                    }
                })
                .catch(error => {
                    console.error('Error updating order:', error);
                    alert('Có lỗi xảy ra khi cập nhật thứ tự. Vui lòng thử lại.');
                    location.reload();
                });
            }
        });
    }
</script>
@endpush


