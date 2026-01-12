@extends('admins.layouts.master')

@section('title', 'Tools Website')
@section('page-title', '🛠️ Tools Website')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/tools-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .tools-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px 0;
        }
        .tool-card {
            background: #fff;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.08);
            margin-bottom: 32px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        .tool-card:hover {
            box-shadow: 0 8px 30px rgba(15, 23, 42, 0.12);
        }
        .tool-card.highlight {
            animation: highlightTool 2s ease-out;
        }
        @keyframes highlightTool {
            0%, 100% { border-color: transparent; background: #fff; }
            50% { border-color: #3b82f6; background: #eff6ff; }
        }
        
        /* Tool Images - Màu xanh lá */
        #images-tool {
            border-left: 5px solid #10b981;
        }
        #images-tool .tool-card-header {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 24px;
        }
        #images-tool .tool-card-header h3 {
            color: #047857;
        }
        
        /* Tool Tags - Màu cam */
        #tags-tool {
            border-left: 5px solid #f59e0b;
        }
        #tags-tool .tool-card-header {
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 24px;
        }
        #tags-tool .tool-card-header h3 {
            color: #d97706;
        }
        
        .tool-card-header {
            margin-bottom: 24px;
            border: none;
        }
        .tool-card-header h3 {
            margin: 0 0 12px 0;
            font-size: 24px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .tool-card-header p {
            margin: 0;
            color: #64748b;
            font-size: 15px;
            line-height: 1.6;
        }
        .tool-card-header code {
            background: rgba(0,0,0,0.05);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 13px;
        }
        .tool-actions {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin-top: 24px;
        }
        .btn {
            padding: 12px 24px;
            border-radius: 10px;
            border: none;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
        }
        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }
        .btn-danger:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4);
        }
        .btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05) !important;
        }
        .stats-info {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 12px;
            padding: 20px 24px;
            margin: 20px 0;
            border: 1px solid #e2e8f0;
        }
        .stats-info p {
            margin: 0;
            color: #0f172a;
            font-size: 16px;
            font-weight: 500;
        }
        .stats-info strong {
            color: #3b82f6;
            font-size: 22px;
            font-weight: 700;
        }
        .unused-tags-list {
            max-height: 450px;
            overflow-y: auto;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            margin-top: 20px;
            background: #fafafa;
        }
        .unused-tag-item, .unused-image-item {
            padding: 12px 16px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s;
        }
        .unused-tag-item:hover, .unused-image-item:hover {
            border-color: #cbd5e1;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .unused-tag-item:last-child, .unused-image-item:last-child {
            margin-bottom: 0;
        }
        .unused-tag-info, .unused-image-info {
            flex: 1;
        }
        .unused-tag-name, .unused-image-name {
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 6px;
            font-size: 15px;
        }
        .unused-tag-meta, .unused-image-meta {
            font-size: 13px;
            color: #64748b;
        }
        .loading {
            display: none;
            text-align: center;
            padding: 30px;
            color: #64748b;
        }
        .loading.active {
            display: block;
        }
        .loading::after {
            content: "...";
            animation: dots 1.5s steps(4, end) infinite;
        }
        @keyframes dots {
            0%, 20% { content: "."; }
            40% { content: ".."; }
            60%, 100% { content: "..."; }
        }
        .alert {
            padding: 16px 20px;
            border-radius: 10px;
            margin: 20px 0;
            font-size: 15px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .alert::before {
            font-size: 20px;
        }
        .alert-success {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            color: #166534;
            border: 2px solid #86efac;
        }
        .alert-success::before {
            content: "✅";
        }
        .alert-danger {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border: 2px solid #fca5a5;
        }
        .alert-danger::before {
            content: "❌";
        }
        .alert-info {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
            border: 2px solid #93c5fd;
        }
        .alert-info::before {
            content: "ℹ️";
        }
        
        .btn-info {
            background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
            color: white;
            text-decoration: none;
        }
        .btn-info:hover {
            background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%);
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }
        
        /* Scrollbar styling */
        .unused-tags-list::-webkit-scrollbar {
            width: 8px;
        }
        .unused-tags-list::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }
        .unused-tags-list::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .unused-tags-list::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
@endpush

@section('content')
    <div class="tools-container">
        <!-- Tool: Xóa Ảnh Không Được Sử Dụng -->
        <div class="tool-card" id="images-tool">
            <div class="tool-card-header">
                <h3>🖼️ Xóa Ảnh Sản Phẩm Không Được Sử Dụng</h3>
                <p>Quét và xóa các ảnh trong thư mục <code>/public/clients/assets/img/clothes</code> (bao gồm tất cả thư mục con) không được sử dụng trong database</p>
            </div>
            <div class="tool-actions">
                <button type="button" class="btn btn-primary" id="btn-check-images">
                    🔍 Kiểm tra ảnh không được sử dụng
                </button>
                <button type="button" class="btn btn-danger" id="btn-delete-images" disabled>
                    🗑️ Xóa ảnh không được sử dụng
                </button>
            </div>
            <div id="images-alert-container" style="margin-top: 16px;"></div>
            <div class="loading" id="images-loading">
                <p>Đang xử lý...</p>
            </div>
            <div class="stats-info" id="images-stats-container" style="display: none; margin-top: 20px;">
                <p>
                    <strong id="unused-images-count">0</strong> ảnh không được sử dụng | 
                    Tổng dung lượng: <strong id="unused-images-size">0 MB</strong>
                </p>
            </div>
            <div class="unused-tags-list" id="unused-images-list" style="display: none; margin-top: 20px;">
                <!-- Ảnh sẽ được hiển thị ở đây -->
            </div>
        </div>

        <!-- Tool: Xóa tags không được sử dụng -->
        <div class="tool-card" id="tags-tool">
            <div class="tool-card-header">
                <h3>🗑️ Xóa Tags Không Được Sử Dụng</h3>
                <p>Xóa tất cả các tags không được bài viết và sản phẩm sử dụng. Tool sẽ tự động cập nhật cột <code>usage_count</code> dựa trên số lượng products/posts sử dụng tag đó, sau đó xóa các tags có <code>usage_count = 0</code>.</p>
            </div>

            <div id="alert-container"></div>

            <div class="stats-info" id="stats-container" style="display: none;">
                <p><strong id="unused-count">0</strong> tag(s) không được sử dụng</p>
            </div>

            <div class="tool-actions">
                <button type="button" class="btn btn-primary" id="btn-check-stats">
                    📊 Kiểm tra tags không được sử dụng
                </button>
                <button type="button" class="btn btn-danger" id="btn-delete-unused" disabled>
                    🗑️ Xóa tags không được sử dụng
                </button>
            </div>

            <div class="loading" id="loading">
                <p>Đang xử lý...</p>
            </div>

            <div class="unused-tags-list" id="unused-tags-list" style="display: none;">
                <!-- Tags sẽ được hiển thị ở đây -->
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const btnCheckStats = document.getElementById('btn-check-stats');
        const btnDeleteUnused = document.getElementById('btn-delete-unused');
        const loading = document.getElementById('loading');
        const alertContainer = document.getElementById('alert-container');
        const statsContainer = document.getElementById('stats-container');
        const unusedTagsList = document.getElementById('unused-tags-list');
        const unusedCount = document.getElementById('unused-count');

        let unusedTagsData = [];

        // Kiểm tra stats
        btnCheckStats.addEventListener('click', async function() {
            this.disabled = true;
            loading.classList.add('active');
            alertContainer.innerHTML = '';
            statsContainer.style.display = 'none';
            unusedTagsList.style.display = 'none';

            try {
                const url = '{{ route("admin.tools.unused-tags-stats") }}';
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                
                console.log('🔵 [DEBUG] Fetch URL:', url);
                console.log('🔵 [DEBUG] CSRF Token:', csrfToken ? 'Found' : 'NOT FOUND');
                
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken || '',
                    },
                });

                console.log('🔵 [DEBUG] Response status:', response.status);
                console.log('🔵 [DEBUG] Response ok:', response.ok);
                console.log('🔵 [DEBUG] Response headers:', Object.fromEntries(response.headers.entries()));

                const contentType = response.headers.get('content-type');
                console.log('🔵 [DEBUG] Content-Type:', contentType);

                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('🔴 [DEBUG] Non-JSON response:', text.substring(0, 500));
                    alertContainer.innerHTML = `<div class="alert alert-danger">❌ Server trả về dữ liệu không hợp lệ (không phải JSON). Status: ${response.status}<br><small>${text.substring(0, 200)}</small></div>`;
                    return;
                }

                const result = await response.json();
                console.log('🔵 [DEBUG] Response JSON:', result);

                if (result.success) {
                    unusedTagsData = result.unused_tags || [];
                    unusedCount.textContent = result.unused_count || 0;

                    console.log('🔵 [DEBUG] Result success:', result.success);
                    console.log('🔵 [DEBUG] Unused count:', result.unused_count);
                    console.log('🔵 [DEBUG] Tags returned:', result.unused_tags?.length || 0);
                    console.log('🔵 [DEBUG] Has more:', result.has_more);

                    if (result.unused_count > 0) {
                        console.log('🟢 [DEBUG] Found unused tags, displaying...');
                        statsContainer.style.display = 'block';
                        unusedTagsList.style.display = 'block';
                        btnDeleteUnused.disabled = false;

                        // Hiển thị cảnh báo nếu có nhiều hơn số lượng hiển thị
                        let warningHtml = '';
                        if (result.has_more) {
                            warningHtml = `<div class="alert alert-info" style="margin-bottom: 16px;">
                                ⚠️ Chỉ hiển thị ${result.limit || 1000} tags đầu tiên trong tổng số ${result.unused_count} tags không được sử dụng.
                            </div>`;
                        }

                        // Hiển thị danh sách tags
                        const tagsHtml = unusedTagsData.map(tag => {
                            const entityType = tag.entity_type_label || 
                                             (tag.entity_type === 'App\\Models\\Product' ? 'Sản phẩm' : 
                                              tag.entity_type === 'App\\Models\\Post' ? 'Bài viết' : 
                                              tag.entity_type);
                            return `
                                <div class="unused-tag-item">
                                    <div class="unused-tag-info">
                                        <div class="unused-tag-name">${tag.name}</div>
                                        <div class="unused-tag-meta">
                                            ID: ${tag.id} | Slug: ${tag.slug} | Entity: ${entityType} (ID: ${tag.entity_id}) | 
                                            Usage Count: <strong style="color: #ef4444;">${tag.usage_count || 0}</strong> | Tạo: ${tag.created_at}
                                        </div>
                                    </div>
                                </div>
                            `;
                        }).join('');

                        unusedTagsList.innerHTML = warningHtml + tagsHtml;
                        console.log('🟢 [DEBUG] Tags HTML inserted into DOM');
                    } else {
                        console.log('🟢 [DEBUG] No unused tags found');
                        statsContainer.style.display = 'block';
                        alertContainer.innerHTML = '<div class="alert alert-success">✅ Không có tags nào không được sử dụng. Tất cả tags đều đang được sử dụng bởi ít nhất một sản phẩm hoặc bài viết.</div>';
                    }
                } else {
                    console.error('🔴 [DEBUG] Result not successful:', result);
                    alertContainer.innerHTML = `<div class="alert alert-danger">❌ ${result.message || 'Có lỗi xảy ra khi kiểm tra.'}</div>`;
                    if (result.error) {
                        console.error('🔴 [DEBUG] Error details:', result.error);
                    }
                }
            } catch (error) {
                alertContainer.innerHTML = `<div class="alert alert-danger">❌ Lỗi: ${error.message}</div>`;
            } finally {
                this.disabled = false;
                loading.classList.remove('active');
            }
        });

        // Xóa tags không được sử dụng
        btnDeleteUnused.addEventListener('click', async function() {
            if (unusedTagsData.length === 0) {
                alert('Không có tags nào để xóa.');
                return;
            }

            if (!confirm(`Bạn có chắc chắn muốn xóa ${unusedTagsData.length} tag(s) không được sử dụng? Hành động này không thể hoàn tác!`)) {
                return;
            }

            this.disabled = true;
            btnCheckStats.disabled = true;
            loading.classList.add('active');
            alertContainer.innerHTML = '';

            try {
                const response = await fetch('{{ route("admin.tools.delete-unused-tags") }}', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                });

                const result = await response.json();

                if (result.success) {
                    alertContainer.innerHTML = `<div class="alert alert-success">✅ ${result.message}</div>`;
                    statsContainer.style.display = 'none';
                    unusedTagsList.style.display = 'none';
                    unusedTagsData = [];
                    btnDeleteUnused.disabled = true;
                } else {
                    alertContainer.innerHTML = `<div class="alert alert-danger">❌ ${result.message || 'Có lỗi xảy ra khi xóa.'}</div>`;
                }
            } catch (error) {
                alertContainer.innerHTML = `<div class="alert alert-danger">❌ Lỗi: ${error.message}</div>`;
            } finally {
                this.disabled = false;
                btnCheckStats.disabled = false;
                loading.classList.remove('active');
            }
        });

        // ===== Xóa Ảnh Không Được Sử Dụng =====
        const btnCheckImages = document.getElementById('btn-check-images');
        const btnDeleteImages = document.getElementById('btn-delete-images');
        const imagesAlertContainer = document.getElementById('images-alert-container');
        const imagesLoading = document.getElementById('images-loading');
        const imagesStatsContainer = document.getElementById('images-stats-container');
        const unusedImagesList = document.getElementById('unused-images-list');
        const unusedImagesCount = document.getElementById('unused-images-count');
        const unusedImagesSize = document.getElementById('unused-images-size');

        let unusedImagesData = [];

        // Kiểm tra ảnh không được sử dụng
        btnCheckImages.addEventListener('click', async function() {
            console.log('🔵 [DEBUG] Button check images clicked');
            this.disabled = true;
            imagesLoading.classList.add('active');
            imagesAlertContainer.innerHTML = '';
            imagesStatsContainer.style.display = 'none';
            unusedImagesList.style.display = 'none';

            try {
                const url = '{{ route("admin.tools.unused-images-stats") }}';
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                
                console.log('🔵 [DEBUG] Fetch URL:', url);
                
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken || '',
                    },
                });

                console.log('🔵 [DEBUG] Response status:', response.status);

                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('🔴 [DEBUG] Non-JSON response:', text.substring(0, 500));
                    imagesAlertContainer.innerHTML = `<div class="alert alert-danger">❌ Server trả về dữ liệu không hợp lệ. Status: ${response.status}</div>`;
                    return;
                }

                const result = await response.json();
                console.log('🔵 [DEBUG] Response JSON:', result);

                if (result.success) {
                    unusedImagesData = result.unused_images || [];
                    unusedImagesCount.textContent = result.unused_count || 0;
                    unusedImagesSize.textContent = result.total_size || '0 B';

                    console.log('🔵 [DEBUG] Unused images count:', result.unused_count);

                    if (result.unused_count > 0) {
                        console.log('🟢 [DEBUG] Found unused images, displaying...');
                        imagesStatsContainer.style.display = 'block';
                        unusedImagesList.style.display = 'block';
                        btnDeleteImages.disabled = false;

                        // Hiển thị cảnh báo nếu có nhiều hơn số lượng hiển thị
                        let warningHtml = '';
                        if (result.has_more) {
                            warningHtml = `<div class="alert alert-info" style="margin-bottom: 16px;">
                                ⚠️ Chỉ hiển thị ${result.limit || 1000} ảnh đầu tiên trong tổng số ${result.unused_count} ảnh không được sử dụng.
                            </div>`;
                        }

                        // Hiển thị danh sách ảnh
                        const imagesHtml = unusedImagesData.map(img => {
                            return `
                                <div class="unused-image-item">
                                    <div class="unused-image-info">
                                        <div class="unused-image-name">🖼️ ${img.name}</div>
                                        <div class="unused-image-meta">
                                            📂 Path: ${img.path} | 💾 Size: ${img.size}
                                        </div>
                                    </div>
                                    ${img.url ? `<a href="${img.url}" target="_blank" class="btn btn-sm btn-info" style="padding: 6px 12px; font-size: 12px;">👁️ Xem</a>` : ''}
                                </div>
                            `;
                        }).join('');

                        unusedImagesList.innerHTML = warningHtml + imagesHtml;
                        console.log('🟢 [DEBUG] Images HTML inserted into DOM');
                    } else {
                        console.log('🟢 [DEBUG] No unused images found');
                        imagesStatsContainer.style.display = 'block';
                        imagesAlertContainer.innerHTML = '<div class="alert alert-success">✅ Không có ảnh nào không được sử dụng. Tất cả ảnh đều đang được sử dụng trong database.</div>';
                    }
                } else {
                    console.error('🔴 [DEBUG] Result not successful:', result);
                    imagesAlertContainer.innerHTML = `<div class="alert alert-danger">❌ ${result.message || 'Có lỗi xảy ra khi kiểm tra.'}</div>`;
                }
            } catch (error) {
                console.error('🔴 [DEBUG] Check images error:', error);
                imagesAlertContainer.innerHTML = `<div class="alert alert-danger">❌ Lỗi: ${error.message}<br><small>Xem console để biết chi tiết</small></div>`;
            } finally {
                console.log('🔵 [DEBUG] Finally block - re-enabling button');
                this.disabled = false;
                imagesLoading.classList.remove('active');
            }
        });

        // Xóa ảnh không được sử dụng
        btnDeleteImages.addEventListener('click', async function() {
            if (unusedImagesData.length === 0) {
                alert('Không có ảnh nào để xóa.');
                return;
            }

            const confirmMsg = `Bạn có chắc chắn muốn xóa ${unusedImagesData.length} ảnh không được sử dụng?\n\nHành động này không thể hoàn tác!`;
            if (!confirm(confirmMsg)) {
                return;
            }

            console.log('🔵 [DEBUG] Deleting unused images...');
            this.disabled = true;
            imagesLoading.classList.add('active');
            imagesAlertContainer.innerHTML = '';

            try {
                const url = '{{ route("admin.tools.delete-unused-images") }}';
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken || '',
                        'Content-Type': 'application/json',
                    },
                });

                const result = await response.json();
                console.log('🔵 [DEBUG] Delete response:', result);

                if (result.success) {
                    imagesAlertContainer.innerHTML = `<div class="alert alert-success">✅ ${result.message}<br><small>Đã giải phóng: ${result.deleted_size_formatted}</small></div>`;
                    
                    // Reset UI
                    unusedImagesData = [];
                    imagesStatsContainer.style.display = 'none';
                    unusedImagesList.style.display = 'none';
                    btnDeleteImages.disabled = true;
                    
                    console.log('🟢 [DEBUG] Images deleted successfully');
                } else {
                    imagesAlertContainer.innerHTML = `<div class="alert alert-danger">❌ ${result.message || 'Có lỗi xảy ra khi xóa.'}</div>`;
                }
            } catch (error) {
                console.error('🔴 [DEBUG] Delete images error:', error);
                imagesAlertContainer.innerHTML = `<div class="alert alert-danger">❌ Lỗi: ${error.message}</div>`;
            } finally {
                this.disabled = false;
                imagesLoading.classList.remove('active');
            }
        });

        // Scroll to tool if URL has hash
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const scrollTo = urlParams.get('scroll');
            
            if (scrollTo && window.location.hash) {
                const targetId = window.location.hash.substring(1); // Remove #
                const targetElement = document.getElementById(targetId);
                
                if (targetElement) {
                    setTimeout(function() {
                        targetElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        // Highlight the tool card briefly
                        targetElement.style.boxShadow = '0 0 20px rgba(59, 130, 246, 0.5)';
                        targetElement.style.transition = 'box-shadow 0.3s';
                        setTimeout(function() {
                            targetElement.style.boxShadow = '';
                        }, 2000);
                    }, 300);
                }
            }
        });
    </script>
@endpush
