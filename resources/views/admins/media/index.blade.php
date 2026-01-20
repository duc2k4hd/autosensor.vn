@extends('admins.layouts.master')

@section('title', 'Quản lý Media')
@section('page-title', '📷 Media Manager')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/media-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .media-manager {
            display: flex;
            height: calc(100vh - 120px);
            gap: 20px;
            min-height: 600px;
            overflow: hidden;
        }

        /* Sidebar - Folder Tree */
        .media-sidebar {
            width: 280px;
            min-width: 250px;
            max-width: 320px;
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            overflow-y: auto;
            overflow-x: hidden;
            flex-shrink: 0;
        }
        
        @media (max-width: 768px) {
            .media-sidebar {
                width: 100%;
                max-width: 100%;
                border-radius: 0;
            }
            
            .media-manager {
                flex-direction: column;
                height: auto;
            }
        }

        .media-sidebar h3 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 15px;
            color: #1e293b;
        }

        .scope-selector {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
        }

        .scope-btn {
            flex: 1;
            padding: 8px 12px;
            border: 2px solid #e2e8f0;
            background: #fff;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }

        .scope-btn.active {
            background: #3b82f6;
            color: #fff;
            border-color: #3b82f6;
        }

        .folder-tree {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .folder-item {
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
        }

        .folder-item:hover {
            background: #f1f5f9;
        }

        .folder-item.active {
            background: #dbeafe;
            color: #1e40af;
            font-weight: 600;
        }

        .folder-icon {
            width: 20px;
            text-align: center;
        }

        .folder-children {
            margin-left: 24px;
            margin-top: 4px;
        }

        /* Main Content */
        .media-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            overflow: hidden;
            min-width: 0;
        }

        /* Toolbar */
        .media-toolbar {
            padding: 15px 20px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            flex-shrink: 0;
            background: #fff;
            z-index: 10;
        }

        .media-toolbar .btn {
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
            font-size: 14px;
        }

        .btn-primary {
            background: #3b82f6;
            color: #fff;
        }

        .btn-primary:hover {
            background: #2563eb;
        }

        .btn-danger {
            background: #ef4444;
            color: #fff;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-secondary {
            background: #64748b;
            color: #fff;
        }

        .search-box {
            flex: 1;
            min-width: 200px;
            max-width: 400px;
            padding: 8px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .search-box:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .per-page-select {
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            background: #fff;
            cursor: pointer;
            font-weight: 500;
            min-width: 100px;
        }
        
        .per-page-select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .view-toggle {
            display: flex;
            gap: 4px;
        }

        .view-btn {
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            background: #fff;
            border-radius: 6px;
            cursor: pointer;
        }

        .view-btn.active {
            background: #3b82f6;
            color: #fff;
            border-color: #3b82f6;
        }

        /* Breadcrumb */
        .media-breadcrumb {
            padding: 12px 20px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            flex-shrink: 0;
            background: #f8fafc;
        }

        .breadcrumb-item {
            color: #64748b;
            cursor: pointer;
        }

        .breadcrumb-item:hover {
            color: #3b82f6;
        }

        .breadcrumb-separator {
            color: #cbd5e1;
        }

        /* Gallery */
        .media-gallery {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 20px;
            min-height: 0;
            background: #f8fafc;
        }
        
        .media-gallery::-webkit-scrollbar {
            width: 8px;
        }
        
        .media-gallery::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        
        .media-gallery::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        
        .media-gallery::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .media-grid-view {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 16px;
            width: 100%;
            padding: 0;
            margin: 0;
        }
        
        @media (min-width: 1200px) {
            .media-grid-view {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 18px;
        }
        }
        
        @media (min-width: 1600px) {
            .media-grid-view {
                grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
                gap: 20px;
            }
        }
        
        @media (max-width: 768px) {
            .media-grid-view {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                gap: 12px;
            }
        }

        .media-list-view {
            display: flex;
            flex-direction: column;
            gap: 8px;
            text-align: start;
        }

        .media-item {
            background: #fff;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
            width: 100%;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            margin: 0;
            padding: 4px;
        }

        .media-item:hover {
            border-color: #3b82f6;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
        }

        .media-item.selected {
            border-color: #3b82f6;
            background: #eff6ff;
        }

        .media-item.grid {
            aspect-ratio: 1;
            height: auto;
        }
        
        .media-item.grid .media-thumb {
            width: 100%;
            height: 0;
            padding-bottom: 100%;
            position: relative;
            overflow: hidden;
        }
        
        .media-item.grid .media-thumb img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        
        .media-item.grid .media-info {
            padding: 10px 12px;
            flex-shrink: 0;
            border-top: 1px solid #f1f5f9;
            background: #fff;
        }

        .media-item.list {
            display: flex;
            align-items: center;
            padding: 12px;
            gap: 16px;
        }

        .media-thumb {
            width: 100%;
            height: auto;
            object-fit: cover;
            display: block;
            background: #f8fafc;
        }
        
        .media-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .media-item.list .media-thumb {
            width: 80px;
            height: 80px;
            border-radius: 8px;
        }

        .media-info {
            padding: 12px;
            flex-shrink: 0;
            min-height: 50px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            background: #fff;
        }

        .media-item.list .media-info {
            flex: 1;
            padding: 0;
        }

        .media-name {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.4;
            color: #1e293b;
        }
        
        .media-item.grid .media-name {
            font-size: 12px;
            margin-bottom: 2px;
        }

        .media-meta {
            font-size: 11px;
            color: #64748b;
            line-height: 1.4;
        }
        
        .media-item.grid .media-meta {
            font-size: 10px;
        }

        .media-checkbox {
            position: absolute;
            top: 8px;
            left: 8px;
            width: 20px;
            height: 20px;
            border: 2px solid #fff;
            border-radius: 4px;
            background: rgba(255,255,255,0.9);
            cursor: pointer;
            z-index: 10;
        }

        .media-checkbox:checked {
            background: #3b82f6;
            border-color: #3b82f6;
        }

        .folder-item-view {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
            height: fit-content;
        }

        .folder-item-view:hover {
            border-color: #3b82f6;
            background: #eff6ff;
        }

        .folder-icon-large {
            font-size: 48px;
            color: #fbbf24;
        }

        /* Preview Panel */
        .media-preview {
            width: 360px;
            min-width: 300px;
            max-width: 400px;
            background: #fff;
            border-left: 1px solid #e2e8f0;
            padding: 20px;
            overflow-y: auto;
            display: none;
            flex-shrink: 0;
            border-radius: 0 12px 12px 0;
        }
        
        @media (max-width: 1200px) {
            .media-preview {
                position: fixed;
                right: 0;
                top: 0;
                bottom: 0;
                width: 100%;
                max-width: 400px;
                z-index: 1000;
                box-shadow: -4px 0 12px rgba(0,0,0,0.1);
            }
        }

        .media-preview.active {
            display: block;
        }

        .preview-image {
            width: 100%;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .preview-info h4 {
            font-size: 18px;
            margin-bottom: 12px;
        }

        .preview-info-item {
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
        }

        .preview-info-label {
            font-weight: 600;
            color: #64748b;
        }

        .preview-actions {
            margin-top: 20px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        /* Upload Dropzone */
        .upload-dropzone {
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 20px;
        }

        .upload-dropzone.dragover {
            border-color: #3b82f6;
            background: #eff6ff;
        }

        /* Context Menu */
        .context-menu {
            position: fixed;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            padding: 8px 0;
            z-index: 1000;
            min-width: 180px;
            display: none;
            max-height: 300px;
        }

        .context-menu-item {
            padding: 10px 16px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .context-menu-item:hover {
            background: #f1f5f9;
        }

        .context-menu-item.danger {
            color: #ef4444;
        }

        /* Loading */
        .media-loading {
            text-align: center;
            padding: 40px;
            color: #64748b;
        }

        .media-empty {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }

        .media-empty-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }

        /* Pagination */
        .media-pagination {
            padding: 15px 20px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            background: #fff;
            flex-shrink: 0;
        }

        .media-pagination .btn {
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            border: 1px solid #e2e8f0;
            background: #fff;
            cursor: pointer;
            transition: all 0.2s;
            min-width: 100px;
            color: red;
        }

        .media-pagination .btn:hover:not(:disabled) {
            background: #f1f5f9;
            border-color: #3b82f6;
        }

        .media-pagination .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination-info {
            font-size: 14px;
            color: #64748b;
            font-weight: 600;
            min-width: 120px;
            text-align: center;
        }

        /* Loading Overlay */
        .media-loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            backdrop-filter: blur(2px);
        }

        .media-loading-overlay.active {
            display: flex;
        }

        .media-loading-overlay-content {
            text-align: center;
            padding: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .media-loading-overlay-content .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f4f6;
            border-top-color: #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 16px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .media-loading-overlay-content .text {
            font-size: 16px;
            color: #64748b;
            font-weight: 600;
        }
    </style>
@endpush

@section('content')
    <div class="media-manager">
        <!-- Sidebar - Folder Tree -->
        <div class="media-sidebar">
            <h3>📁 Thư mục</h3>
            
            <div class="scope-selector">
                <button class="scope-btn active" data-scope="admin">Admin</button>
                <button class="scope-btn" data-scope="client">Client</button>
                <button class="scope-btn" data-scope="catalog">Catalog</button>
            </div>

            <div class="folder-tree" id="folderTree">
                <div class="media-loading">Đang tải...</div>
            </div>

            <div style="margin-top: 20px;">
                <button class="btn btn-primary" style="width: 100%;" id="btnCreateFolder">
                    ➕ Tạo thư mục
                </button>
            </div>
            </div>

        <!-- Main Content -->
        <div class="media-content">
            <!-- Toolbar -->
            <div class="media-toolbar">
                <button class="btn btn-primary" id="btnUpload">
                    📤 Upload
                </button>
                <button class="btn btn-secondary" id="btnRefresh">
                    🔄 Làm mới
                </button>
                <button class="btn btn-secondary" id="btnSelectAll">
                    ☑️ Chọn tất cả
                </button>
                <button class="btn btn-danger" id="btnDeleteSelected" style="display: none;">
                    🗑️ Xóa đã chọn
                </button>
                <input type="text" class="search-box" id="searchBox" placeholder="🔍 Tìm kiếm...">
                <select class="per-page-select" id="perPageSelect" title="Số lượng hiển thị">
                    <option value="100" selected>100 ảnh</option>
                    <option value="200">200 ảnh</option>
                    <option value="500">500 ảnh</option>
                    <option value="2000">2000 ảnh</option>
                </select>
                <div class="view-toggle">
                    <button class="view-btn active" data-view="grid" title="Grid view">⊞</button>
                    <button class="view-btn" data-view="list" title="List view">☰</button>
            </div>
            </div>

            <!-- Breadcrumb -->
            <div class="media-breadcrumb" id="breadcrumb">
                <span class="breadcrumb-item" data-path="">🏠 Root</span>
        </div>

            <!-- Gallery -->
            <div class="media-gallery" id="mediaGallery">
                <div class="media-loading">Đang tải...</div>
            </div>
            
            <!-- Pagination -->
            <div class="media-pagination" id="mediaPagination" style="display: none;">
                <button class="btn btn-secondary" id="btnPrevPage" disabled>
                    ← Trước
                </button>
                <span class="pagination-info" id="paginationInfo">
                    Trang 1 / 1
                </span>
                <button class="btn btn-secondary" id="btnNextPage" disabled>
                    Sau →
                </button>
            </div>
        </div>

        <!-- Preview Panel -->
        <div class="media-preview" id="previewPanel">
            <div class="preview-image-container" id="previewImageContainer"></div>
            <div class="preview-info" id="previewInfo"></div>
            <div class="preview-actions" id="previewActions"></div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="media-loading-overlay" id="mediaLoadingOverlay">
        <div class="media-loading-overlay-content">
            <div class="spinner"></div>
            <div class="text">Đang tải...</div>
        </div>
    </div>

    <!-- Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Files</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                <div class="modal-body">
                    <div class="upload-dropzone" id="uploadDropzone">
                        <p>📤 Kéo thả files vào đây hoặc click để chọn</p>
                        <input type="file" id="fileInput" multiple style="display: none;">
                    </div>
                    <div id="uploadProgress" style="display: none;"></div>
                    </div>
                        </div>
                    </div>
        </div>

    <!-- Context Menu -->
    <div class="context-menu" id="contextMenu">
        <div class="context-menu-item" data-action="rename">✏️ Đổi tên</div>
        <div class="context-menu-item" data-action="copy">📋 Copy</div>
        <div class="context-menu-item" data-action="cut">✂️ Cut</div>
        <div class="context-menu-item" data-action="move">📁 Di chuyển</div>
        <div class="context-menu-item" data-action="download">⬇️ Download</div>
        <div class="context-menu-item danger" data-action="delete">🗑️ Xóa</div>
        </div>
@endsection

@push('scripts')
    <script>
        // Global state
        const mediaState = {
            scope: '{{ $scope }}',
            currentFolder: '{{ $folder }}',
            view: 'grid',
            selectedFiles: new Set(),
            clipboard: {
                type: null, // 'copy' or 'cut'
                files: []
            },
            displayedFiles: [], // All files from API
            displayedCount: 0, // Number of files currently displayed
            perPage: 100, // Items per page (100, 200, 500, 2000)
            currentPage: 1, // Current page number
            totalFiles: 0, // Total files count
            hasMore: false // Has more pages
        };

        // API base URL
        const apiBase = '{{ route("admin.media.list") }}';

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            initScopeSelector();
            initFolderTree();
            initGallery();
            initUpload();
            initSearch();
            initViewToggle();
            initContextMenu();
            initKeyboardShortcuts();
        });

        // Loading overlay functions
        function showLoadingOverlay(text = 'Đang tải...') {
            const overlay = document.getElementById('mediaLoadingOverlay');
            const textEl = overlay.querySelector('.text');
            if (textEl) {
                textEl.textContent = text;
            }
            overlay.classList.add('active');
        }

        function hideLoadingOverlay() {
            const overlay = document.getElementById('mediaLoadingOverlay');
            overlay.classList.remove('active');
        }

        // Scope selector
        function initScopeSelector() {
            document.querySelectorAll('.scope-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    // Show loading overlay
                    showLoadingOverlay('Đang chuyển scope...');
                    
                    document.querySelectorAll('.scope-btn').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    mediaState.scope = btn.dataset.scope;
                    mediaState.currentFolder = '';
                    // Clear selection khi chuyển scope
                    mediaState.selectedFiles.clear();
                    updateSelectionUI();
                    loadFolderTree();
                    loadGallery();
                });
            });
        }

        // Load folder tree
        function loadFolderTree() {
            fetch(`{{ route("admin.media.folder-tree") }}?scope=${mediaState.scope}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        renderFolderTree(data.tree);
                    }
                })
                .catch(err => console.error('Error loading folder tree:', err));
        }

        function initFolderTree() {
            loadFolderTree();
        }

        function renderFolderTree(tree, container = null, level = 0) {
            const ul = container || document.getElementById('folderTree');
            if (!container) ul.innerHTML = '';

            tree.forEach(folder => {
                const li = document.createElement('div');
                li.className = 'folder-item';
                li.dataset.path = folder.path;
                li.style.paddingLeft = `${12 + level * 16}px`;
                
                li.innerHTML = `
                    <span class="folder-icon">${folder.has_children ? '📁' : '📂'}</span>
                    <span>${folder.name}</span>
                `;

                li.addEventListener('click', (e) => {
                    e.stopPropagation();
                    // Show loading overlay
                    showLoadingOverlay('Đang tải thư mục...');
                    
                    document.querySelectorAll('.folder-item').forEach(item => {
                        item.classList.remove('active');
                    });
                    li.classList.add('active');
                    // Normalize path to ensure it's relative
                    let path = folder.path || '';
                    // Remove full path if present
                    if (path.includes('public/admins/img/')) {
                        path = path.split('public/admins/img/')[1] || '';
                    } else if (path.includes('public/clients/assets/img/')) {
                        path = path.split('public/clients/assets/img/')[1] || '';
                    } else if (path.includes('public/clients/assets/catalog/')) {
                        path = path.split('public/clients/assets/catalog/')[1] || '';
                    } else if (path.includes('admins/img/')) {
                        path = path.split('admins/img/')[1] || '';
                    } else if (path.includes('clients/assets/img/')) {
                        path = path.split('clients/assets/img/')[1] || '';
                    } else if (path.includes('clients/assets/catalog/')) {
                        path = path.split('clients/assets/catalog/')[1] || '';
                    }
                    path = path.replace(/^\/+|\/+$/g, '');
                    mediaState.currentFolder = path;
                    loadGallery();
                    updateBreadcrumb();
                });

                ul.appendChild(li);

                if (folder.has_children && folder.children) {
                    renderFolderTree(folder.children, ul, level + 1);
                }
            });
        }

        // Load gallery với pagination
        function loadGallery(page = null) {
            // QUAN TRỌNG: Clear selection khi load files mới để tránh xóa nhầm
            mediaState.selectedFiles.clear();
            updateSelectionUI();
            
            // Reset về page 1 nếu không có page param (khi chuyển folder)
            if (page === null) {
                mediaState.currentPage = 1;
            } else {
                mediaState.currentPage = page;
            }
            
            // Normalize folder path - remove any full path and keep only relative path
            let folder = mediaState.currentFolder || '';
            
            // If folder contains full path, extract relative part
            if (folder.includes('public/admins/img/')) {
                folder = folder.split('public/admins/img/')[1] || '';
            } else if (folder.includes('public/clients/assets/img/')) {
                folder = folder.split('public/clients/assets/img/')[1] || '';
            } else if (folder.includes('public/clients/assets/catalog/')) {
                folder = folder.split('public/clients/assets/catalog/')[1] || '';
            } else if (folder.includes('admins/img/')) {
                folder = folder.split('admins/img/')[1] || '';
            } else if (folder.includes('clients/assets/img/')) {
                folder = folder.split('clients/assets/img/')[1] || '';
            } else if (folder.includes('clients/assets/catalog/')) {
                folder = folder.split('clients/assets/catalog/')[1] || '';
            }
            
            // Remove any leading/trailing slashes
            folder = folder.replace(/^\/+|\/+$/g, '');
            
            const params = new URLSearchParams({
                scope: mediaState.scope,
                folder: folder,
                page: mediaState.currentPage,
                per_page: mediaState.perPage
            });

            document.getElementById('mediaGallery').innerHTML = '<div class="media-loading">Đang tải...</div>';

            fetch(`${apiBase}?${params}`)
                .then(r => r.json())
                .then(data => {
                    // Reset pagination when loading new folder
                    mediaState.displayedCount = 0;
                    
                    // Cập nhật pagination state từ response
                    if (data.pagination) {
                        mediaState.totalFiles = parseInt(data.pagination.total) || 0;
                        mediaState.hasMore = data.pagination.has_more || false;
                        mediaState.currentPage = parseInt(data.pagination.page) || 1;
                    }
                    
                    renderGallery(data.files || [], data.folders || []);
                    updatePaginationUI();
                    // Hide loading overlay khi load xong
                    hideLoadingOverlay();
                })
                .catch(err => {
                    console.error('Error loading gallery:', err);
                    document.getElementById('mediaGallery').innerHTML = '<div class="media-empty">Lỗi khi tải dữ liệu</div>';
                    // Hide loading overlay khi có lỗi
                    hideLoadingOverlay();
                });
        }

        function initGallery() {
            loadGallery();
        }

        function renderGallery(files, folders) {
            const container = document.getElementById('mediaGallery');
            
            // Store all files for pagination
            mediaState.displayedFiles = files;
            mediaState.displayedCount = 0;
            
            if (files.length === 0 && folders.length === 0) {
                container.innerHTML = `
                    <div class="media-empty">
                        <div class="media-empty-icon">📭</div>
                        <p>Thư mục trống</p>
                    </div>
                `;
                return;
            }

            const viewClass = mediaState.view === 'grid' ? 'media-grid-view' : 'media-list-view';
            container.className = `media-gallery ${viewClass}`;
            container.innerHTML = '';
            
            // Render folders first
            renderFolders(folders, container);
            
            // Render first page of files (100 items)
            renderFilesPage(container);
        }
        
        function renderFolders(folders, container) {

            folders.forEach(folder => {
                const item = document.createElement('div');
                item.className = `folder-item-view ${mediaState.view}`;
                item.dataset.type = 'folder';
                item.dataset.path = folder.path;
                
                item.innerHTML = `
                    <span class="folder-icon-large">📁</span>
                    <div>
                        <div class="media-name">${folder.name}</div>
                        <div class="media-meta">Thư mục</div>
                    </div>
                `;

                item.addEventListener('dblclick', () => {
                    // Show loading overlay
                    showLoadingOverlay('Đang tải thư mục...');
                    
                    // Normalize path to ensure it's relative
                    let path = folder.path || '';
                    // Remove full path if present
                    if (path.includes('public/admins/img/')) {
                        path = path.split('public/admins/img/')[1] || '';
                    } else if (path.includes('public/clients/assets/img/')) {
                        path = path.split('public/clients/assets/img/')[1] || '';
                    } else if (path.includes('public/clients/assets/catalog/')) {
                        path = path.split('public/clients/assets/catalog/')[1] || '';
                    } else if (path.includes('admins/img/')) {
                        path = path.split('admins/img/')[1] || '';
                    } else if (path.includes('clients/assets/img/')) {
                        path = path.split('clients/assets/img/')[1] || '';
                    } else if (path.includes('clients/assets/catalog/')) {
                        path = path.split('clients/assets/catalog/')[1] || '';
                    }
                    path = path.replace(/^\/+|\/+$/g, '');
                    mediaState.currentFolder = path;
                    loadGallery();
                    updateBreadcrumb();
                });

                item.addEventListener('contextmenu', (e) => {
                    e.preventDefault();
                    showContextMenu(e, 'folder', folder.path);
                });

                container.appendChild(item);
            });
        }
        
        function renderFilesPage(container) {
            const startIndex = mediaState.displayedCount;
            const perPage = parseInt(mediaState.perPage) || 100;
            const endIndex = Math.min(startIndex + perPage, mediaState.displayedFiles.length);
            const filesToShow = mediaState.displayedFiles.slice(startIndex, endIndex);
            
            // Remove old "Load more" button if exists
            const oldBtn = document.getElementById('btnLoadMore');
            if (oldBtn) {
                oldBtn.parentElement.remove();
            }
            
            filesToShow.forEach(file => {
                const item = document.createElement('div');
                item.className = `media-item ${mediaState.view}`;
                item.dataset.type = 'file';
                item.dataset.path = file.path;
                
                const isImage = file.mime_type?.startsWith('image/');
                // Build correct image URL using getAssetPath helper
                let imageUrl = '';
                if (isImage) {
                    if (file.thumbnail_path) {
                        // Use thumbnail if available
                        imageUrl = getAssetPath(file.thumbnail_path, mediaState.scope);
                    } else {
                        // Use original image
                        imageUrl = getAssetPath(file.path, mediaState.scope);
                    }
                }

                const isSelected = mediaState.selectedFiles.has(file.path);
                if (mediaState.view === 'grid') {
                    item.innerHTML = `
                        <input type="checkbox" class="media-checkbox" data-path="${file.path}" ${isSelected ? 'checked' : ''}>
                        ${isImage 
                            ? `<div class="media-thumb"><img src="${imageUrl}" alt="${file.filename}" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\'%3E%3Crect width=\'100\' height=\'100\' fill=\'%23ddd\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\'%3EImage%3C/text%3E%3C/svg%3E';"></div>` 
                            : '<div class="media-thumb" style="display:flex;align-items:center;justify-content:center;font-size:48px;background:#f8fafc;">📄</div>'}
                        <div class="media-info">
                            <div class="media-name" title="${file.filename}">${file.filename}</div>
                            <div class="media-meta">${formatFileSize(file.size)}</div>
                        </div>
                    `;
                } else {
                    item.innerHTML = `
                        <input type="checkbox" class="media-checkbox" data-path="${file.path}" ${isSelected ? 'checked' : ''}>
                        ${isImage 
                            ? `<img src="${imageUrl}" class="media-thumb" alt="${file.filename}" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'80\' height=\'80\'%3E%3Crect width=\'80\' height=\'80\' fill=\'%23ddd\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\'%3EImage%3C/text%3E%3C/svg%3E';">` 
                            : '<div style="width:80px;height:80px;display:flex;align-items:center;justify-content:center;font-size:32px;background:#f8fafc;">📄</div>'}
                        <div class="media-info">
                            <div class="media-name" title="${file.filename}">${file.filename}</div>
                            <div class="media-meta">${formatFileSize(file.size)} • ${file.extension} • ${file.created_at}</div>
                        </div>
                    `;
                }

                item.addEventListener('click', (e) => {
                    // Don't toggle if clicking checkbox directly - handle it separately
                    if (e.target.type === 'checkbox') {
                        const path = e.target.dataset.path;
                        if (e.target.checked) {
                            mediaState.selectedFiles.add(path);
                        } else {
                            mediaState.selectedFiles.delete(path);
                        }
                        updateSelectionUI();
                        return;
                    }
                    // Don't toggle if clicking buttons
                    if (e.target.closest('button')) {
                        return;
                    }
                    // Toggle selection on item click
                    toggleSelectFile(file.path);
                });

                item.addEventListener('dblclick', () => {
                    showPreview(file);
                });

                item.addEventListener('contextmenu', (e) => {
                    e.preventDefault();
                    showContextMenu(e, 'file', file.path);
                });

                container.appendChild(item);
            });
            
            // Update displayed count
            mediaState.displayedCount = endIndex;
            
            // Show "Load more" button if there are more files
            if (endIndex < mediaState.displayedFiles.length) {
                const loadMoreBtn = document.createElement('div');
                loadMoreBtn.className = 'load-more-container';
                loadMoreBtn.style.cssText = 'text-align:center;padding:20px;margin-top:20px;grid-column:1/-1;';
                loadMoreBtn.innerHTML = `
                    <button class="btn btn-primary" id="btnLoadMore" style="padding:12px 24px;font-size:16px;">
                        📥 Load thêm (${mediaState.displayedFiles.length - endIndex} ảnh còn lại)
                    </button>
                `;
                container.appendChild(loadMoreBtn);
                
                // Add event listener
                document.getElementById('btnLoadMore').addEventListener('click', () => {
                    renderFilesPage(container);
                });
            }
        }

        // Upload
        function initUpload() {
            const dropzone = document.getElementById('uploadDropzone');
            const fileInput = document.getElementById('fileInput');
            const uploadModal = new bootstrap.Modal(document.getElementById('uploadModal'));

            document.getElementById('btnUpload').addEventListener('click', () => {
                uploadModal.show();
            });

            dropzone.addEventListener('click', () => {
                fileInput.click();
            });

            dropzone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropzone.classList.add('dragover');
            });

            dropzone.addEventListener('dragleave', () => {
                dropzone.classList.remove('dragover');
            });

            dropzone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropzone.classList.remove('dragover');
                handleUpload(e.dataTransfer.files);
            });

            fileInput.addEventListener('change', (e) => {
                handleUpload(e.target.files);
            });
        }

        async function handleUpload(files) {
            const progressDiv = document.getElementById('uploadProgress');
            progressDiv.style.display = 'block';

            const allFiles = Array.from(files);
            const batchSize = 20; // tối đa 20 file/request theo giới hạn server
            const concurrency = 4; // chạy song song 4 request để tận dụng băng thông/RAM
            const totalCount = allFiles.length;
            let totalSuccess = 0;
            let totalFailed = 0;

            // Chuẩn bị các batch
            const batches = [];
            for (let i = 0; i < allFiles.length; i += batchSize) {
                batches.push(allFiles.slice(i, i + batchSize));
            }
            const totalBatches = batches.length;

            let completedBatches = 0;

            async function uploadBatch(batchFiles, batchIndex) {
                const start = batchIndex * batchSize;
                const end = start + batchFiles.length;

                const formData = new FormData();
                formData.append('scope', mediaState.scope);
                formData.append('folder', mediaState.currentFolder);
                batchFiles.forEach(file => formData.append('files[]', file));

                progressDiv.innerHTML = `<p>Đang upload đợt ${batchIndex + 1}/${totalBatches} (${start + 1} - ${end} / ${totalCount} file)...</p>`;

                try {
                    const response = await fetch('{{ route("admin.media.upload") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: formData
                    });

                    if (! response.ok) {
                        let errorMessage = `HTTP ${response.status}: ${response.statusText}`;
                        try {
                            const errorData = await response.json();
                            if (errorData && (errorData.error || errorData.message)) {
                                errorMessage = errorData.error || errorData.message;
                            }
                        } catch (e) {
                            // ignore parse error
                        }
                        throw new Error(errorMessage);
                    }

                    const data = await response.json();
                    const batchSuccessCount = data.files ? data.files.filter(f => f.success).length : batchFiles.length;
                    const batchTotalCount = data.files ? data.files.length : batchFiles.length;
                    const batchFailedCount = batchTotalCount - batchSuccessCount;

                    totalSuccess += batchSuccessCount;
                    totalFailed += batchFailedCount;

                    completedBatches++;
                    progressDiv.innerHTML = `<p style="color:green;">Đợt ${batchIndex + 1}/${totalBatches} xong: ${batchSuccessCount}/${batchTotalCount} (đã xong ${completedBatches}/${totalBatches} đợt).</p>`;
                } catch (err) {
                    console.error('Upload error:', err);
                    totalFailed += batchFiles.length;
                    completedBatches++;
                    progressDiv.innerHTML = `<p style="color:red;">Lỗi đợt ${batchIndex + 1}/${totalBatches}: ${err.message}</p>`;
                }
            }

            // Pool concurrency
            let nextBatchIndex = 0;
            const workers = [];

            for (let i = 0; i < Math.min(concurrency, totalBatches); i++) {
                workers.push((async function worker() {
                    while (nextBatchIndex < totalBatches) {
                        const current = nextBatchIndex++;
                        await uploadBatch(batches[current], current);
                    }
                })());
            }

            await Promise.all(workers);

            // Tổng kết
            progressDiv.innerHTML = `<p style="color:${totalFailed === 0 ? 'green' : 'orange'};">Upload hoàn tất: thành công ${totalSuccess}/${totalCount} file, thất bại ${totalFailed} file.</p>`;

            setTimeout(() => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('uploadModal'));
                if (modal) {
                    modal.hide();
                }
                loadGallery();
            }, 1200);
        }

        // Search với debounce 2s
        function initSearch() {
            let searchTimeout;
            document.getElementById('searchBox').addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                const query = e.target.value.trim();
                
                // Hiển thị loading khi đang debounce
                if (query) {
                    document.getElementById('mediaGallery').innerHTML = '<div class="media-loading">Đang tìm kiếm...</div>';
                }
                
                // Debounce 2s (2000ms) để tránh load nhiều khi user đang gõ
                searchTimeout = setTimeout(() => {
                    if (query) {
                        searchFiles(query);
                    } else {
                        // Reset về page 1 khi clear search
                        mediaState.displayedCount = 0;
                        loadGallery();
                    }
                }, 2000);
            });
        }

        function searchFiles(query) {
            // Clear selection khi search để tránh xóa nhầm
            mediaState.selectedFiles.clear();
            updateSelectionUI();
            
            // Normalize folder path
            let folder = mediaState.currentFolder || '';
            if (folder.includes('public/admins/img/')) {
                folder = folder.split('public/admins/img/')[1] || '';
            } else if (folder.includes('public/clients/assets/img/')) {
                folder = folder.split('public/clients/assets/img/')[1] || '';
            } else if (folder.includes('admins/img/')) {
                folder = folder.split('admins/img/')[1] || '';
            } else if (folder.includes('clients/assets/img/')) {
                folder = folder.split('clients/assets/img/')[1] || '';
            }
            folder = folder.replace(/^\/+|\/+$/g, '');
            
            // ✅ Dùng API list với param search (thay vì route search riêng)
            const params = new URLSearchParams({
                scope: mediaState.scope,
                folder: folder,
                search: query,
                page: 1, // Reset về page 1 khi search
                per_page: mediaState.perPage
            });

            document.getElementById('mediaGallery').innerHTML = '<div class="media-loading">Đang tìm kiếm...</div>';

            fetch(`${apiBase}?${params}`)
                .then(r => r.json())
                .then(data => {
                    // Reset pagination when searching
                    mediaState.displayedCount = 0;
                    mediaState.currentPage = 1;
                    
                    // Cập nhật pagination state từ response
                    if (data.pagination) {
                        mediaState.totalFiles = parseInt(data.pagination.total) || 0;
                        mediaState.hasMore = data.pagination.has_more || false;
                        mediaState.currentPage = parseInt(data.pagination.page) || 1;
                    }
                    
                    renderGallery(data.files || [], data.folders || []);
                    updatePaginationUI();
                })
                .catch(err => {
                    console.error('Error searching files:', err);
                    document.getElementById('mediaGallery').innerHTML = '<div class="media-empty">Lỗi khi tìm kiếm</div>';
                });
        }

        // View toggle
        function initViewToggle() {
            document.querySelectorAll('.view-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    mediaState.view = btn.dataset.view;
                    loadGallery();
                });
            });
        }

        // Context menu
        function initContextMenu() {
            const menu = document.getElementById('contextMenu');
            
            document.addEventListener('click', () => {
                menu.style.display = 'none';
            });

            document.querySelectorAll('.context-menu-item').forEach(item => {
                item.addEventListener('click', () => {
                    const action = item.dataset.action;
                    const targetPath = menu.dataset.targetPath;
                    const targetType = menu.dataset.targetType;
                    
                    handleContextAction(action, targetPath, targetType);
                    menu.style.display = 'none';
                });
            });
        }

        function showContextMenu(e, type, path) {
            const menu = document.getElementById('contextMenu');
            menu.style.display = 'block';

            // Tính toán vị trí để menu không bị tràn màn hình
            const clickX = e.clientX;
            const clickY = e.clientY;
            const menuRect = menu.getBoundingClientRect();
            const viewportWidth = window.innerWidth;
            const viewportHeight = window.innerHeight;

            let left = clickX;
            let top = clickY;

            // Nếu tràn bên phải, đẩy menu sang trái
            if (left + menuRect.width > viewportWidth) {
                left = Math.max(8, viewportWidth - menuRect.width - 8);
            }

            // Nếu tràn xuống dưới, hiển thị lên trên
            if (top + menuRect.height > viewportHeight) {
                top = Math.max(8, viewportHeight - menuRect.height - 8);
            }

            menu.style.left = left + 'px';
            menu.style.top = top + 'px';
            menu.dataset.targetPath = path;
            menu.dataset.targetType = type;
        }

        function handleContextAction(action, path, type) {
            switch(action) {
                case 'rename':
                    renameItem(path, type);
                    break;
                case 'copy':
                    copyToClipboard(path, 'copy');
                    break;
                case 'cut':
                    copyToClipboard(path, 'cut');
                    break;
                case 'move':
                    moveFile(path);
                    break;
                case 'download':
                    downloadFile(path);
                    break;
                case 'delete':
                    deleteItem(path, type);
                    break;
            }
        }

        // Helper functions
        // Base URLs from asset() helper - ensure they end with /
        const assetBase = '{{ asset("") }}';
        let adminImgBase = '{{ asset("admins/img/") }}';
        let clientImgBase = '{{ asset("clients/assets/img/") }}';
        let catalogBase = '{{ asset("clients/assets/catalog/") }}';
        
        // Ensure base URLs always end with /
        adminImgBase = adminImgBase.replace(/\/+$/, '') + '/';
        clientImgBase = clientImgBase.replace(/\/+$/, '') + '/';
        catalogBase = catalogBase.replace(/\/+$/, '') + '/';
        
        function getAssetPath(relativePath, scope) {
            if (!relativePath) return '';
            
            // Remove any leading slashes
            relativePath = relativePath.replace(/^\/+/, '');
            
            // If path already contains full public path, extract relative part
            if (relativePath.includes('public/admins/img/')) {
                relativePath = relativePath.split('public/admins/img/')[1] || relativePath;
            } else if (relativePath.includes('public/clients/assets/img/')) {
                relativePath = relativePath.split('public/clients/assets/img/')[1] || relativePath;
            } else if (relativePath.includes('public/clients/assets/catalog/')) {
                relativePath = relativePath.split('public/clients/assets/catalog/')[1] || relativePath;
            }
            
            // If path already contains scope prefix, extract just the relative part
            if (relativePath.startsWith('admins/img/')) {
                relativePath = relativePath.replace('admins/img/', '');
            } else if (relativePath.startsWith('clients/assets/img/')) {
                relativePath = relativePath.replace('clients/assets/img/', '');
            } else if (relativePath.startsWith('clients/assets/catalog/')) {
                relativePath = relativePath.replace('clients/assets/catalog/', '');
            }
            
            // Remove any leading slashes again after processing
            relativePath = relativePath.replace(/^\/+/, '');
            
            // Build URL: base (always ends with /) + path (never starts with /)
            const base = scope === 'admin' ? adminImgBase : (scope === 'client' ? clientImgBase : catalogBase);
            return base + relativePath;
        }
        
        function getDisplayPath(relativePath, scope) {
            // Same as getAssetPath - returns URL using asset()
            return getAssetPath(relativePath, scope);
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }

        function updateBreadcrumb() {
            const breadcrumb = document.getElementById('breadcrumb');
            const parts = mediaState.currentFolder ? mediaState.currentFolder.split('/') : [];
            
            breadcrumb.innerHTML = '<span class="breadcrumb-item" data-path="">🏠 Root</span>';
            
            let currentPath = '';
            parts.forEach(part => {
                currentPath += (currentPath ? '/' : '') + part;
                breadcrumb.innerHTML += `<span class="breadcrumb-separator">/</span><span class="breadcrumb-item" data-path="${currentPath}">${part}</span>`;
            });

            breadcrumb.querySelectorAll('.breadcrumb-item').forEach(item => {
                item.addEventListener('click', () => {
                    // Show loading overlay
                    showLoadingOverlay('Đang tải thư mục...');
                    
                    mediaState.currentFolder = item.dataset.path;
                    loadGallery();
                    updateBreadcrumb();
                });
            });
        }

        function toggleSelectFile(path) {
            const checkbox = document.querySelector(`.media-checkbox[data-path="${path}"]`);
            if (checkbox) {
                checkbox.checked = !checkbox.checked;
                if (checkbox.checked) {
                    mediaState.selectedFiles.add(path);
                } else {
                    mediaState.selectedFiles.delete(path);
                }
                updateSelectionUI();
            }
        }

        function updateSelectionUI() {
            const hasSelection = mediaState.selectedFiles.size > 0;
            document.getElementById('btnDeleteSelected').style.display = hasSelection ? 'block' : 'none';
        }

        function showPreview(file) {
            const panel = document.getElementById('previewPanel');
            const imageContainer = document.getElementById('previewImageContainer');
            const infoContainer = document.getElementById('previewInfo');
            const actionsContainer = document.getElementById('previewActions');

            fetch(`{{ route("admin.media.info") }}?path=${encodeURIComponent(file.path)}&scope=${mediaState.scope}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const info = data.data;
                        const isImage = info.mime_type?.startsWith('image/');
                        const imageUrl = isImage 
                            ? getAssetPath(info.path, mediaState.scope)
                            : '';

                        imageContainer.innerHTML = isImage 
                            ? `<img src="${imageUrl}" class="preview-image" alt="${info.filename}">`
                            : `<div style="text-align:center;padding:40px;"><div style="font-size:64px;">📄</div><p>${info.filename}</p></div>`;

                        infoContainer.innerHTML = `
                            <h4>${info.filename}</h4>
                            <div class="preview-info-item">
                                <span class="preview-info-label">Kích thước:</span>
                                <span>${formatFileSize(info.size)}</span>
                            </div>
                            <div class="preview-info-item">
                                <span class="preview-info-label">Loại:</span>
                                <span>${info.mime_type}</span>
                            </div>
                            ${info.dimensions ? `
                            <div class="preview-info-item">
                                <span class="preview-info-label">Kích thước ảnh:</span>
                                <span>${info.dimensions.width} × ${info.dimensions.height}</span>
                            </div>
                            ` : ''}
                            <div class="preview-info-item">
                                <span class="preview-info-label">Ngày tạo:</span>
                                <span>${info.created_at}</span>
                            </div>
                            <div class="preview-info-item">
                                <span class="preview-info-label">Đường dẫn:</span>
                                <span style="word-break:break-all;font-size:11px;">${getDisplayPath(info.path, mediaState.scope)}</span>
                            </div>
                        `;

                        actionsContainer.innerHTML = `
                            <button class="btn btn-primary" onclick="copyUrl('${imageUrl || getDisplayPath(info.path, mediaState.scope)}')">📋 Copy URL</button>
                            <button class="btn btn-secondary" onclick="renameItem('${info.path}', 'file')">✏️ Đổi tên</button>
                            <button class="btn btn-danger" onclick="deleteItem('${info.path}', 'file')">🗑️ Xóa</button>
                        `;

                        panel.classList.add('active');
                    }
                });
        }

        function copyUrl(url) {
            // Ensure URL is not duplicated (remove any full URL prefix if already present)
            let cleanUrl = url;
            if (url.includes('http://') || url.includes('https://')) {
                // If URL already contains protocol, use it as is
                cleanUrl = url;
            } else {
                // If it's a relative path, use getAssetPath to build full URL
                cleanUrl = getAssetPath(url, mediaState.scope);
            }
            
            navigator.clipboard.writeText(cleanUrl).then(() => {
                alert('Đã copy URL!');
            }).catch(err => {
                console.error('Copy failed:', err);
                alert('Không thể copy URL');
            });
        }

        function renameItem(path, type) {
            const currentName = path.split(/[/\\]/).pop();
            const newName = prompt('Nhập tên mới:', currentName);
            if (newName && newName.trim() && newName !== currentName) {
                fetch('{{ route("admin.media.rename") }}', {
                method: 'POST',
                headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        path: path,
                        new_name: newName.trim(),
                        scope: mediaState.scope
                    })
                })
                .then(r => {
                    if (!r.ok) {
                        return r.json().then(data => {
                            throw new Error(data.error || `HTTP ${r.status}: ${r.statusText}`);
                        });
                    }
                    return r.json();
                })
                .then(data => {
                    if (data.success) {
                        loadGallery();
                        loadFolderTree();
                        if (mediaState.selectedFile && mediaState.selectedFile.path === path) {
                            // Reload file info if currently selected
                            loadFileInfo(path);
                        }
                    } else {
                        alert('Lỗi: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(err => {
                    console.error('Rename error:', err);
                    alert('Lỗi khi đổi tên: ' + err.message);
                });
            }
        }

        function deleteItem(path, type) {
            // Không cho xóa file mặc định no-image.webp
            if (typeof path === 'string' && path.endsWith('no-image.webp')) {
                alert('File no-image.webp là ảnh mặc định, không xóa.');
                return;
            }

            let force = false;
            if (type === 'folder') {
                const confirmText = 'Bạn có chắc muốn xóa thư mục này và TOÀN BỘ file bên trong? Hành động này không thể hoàn tác.';
                if (!confirm(confirmText)) {
                    return;
                }
                force = true; // xóa luôn cả nội dung bên trong
                // Hiển thị loading overlay khi xóa folder
                showLoadingOverlay('Đang xóa thư mục...');
            } else {
                if (!confirm('Bạn có chắc muốn xóa file này?')) {
                    return;
                }
                // Hiển thị loading overlay khi xóa file
                showLoadingOverlay('Đang xóa file...');
            }

            const endpoint = type === 'folder'
                ? '{{ route("admin.media.folder.delete") }}'
                : '{{ route("admin.media.delete") }}';

            const payload = {
                path: path,
                scope: mediaState.scope,
            };
            if (type === 'folder') {
                payload.force = true;
            }

            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(payload)
            })
            .then(r => {
                if (! r.ok) {
                    return r.json().then(data => {
                        throw new Error(data.error || `HTTP ${r.status}: ${r.statusText}`);
                    });
                }
                return r.json();
            })
            .then(data => {
                if (data.success) {
                    // loadGallery() sẽ tự ẩn overlay khi hoàn thành
                    loadGallery();
                    loadFolderTree();
                } else {
                    alert('Lỗi: ' + (data.error || 'Unknown error'));
                    // Ẩn loading overlay khi có lỗi
                    hideLoadingOverlay();
                }
            })
            .catch(err => {
                console.error('Delete error:', err);
                alert('Lỗi khi xóa: ' + err.message);
                // Ẩn loading overlay khi có lỗi
                hideLoadingOverlay();
            });
        }

        function copyToClipboard(path, type) {
            mediaState.clipboard.type = type;
            mediaState.clipboard.files = [path];
            alert(type === 'copy' ? 'Đã copy!' : 'Đã cut!');
        }

        function moveFile(path) {
            // TODO: Show folder picker
            alert('Tính năng di chuyển đang phát triển');
        }

        function downloadFile(path) {
            const url = getAssetPath(path, mediaState.scope);
            window.open(url, '_blank');
        }

        // Select all button - Chọn tất cả files đã load (không chỉ files đang hiển thị)
        document.getElementById('btnSelectAll').addEventListener('click', () => {
            // Lấy tất cả files từ mediaState.displayedFiles (tất cả files đã load từ API)
            const allFiles = mediaState.displayedFiles || [];
            const allCheckboxes = document.querySelectorAll('.media-checkbox');
            
            // Kiểm tra xem đã chọn tất cả chưa (dựa trên số lượng files đã load)
            const allSelected = allFiles.length > 0 && allFiles.every(file => mediaState.selectedFiles.has(file.path));
            
            if (allSelected) {
                // Bỏ chọn tất cả
                allFiles.forEach(file => {
                    mediaState.selectedFiles.delete(file.path);
                });
                allCheckboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
            } else {
                // Chọn tất cả files đã load
                allFiles.forEach(file => {
                    mediaState.selectedFiles.add(file.path);
                });
                // Cập nhật checkbox trên DOM
                allCheckboxes.forEach(checkbox => {
                    const path = checkbox.dataset.path;
                    if (mediaState.selectedFiles.has(path)) {
                        checkbox.checked = true;
                    }
                });
            }
            
            updateSelectionUI();
        });

        // Delete selected button
        document.getElementById('btnDeleteSelected').addEventListener('click', () => {
            if (mediaState.selectedFiles.size === 0) {
                return;
            }
            
            if (!confirm(`Bạn có chắc muốn xóa ${mediaState.selectedFiles.size} file đã chọn?`)) {
                return;
            }

            const filesToDelete = Array.from(mediaState.selectedFiles);
            // Bỏ qua file mặc định
            const skipped = filesToDelete.filter(p => typeof p === 'string' && p.endsWith('no-image.webp'));
            const deletable = filesToDelete.filter(p => !(typeof p === 'string' && p.endsWith('no-image.webp')));
            if (skipped.length > 0) {
                // Loại khỏi selection để tránh xóa ở lần sau
                skipped.forEach(p => mediaState.selectedFiles.delete(p));
                alert(`Bỏ qua ${skipped.length} file mặc định (no-image.webp), sẽ không xóa.`);
            }
            if (deletable.length === 0) {
                updateSelectionUI();
                return;
            }
            
            // Hiển thị loading overlay khi xóa nhiều file
            showLoadingOverlay(`Đang xóa ${deletable.length} file...`);
            
            // Gọi bulk delete 1 lần cho nhanh
            fetch('{{ route("admin.media.bulk-delete") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    paths: deletable,
                    scope: mediaState.scope
                })
            })
            .then(r => {
                if (!r.ok) {
                    return r.json().then(data => {
                        throw new Error(data.error || `HTTP ${r.status}: ${r.statusText}`);
                    });
                }
                return r.json();
            })
            .then(data => {
                const deletedCount = data.deleted_count ?? deletable.length;
                const failedCount = data.failed_paths ? data.failed_paths.length : (deletable.length - deletedCount);

                // Clear selection
                mediaState.selectedFiles.clear();
                document.querySelectorAll('.media-checkbox').forEach(cb => cb.checked = false);
                updateSelectionUI();

                // Reload gallery (sẽ tự ẩn overlay khi hoàn thành)
                loadGallery();
                loadFolderTree();

                // Show result
                if (failedCount === 0) {
                    alert(`Đã xóa thành công ${deletedCount} file!`);
                } else {
                    alert(`Đã xóa ${deletedCount} file, ${failedCount} file lỗi!`);
                }
            })
            .catch(err => {
                console.error('Bulk delete error:', err);
                alert('Lỗi khi xóa: ' + err.message);
                // Ẩn loading overlay khi có lỗi
                hideLoadingOverlay();
            });
        });

        // Keyboard shortcuts
        function initKeyboardShortcuts() {
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Delete' && mediaState.selectedFiles.size > 0) {
                    e.preventDefault();
                    document.getElementById('btnDeleteSelected').click();
                }
                if (e.ctrlKey && e.key === 'a') {
                    e.preventDefault();
                    document.getElementById('btnSelectAll').click();
                }
            });
        }

        // Refresh button
        document.getElementById('btnRefresh').addEventListener('click', () => {
            loadFolderTree();
            loadGallery();
        });

        // Create folder
        document.getElementById('btnCreateFolder').addEventListener('click', () => {
            const name = prompt('Nhập tên thư mục:');
            if (name) {
                fetch('{{ route("admin.media.folder.create") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        path: mediaState.currentFolder,
                        name: name,
                        scope: mediaState.scope
                    })
                })
                .then(async r => {
                    if (! r.ok) {
                        let message = `HTTP ${r.status}: ${r.statusText}`;
                        try {
                            const data = await r.json();
                            if (data && (data.error || data.message)) {
                                message = data.error || data.message;
                            }
                        } catch (e) {
                            // có thể server trả về HTML (500), bỏ qua lỗi parse JSON
                        }
                        throw new Error(message);
                    }
                    return r.json();
                })
                .then(data => {
                    if (data.success) {
                        loadFolderTree();
                        loadGallery();
                    } else {
                        alert('Lỗi: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(err => {
                    console.error('Create folder error:', err);
                    alert('Lỗi khi tạo thư mục: ' + err.message);
                });
            }
        });

        // Pagination functions
        function updatePaginationUI() {
            const paginationDiv = document.getElementById('mediaPagination');
            const prevBtn = document.getElementById('btnPrevPage');
            const nextBtn = document.getElementById('btnNextPage');
            const infoSpan = document.getElementById('paginationInfo');
            
            // Đảm bảo totalFiles và perPage là số
            const totalFiles = parseInt(mediaState.totalFiles) || 0;
            const perPage = parseInt(mediaState.perPage) || 100;
            
            // Chỉ hiển thị pagination nếu có nhiều hơn 1 page
            const totalPages = perPage > 0 ? Math.ceil(totalFiles / perPage) : 1;
            
            if (totalPages <= 1 || totalFiles === 0) {
                paginationDiv.style.display = 'none';
                return;
            }
            
            paginationDiv.style.display = 'flex';
            
            // Cập nhật buttons
            prevBtn.disabled = mediaState.currentPage <= 1;
            nextBtn.disabled = !mediaState.hasMore && mediaState.currentPage >= totalPages;
            
            // Cập nhật info
            infoSpan.textContent = `Trang ${mediaState.currentPage} / ${totalPages} (${totalFiles} ảnh)`;
        }
        
        function prevPage() {
            if (mediaState.currentPage > 1) {
                loadGallery(mediaState.currentPage - 1);
                // Scroll to top
                document.getElementById('mediaGallery').scrollTop = 0;
            }
        }
        
        function nextPage() {
            if (mediaState.hasMore) {
                loadGallery(mediaState.currentPage + 1);
                // Scroll to top
                document.getElementById('mediaGallery').scrollTop = 0;
            }
        }
        
        // Pagination event listeners
        document.getElementById('btnPrevPage').addEventListener('click', prevPage);



        document.getElementById('btnNextPage').addEventListener('click', nextPage);
        
        // Per page selector event listener
        const perPageSelect = document.getElementById('perPageSelect');
        if (perPageSelect) {
            perPageSelect.addEventListener('change', function(e) {
                mediaState.perPage = parseInt(e.target.value);
                mediaState.currentPage = 1; // Reset về page 1 khi thay đổi perPage
                loadGallery(1);
            });
        }

        // Initial breadcrumb
        updateBreadcrumb();
    </script>
@endpush

