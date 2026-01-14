@extends('admins.layouts.master')

@section('title', 'Quản lý danh mục')
@section('page-title', '🏷️ Danh mục sản phẩm')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/category-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .category-container {
            display: grid;
            grid-template-columns: 240px 1fr;
            gap: 16px;
            align-items: start;
        }
        
        .category-sidebar {
            position: sticky;
            top: 20px;
            background: #fff;
            border-radius: 8px;
            padding: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }
        
        .category-sidebar h3 {
            margin: 0 0 12px;
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
            padding-bottom: 8px;
            border-bottom: 2px solid #f3f4f6;
        }
        
        .category-main {
            background: #fff;
            border-radius: 8px;
            padding: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .category-tree {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .tree-item {
            padding: 6px 8px;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 2px;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: background 0.15s;
            font-size: 13px;
        }
        
        .tree-item:hover {
            background: #f1f5f9;
        }
        
        .tree-item.active {
            background: #dbeafe;
            color: #1e40af;
            font-weight: 600;
        }
        
        .tree-toggle {
            width: 16px;
            text-align: center;
            cursor: pointer;
            font-size: 10px;
            color: #64748b;
        }
        
        .tree-children {
            margin-left: 20px;
            margin-top: 2px;
            display: none;
        }
        
        .tree-children.expanded {
            display: block;
        }
        
        .category-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            font-size: 12px;
        }
        
        .category-table th, .category-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #eef2f7;
            text-align: left;
        }
        
        .category-table th {
            background: #f8fafc;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #475569;
            font-weight: 600;
            font-size: 11px;
            white-space: nowrap;
        }
        
        .category-table tr:hover td {
            background: #f9fafb;
        }
        
        .category-image {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #e5e7eb;
        }
        
        .filter-bar {
            display: grid;
            grid-template-columns: 1fr auto auto auto auto auto auto;
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
        
        .parent-select {
            min-width: 160px;
            font-size: 11px;
            padding: 4px 6px;
        }
        
        @media (max-width: 1400px) {
            .category-container {
                grid-template-columns: 200px 1fr;
            }
            
            .filter-bar {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 1024px) {
            .category-container {
                grid-template-columns: 1fr;
            }
            
            .category-sidebar {
                position: relative;
                top: 0;
                max-height: 300px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="category-container">
        <!-- Sidebar - Tree View -->
        <div class="category-sidebar">
            <h3>📁 Cây danh mục</h3>
            <ul class="category-tree" id="categoryTree">
                @foreach($tree ?? [] as $item)
                    @include('admins.categories.partials.tree-item', ['item' => $item, 'level' => 0])
                @endforeach
            </ul>
        </div>

        <!-- Main Content -->
        <div class="category-main">
            <div class="page-header">
                <h2>Danh sách danh mục</h2>
                <div class="page-header-actions">
                @if($parentId)
                        <a href="{{ route('admin.categories.edit', $parentId) }}" class="btn btn-info btn-sm">✏️ Sửa danh mục cha</a>
                @endif
                    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary btn-sm">➕ Thêm mới</a>
                    <a href="{{ route('admin.categories.export') }}" class="btn btn-success btn-sm">📥 Xuất Excel</a>
                    <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#importModal">📤 Nhập Excel</button>
                </div>
            </div>

            <form class="filter-bar" method="GET">
                <input type="text" name="keyword" placeholder="🔍 Tìm tên hoặc slug..." value="{{ request('keyword') }}">
                <select name="status">
                    <option value="">Trạng thái</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Đang hiển thị</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Tạm ẩn</option>
                </select>
                <select name="only_root">
                    <option value="">Loại</option>
                    <option value="1" {{ request('only_root') === '1' ? 'selected' : '' }}>Chỉ gốc</option>
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
                @if(request()->anyFilled(['keyword', 'status', 'only_root', 'sort_by', 'per_page', 'parent_id']))
                    <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">Xóa</a>
                @endif
            </form>

            <form id="category-bulk-form" action="{{ route('admin.categories.bulk-action') }}" method="POST">
                @csrf
                <div class="table-responsive">
                    <table class="category-table">
                        <thead>
                        <tr>
                            <th style="width:30px;">
                                <input type="checkbox" id="select-all-categories">
                            </th>
                            <th style="width:50px;">ID</th>
                            <th style="width:50px;">Ảnh</th>
                            <th>Tên</th>
                            <th style="width:140px;">Slug</th>
                            <th style="width:160px;">Danh mục cha</th>
                            <th style="width:60px;text-align:center;">TT</th>
                            <th style="width:70px;text-align:center;">Con</th>
                            <th style="width:80px;">Trạng thái</th>
                            <th style="width:100px;">Ngày tạo</th>
                            <th style="width:120px;">Thao tác</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($categories as $category)
                            <tr>
                                <td>
                                    @if($category->id === 1)
                                        <input type="checkbox" disabled title="Không thể chọn danh mục mặc định (ID: 1)" style="opacity:0.5;cursor:not-allowed;">
                                    @else
                                        <input type="checkbox" name="selected[]" value="{{ $category->id }}" class="category-checkbox" form="category-bulk-form">
                                    @endif
                                </td>
                                <td>{{ $category->id }}</td>
                                <td>
                                    @php
                                        $imagePath = 'clients/assets/img/categories/' . $category->image;
                                        $imageUrl = $category->image && file_exists(public_path($imagePath)) 
                                            ? asset($imagePath) 
                                            : asset('clients/assets/img/categories/no-image.webp');
                                    @endphp
                                    <img src="{{ $imageUrl }}" 
                                             alt="{{ $category->name }}" 
                                             class="category-image"
                                         onerror="this.src='{{ asset('clients/assets/img/categories/no-image.webp') }}';">
                                </td>
                                <td>
                                    <strong style="font-size:13px;">{{ $category->name }}</strong>
                                </td>
                                <td>
                                    <span class="slug-code" title="{{ $category->slug }}">{{ $category->slug }}</span>
                                </td>
                                <td>
                                    @if($category->id === 1)
                                        <span style="color:#94a3b8;font-size:11px;">Root</span>
                                    @else
                                        <form action="{{ route('admin.categories.update-parent', $category) }}" method="POST" style="display:inline;" class="parent-change-form">
                                            @csrf
                                            @method('PATCH')
                                            <select name="parent_id" class="form-control parent-select" onchange="this.form.submit()">
                                                <option value="" {{ !$category->parent_id ? 'selected' : '' }}>🏠 Root</option>
                                                @foreach(\App\Helpers\CategoryHelper::getDropdownOptions($category->id) as $option)
                                                    <option value="{{ $option['value'] }}" {{ $category->parent_id == $option['value'] ? 'selected' : '' }}>
                                                        {{ $option['label'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </form>
                                    @endif
                                </td>
                                <td style="text-align:center;font-size:11px;">{{ $category->order }}</td>
                                <td style="text-align:center;">
                                    <span class="badge badge-info">
                                        {{ $category->children()->count() }}
                                    </span>
                                </td>
                                <td>
                                    @if($category->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-danger">Inactive</span>
                                    @endif
                                </td>
                                <td style="font-size:11px;color:#64748b;">
                                    {{ $category->created_at?->format('d/m/Y') ?? '-' }}
                                </td>
                                <td>
                                    <div class="actions">
                                        <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-secondary btn-sm" title="Sửa">✏️</a>
                                        @if($category->id === 1)
                                            <button type="button" class="btn btn-danger btn-sm" disabled 
                                                    title="Không thể xóa" 
                                                    style="opacity:0.5;cursor:not-allowed;">🗑️</button>
                                        @else
                                            <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" style="display:inline;" 
                                                  onsubmit="return confirm('Xóa danh mục này?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm" title="Xóa">🗑️</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" style="text-align:center;padding:30px;color:#94a3b8;">
                                    <div style="font-size:36px;margin-bottom:12px;">📁</div>
                                    <div style="font-size:13px;">Chưa có danh mục nào</div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div style="margin-top:12px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    <button type="submit" class="btn btn-secondary btn-sm" name="bulk_action" value="hide">Ẩn đã chọn</button>
                    <button type="submit" class="btn btn-primary btn-sm" name="bulk_action" value="show">Hiển thị đã chọn</button>
                    @can('deleteAny', \App\Models\Category::class)
                        <button type="submit" class="btn btn-danger btn-sm" name="bulk_action" value="delete" 
                                onclick="return confirm('Xóa các danh mục đã chọn?');">Xóa đã chọn</button>
                    @endcan
                </div>
            </form>

            <div style="margin-top:16px;">
                {{ $categories->links() }}
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Select all checkbox
            const selectAll = document.getElementById('select-all-categories');
            const checkboxes = document.querySelectorAll('.category-checkbox');
            const form = document.getElementById('category-bulk-form');

            if (selectAll) {
                selectAll.addEventListener('change', () => {
                    checkboxes.forEach(cb => cb.checked = selectAll.checked);
                });
            }

            if (form) {
                form.addEventListener('submit', (e) => {
                    const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
                    if (!anyChecked) {
                        e.preventDefault();
                        alert('Vui lòng chọn ít nhất một danh mục.');
                    }
                });
            }

            // Tree toggle
            document.querySelectorAll('.tree-toggle').forEach(toggle => {
                toggle.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const children = toggle.closest('.tree-item').querySelector('.tree-children');
                    if (children) {
                        children.classList.toggle('expanded');
                        toggle.textContent = children.classList.contains('expanded') ? '▼' : '▶';
                    }
                });
            });

            // Tree item click
            document.querySelectorAll('.tree-item').forEach(item => {
                item.addEventListener('click', (e) => {
                    if (e.target.classList.contains('tree-toggle')) return;
                    
                    // Update active state
                    document.querySelectorAll('.tree-item').forEach(i => i.classList.remove('active'));
                    item.classList.add('active');
                    
                    // Filter by parent
                    const categoryId = item.dataset.categoryId;
                    if (categoryId) {
                        window.location.href = '{{ route("admin.categories.index") }}?parent_id=' + categoryId;
                    }
                });
            });
        });
    </script>

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1" role="dialog" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importModalLabel">📤 Nhập danh mục từ Excel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('admin.categories.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('success') }}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ session('error') }}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        <div class="form-group">
                            <label for="excel_file">Chọn file Excel (.xlsx, .xls)</label>
                            <input type="file" class="form-control-file @error('excel_file') is-invalid @enderror" 
                                   id="excel_file" name="excel_file" accept=".xlsx,.xls" required>
                            @error('excel_file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                <strong>Cấu trúc file Excel:</strong>
                                <ul class="mb-0 mt-2" style="font-size: 11px;">
                                    <li><strong>Cột A:</strong> ID (tùy chọn, dùng để tham chiếu)</li>
                                    <li><strong>Cột B:</strong> Parent Slug (slug của danh mục cha, để trống nếu là danh mục gốc)</li>
                                    <li><strong>Cột C:</strong> Name (Tên danh mục - bắt buộc)</li>
                                    <li><strong>Cột D:</strong> Slug (Slug duy nhất - bắt buộc)</li>
                                    <li><strong>Cột E:</strong> Description (Mô tả)</li>
                                    <li><strong>Cột F:</strong> Image (Đường dẫn ảnh)</li>
                                    <li><strong>Cột G:</strong> Order (Thứ tự hiển thị, mặc định 0)</li>
                                    <li><strong>Cột H:</strong> Is Active (Yes/No, mặc định Yes)</li>
                                    <li><strong>Cột I:</strong> Meta Title (SEO - tùy chọn)</li>
                                    <li><strong>Cột J:</strong> Meta Description (SEO - tùy chọn)</li>
                                    <li><strong>Cột K:</strong> Meta Keywords (SEO - tùy chọn)</li>
                                    <li><strong>Cột L:</strong> Meta Canonical (SEO - tùy chọn)</li>
                                    <li><strong>Cột M:</strong> Created At (YYYY-MM-DD HH:mm:ss, tùy chọn)</li>
                                    <li><strong>Cột N:</strong> Updated At (YYYY-MM-DD HH:mm:ss, tùy chọn)</li>
                                </ul>
                                <strong class="mt-2 d-block">Lưu ý:</strong>
                                <ul class="mb-0" style="font-size: 11px;">
                                    <li>Nếu slug đã tồn tại, danh mục sẽ được cập nhật</li>
                                    <li>Nếu slug chưa tồn tại, danh mục mới sẽ được tạo</li>
                                    <li>Parent Slug phải tồn tại trong hệ thống (hoặc để trống cho danh mục gốc)</li>
                                    <li>File xuất và nhập có cấu trúc giống hệt nhau (round-trip)</li>
                                </ul>
                            </small>
                        </div>

                        @if (session('import_errors') && count(session('import_errors')) > 0)
                            <div class="alert alert-warning">
                                <strong>Chi tiết lỗi:</strong>
                                <ul class="mb-0" style="max-height: 200px; overflow-y: auto; font-size: 11px;">
                                    @foreach (session('import_errors') as $error)
                                        <li>Row {{ $error['row'] ?? 'N/A' }}: {{ $error['message'] ?? 'Unknown error' }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-primary">📤 Nhập danh mục</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush
