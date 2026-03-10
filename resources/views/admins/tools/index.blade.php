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
        
        /* Tool Cache - Màu tím */
        #cache-tool {
            border-left: 5px solid #8b5cf6;
        }
        #cache-tool .tool-card-header {
            background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 24px;
        }
        #cache-tool .tool-card-header h3 {
            color: #7c3aed;
        }
        
        /* Tool Application Cache - Màu xanh dương */
        #app-cache-tool {
            border-left: 5px solid #3b82f6;
        }
        #app-cache-tool .tool-card-header {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 24px;
        }
        #app-cache-tool .tool-card-header h3 {
            color: #2563eb;
        }
        
        /* Tool Temp Files - Màu xám */
        #temp-files-tool {
            border-left: 5px solid #6b7280;
        }
        #temp-files-tool .tool-card-header {
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 24px;
        }
        #temp-files-tool .tool-card-header h3 {
            color: #4b5563;
        }
        
        /* Tool Database - Màu đỏ */
        #database-tool {
            border-left: 5px solid #ef4444;
        }
        #database-tool .tool-card-header {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 24px;
        }
        #database-tool .tool-card-header h3 {
            color: #dc2626;
        }
        
        /* Tool System Info - Màu xanh lá */
        #system-info-tool {
            border-left: 5px solid #22c55e;
        }
        #system-info-tool .tool-card-header {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 24px;
        }
        #system-info-tool .tool-card-header h3 {
            color: #16a34a;
        }
        
        /* Tool Sessions - Màu vàng */
        #sessions-tool {
            border-left: 5px solid #eab308;
        }
        #sessions-tool .tool-card-header {
            background: linear-gradient(135deg, #fefce8 0%, #fef9c3 100%);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 24px;
        }
        #sessions-tool .tool-card-header h3 {
            color: #ca8a04;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }
        .info-item {
            background: #f8fafc;
            padding: 12px;
            border-radius: 8px;
            border-left: 3px solid #3b82f6;
        }
        .info-item strong {
            display: block;
            color: #1e40af;
            margin-bottom: 4px;
        }
        .info-item span {
            color: #64748b;
            font-size: 14px;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: white;
        }
        .btn-warning:hover {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }
        
        /* Tool Application Cache - Màu xanh dương */
        #app-cache-tool {
            border-left: 5px solid #3b82f6;
        }
        #app-cache-tool .tool-card-header {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 24px;
        }
        #app-cache-tool .tool-card-header h3 {
            color: #2563eb;
        }
        
        /* Tool Temp Files - Màu xám */
        #temp-files-tool {
            border-left: 5px solid #6b7280;
        }
        #temp-files-tool .tool-card-header {
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 24px;
        }
        #temp-files-tool .tool-card-header h3 {
            color: #4b5563;
        }
        
        /* Tool Database - Màu đỏ */
        #database-tool {
            border-left: 5px solid #ef4444;
        }
        #database-tool .tool-card-header {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 24px;
        }
        #database-tool .tool-card-header h3 {
            color: #dc2626;
        }
        
        /* Tool System Info - Màu xanh lá */
        #system-info-tool {
            border-left: 5px solid #22c55e;
        }
        #system-info-tool .tool-card-header {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 24px;
        }
        #system-info-tool .tool-card-header h3 {
            color: #16a34a;
        }
        
        /* Tool Sessions - Màu vàng */
        #sessions-tool {
            border-left: 5px solid #eab308;
        }
        #sessions-tool .tool-card-header {
            background: linear-gradient(135deg, #fefce8 0%, #fef9c3 100%);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 24px;
        }
        #sessions-tool .tool-card-header h3 {
            color: #ca8a04;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }
        .info-item {
            background: #f8fafc;
            padding: 12px;
            border-radius: 8px;
            border-left: 3px solid #3b82f6;
        }
        .info-item strong {
            display: block;
            color: #1e40af;
            margin-bottom: 4px;
        }
        .info-item span {
            color: #64748b;
            font-size: 14px;
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
        
        /* Loading Overlay */
        .tools-loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.95);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            backdrop-filter: blur(4px);
        }
        .tools-loading-overlay.active {
            display: flex;
        }
        .tools-loading-overlay-content {
            text-align: center;
            padding: 40px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            max-width: 400px;
        }
        .tools-loading-overlay-content .spinner {
            width: 60px;
            height: 60px;
            border: 5px solid #f3f4f6;
            border-top-color: #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .tools-loading-overlay-content .text {
            font-size: 18px;
            color: #64748b;
            font-weight: 600;
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

        <!-- Tool: Xóa Cache/Log Files -->
        <div class="tool-card" id="cache-tool">
            <div class="tool-card-header">
                <h3>🗑️ Xóa Cache/Log Files</h3>
                <p>Xóa các log files cũ trong thư mục <code>storage/logs</code>. Giữ lại 7 log files gần nhất và các file đứng một mình (browser.log, media.log, etc.)</p>
            </div>
            <div class="tool-actions">
                <button type="button" class="btn btn-danger" id="btn-clear-cache">
                    🗑️ Xóa Cache/Log Files
                </button>
            </div>
            <div id="cache-alert-container" style="margin-top: 16px;"></div>
            <div class="loading" id="cache-loading">
                <p>Đang xử lý...</p>
            </div>
            <div class="stats-info" id="cache-stats-container" style="display: none; margin-top: 20px;">
                <p>
                    <strong id="cache-deleted-count">0</strong> files đã xóa | 
                    Tiết kiệm: <strong id="cache-size-deleted">0 B</strong>
                </p>
            </div>
        </div>

        <!-- Tool: Clear Application Cache -->
        <div class="tool-card" id="app-cache-tool">
            <div class="tool-card-header">
                <h3>⚡ Xóa Application Cache</h3>
                <p>Xóa cache Laravel: config, route, view, application. Hữu ích khi cập nhật code/config nhưng thay đổi chưa hiển lên.</p>
            </div>
            <div class="tool-actions">
                <button type="button" class="btn btn-primary" id="btn-clear-app-cache">
                    ⚡ Xóa Application Cache
                </button>
            </div>
            <div id="app-cache-alert-container" style="margin-top: 16px;"></div>
            <div class="loading" id="app-cache-loading">
                <p>Đang xử lý...</p>
            </div>
        </div>

        <!-- Tool: Clean Temporary Files -->
        <div class="tool-card" id="temp-files-tool">
            <div class="tool-card-header">
                <h3>🧹 Dọn Dẹp File Tạm</h3>
                <p>Xóa file tạm trong <code>storage/app/exports</code>, <code>storage/app/imports</code>, <code>storage/app/temp</code>. Xóa file cũ hơn số ngày chỉ định.</p>
            </div>
            <div class="tool-actions">
                <input type="number" id="temp-files-days" value="7" min="1" max="365" style="padding: 8px; border-radius: 6px; border: 1px solid #ddd; width: 80px;">
                <span style="margin: 0 8px;">ngày</span>
                <button type="button" class="btn btn-danger" id="btn-clean-temp-files">
                    🧹 Dọn Dẹp File Tạm
                </button>
            </div>
            <div id="temp-files-alert-container" style="margin-top: 16px;"></div>
            <div class="loading" id="temp-files-loading">
                <p>Đang xử lý...</p>
            </div>
            <div class="stats-info" id="temp-files-stats-container" style="display: none; margin-top: 20px;">
                <p>
                    <strong id="temp-files-deleted-count">0</strong> files đã xóa | 
                    Tiết kiệm: <strong id="temp-files-size-deleted">0 B</strong>
                </p>
            </div>
        </div>

        <!-- Tool: Database Optimization -->
        <div class="tool-card" id="database-tool">
            <div class="tool-card-header">
                <h3>🗄️ Tối Ưu Database</h3>
                <p>Optimize/Analyze tables, kiểm tra kích thước database, tìm tables lớn.</p>
            </div>
            <div class="tool-actions">
                <button type="button" class="btn btn-primary" id="btn-analyze-db">
                    📊 Analyze Tables
                </button>
                <button type="button" class="btn btn-danger" id="btn-optimize-db">
                    ⚡ Optimize Tables
                </button>
            </div>
            <div id="database-alert-container" style="margin-top: 16px;"></div>
            <div class="loading" id="database-loading">
                <p>Đang xử lý...</p>
            </div>
            <div class="stats-info" id="database-stats-container" style="display: none; margin-top: 20px;">
                <p>
                    <strong id="database-tables-count">0</strong> tables | 
                    Tổng kích thước: <strong id="database-total-size">0 MB</strong>
                </p>
            </div>
        </div>

        <!-- Tool: System Information -->
        <div class="tool-card" id="system-info-tool">
            <div class="tool-card-header">
                <h3>ℹ️ Thông Tin Hệ Thống</h3>
                <p>Hiển thị thông tin server: PHP version, Laravel version, disk usage, memory usage.</p>
            </div>
            <div class="tool-actions">
                <button type="button" class="btn btn-info" id="btn-get-system-info">
                    ℹ️ Xem Thông Tin Hệ Thống
                </button>
            </div>
            <div id="system-info-alert-container" style="margin-top: 16px;"></div>
            <div class="loading" id="system-info-loading">
                <p>Đang xử lý...</p>
            </div>
            <div id="system-info-container" style="display: none; margin-top: 20px;"></div>
        </div>

        <!-- Tool: Analyze Disk Usage -->
        <div class="tool-card" id="disk-usage-tool">
            <div class="tool-card-header">
                <h3>💾 Phân Tích Dung Lượng Đĩa</h3>
                <p>Kiểm tra dung lượng các thư mục chính, tìm thư mục/file chiếm nhiều dung lượng nhất.</p>
            </div>
            <div class="tool-actions">
                <button type="button" class="btn btn-warning" id="btn-analyze-disk-usage">
                    🔍 Phân Tích Dung Lượng
                </button>
            </div>
            <div id="disk-usage-alert-container" style="margin-top: 16px;"></div>
            <div class="loading" id="disk-usage-loading">
                <p>Đang phân tích...</p>
            </div>
            <div id="disk-usage-container" style="display: none; margin-top: 20px;"></div>
        </div>

        <!-- Tool: Clear Old Sessions -->
        <div class="tool-card" id="sessions-tool">
            <div class="tool-card-header">
                <h3>🔐 Xóa Sessions Cũ</h3>
                <p>Xóa sessions cũ trong database/files. Giảm dung lượng storage.</p>
            </div>
            <div class="tool-actions">
                <input type="number" id="sessions-days" value="30" min="1" max="365" style="padding: 8px; border-radius: 6px; border: 1px solid #ddd; width: 80px;">
                <span style="margin: 0 8px;">ngày</span>
                <button type="button" class="btn btn-warning" id="btn-clear-sessions">
                    🔐 Xóa Sessions Cũ
                </button>
            </div>
            <div id="sessions-alert-container" style="margin-top: 16px;"></div>
            <div class="loading" id="sessions-loading">
                <p>Đang xử lý...</p>
            </div>
            <div class="stats-info" id="sessions-stats-container" style="display: none; margin-top: 20px;">
                <p>
                    <strong id="sessions-deleted-count">0</strong> sessions đã xóa
                </p>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="tools-loading-overlay" id="toolsLoadingOverlay">
        <div class="tools-loading-overlay-content">
            <div class="spinner"></div>
            <div class="text" id="toolsLoadingText">Đang xử lý...</div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Loading overlay functions
        function showToolsLoadingOverlay(text = 'Đang xử lý...') {
            const overlay = document.getElementById('toolsLoadingOverlay');
            const textEl = document.getElementById('toolsLoadingText');
            if (overlay) {
                if (textEl) {
                    textEl.textContent = text;
                }
                overlay.classList.add('active');
            }
        }

        function hideToolsLoadingOverlay() {
            const overlay = document.getElementById('toolsLoadingOverlay');
            if (overlay) {
                overlay.classList.remove('active');
            }
        }

        // Đảm bảo code chạy sau khi DOM đã load
        document.addEventListener('DOMContentLoaded', function() {
            const btnCheckStats = document.getElementById('btn-check-stats');
            const btnDeleteUnused = document.getElementById('btn-delete-unused');
            const loading = document.getElementById('loading');
            const alertContainer = document.getElementById('alert-container');
            const statsContainer = document.getElementById('stats-container');
            const unusedTagsList = document.getElementById('unused-tags-list');
            const unusedCount = document.getElementById('unused-count');

            let unusedTagsData = [];

            // Kiểm tra stats
            if (btnCheckStats) {
                btnCheckStats.addEventListener('click', async function() {
                    showToolsLoadingOverlay('Đang kiểm tra tags không được sử dụng...');
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
                        hideToolsLoadingOverlay();
                    }
                });
            }

            // Xóa tags không được sử dụng
            if (btnDeleteUnused) {
                btnDeleteUnused.addEventListener('click', async function() {
                    if (unusedTagsData.length === 0) {
                        alert('Không có tags nào để xóa.');
                        return;
                    }

                    if (!confirm(`Bạn có chắc chắn muốn xóa ${unusedTagsData.length} tag(s) không được sử dụng? Hành động này không thể hoàn tác!`)) {
                        return;
                    }

                    showToolsLoadingOverlay('Đang xóa tags không được sử dụng...');
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
                        hideToolsLoadingOverlay();
                    }
                });
            }

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
            if (btnCheckImages) {
                btnCheckImages.addEventListener('click', async function() {
                    console.log('🔵 [DEBUG] Button check images clicked');
                    showToolsLoadingOverlay('Đang kiểm tra ảnh không được sử dụng...');
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
                        hideToolsLoadingOverlay();
                    }
                });
            }

            // Xóa ảnh không được sử dụng
            if (btnDeleteImages) {
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
                    showToolsLoadingOverlay('Đang xóa toàn bộ ảnh không được sử dụng. Quá trình này có thể mất tới 1 phút...');
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
                        hideToolsLoadingOverlay();
                    }
                });
            }

            // Clear Cache
            const btnClearCache = document.getElementById('btn-clear-cache');
            const cacheLoading = document.getElementById('cache-loading');
            const cacheAlertContainer = document.getElementById('cache-alert-container');
            const cacheStatsContainer = document.getElementById('cache-stats-container');
            const cacheDeletedCount = document.getElementById('cache-deleted-count');
            const cacheSizeDeleted = document.getElementById('cache-size-deleted');

            if (btnClearCache) {
            btnClearCache.addEventListener('click', async function() {
                const confirmMsg = 'Bạn có chắc chắn muốn xóa cache/log files?\n\nTool sẽ:\n- Xóa tất cả log files cũ (theo pattern laravel-YYYY-MM-DD.log)\n- Giữ lại 7 log files gần nhất\n- Giữ lại các file đứng một mình (browser.log, media.log, etc.)\n\nHành động này không thể hoàn tác!';
                if (!confirm(confirmMsg)) {
                    return;
                }

                console.log('🔵 [DEBUG] Clearing cache...');
                showToolsLoadingOverlay('Đang xóa cache/log files...');
                this.disabled = true;
                cacheLoading.classList.add('active');
                cacheAlertContainer.innerHTML = '';
                cacheStatsContainer.style.display = 'none';

                try {
                    const url = '{{ route("admin.tools.clear-cache") }}';
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken || '',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                    });

                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        const text = await response.text();
                        console.error('🔴 [DEBUG] Non-JSON response:', text.substring(0, 200));
                        throw new Error('Server trả dữ liệu không hợp lệ (không phải JSON). Vui lòng xem log server.');
                    }

                    const result = await response.json();
                    console.log('🔵 [DEBUG] Clear cache response:', result);

                    if (result.success) {
                        cacheAlertContainer.innerHTML = `<div class="alert alert-success">✅ ${result.message}</div>`;
                        
                        if (result.stats) {
                            cacheDeletedCount.textContent = result.stats.deleted_logs;
                            cacheSizeDeleted.textContent = result.stats.total_size_deleted;
                            cacheStatsContainer.style.display = 'block';
                        }
                        
                        // Hiển thị thông tin chi tiết nếu có
                        if (result.kept_files && result.kept_files.length > 0) {
                            let keptInfo = '<div style="margin-top: 12px; padding: 12px; background: #f0f9ff; border-radius: 8px;"><strong>📁 Files được giữ lại:</strong><ul style="margin: 8px 0 0 0; padding-left: 20px;">';
                            result.kept_files.forEach(file => {
                                keptInfo += `<li>${file.filename} (${file.size})</li>`;
                            });
                            keptInfo += '</ul></div>';
                            cacheAlertContainer.innerHTML += keptInfo;
                        }
                        
                        if (result.standalone_files && result.standalone_files.length > 0) {
                            let standaloneInfo = '<div style="margin-top: 12px; padding: 12px; background: #fef3c7; border-radius: 8px;"><strong>📄 Files đứng một mình (được giữ lại):</strong><ul style="margin: 8px 0 0 0; padding-left: 20px;">';
                            result.standalone_files.forEach(file => {
                                standaloneInfo += `<li>${file.filename} (${file.size})</li>`;
                            });
                            standaloneInfo += '</ul></div>';
                            cacheAlertContainer.innerHTML += standaloneInfo;
                        }
                        
                        console.log('🟢 [DEBUG] Cache cleared successfully');
                    } else {
                        cacheAlertContainer.innerHTML = `<div class="alert alert-danger">❌ ${result.message || 'Có lỗi xảy ra khi xóa cache.'}</div>`;
                    }
                } catch (error) {
                    console.error('🔴 [DEBUG] Clear cache error:', error);
                    cacheAlertContainer.innerHTML = `<div class="alert alert-danger">❌ Có lỗi xảy ra: ${error.message}</div>`;
                } finally {
                    cacheLoading.classList.remove('active');
                    this.disabled = false;
                    hideToolsLoadingOverlay();
                }
            });
            }

            // Clear Application Cache
            const btnClearAppCache = document.getElementById('btn-clear-app-cache');
            const appCacheLoading = document.getElementById('app-cache-loading');
            const appCacheAlertContainer = document.getElementById('app-cache-alert-container');

            if (btnClearAppCache) {
            btnClearAppCache.addEventListener('click', async function() {
                console.log('🔵 [DEBUG] Clearing application cache...');
                showToolsLoadingOverlay('Đang xóa application cache...');
                this.disabled = true;
                appCacheLoading.classList.add('active');
                appCacheAlertContainer.innerHTML = '';

                try {
                    const url = '{{ route("admin.tools.clear-application-cache") }}';
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken || '',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                    });

                    const result = await response.json();
                    if (result.success) {
                        appCacheAlertContainer.innerHTML = `<div class="alert alert-success">✅ ${result.message}</div>`;
                        if (result.cleared) {
                            appCacheAlertContainer.innerHTML += `<div style="margin-top: 8px;"><small>Đã xóa: ${result.cleared.join(', ')}</small></div>`;
                        }
                    } else {
                        appCacheAlertContainer.innerHTML = `<div class="alert alert-danger">❌ ${result.message}</div>`;
                    }
                } catch (error) {
                    appCacheAlertContainer.innerHTML = `<div class="alert alert-danger">❌ Lỗi: ${error.message}</div>`;
                } finally {
                    appCacheLoading.classList.remove('active');
                    this.disabled = false;
                    hideToolsLoadingOverlay();
                }
            });
            }

            // Clean Temporary Files
            const btnCleanTempFiles = document.getElementById('btn-clean-temp-files');
            const tempFilesLoading = document.getElementById('temp-files-loading');
            const tempFilesAlertContainer = document.getElementById('temp-files-alert-container');
            const tempFilesStatsContainer = document.getElementById('temp-files-stats-container');
            const tempFilesDeletedCount = document.getElementById('temp-files-deleted-count');
            const tempFilesSizeDeleted = document.getElementById('temp-files-size-deleted');

            if (btnCleanTempFiles) {
            btnCleanTempFiles.addEventListener('click', async function() {
                const days = document.getElementById('temp-files-days').value;
                const confirmMsg = `Bạn có chắc chắn muốn xóa file tạm cũ hơn ${days} ngày?`;
                if (!confirm(confirmMsg)) return;

                showToolsLoadingOverlay('Đang dọn dẹp file tạm...');
                this.disabled = true;
                tempFilesLoading.classList.add('active');
                tempFilesAlertContainer.innerHTML = '';
                tempFilesStatsContainer.style.display = 'none';

                try {
                    const url = '{{ route("admin.tools.clean-temporary-files") }}';
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken || '',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ days: days }),
                    });

                    const result = await response.json();
                    if (result.success) {
                        tempFilesAlertContainer.innerHTML = `<div class="alert alert-success">✅ ${result.message}</div>`;
                        if (result.stats) {
                            tempFilesDeletedCount.textContent = result.stats.deleted_count;
                            tempFilesSizeDeleted.textContent = result.stats.total_size;
                            tempFilesStatsContainer.style.display = 'block';
                        }
                    } else {
                        tempFilesAlertContainer.innerHTML = `<div class="alert alert-danger">❌ ${result.message}</div>`;
                    }
                } catch (error) {
                    tempFilesAlertContainer.innerHTML = `<div class="alert alert-danger">❌ Lỗi: ${error.message}</div>`;
                } finally {
                    tempFilesLoading.classList.remove('active');
                    this.disabled = false;
                    hideToolsLoadingOverlay();
                }
            });
            }

            // Database Optimization
            const btnAnalyzeDb = document.getElementById('btn-analyze-db');
            const btnOptimizeDb = document.getElementById('btn-optimize-db');
            const databaseLoading = document.getElementById('database-loading');
            const databaseAlertContainer = document.getElementById('database-alert-container');
            const databaseStatsContainer = document.getElementById('database-stats-container');
            const databaseTablesCount = document.getElementById('database-tables-count');
            const databaseTotalSize = document.getElementById('database-total-size');

            if (btnAnalyzeDb && btnOptimizeDb) {
            async function handleDatabaseAction(action) {
                const btn = action === 'analyze' ? btnAnalyzeDb : btnOptimizeDb;
                const actionText = action === 'analyze' ? 'analyze' : 'optimize';
                const confirmMsg = action === 'optimize' ? 'Bạn có chắc chắn muốn optimize database? Quá trình này có thể mất thời gian.' : null;
                
                if (confirmMsg && !confirm(confirmMsg)) return;

                showToolsLoadingOverlay(action === 'analyze' ? 'Đang analyze database...' : 'Đang optimize database...');
                btn.disabled = true;
                databaseLoading.classList.add('active');
                databaseAlertContainer.innerHTML = '';
                databaseStatsContainer.style.display = 'none';

                try {
                    const url = '{{ route("admin.tools.optimize-database") }}';
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken || '',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ action: actionText }),
                    });

                    const result = await response.json();
                    if (result.success) {
                        databaseAlertContainer.innerHTML = `<div class="alert alert-success">✅ ${result.message}</div>`;
                        if (result.stats) {
                            databaseTablesCount.textContent = result.stats.tables_count;
                            databaseTotalSize.textContent = result.stats.total_size_mb + ' MB';
                            databaseStatsContainer.style.display = 'block';
                        }
                    } else {
                        databaseAlertContainer.innerHTML = `<div class="alert alert-danger">❌ ${result.message}</div>`;
                    }
                } catch (error) {
                    databaseAlertContainer.innerHTML = `<div class="alert alert-danger">❌ Lỗi: ${error.message}</div>`;
                } finally {
                    databaseLoading.classList.remove('active');
                    btn.disabled = false;
                    hideToolsLoadingOverlay();
                }
            }

            btnAnalyzeDb.addEventListener('click', () => handleDatabaseAction('analyze'));
            btnOptimizeDb.addEventListener('click', () => handleDatabaseAction('optimize'));
            }

            // System Information
            const btnGetSystemInfo = document.getElementById('btn-get-system-info');
            const systemInfoLoading = document.getElementById('system-info-loading');
            const systemInfoAlertContainer = document.getElementById('system-info-alert-container');
            const systemInfoContainer = document.getElementById('system-info-container');

            if (btnGetSystemInfo) {
            btnGetSystemInfo.addEventListener('click', async function() {
                showToolsLoadingOverlay('Đang lấy thông tin hệ thống...');
                this.disabled = true;
                systemInfoLoading.classList.add('active');
                systemInfoAlertContainer.innerHTML = '';
                systemInfoContainer.style.display = 'none';

                try {
                    const url = '{{ route("admin.tools.system-info") }}';
                    const response = await fetch(url, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    });

                    const result = await response.json();
                    if (result.success && result.info) {
                        const info = result.info;
                        let html = '<div class="info-grid">';
                        
                        if (info.php) {
                            html += '<div class="info-item"><strong>PHP Version</strong><span>' + info.php.version + '</span></div>';
                            html += '<div class="info-item"><strong>Memory Limit</strong><span>' + info.php.memory_limit + '</span></div>';
                            html += '<div class="info-item"><strong>Max Execution Time</strong><span>' + info.php.max_execution_time + 's</span></div>';
                        }
                        
                        if (info.laravel) {
                            html += '<div class="info-item"><strong>Laravel Version</strong><span>' + info.laravel.version + '</span></div>';
                            html += '<div class="info-item"><strong>Environment</strong><span>' + info.laravel.environment + '</span></div>';
                            html += '<div class="info-item"><strong>Debug Mode</strong><span>' + (info.laravel.debug ? 'On' : 'Off') + '</span></div>';
                        }
                        
                        if (info.disk && Object.keys(info.disk).length > 0) {
                            html += '<div class="info-item"><strong>Disk Total</strong><span>' + info.disk.total + '</span></div>';
                            html += '<div class="info-item"><strong>Disk Used</strong><span>' + info.disk.used + ' (' + info.disk.usage_percent + '%)</span></div>';
                            html += '<div class="info-item"><strong>Disk Free</strong><span>' + info.disk.free + '</span></div>';
                        }
                        
                        if (info.memory) {
                            html += '<div class="info-item"><strong>Current Memory</strong><span>' + info.memory.current_usage + '</span></div>';
                            html += '<div class="info-item"><strong>Peak Memory</strong><span>' + info.memory.peak_usage + '</span></div>';
                        }
                        
                        html += '</div>';
                        systemInfoContainer.innerHTML = html;
                        systemInfoContainer.style.display = 'block';
                    } else {
                        systemInfoAlertContainer.innerHTML = `<div class="alert alert-danger">❌ ${result.message || 'Không thể lấy thông tin hệ thống'}</div>`;
                    }
                } catch (error) {
                    systemInfoAlertContainer.innerHTML = `<div class="alert alert-danger">❌ Lỗi: ${error.message}</div>`;
                } finally {
                    systemInfoLoading.classList.remove('active');
                    this.disabled = false;
                    hideToolsLoadingOverlay();
                }
            });
            }

            // Analyze Disk Usage
            const btnAnalyzeDiskUsage = document.getElementById('btn-analyze-disk-usage');
            const diskUsageLoading = document.getElementById('disk-usage-loading');
            const diskUsageAlertContainer = document.getElementById('disk-usage-alert-container');
            const diskUsageContainer = document.getElementById('disk-usage-container');

            if (btnAnalyzeDiskUsage) {
                btnAnalyzeDiskUsage.addEventListener('click', async function() {
                    console.log('🔵 [DEBUG] Analyzing disk usage...');
                    showToolsLoadingOverlay('Đang phân tích dung lượng đĩa...');
                    this.disabled = true;
                    diskUsageLoading.classList.add('active');
                    diskUsageAlertContainer.innerHTML = '';
                    diskUsageContainer.style.display = 'none';

                    try {
                        const url = '{{ route("admin.tools.analyze-disk-usage") }}';
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        
                        const response = await fetch(url, {
                            method: 'GET',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrfToken || '',
                                'Accept': 'application/json',
                            },
                        });

                        const result = await response.json();
                        console.log('🔵 [DEBUG] Disk usage response:', result);

                        if (result.success) {
                            diskUsageAlertContainer.innerHTML = `<div class="alert alert-success">✅ ${result.message}</div>`;
                            diskUsageContainer.style.display = 'block';
                            
                            let html = '<div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">';
                            
                            // Tổng dung lượng
                            if (result.stats) {
                                html += `<h4 style="margin-bottom: 16px;">📊 Tổng Dung Lượng: <strong>${result.stats.total_size_formatted}</strong></h4>`;
                            }
                            
                            // Danh sách thư mục chính
                            if (result.directories && result.directories.length > 0) {
                                html += '<h5 style="margin-top: 20px; margin-bottom: 12px;">📁 Các Thư Mục Chính:</h5>';
                                html += '<table class="table table-bordered table-sm" style="font-size: 13px;">';
                                html += '<thead><tr><th>Thư Mục</th><th>Dung Lượng</th><th>Tỷ Lệ</th></tr></thead><tbody>';
                                
                                result.directories.forEach(dir => {
                                    const barWidth = Math.min(dir.percentage, 100);
                                    html += `<tr>
                                        <td><code>${dir.path}</code></td>
                                        <td><strong>${dir.size_formatted}</strong></td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <div style="flex: 1; background: #e9ecef; height: 20px; border-radius: 4px; overflow: hidden;">
                                                    <div style="background: ${dir.percentage > 30 ? '#dc3545' : dir.percentage > 15 ? '#ffc107' : '#28a745'}; height: 100%; width: ${barWidth}%;"></div>
                                                </div>
                                                <span style="min-width: 50px; text-align: right;">${dir.percentage}%</span>
                                            </div>
                                        </td>
                                    </tr>`;
                                });
                                
                                html += '</tbody></table>';
                            }
                            
                            // Top thư mục trong storage
                            if (result.storage_top_directories && result.storage_top_directories.length > 0) {
                                html += '<h5 style="margin-top: 24px; margin-bottom: 12px;">📂 Top Thư Mục Lớn Nhất Trong Storage:</h5>';
                                html += '<table class="table table-bordered table-sm" style="font-size: 13px;">';
                                html += '<thead><tr><th>Thư Mục</th><th>Dung Lượng</th></tr></thead><tbody>';
                                
                                result.storage_top_directories.forEach(dir => {
                                    html += `<tr>
                                        <td><code>${dir.path}</code></td>
                                        <td><strong>${dir.size_formatted}</strong></td>
                                    </tr>`;
                                });
                                
                                html += '</tbody></table>';
                            }
                            
                            // Top file lớn nhất trong storage
                            if (result.storage_top_files && result.storage_top_files.length > 0) {
                                html += '<h5 style="margin-top: 24px; margin-bottom: 12px;">📄 Top File Lớn Nhất Trong Storage:</h5>';
                                html += '<table class="table table-bordered table-sm" style="font-size: 13px;">';
                                html += '<thead><tr><th>File</th><th>Dung Lượng</th></tr></thead><tbody>';
                                
                                result.storage_top_files.forEach(file => {
                                    html += `<tr>
                                        <td><code>${file.path}</code></td>
                                        <td><strong>${file.size_formatted}</strong></td>
                                    </tr>`;
                                });
                                
                                html += '</tbody></table>';
                            }
                            
                            // Chi tiết storage/framework
                            if (result.framework_details && result.framework_details.subdirectories && result.framework_details.subdirectories.length > 0) {
                                html += '<h5 style="margin-top: 24px; margin-bottom: 12px;">⚙️ Chi Tiết Storage/Framework:</h5>';
                                const formatBytes = (bytes) => {
                                    if (bytes === 0) return '0 B';
                                    const k = 1024;
                                    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
                                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                                    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
                                };
                                html += `<p style="margin-bottom: 12px; color: #666;"><strong>Tổng dung lượng:</strong> ${result.framework_details.total_size ? formatBytes(result.framework_details.total_size) : 'N/A'}</p>`;
                                html += '<div style="background: #fff3cd; padding: 12px; border-radius: 6px; margin-bottom: 12px; border-left: 4px solid #ffc107;">';
                                html += '<strong>💡 Giải thích:</strong><br>';
                                html += '<ul style="margin: 8px 0 0 20px; padding: 0;">';
                                html += '<li><strong>cache/</strong> - Compiled views, config cache, route cache (có thể xóa bằng <code>php artisan cache:clear</code>)</li>';
                                html += '<li><strong>sessions/</strong> - Session files nếu dùng file driver (có thể xóa sessions cũ)</li>';
                                html += '<li><strong>views/</strong> - Compiled Blade views (tự động tạo lại khi cần)</li>';
                                html += '<li><strong>testing/</strong> - Testing files</li>';
                                html += '<li><strong>logs/</strong> - Log files (nếu có)</li>';
                                html += '</ul>';
                                html += '</div>';
                                html += '<table class="table table-bordered table-sm" style="font-size: 13px;">';
                                html += '<thead><tr><th>Thư Mục Con</th><th>Dung Lượng</th><th>Mô Tả</th></tr></thead><tbody>';
                                
                                result.framework_details.subdirectories.forEach(dir => {
                                    let description = '';
                                    const dirName = dir.name.toLowerCase();
                                    if (dirName.includes('cache')) {
                                        description = '⚠️ Compiled cache - có thể xóa';
                                    } else if (dirName.includes('session')) {
                                        description = '🔐 Session files - xóa sessions cũ';
                                    } else if (dirName.includes('view')) {
                                        description = '👁️ Compiled views - tự động tạo lại';
                                    } else if (dirName.includes('test')) {
                                        description = '🧪 Testing files';
                                    } else if (dirName.includes('log')) {
                                        description = '📝 Log files';
                                    } else {
                                        description = '📁 Framework files';
                                    }
                                    
                                    html += `<tr>
                                        <td><code>${dir.path}</code></td>
                                        <td><strong>${dir.size_formatted}</strong></td>
                                        <td>${description}</td>
                                    </tr>`;
                                });
                                
                                html += '</tbody></table>';
                            }
                            
                            html += '</div>';
                            diskUsageContainer.innerHTML = html;
                            
                            console.log('🟢 [DEBUG] Disk usage analysis completed');
                        } else {
                            diskUsageAlertContainer.innerHTML = `<div class="alert alert-danger">❌ ${result.message || 'Có lỗi xảy ra khi phân tích.'}</div>`;
                        }
                    } catch (error) {
                        console.error('🔴 [DEBUG] Analyze disk usage error:', error);
                        diskUsageAlertContainer.innerHTML = `<div class="alert alert-danger">❌ Lỗi: ${error.message}</div>`;
                    } finally {
                        this.disabled = false;
                        diskUsageLoading.classList.remove('active');
                        hideToolsLoadingOverlay();
                    }
                });
            }

            // Clear Old Sessions
            const btnClearSessions = document.getElementById('btn-clear-sessions');
            const sessionsLoading = document.getElementById('sessions-loading');
            const sessionsAlertContainer = document.getElementById('sessions-alert-container');
            const sessionsStatsContainer = document.getElementById('sessions-stats-container');
            const sessionsDeletedCount = document.getElementById('sessions-deleted-count');

            if (btnClearSessions) {
            btnClearSessions.addEventListener('click', async function() {
                const days = document.getElementById('sessions-days').value;
                const confirmMsg = `Bạn có chắc chắn muốn xóa sessions cũ hơn ${days} ngày?`;
                if (!confirm(confirmMsg)) return;

                showToolsLoadingOverlay('Đang xóa sessions cũ...');
                this.disabled = true;
                sessionsLoading.classList.add('active');
                sessionsAlertContainer.innerHTML = '';
                sessionsStatsContainer.style.display = 'none';

                try {
                    const url = '{{ route("admin.tools.clear-old-sessions") }}';
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken || '',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ days: days }),
                    });

                    const result = await response.json();
                    if (result.success) {
                        sessionsAlertContainer.innerHTML = `<div class="alert alert-success">✅ ${result.message}</div>`;
                        if (result.stats) {
                            sessionsDeletedCount.textContent = result.stats.deleted_count;
                            sessionsStatsContainer.style.display = 'block';
                        }
                    } else {
                        sessionsAlertContainer.innerHTML = `<div class="alert alert-danger">❌ ${result.message}</div>`;
                    }
                } catch (error) {
                    sessionsAlertContainer.innerHTML = `<div class="alert alert-danger">❌ Lỗi: ${error.message}</div>`;
                } finally {
                    sessionsLoading.classList.remove('active');
                    this.disabled = false;
                    hideToolsLoadingOverlay();
                }
            });
            }

            // Scroll to tool if URL has hash
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
        }); // End DOMContentLoaded
    </script>
@endpush
