@extends('admins.layouts.master')

@section('title', 'Quản lý hãng')
@section('page-title', '🏢 Danh sách hãng')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/brands-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .brand-container {
            background: #fff;
            border-radius: 8px;
            padding: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .brand-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            font-size: 12px;
        }
        
        .brand-table th, .brand-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #eef2f7;
            text-align: left;
        }
        
        .brand-table th {
            background: #f8fafc;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #475569;
            font-weight: 600;
            font-size: 11px;
            white-space: nowrap;
        }
        
        .brand-table tr:hover td {
            background: #f9fafb;
        }
        
        .brand-image {
            width: 200px;
            height: 50px;
            object-fit: contain;
            border-radius: 4px;
            border: 1px solid #e5e7eb;
        }
        
        .filter-bar {
            display: grid;
            grid-template-columns: 1fr auto auto auto auto auto;
            gap: 8px;
            margin-bottom: 16px;
            padding: 12px;
            background: #f8fafc;
            border-radius: 6px;
            align-items: center;
        }
        
        .filter-bar input {
            padding: 6px 10px;
            border: 1px solid #cbd5f5;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .filter-bar select {
            padding: 6px 8px;
            border: 1px solid #cbd5f5;
            border-radius: 4px;
            font-size: 12px;
            min-width: 120px;
        }
        
        .filter-bar .btn {
            padding: 6px 12px;
            font-size: 12px;
            white-space: nowrap;
        }
        
        .badge {
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-success {
            background: #dcfce7;
            color: #15803d;
        }
        
        .badge-danger {
            background: #fee2e2;
            color: #b91c1c;
        }
        
        .badge-info {
            background: #e0e7ff;
            color: #4338ca;
        }
        
        .actions {
            display: flex;
            gap: 4px;
        }
        
        .btn-sm {
            padding: 4px 8px;
            font-size: 11px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        
        .page-header h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }
        
        .page-header-actions {
            display: flex;
            gap: 8px;
        }
        
        .slug-code {
            background: #f1f5f9;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-family: 'Courier New', monospace;
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: inline-block;
        }
        
        @media (max-width: 1024px) {
            .filter-bar {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    <div class="brand-container">
        <div class="page-header">
            <h2>Danh sách hãng</h2>
            <div class="page-header-actions">
                <a href="{{ route('admin.brands.create') }}" class="btn btn-primary btn-sm">➕ Thêm mới</a>
            </div>
        </div>

        <form class="filter-bar" method="GET">
            <input type="text" name="keyword" placeholder="🔍 Tìm tên hoặc slug..." value="{{ request('keyword') }}">
            <select name="status">
                <option value="">Trạng thái</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Đang hiển thị</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Tạm ẩn</option>
            </select>
            <select name="sort_by">
                <option value="order" {{ request('sort_by') === 'order' ? 'selected' : '' }}>Sắp xếp</option>
                <option value="name" {{ request('sort_by') === 'name' ? 'selected' : '' }}>Theo tên</option>
                <option value="created_at" {{ request('sort_by') === 'created_at' ? 'selected' : '' }}>Theo ngày</option>
            </select>
            <select name="per_page">
                <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50/trang</option>
                <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100/trang</option>
            </select>
            <button type="submit" class="btn btn-primary">Lọc</button>
            @if(request()->anyFilled(['keyword', 'status', 'sort_by', 'per_page']))
                <a href="{{ route('admin.brands.index') }}" class="btn btn-secondary">Xóa</a>
            @endif
        </form>

        <div class="table-responsive">
            <table class="brand-table">
                <thead>
                <tr>
                    <th style="width:50px;">ID</th>
                    <th style="width:50px;">Ảnh</th>
                    <th>Tên hãng</th>
                    <th style="width:140px;">Slug</th>
                    <th style="width:100px;">Website</th>
                    <th style="width:80px;">Quốc gia</th>
                    <th style="width:60px;text-align:center;">TT</th>
                    <th style="width:80px;">Trạng thái</th>
                    <th style="width:100px;">Ngày tạo</th>
                    <th style="width:120px;">Thao tác</th>
                </tr>
                </thead>
                <tbody>
                @forelse($brands as $brand)
                    <tr>
                        <td>{{ $brand->id }}</td>
                        <td>
                            @php
                                // Tối ưu: đơn giản hóa logic, tránh nhiều file_exists và 404 errors
                                // Luôn dùng business/no-image.webp làm fallback vì file này chắc chắn tồn tại
                                $fallbackUrl = asset('clients/assets/img/business/no-image.webp');
                                $imageUrl = $fallbackUrl; // Default
                                
                                // Chỉ check và set imageUrl nếu brand có image hợp lệ
                                if (!empty($brand->image) && is_string($brand->image)) {
                                    $imagePath = trim($brand->image);
                                    if (!empty($imagePath)) {
                                        $fullPath = public_path($imagePath);
                                        // Sử dụng @ để suppress warnings, chỉ check một lần
                                        if (@file_exists($fullPath) && @is_file($fullPath)) {
                                            $imageUrl = asset($imagePath);
                                        }
                                    }
                                }
                            @endphp
                            <img src="{{ $imageUrl }}" 
                                     alt="{{ $brand->name }}" 
                                     class="brand-image"
                                     loading="lazy"
                                     decoding="async"
                                     onerror="this.onerror=null; if(this.src !== '{{ $fallbackUrl }}') { this.src='{{ $fallbackUrl }}'; }">
                        </td>
                        <td>
                            <strong style="font-size:13px;">{{ $brand->name }}</strong>
                        </td>
                        <td>
                            <span class="slug-code" title="{{ $brand->slug }}">{{ $brand->slug }}</span>
                        </td>
                        <td>
                            @if($brand->website)
                                <a href="{{ $brand->website }}" target="_blank" rel="noopener noreferrer" 
                                   style="font-size:11px;color:#3b82f6;text-decoration:none;">
                                    {{ Str::limit($brand->website, 20) }}
                                </a>
                            @else
                                <span style="color:#94a3b8;font-size:11px;">-</span>
                            @endif
                        </td>
                        <td>
                            <span style="font-size:11px;">{{ $brand->country ?? '-' }}</span>
                        </td>
                        <td style="text-align:center;font-size:11px;">{{ $brand->order }}</td>
                        <td>
                            @if($brand->is_active)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-danger">Inactive</span>
                            @endif
                        </td>
                        <td style="font-size:11px;color:#64748b;">
                            {{ $brand->created_at?->format('d/m/Y') ?? '-' }}
                        </td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('admin.brands.edit', $brand) }}" class="btn btn-secondary btn-sm" title="Sửa">✏️</a>
                                <form action="{{ route('admin.brands.destroy', $brand) }}" method="POST" style="display:inline;" 
                                      onsubmit="return confirm('Xóa hãng này? Hãng đang được sử dụng bởi {{ $brand->products_count ?? 0 }} sản phẩm. Bạn có chắc chắn muốn xóa?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" title="Xóa">🗑️</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" style="text-align:center;padding:30px;color:#94a3b8;">
                            <div style="font-size:36px;margin-bottom:12px;">🏢</div>
                            <div style="font-size:13px;">Chưa có hãng nào</div>
                            <div style="margin-top:12px;">
                                <a href="{{ route('admin.brands.create') }}" class="btn btn-primary btn-sm">➕ Thêm hãng đầu tiên</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:16px;">
            {{ $brands->links() }}
        </div>
    </div>
@endsection
