@extends('admins.layouts.master')

@section('title', 'Quản lý sản phẩm phổ biến')
@section('page-title', '⭐ Sản phẩm phổ biến')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/products-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .featured-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-top: 24px;
        }

        @media (max-width: 1024px) {
            .featured-container {
                grid-template-columns: 1fr;
            }
        }

        .featured-section {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid #e2e8f0;
        }

        .search-box {
            position: relative;
            margin-bottom: 20px;
        }

        .search-input {
            width: 100%;
            padding: 12px 16px;
            padding-right: 48px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
        }

        .search-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .search-btn {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            padding: 8px 16px;
            background: #3b82f6;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .search-btn:hover {
            background: #2563eb;
        }

        .search-btn:disabled {
            background: #94a3b8;
            cursor: not-allowed;
        }

        .product-list {
            max-height: 600px;
            overflow-y: auto;
        }

        .product-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 12px;
            transition: all 0.2s;
        }

        .product-item:hover {
            border-color: #3b82f6;
            background: #f8fafc;
        }

        .product-item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
            flex-shrink: 0;
        }

        .product-item-info {
            flex: 1;
            min-width: 0;
        }

        .product-item-name {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 4px;
            font-size: 14px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .product-item-meta {
            font-size: 12px;
            color: #64748b;
        }

        .product-item-actions {
            display: flex;
            gap: 8px;
            flex-shrink: 0;
        }

        .btn-action {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-add {
            background: #10b981;
            color: #fff;
        }

        .btn-add:hover {
            background: #059669;
        }

        .btn-remove {
            background: #ef4444;
            color: #fff;
        }

        .btn-remove:hover {
            background: #dc2626;
        }

        .btn-bulk {
            background: #3b82f6;
            color: #fff;
            padding: 8px 16px;
            border-radius: 6px;
            border: none;
            font-weight: 500;
            cursor: pointer;
            margin-top: 12px;
            width: 100%;
        }

        .btn-bulk:hover {
            background: #2563eb;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #64748b;
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 12px;
        }

        .empty-state-text {
            font-size: 14px;
        }

        .loading {
            text-align: center;
            padding: 20px;
            color: #64748b;
        }

        .checkbox-item {
            margin-right: 8px;
        }

        .featured-badge {
            display: inline-block;
            padding: 2px 8px;
            background: #fef3c7;
            color: #92400e;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 8px;
        }
    </style>
@endpush

@section('content')
    <div class="featured-container">
        <!-- Tìm kiếm và thêm sản phẩm -->
        <div class="featured-section">
            <h2 class="section-title">🔍 Tìm kiếm sản phẩm</h2>
            
            <div class="search-box">
                <input 
                    type="text" 
                    id="search-input" 
                    class="search-input" 
                    placeholder="Nhập tên hoặc SKU sản phẩm..."
                    autocomplete="off"
                >
                <button class="search-btn" id="search-btn">Tìm kiếm</button>
            </div>

            <div id="search-results" class="product-list">
                <div class="empty-state">
                    <div class="empty-state-icon">🔍</div>
                    <div class="empty-state-text">Nhập từ khóa để tìm kiếm sản phẩm</div>
                </div>
            </div>
        </div>

        <!-- Danh sách sản phẩm phổ biến -->
        <div class="featured-section">
            <h2 class="section-title">⭐ Sản phẩm phổ biến ({{ $featuredProducts->count() }})</h2>
            
            <div id="featured-list" class="product-list">
                @if($featuredProducts->isEmpty())
                    <div class="empty-state">
                        <div class="empty-state-icon">⭐</div>
                        <div class="empty-state-text">Chưa có sản phẩm phổ biến</div>
                    </div>
                @else
                    @foreach($featuredProducts as $product)
                        <div class="product-item" data-product-id="{{ $product->id }}">
                            <input 
                                type="checkbox" 
                                class="checkbox-item featured-checkbox" 
                                value="{{ $product->id }}"
                            >
                            <img 
                                src="{{ $product->primaryImage?->url ? asset('clients/assets/img/clothes/' . $product->primaryImage->url) : asset('clients/assets/img/clothes/no-image.webp') }}" 
                                alt="{{ $product->name }}"
                                class="product-item-image"
                                onerror="this.onerror=null;this.src='{{ asset('clients/assets/img/clothes/no-image.webp') }}'"
                            >
                            <div class="product-item-info">
                                <div class="product-item-name">{{ $product->name }}</div>
                                <div class="product-item-meta">
                                    SKU: {{ $product->sku ?? 'N/A' }}
                                    @if($product->primaryCategory)
                                        | {{ $product->primaryCategory->name }}
                                    @endif
                                </div>
                            </div>
                            <div class="product-item-actions">
                                <button 
                                    class="btn-action btn-remove" 
                                    onclick="removeProduct({{ $product->id }})"
                                >
                                    Xóa
                                </button>
                            </div>
                        </div>
                    @endforeach
                    
                    <button 
                        class="btn-bulk" 
                        id="bulk-remove-btn"
                        onclick="bulkRemove()"
                        style="display: none;"
                    >
                        Xóa các sản phẩm đã chọn
                    </button>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let searchTimeout;
        const searchInput = document.getElementById('search-input');
        const searchBtn = document.getElementById('search-btn');
        const searchResults = document.getElementById('search-results');
        const featuredCheckboxes = document.querySelectorAll('.featured-checkbox');
        const bulkRemoveBtn = document.getElementById('bulk-remove-btn');

        // Tìm kiếm khi nhấn Enter
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                performSearch();
            }
        });

        // Tìm kiếm khi click nút
        searchBtn.addEventListener('click', performSearch);

        // Tự động tìm kiếm khi gõ (debounce)
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const keyword = this.value.trim();
            
            if (keyword.length >= 2) {
                searchTimeout = setTimeout(() => {
                    performSearch();
                }, 500);
            } else if (keyword.length === 0) {
                showEmptyState();
            }
        });

        // Xử lý checkbox
        if (featuredCheckboxes.length > 0) {
            featuredCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateBulkButton);
            });
        }

        function updateBulkButton() {
            const checked = document.querySelectorAll('.featured-checkbox:checked');
            if (bulkRemoveBtn) {
                bulkRemoveBtn.style.display = checked.length > 0 ? 'block' : 'none';
            }
        }

        function performSearch() {
            const keyword = searchInput.value.trim();
            
            if (!keyword) {
                showEmptyState();
                return;
            }

            searchBtn.disabled = true;
            searchBtn.textContent = 'Đang tìm...';
            searchResults.innerHTML = '<div class="loading">Đang tìm kiếm...</div>';

            fetch(`{{ route('admin.featured-products.search') }}?keyword=${encodeURIComponent(keyword)}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                searchBtn.disabled = false;
                searchBtn.textContent = 'Tìm kiếm';

                if (!data.success) {
                    searchResults.innerHTML = `<div class="empty-state"><div class="empty-state-text">${data.message || 'Có lỗi xảy ra'}</div></div>`;
                    return;
                }

                if (data.data.length === 0) {
                    searchResults.innerHTML = '<div class="empty-state"><div class="empty-state-icon">🔍</div><div class="empty-state-text">Không tìm thấy sản phẩm nào</div></div>';
                    return;
                }

                let html = '';
                data.data.forEach(product => {
                    html += `
                        <div class="product-item" data-product-id="${product.id}">
                            <input 
                                type="checkbox" 
                                class="checkbox-item search-checkbox" 
                                value="${product.id}"
                            >
                            <img 
                                src="${product.image}" 
                                alt="${product.name}"
                                class="product-item-image"
                                onerror="this.onerror=null;this.src='{{ asset('clients/assets/img/clothes/no-image.webp') }}'"
                            >
                            <div class="product-item-info">
                                <div class="product-item-name">${product.name}</div>
                                <div class="product-item-meta">
                                    SKU: ${product.sku || 'N/A'}
                                    ${product.category ? '| ' + product.category : ''}
                                    ${product.brand ? '| ' + product.brand : ''}
                                </div>
                            </div>
                            <div class="product-item-actions">
                                <button 
                                    class="btn-action btn-add" 
                                    onclick="addProduct(${product.id})"
                                >
                                    Thêm
                                </button>
                            </div>
                        </div>
                    `;
                });

                html += `
                    <button 
                        class="btn-bulk" 
                        id="bulk-add-btn"
                        onclick="bulkAdd()"
                        style="display: none;"
                    >
                        Thêm các sản phẩm đã chọn
                    </button>
                `;

                searchResults.innerHTML = html;

                // Xử lý checkbox trong kết quả tìm kiếm
                const searchCheckboxes = document.querySelectorAll('.search-checkbox');
                const bulkAddBtn = document.getElementById('bulk-add-btn');
                
                searchCheckboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        const checked = document.querySelectorAll('.search-checkbox:checked');
                        if (bulkAddBtn) {
                            bulkAddBtn.style.display = checked.length > 0 ? 'block' : 'none';
                        }
                    });
                });
            })
            .catch(error => {
                console.error('Error:', error);
                searchBtn.disabled = false;
                searchBtn.textContent = 'Tìm kiếm';
                searchResults.innerHTML = '<div class="empty-state"><div class="empty-state-text">Có lỗi xảy ra khi tìm kiếm</div></div>';
            });
        }

        function showEmptyState() {
            searchResults.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">🔍</div>
                    <div class="empty-state-text">Nhập từ khóa để tìm kiếm sản phẩm</div>
                </div>
            `;
        }

        function addProduct(productId) {
            if (!confirm('Bạn có chắc muốn thêm sản phẩm này vào danh sách phổ biến?')) {
                return;
            }

            fetch(`{{ route('admin.featured-products.add') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    product_ids: [productId]
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message || 'Có lỗi xảy ra');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi thêm sản phẩm');
            });
        }

        function removeProduct(productId) {
            if (!confirm('Bạn có chắc muốn xóa sản phẩm này khỏi danh sách phổ biến?')) {
                return;
            }

            fetch(`{{ route('admin.featured-products.remove') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    product_ids: [productId]
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message || 'Có lỗi xảy ra');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi xóa sản phẩm');
            });
        }

        function bulkAdd() {
            const checked = document.querySelectorAll('.search-checkbox:checked');
            if (checked.length === 0) {
                alert('Vui lòng chọn ít nhất một sản phẩm');
                return;
            }

            const productIds = Array.from(checked).map(cb => parseInt(cb.value));
            
            if (!confirm(`Bạn có chắc muốn thêm ${productIds.length} sản phẩm vào danh sách phổ biến?`)) {
                return;
            }

            fetch(`{{ route('admin.featured-products.add') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    product_ids: productIds
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message || 'Có lỗi xảy ra');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi thêm sản phẩm');
            });
        }

        function bulkRemove() {
            const checked = document.querySelectorAll('.featured-checkbox:checked');
            if (checked.length === 0) {
                alert('Vui lòng chọn ít nhất một sản phẩm');
                return;
            }

            const productIds = Array.from(checked).map(cb => parseInt(cb.value));
            
            if (!confirm(`Bạn có chắc muốn xóa ${productIds.length} sản phẩm khỏi danh sách phổ biến?`)) {
                return;
            }

            fetch(`{{ route('admin.featured-products.remove') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    product_ids: productIds
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message || 'Có lỗi xảy ra');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi xóa sản phẩm');
            });
        }
    </script>
@endpush
