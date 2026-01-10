@extends('admins.layouts.master')

@section('title', 'Quản lý sản phẩm')
@section('page-title', '📦 Sản phẩm')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/products-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slim-select@2.8.2/dist/slimselect.css">
    <style>
        .product-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .product-table th, .product-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #eef2f7;
            text-align: left;
        }
        .product-table th {
            background: #f8fafc;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #475569;
        }
        .product-table tr:hover td {
            background: #f1f5f9;
        }
        .filter-bar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .filter-bar input,
        .filter-bar select {
            padding: 8px 12px;
            border: 1px solid #cbd5f5;
            border-radius: 6px;
        }
        .badge {
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success {
            background: #dcfce7;
            color: #15803d;
        }
        .badge-danger {
            background: #fee2e2;
            color: #b91c1c;
        }
        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }
        .stock-cell {
            white-space: nowrap;
        }
        .stock-note {
            font-size: 11px;
            color: #64748b;
            display: block;
            margin-top: 2px;
        }
        .actions {
            display: flex;
            gap: 8px;
        }
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
        }
        .product-image-cell {
            width: 80px;
        }
        
        /* Export/Import Overlay */
        .export-overlay,
        .import-overlay {
            display: none !important;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        .export-overlay.active,
        .import-overlay.active {
            display: flex !important;
        }
        .export-modal {
            background: white;
            border-radius: 12px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        .export-progress {
            margin: 20px 0;
        }
        .export-progress-bar {
            width: 100%;
            height: 30px;
            background: #e5e7eb;
            border-radius: 15px;
            overflow: hidden;
            position: relative;
        }
        .export-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #2563EB, #1D4ED8);
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 12px;
        }
        .export-status {
            text-align: center;
            margin: 15px 0;
            font-size: 14px;
            color: #475569;
        }
        .btn-cancel-export {
            margin-top: 15px;
            width: 100%;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/slim-select@2.8.2/dist/slimselect.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const selectAll = document.getElementById('select-all-products');
            const checkboxes = document.querySelectorAll('.product-checkbox');
            const form = document.getElementById('bulk-action-form');

            if (!selectAll || !form) {
                return;
            }

            selectAll.addEventListener('change', () => {
                checkboxes.forEach(cb => {
                    cb.checked = selectAll.checked;
                });
            });

            form.addEventListener('submit', (e) => {
                const hasSelected = Array.from(checkboxes).some(cb => cb.checked);
                if (!hasSelected) {
                    e.preventDefault();
                    alert('Vui lòng chọn ít nhất một sản phẩm trước khi thực hiện hành động.');
                }
            });
        });

        // Export Products với filter
        document.addEventListener('DOMContentLoaded', function() {
            const btnExport = document.getElementById('btn-export-products');
            if (!btnExport) return;

            const overlay = document.createElement('div');
            overlay.className = 'export-overlay';
            overlay.style.display = 'none'; // Đảm bảo ẩn mặc định
            overlay.innerHTML = `
                <div class="export-modal">
                    <h4 style="margin-bottom: 20px;">Đang xuất sản phẩm...</h4>
                    <div class="export-progress">
                        <div class="export-progress-bar">
                            <div class="export-progress-fill" style="width: 0%;">0%</div>
                        </div>
                    </div>
                    <div class="export-status">Đang xử lý...</div>
                    <button type="button" class="btn btn-danger btn-cancel-export">Hủy xuất</button>
                </div>
            `;
            document.body.appendChild(overlay);

            const progressFill = overlay.querySelector('.export-progress-fill');
            const statusText = overlay.querySelector('.export-status');
            const btnCancel = overlay.querySelector('.btn-cancel-export');

            let exportSessionId = null;
            let progressInterval = null;
            let chunkInterval = null;
            let isCancelled = false;
            let isDownloading = false; // Flag để tránh download nhiều lần
            
            // Khởi tạo SlimSelect cho category và brand
            let categorySlimSelect = null;
            let brandSlimSelect = null;
            
            // Đợi một chút để đảm bảo SlimSelect đã load
            setTimeout(function() {
                if (typeof SlimSelect !== 'undefined') {
                    const categorySelect = document.getElementById('export-category-ids');
                    const brandSelect = document.getElementById('export-brand-ids');
                    
                    if (categorySelect) {
                        categorySlimSelect = new SlimSelect({
                            select: '#export-category-ids',
                            placeholder: 'Tìm kiếm và chọn danh mục...',
                            searchPlaceholder: 'Gõ để tìm kiếm...',
                            searchText: 'Không tìm thấy',
                            searchingText: 'Đang tìm kiếm...',
                            allowDeselectOption: true,
                            closeOnSelect: false,
                        });
                    }
                    
                    if (brandSelect) {
                        brandSlimSelect = new SlimSelect({
                            select: '#export-brand-ids',
                            placeholder: 'Tìm kiếm và chọn hãng...',
                            searchPlaceholder: 'Gõ để tìm kiếm...',
                            searchText: 'Không tìm thấy',
                            searchingText: 'Đang tìm kiếm...',
                            allowDeselectOption: true,
                            closeOnSelect: false,
                        });
                    }
                }
            }, 100);

            // Hàm download file với kiểm tra
            async function downloadFile(url, retryCount = 0) {
                if (isDownloading) return;
                
                const maxRetries = 5;
                if (retryCount >= maxRetries) {
                    statusText.textContent = 'Lỗi: Không thể tải file sau nhiều lần thử.';
                    setTimeout(() => {
                        overlay.classList.remove('active');
                        isDownloading = false;
                    }, 3000);
                    return;
                }
                
                isDownloading = true;
                statusText.textContent = `Đang kiểm tra file... (${retryCount + 1}/${maxRetries})`;
                
                try {
                    // Kiểm tra file có tồn tại không bằng cách fetch
                    const response = await fetch(url, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    
                    // Kiểm tra response
                    if (!response.ok) {
                        // Nếu 404 hoặc 202 (đang tạo), đợi và retry
                        if (response.status === 404 || response.status === 202) {
                            statusText.textContent = 'File chưa sẵn sàng, đang đợi...';
                            isDownloading = false;
                            setTimeout(() => {
                                downloadFile(url, retryCount + 1);
                            }, 2000);
                            return;
                        }
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    
                    // Kiểm tra content-type
                    const contentType = response.headers.get('content-type');
                    if (contentType && !contentType.includes('spreadsheet') && !contentType.includes('excel') && !contentType.includes('application/vnd.openxmlformats')) {
                        // Nếu không phải Excel file, có thể là HTML error page
                        if (contentType.includes('text/html')) {
                            statusText.textContent = 'File chưa sẵn sàng, đang đợi...';
                            isDownloading = false;
                            setTimeout(() => {
                                downloadFile(url, retryCount + 1);
                            }, 2000);
                            return;
                        }
                    }
                    
                    // Lấy blob và download
                    const blob = await response.blob();
                    
                    // Kiểm tra blob size (phải > 0)
                    if (blob.size === 0) {
                        throw new Error('File rỗng');
                    }
                    
                    // Tạo URL từ blob và download
                    const blobUrl = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = blobUrl;
                    link.download = `products_export_${new Date().toISOString().slice(0,10)}_${Date.now()}.xlsx`;
                    link.style.display = 'none';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    // Cleanup blob URL
                    setTimeout(() => {
                        window.URL.revokeObjectURL(blobUrl);
                    }, 100);
                    
                    statusText.textContent = 'Đã tải file thành công!';
                    
                    // Đóng overlay sau khi download
                    setTimeout(() => {
                        overlay.classList.remove('active');
                        isDownloading = false;
                    }, 1000);
                    
                } catch (error) {
                    console.error('Download error:', error);
                    
                    // Nếu lỗi, retry sau 2 giây
                    if (retryCount < maxRetries - 1) {
                        statusText.textContent = `Lỗi: ${error.message}. Đang thử lại...`;
                        isDownloading = false;
                        setTimeout(() => {
                            downloadFile(url, retryCount + 1);
                        }, 2000);
                    } else {
                        statusText.textContent = `Lỗi: ${error.message}. Không thể tải file.`;
                        setTimeout(() => {
                            overlay.classList.remove('active');
                            isDownloading = false;
                        }, 3000);
                    }
                }
            }

            btnExport.addEventListener('click', async function() {
                // Lấy giá trị từ SlimSelect hoặc native select
                let categoryIds = [];
                let brandIds = [];
                
                if (categorySlimSelect) {
                    categoryIds = categorySlimSelect.selected().map(id => parseInt(id)).filter(id => !isNaN(id));
                } else {
                    categoryIds = Array.from(document.getElementById('export-category-ids').selectedOptions)
                        .map(opt => parseInt(opt.value));
                }
                
                if (brandSlimSelect) {
                    brandIds = brandSlimSelect.selected().map(id => parseInt(id)).filter(id => !isNaN(id));
                } else {
                    brandIds = Array.from(document.getElementById('export-brand-ids').selectedOptions)
                        .map(opt => parseInt(opt.value));
                }

                if (categoryIds.length === 0 && brandIds.length === 0) {
                    if (!confirm('Bạn chưa chọn danh mục hoặc hãng nào. Sẽ xuất TẤT CẢ sản phẩm. Bạn có muốn tiếp tục?')) {
                        return;
                    }
                }

                isCancelled = false;
                isDownloading = false;
                overlay.classList.add('active');
                progressFill.style.width = '0%';
                progressFill.textContent = '0%';
                statusText.textContent = 'Đang khởi tạo...';

                try {
                    // Bắt đầu export
                    const startResponse = await fetch('{{ route("admin.products.export-import.export.start") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            category_ids: categoryIds,
                            brand_ids: brandIds
                        })
                    });

                    const startData = await startResponse.json();
                    if (!startData.success) {
                        throw new Error(startData.message || 'Lỗi khi bắt đầu xuất');
                    }

                    exportSessionId = startData.session_id;
                    statusText.textContent = `Tổng: ${startData.total_products} sản phẩm. Đang xử lý...`;

                    // Bắt đầu xử lý chunks (chỉ dùng progress polling, không dùng chunk interval)
                    // Vì chunk processing sẽ được trigger bởi progress polling

                    // Xử lý chunks tuần tự
                    let currentChunk = 0;
                    const chunkSize = 100;
                    let isProcessingChunk = false;

                    async function processNextChunk() {
                        if (isCancelled || isDownloading || isProcessingChunk) return;

                        isProcessingChunk = true;
                        try {
                            const chunkResponse = await fetch('{{ route("admin.products.export-import.export.chunk") }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    session_id: exportSessionId,
                                    chunk: currentChunk,
                                    chunk_size: chunkSize
                                })
                            });

                            const chunkData = await chunkResponse.json();
                            
                            if (chunkData.cancelled) {
                                clearInterval(progressInterval);
                                statusText.textContent = 'Đã hủy xuất.';
                                setTimeout(() => overlay.classList.remove('active'), 2000);
                                return;
                            }

                            if (!chunkData.success) {
                                throw new Error(chunkData.message || 'Lỗi khi xử lý chunk');
                            }

                            if (chunkData.completed && chunkData.file_url) {
                                clearInterval(progressInterval);
                                progressFill.style.width = '100%';
                                progressFill.textContent = '100%';
                                statusText.textContent = 'Hoàn thành! Đang kiểm tra file...';
                                
                                // Đợi một chút để đảm bảo file đã được tạo hoàn toàn
                                setTimeout(() => {
                                    downloadFile(chunkData.file_url);
                                }, 2000);
                                return;
                            }

                            // Chunk đã xử lý xong, tiếp tục chunk tiếp theo
                            currentChunk++;
                            isProcessingChunk = false;
                            
                            // Xử lý chunk tiếp theo sau 200ms
                            setTimeout(processNextChunk, 200);
                        } catch (error) {
                            clearInterval(progressInterval);
                            statusText.textContent = 'Lỗi: ' + error.message;
                            console.error('Chunk error:', error);
                            isProcessingChunk = false;
                        }
                    }

                    // Bắt đầu xử lý chunk đầu tiên
                    processNextChunk();

                    // Polling progress để update UI
                    progressInterval = setInterval(async () => {
                        if (isCancelled || !exportSessionId || isDownloading) return;

                        try {
                            const progressResponse = await fetch(`{{ route("admin.products.export-import.export.progress") }}?session_id=${exportSessionId}`);
                            const progressData = await progressResponse.json();

                            if (progressData.success) {
                                const progress = progressData.progress || 0;
                                progressFill.style.width = progress + '%';
                                progressFill.textContent = Math.round(progress) + '%';
                                statusText.textContent = `Đã xử lý: ${progressData.processed}/${progressData.total} sản phẩm`;

                                if (progressData.completed && progressData.file_url) {
                                    clearInterval(progressInterval);
                                    progressFill.style.width = '100%';
                                    progressFill.textContent = '100%';
                                    statusText.textContent = 'Hoàn thành! Đang kiểm tra file...';
                                    
                                    // Đợi một chút để đảm bảo file đã được tạo hoàn toàn
                                    setTimeout(() => {
                                        downloadFile(progressData.file_url);
                                    }, 2000);
                                }
                                
                                // Nếu đang finalize, tiếp tục đợi
                                if (progressData.message && progressData.message.includes('Đang tạo file')) {
                                    statusText.textContent = progressData.message;
                                }

                                if (progressData.cancelled) {
                                    clearInterval(progressInterval);
                                    statusText.textContent = 'Đã hủy xuất.';
                                    setTimeout(() => overlay.classList.remove('active'), 2000);
                                }
                            }
                        } catch (error) {
                            console.error('Progress error:', error);
                        }
                    }, 2000); // Poll mỗi 2 giây

                } catch (error) {
                    clearInterval(chunkInterval);
                    clearInterval(progressInterval);
                    statusText.textContent = 'Lỗi: ' + error.message;
                    console.error('Export error:', error);
                }
            });

            btnCancel.addEventListener('click', async function() {
                if (!exportSessionId) {
                    overlay.classList.remove('active');
                    return;
                }

                if (!confirm('Bạn có chắc muốn hủy xuất sản phẩm?')) {
                    return;
                }

                isCancelled = true;
                clearInterval(chunkInterval);
                clearInterval(progressInterval);

                try {
                    await fetch('{{ route("admin.products.export-import.export.cancel") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            session_id: exportSessionId
                        })
                    });

                    statusText.textContent = 'Đã hủy xuất.';
                    setTimeout(() => overlay.classList.remove('active'), 2000);
                } catch (error) {
                    console.error('Cancel error:', error);
                    overlay.classList.remove('active');
                }
            });
        });

        // Import Products với API
        (function() {
            const btnImport = document.getElementById('btn-import-products');
            const fileInput = document.getElementById('import-excel-file');
            if (!btnImport || !fileInput) return;

            // Tạo overlay (dùng chung với export)
            const importOverlay = document.createElement('div');
            importOverlay.className = 'import-overlay';
            importOverlay.style.display = 'none'; // Đảm bảo ẩn mặc định
            importOverlay.innerHTML = `
                <div class="export-modal">
                    <h4 style="margin-bottom: 20px;">Đang nhập sản phẩm...</h4>
                    <div class="export-progress">
                        <div class="export-progress-bar">
                            <div class="export-progress-fill" style="width: 0%;">0%</div>
                        </div>
                    </div>
                    <div class="export-status">Đang xử lý...</div>
                    <button type="button" class="btn btn-danger btn-cancel-export">Hủy nhập</button>
                </div>
            `;
            document.body.appendChild(importOverlay);

            const progressFill = importOverlay.querySelector('.export-progress-fill');
            const statusText = importOverlay.querySelector('.export-status');
            const btnCancel = importOverlay.querySelector('.btn-cancel-export');

            let importSessionId = null;
            let progressInterval = null;
            let chunkInterval = null;
            let isCancelled = false;
            let isProcessing = false;

            // Enable/disable button khi chọn file
            fileInput.addEventListener('change', function() {
                btnImport.disabled = !this.files || this.files.length === 0;
            });

            btnImport.addEventListener('click', async function() {
                if (!fileInput.files || fileInput.files.length === 0) {
                    alert('Vui lòng chọn file Excel để nhập.');
                    return;
                }

                const file = fileInput.files[0];
                
                // Kiểm tra file size (10MB)
                if (file.size > 10 * 1024 * 1024) {
                    alert('File quá lớn. Vui lòng chọn file nhỏ hơn 10MB.');
                    return;
                }

                // Kiểm tra extension
                const fileName = file.name.toLowerCase();
                if (!fileName.endsWith('.xlsx') && !fileName.endsWith('.xls')) {
                    alert('Vui lòng chọn file Excel (.xlsx hoặc .xls).');
                    return;
                }

                isCancelled = false;
                isProcessing = false;
                importOverlay.classList.add('active');
                progressFill.style.width = '0%';
                progressFill.textContent = '0%';
                statusText.textContent = 'Đang upload file...';

                try {
                    // Upload file và bắt đầu import
                    const formData = new FormData();
                    formData.append('excel_file', file);

                    const startResponse = await fetch('{{ route("admin.products.export-import.import.start") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: formData
                    });

                    const startData = await startResponse.json();
                    if (!startData.success) {
                        throw new Error(startData.message || 'Lỗi khi bắt đầu nhập');
                    }

                    importSessionId = startData.session_id;
                    statusText.textContent = `Tổng: ${startData.total_rows} dòng. Đang xử lý...`;

                    // Xử lý chunks tuần tự
                    let currentChunk = 0;
                    const chunkSize = 50;
                    let isProcessingChunk = false;

                    async function processNextChunk() {
                        if (isCancelled || isProcessingChunk) return;

                        isProcessingChunk = true;
                        try {
                            const chunkResponse = await fetch('{{ route("admin.products.export-import.import.chunk") }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    session_id: importSessionId,
                                    chunk: currentChunk,
                                    chunk_size: chunkSize
                                })
                            });

                            const chunkData = await chunkResponse.json();
                            
                            if (chunkData.cancelled) {
                                clearInterval(progressInterval);
                                statusText.textContent = 'Đã hủy nhập.';
                                setTimeout(() => importOverlay.classList.remove('active'), 2000);
                                return;
                            }

                            if (!chunkData.success) {
                                throw new Error(chunkData.message || 'Lỗi khi xử lý chunk');
                            }

                            if (chunkData.completed) {
                                clearInterval(progressInterval);
                                progressFill.style.width = '100%';
                                progressFill.textContent = '100%';
                                
                                const errorsCount = chunkData.errors_count || 0;
                                if (errorsCount > 0) {
                                    statusText.textContent = `Hoàn thành! Có ${errorsCount} lỗi.`;
                                } else {
                                    statusText.textContent = 'Hoàn thành! Đã nhập thành công.';
                                }
                                
                                setTimeout(() => {
                                    importOverlay.classList.remove('active');
                                    fileInput.value = '';
                                    btnImport.disabled = true;
                                    
                                    // Reload trang để xem sản phẩm mới (không có alert)
                                    window.location.reload();
                                }, 2000);
                                return;
                            }

                            // Chunk đã xử lý xong, tiếp tục chunk tiếp theo
                            currentChunk++;
                            isProcessingChunk = false;
                            
                            // Xử lý chunk tiếp theo sau 300ms
                            setTimeout(processNextChunk, 300);
                        } catch (error) {
                            clearInterval(progressInterval);
                            statusText.textContent = 'Lỗi: ' + error.message;
                            console.error('Chunk error:', error);
                            isProcessingChunk = false;
                        }
                    }

                    // Bắt đầu xử lý chunk đầu tiên
                    processNextChunk();

                    // Polling progress để update UI
                    progressInterval = setInterval(async () => {
                        if (isCancelled || !importSessionId) return;

                        try {
                            const progressResponse = await fetch(`{{ route("admin.products.export-import.import.progress") }}?session_id=${importSessionId}`);
                            const progressData = await progressResponse.json();

                            if (progressData.success) {
                                const progress = progressData.progress || 0;
                                progressFill.style.width = progress + '%';
                                progressFill.textContent = Math.round(progress) + '%';
                                
                                const errorsCount = progressData.errors_count || 0;
                                statusText.textContent = `Đã xử lý: ${progressData.processed}/${progressData.total} dòng${errorsCount > 0 ? ` (${errorsCount} lỗi)` : ''}`;

                                if (progressData.completed) {
                                    clearInterval(progressInterval);
                                    progressFill.style.width = '100%';
                                    progressFill.textContent = '100%';
                                    
                                    if (errorsCount > 0) {
                                        statusText.textContent = `Hoàn thành! Có ${errorsCount} lỗi.`;
                                    } else {
                                        statusText.textContent = 'Hoàn thành! Đã nhập thành công.';
                                    }
                                    
                                    setTimeout(() => {
                                        importOverlay.classList.remove('active');
                                        fileInput.value = '';
                                        btnImport.disabled = true;
                                        
                                        // Reload trang để xem sản phẩm mới (không có alert)
                                        window.location.reload();
                                    }, 2000);
                                }

                                if (progressData.cancelled) {
                                    clearInterval(progressInterval);
                                    statusText.textContent = 'Đã hủy nhập.';
                                    setTimeout(() => importOverlay.classList.remove('active'), 2000);
                                }
                            }
                        } catch (error) {
                            console.error('Progress error:', error);
                        }
                    }, 2000); // Poll mỗi 2 giây

                } catch (error) {
                    clearInterval(chunkInterval);
                    clearInterval(progressInterval);
                    statusText.textContent = 'Lỗi: ' + error.message;
                    console.error('Import error:', error);
                }
            });

            btnCancel.addEventListener('click', async function() {
                if (!importSessionId) {
                    importOverlay.classList.remove('active');
                    return;
                }

                if (!confirm('Bạn có chắc muốn hủy nhập sản phẩm?')) {
                    return;
                }

                isCancelled = true;
                clearInterval(chunkInterval);
                clearInterval(progressInterval);

                try {
                    await fetch('{{ route("admin.products.export-import.import.cancel") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            session_id: importSessionId
                        })
                    });

                    statusText.textContent = 'Đã hủy nhập.';
                    setTimeout(() => importOverlay.classList.remove('active'), 2000);
                } catch (error) {
                    console.error('Cancel error:', error);
                    importOverlay.classList.remove('active');
                }
            });
        });
    </script>
@endpush

@section('content')
    <div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h2 style="margin:0;">Danh sách sản phẩm</h2>
            <div style="display:flex;gap:10px;">
                <a href="{{ route('admin.products.create') }}" class="btn btn-primary">➕ Thêm sản phẩm</a>
            </div>
        </div>

        <form class="filter-bar" method="GET">
            <input type="text" name="keyword" placeholder="Tìm SKU hoặc tên..."
                   value="{{ request('keyword') }}">
            <select name="status">
                <option value="">-- Trạng thái --</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Đang bán</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Tạm ẩn</option>
            </select>
            <button type="submit" class="btn btn-primary">Lọc</button>
        </form>

        {{-- Import Excel với API --}}
        <div class="card shadow-sm mb-4" style="padding: 20px; background: #fff; border-radius: 8px;">
            <h5 style="margin-bottom: 15px;">📥 Nhập sản phẩm từ Excel</h5>
            <div class="row g-3">
                <div class="col-md-10">
                    <label class="form-label">Chọn file Excel</label>
                    <input type="file" id="import-excel-file" class="form-control" accept=".xlsx,.xls">
                    <small class="text-muted">Chỉ chấp nhận file .xlsx hoặc .xls (tối đa 10MB)</small>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" id="btn-import-products" class="btn btn-primary w-100" disabled>
                        📥 Nhập Excel
                    </button>
                </div>
            </div>
        </div>

        {{-- Export/Import với filter --}}
        <div class="card shadow-sm mb-4" style="padding: 20px; background: #fff; border-radius: 8px;">
            <h5 style="margin-bottom: 15px;">📤 Xuất sản phẩm theo bộ lọc</h5>
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Chọn danh mục (có thể chọn nhiều)</label>
                    <select id="export-category-ids" class="form-select" multiple>
                        @foreach(\App\Models\Category::where('is_active', true)->orderBy('name')->get() as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Gõ để tìm kiếm danh mục</small>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Chọn hãng (có thể chọn nhiều)</label>
                    <select id="export-brand-ids" class="form-select" multiple>
                        @foreach(\App\Models\Brand::where('is_active', true)->orderBy('name')->get() as $brand)
                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Gõ để tìm kiếm hãng</small>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" id="btn-export-products" class="btn btn-success w-100">
                        📤 Xuất Excel
                    </button>
                </div>
            </div>
            <div class="mt-3">
                <small class="text-muted">
                    <strong>Lưu ý:</strong> Nếu không chọn danh mục/hãng nào, sẽ xuất tất cả sản phẩm.
                </small>
            </div>
        </div>

        <div class="table-responsive">
            <table class="product-table">
                <thead>
                <tr>
                    <th style="width:40px;">
                        <input type="checkbox" id="select-all-products">
                    </th>
                    <th class="product-image-cell">Ảnh</th>
                    <th>SKU</th>
                    <th>Tên</th>
                    <th>Danh mục</th>
                    <th>Giá</th>
                    <th>Stock</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($products as $product)
                    <tr>
                        <td>
                            <input type="checkbox" name="selected[]" value="{{ $product->id }}" class="product-checkbox" form="bulk-action-form">
                        </td>
                        <td class="product-image-cell">
                            @php
                                $imageUrl = null;
                                $imagePath = null;
                                
                                // Lấy ảnh đầu tiên từ product
                                if ($product->primaryImage && $product->primaryImage->url) {
                                    $imagePath = 'clients/assets/img/clothes/' . $product->primaryImage->url;
                                    $fullPath = public_path($imagePath);
                                    
                                    // Kiểm tra file tồn tại
                                    if (file_exists($fullPath)) {
                                        $imageUrl = asset($imagePath);
                                    }
                                }
                                
                                // Fallback về no-image.webp nếu không có ảnh hoặc file không tồn tại
                                if (!$imageUrl) {
                                    $imageUrl = asset('clients/assets/img/clothes/no-image.webp');
                                }
                            @endphp
                            <img src="{{ $imageUrl }}" alt="{{ $product->name }}" class="product-image" loading="lazy">
                        </td>
                        <td>{{ $product->sku }}</td>
                        <td>
                            <strong>{{ $product->name }}</strong><br>
                            <small>Slug: {{ $product->slug }}</small>
                        </td>
                        <td>{{ $product->primaryCategory->name ?? '-' }}</td>
                        <td>{{ number_format($product->price) }}₫</td>
                        <td class="stock-cell">
                            <strong>{{ $product->stock_quantity }}</strong>
                            @if(! is_null($product->stock_quantity))
                                @if($product->stock_quantity <= 0)
                                    <span class="badge badge-danger">Hết hàng</span>
                                @elseif($product->stock_quantity <= 5)
                                    <span class="badge badge-warning">Sắp hết</span>
                                @else
                                    <span class="badge badge-success">Còn hàng</span>
                                @endif
                                <a href="{{ route('admin.products.inventory', $product) }}" class="stock-note">Xem lịch sử kho</a>
                            @endif
                        </td>
                        <td>
                            @if($product->is_active)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-danger">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-secondary">✏️</a>
                                @if($product->is_active)
                                    <form action="{{ route('admin.products.destroy', $product) }}" method="POST"
                                           onsubmit="return confirm('Chuyển sản phẩm này sang trạng thái TẠM ẨN?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-primary" style="background:#ef4444;border:none;">Ẩn</button>
                                    </form>
                                @else
                                    <form action="{{ route('admin.products.restore', $product) }}" method="POST"
                                           onsubmit="return confirm('Khôi phục sản phẩm này về trạng thái tạm ẩn?')">
                                        @csrf
                                        <button type="submit" class="btn btn-secondary">Khôi phục</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center;padding:40px;color:#94a3b8;">Chưa có sản phẩm nào</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <form action="{{ route('admin.products.bulk-action') }}" method="POST" id="bulk-action-form" style="margin-top:10px; display:flex; gap:10px;">
            @csrf
            <button type="submit" class="btn btn-success" name="bulk_action" value="restore">Khôi phục những sản phẩm đã chọn</button>
            <button type="submit" class="btn btn-danger" name="bulk_action" value="delete">Xóa mềm các sản phẩm đã chọn</button>
        </form>

        <div style="margin-top:20px;">
            {{ $products->links() }}
        </div>
    </div>
@endsection

