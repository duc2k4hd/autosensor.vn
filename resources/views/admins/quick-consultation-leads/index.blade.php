@extends('admins.layouts.master')

@section('title', 'Quản lý Leads Tư vấn Nhanh')
@section('page-title', '💬 Quản lý Leads Tư vấn Nhanh')

@section('content')
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase mb-2">Tổng leads</h6>
                        <h3 class="mb-0">{{ number_format($stats['total'] ?? 0) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase mb-2">Chưa liên hệ</h6>
                        <h3 class="mb-0 text-primary">{{ number_format($stats['new'] ?? 0) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase mb-2">Đã liên hệ</h6>
                        <h3 class="mb-0 text-success">{{ number_format($stats['contacted'] ?? 0) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase mb-2">Xem nhiều sản phẩm</h6>
                        <h3 class="mb-0 text-info">{{ number_format($stats['multiple_products'] ?? 0) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.quick-consultation-leads.index') }}" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Từ khóa</label>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control"
                               placeholder="Tên, email, điện thoại, sản phẩm...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Loại trigger</label>
                        <select name="trigger_type" class="form-select">
                            <option value="">Tất cả</option>
                            <option value="view_time" @selected(($filters['trigger_type'] ?? '') === 'view_time')>Xem lâu</option>
                            <option value="multiple_products" @selected(($filters['trigger_type'] ?? '') === 'multiple_products')>Nhiều sản phẩm</option>
                            <option value="manual" @selected(($filters['trigger_type'] ?? '') === 'manual')>Thủ công</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Trạng thái</label>
                        <select name="is_contacted" class="form-select">
                            <option value="">Tất cả</option>
                            <option value="0" @selected(($filters['is_contacted'] ?? '') === '0')>Chưa liên hệ</option>
                            <option value="1" @selected(($filters['is_contacted'] ?? '') === '1')>Đã liên hệ</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Từ ngày</label>
                        <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Đến ngày</label>
                        <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">/trang</label>
                        <input type="number" name="per_page" value="{{ $filters['per_page'] ?? 20 }}" min="5" max="100"
                               class="form-control">
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary">Lọc</button>
                        <a href="{{ route('admin.quick-consultation-leads.index') }}" class="btn btn-outline-secondary">Xóa lọc</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <div>
                        <strong>{{ $leads->total() }}</strong> bản ghi
                    </div>
                    <div>
                        <select name="sort" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                            <option value="latest" @selected(($filters['sort'] ?? 'latest') === 'latest')>Mới nhất</option>
                            <option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>Cũ nhất</option>
                            <option value="contacted" @selected(($filters['sort'] ?? '') === 'contacted')>Đã liên hệ</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                        <tr>
                            <th>Khách hàng</th>
                            <th>Sản phẩm</th>
                            <th>Loại trigger</th>
                            <th>Behavior Data</th>
                            <th>Trạng thái</th>
                            <th>Thời gian</th>
                            <th>Thao tác</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($leads as $lead)
                            <tr>
                                <td>
                                    <strong>{{ $lead->name ?? 'Không tên' }}</strong>
                                    <div class="text-muted small">
                                        📞 {{ $lead->phone }}<br>
                                        @if($lead->email)
                                            ✉️ {{ $lead->email }}<br>
                                        @endif
                                        @if($lead->message)
                                            💬 {{ \Illuminate\Support\Str::limit($lead->message, 50) }}
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if($lead->product)
                                        <a href="{{ route('client.product.detail', ['slug' => $lead->product->slug]) }}" target="_blank">
                                            <strong>{{ $lead->product->name }}</strong>
                                        </a>
                                        <div class="text-muted small">
                                            SKU: {{ $lead->product->sku }}
                                        </div>
                                    @else
                                        <span class="text-muted">Đã xóa</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $triggerLabels = [
                                            'view_time' => '⏱️ Xem lâu',
                                            'multiple_products' => '📦 Nhiều sản phẩm',
                                            'manual' => '✋ Thủ công',
                                        ];
                                    @endphp
                                    <span class="badge bg-info">
                                        {{ $triggerLabels[$lead->trigger_type] ?? $lead->trigger_type }}
                                    </span>
                                </td>
                                <td>
                                    @if($lead->behavior_data)
                                        <div class="small text-muted">
                                            @if(isset($lead->behavior_data['viewTime']))
                                                Thời gian: {{ $lead->behavior_data['viewTime'] }}s<br>
                                            @endif
                                            @if(isset($lead->behavior_data['viewedCount']))
                                                Đã xem: {{ $lead->behavior_data['viewedCount'] }} sản phẩm<br>
                                            @endif
                                            @if(isset($lead->behavior_data['categoryIds']))
                                                Categories: {{ count($lead->behavior_data['categoryIds']) }}<br>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($lead->is_contacted)
                                        <span class="badge bg-success">Đã liên hệ</span>
                                        @if($lead->contacted_at)
                                            <div class="text-muted small">
                                                {{ $lead->contacted_at->format('d/m/Y H:i') }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="badge bg-warning">Chưa liên hệ</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="small">
                                        {{ $lead->created_at->format('d/m/Y') }}<br>
                                        <span class="text-muted">{{ $lead->created_at->format('H:i:s') }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.quick-consultation-leads.show', $lead) }}" class="btn btn-outline-primary" title="Xem chi tiết">
                                            👁️
                                        </a>
                                        @if(!$lead->is_contacted)
                                            <form method="POST" action="{{ route('admin.quick-consultation-leads.mark-contacted', $lead) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success" title="Đánh dấu đã liên hệ" onclick="return confirm('Đánh dấu đã liên hệ?')">
                                                    ✓
                                                </button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('admin.quick-consultation-leads.destroy', $lead) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Xóa" onclick="return confirm('Xóa lead này?')">
                                                🗑️
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    Chưa có lead nào
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $leads->links('pagination.custom') }}
                </div>
            </div>
        </div>
    </div>
@endsection
