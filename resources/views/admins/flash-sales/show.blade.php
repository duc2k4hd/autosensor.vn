@extends('admins.layouts.master')

@section('title', 'Chi tiết Flash Sale: ' . $flashSale->title)
@section('page-title', '📊 Chi tiết Flash Sale')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/flash-sale-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .info-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 1px 6px rgba(15,23,42,0.06);
            margin-bottom: 20px;
        }
        .info-card h3 {
            margin: 0 0 16px;
            font-size: 18px;
            font-weight: 600;
            color: #0f172a;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
        }
        .info-row {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 12px;
            padding: 8px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #64748b;
        }
        .info-value {
            color: #0f172a;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 1px 6px rgba(15,23,42,0.06);
            text-align: center;
        }
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #0f172a;
            margin: 8px 0;
        }
        .stat-label {
            font-size: 14px;
            color: #64748b;
        }
        .badge {
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-running {
            background: #dcfce7;
            color: #15803d;
        }
        .badge-draft {
            background: #fef3c7;
            color: #92400e;
        }
        .badge-ended {
            background: #e5e7eb;
            color: #374151;
        }
    </style>
@endpush

@section('content')
    <div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <a href="{{ route('admin.flash-sales.index') }}" class="btn btn-secondary">← Quay lại</a>
            <div style="display:flex;gap:10px;">
                <a href="{{ route('admin.flash-sales.edit', $flashSale) }}" class="btn btn-primary">✏️ Sửa</a>
                <a href="{{ route('admin.flash-sales.items', $flashSale) }}" class="btn btn-info">📦 Quản lý sản phẩm</a>
                <a href="{{ route('admin.flash-sales.preview', $flashSale) }}" class="btn btn-success" target="_blank">👁️ Xem trước</a>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Tổng sản phẩm</div>
                <div class="stat-value">{{ $flashSaleStats['total_items'] ?? 0 }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Tổng đã bán</div>
                <div class="stat-value">{{ $flashSaleStats['total_sold'] ?? 0 }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Tổng còn lại</div>
                <div class="stat-value">{{ $flashSaleStats['total_remaining'] ?? 0 }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Lượt xem</div>
                <div class="stat-value">{{ number_format($flashSale->views ?? 0) }}</div>
            </div>
        </div>

        <!-- Thông tin cơ bản -->
        <div class="info-card">
            <h3>📋 Thông tin cơ bản</h3>
            <div class="info-row">
                <div class="info-label">Tên chương trình:</div>
                <div class="info-value"><strong>{{ $flashSale->title }}</strong></div>
            </div>
            @if($flashSale->tag)
            <div class="info-row">
                <div class="info-label">Tag/Label:</div>
                <div class="info-value">{{ $flashSale->tag }}</div>
            </div>
            @endif
            <div class="info-row">
                <div class="info-label">Mô tả:</div>
                <div class="info-value">{!! nl2br(e($flashSale->description)) ?: '-' !!}</div>
            </div>
            @if($flashSale->banner)
            <div class="info-row">
                <div class="info-label">Banner:</div>
                <div class="info-value">
                    <img src="{{ asset($flashSale->banner) }}" alt="Banner" style="max-width:300px;border-radius:8px;">
                </div>
            </div>
            @endif
        </div>

        <!-- Thời gian -->
        <div class="info-card">
            <h3>⏰ Thời gian</h3>
            <div class="info-row">
                <div class="info-label">Bắt đầu:</div>
                <div class="info-value">{{ $flashSale->start_time->format('d/m/Y H:i:s') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Kết thúc:</div>
                <div class="info-value">{{ $flashSale->end_time->format('d/m/Y H:i:s') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Thời lượng:</div>
                <div class="info-value">
                    {{ $flashSale->start_time->diffInDays($flashSale->end_time) }} ngày
                    {{ $flashSale->start_time->diffInHours($flashSale->end_time) % 24 }} giờ
                </div>
            </div>
            @if($flashSale->isActive())
            <div class="info-row">
                <div class="info-label">Còn lại:</div>
                <div class="info-value" id="remaining-time"></div>
            </div>
            @endif
        </div>

        <!-- Trạng thái -->
        <div class="info-card">
            <h3>📊 Trạng thái</h3>
            <div class="info-row">
                <div class="info-label">Status:</div>
                <div class="info-value">
                    @if($flashSale->status === 'draft')
                        <span class="badge badge-draft">🟡 Draft</span>
                    @elseif($flashSale->status === 'active' && $flashSale->isActive())
                        <span class="badge badge-running">🟢 Running</span>
                    @else
                        <span class="badge badge-ended">🔴 Ended</span>
                    @endif
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Bật/Tắt:</div>
                <div class="info-value">
                    @if($flashSale->is_active)
                        <span class="badge badge-running">Active</span>
                    @else
                        <span class="badge badge-ended">Inactive</span>
                    @endif
                </div>
            </div>
            @if(!$flashSale->canEdit())
            <div class="info-row">
                <div class="info-label">Khóa:</div>
                <div class="info-value"><span class="badge badge-warning">🔒 Locked</span></div>
            </div>
            @endif
            <div class="info-row">
                <div class="info-label">Giới hạn mua mỗi khách:</div>
                <div class="info-value">{{ $flashSale->max_per_user ?? 'Không giới hạn' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Số lượng hiển thị:</div>
                <div class="info-value">{{ $flashSale->display_limit ?? 20 }} sản phẩm</div>
            </div>
        </div>

        <!-- Người tạo -->
        <div class="info-card">
            <h3>👤 Thông tin tạo</h3>
            <div class="info-row">
                <div class="info-label">Người tạo:</div>
                <div class="info-value">{{ $flashSale->creator->name ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Ngày tạo:</div>
                <div class="info-value">{{ $flashSale->created_at->format('d/m/Y H:i:s') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Cập nhật lần cuối:</div>
                <div class="info-value">{{ $flashSale->updated_at->format('d/m/Y H:i:s') }}</div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @if($flashSale->isActive())
    <script>
        // Countdown timer
        const endTime = new Date('{{ $flashSale->end_time->toIso8601String() }}').getTime();
        const remainingEl = document.getElementById('remaining-time');
        
        function updateCountdown() {
            const now = new Date().getTime();
            const distance = endTime - now;
            
            if (distance < 0) {
                remainingEl.textContent = 'Đã kết thúc';
                return;
            }
            
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            remainingEl.textContent = `${days} ngày ${hours} giờ ${minutes} phút ${seconds} giây`;
        }
        
        updateCountdown();
        setInterval(updateCountdown, 1000);
    </script>
    @endif
@endpush

