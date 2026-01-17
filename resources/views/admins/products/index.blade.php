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
                
                const maxRetries = 15; // Tăng số lần retry
                if (retryCount >= maxRetries) {
                    statusText.textContent = 'Lỗi: Không thể tải file sau nhiều lần thử. Vui lòng thử export lại.';
                    setTimeout(() => {
                        overlay.classList.remove('active');
                        isDownloading = false;
                    }, 3000);
                    return;
                }
                
                isDownloading = true;
                statusText.textContent = `Đang kiểm tra file... (${retryCount + 1}/${maxRetries})`;
                
                try {
                    // QUAN TRỌNG: KHÔNG gửi X-Requested-With header để server trả file Excel thay vì JSON
                    const downloadResponse = await fetch(url, {
                        method: 'GET',
                        // KHÔNG gửi X-Requested-With để server trả file Excel
                    });
                    
                    // Kiểm tra response
                    if (!downloadResponse.ok) {
                        // Nếu 404 hoặc 202 (đang tạo), đợi và retry
                        if (downloadResponse.status === 404 || downloadResponse.status === 202) {
                            statusText.textContent = 'File chưa sẵn sàng, đang đợi...';
                            isDownloading = false;
                            setTimeout(() => {
                                downloadFile(url, retryCount + 1);
                            }, 3000); // Tăng thời gian đợi lên 3s
                            return;
                        }
                        
                        // Nếu là lỗi khác, đọc response để lấy error message
                        const errorText = await downloadResponse.text();
                        if (errorText.includes('<html>') || errorText.includes('<!DOCTYPE')) {
                            // Là HTML error page, đợi và retry
                            statusText.textContent = 'File chưa sẵn sàng, đang đợi...';
                            isDownloading = false;
                            setTimeout(() => {
                                downloadFile(url, retryCount + 1);
                            }, 3000);
                            return;
                        }
                        
                        throw new Error(`HTTP ${downloadResponse.status}: ${downloadResponse.statusText}`);
                    }
                    
                    // Kiểm tra content-type
                    const contentType = downloadResponse.headers.get('content-type') || '';
                    const isExcelFile = contentType.includes('spreadsheet') || 
                                       contentType.includes('excel') || 
                                       contentType.includes('application/vnd.openxmlformats') ||
                                       contentType.includes('application/octet-stream');
                    
                    // Nếu không phải Excel file, có thể là JSON hoặc HTML
                    if (!isExcelFile) {
                        if (contentType.includes('application/json')) {
                            // Server trả JSON (có thể là error hoặc file_url)
                            const jsonData = await downloadResponse.json();
                            if (jsonData.file_url) {
                                // Có file_url mới, dùng nó
                                statusText.textContent = 'Đang chuyển hướng...';
                                isDownloading = false;
                                setTimeout(() => {
                                    downloadFile(jsonData.file_url, 0); // Reset retry count
                                }, 500);
                                return;
                            }
                            if (jsonData.message) {
                                statusText.textContent = jsonData.message;
                                isDownloading = false;
                                setTimeout(() => {
                                    downloadFile(url, retryCount + 1);
                                }, 3000);
                                return;
                            }
                        }
                        
                        if (contentType.includes('text/html')) {
                            // Là HTML error page, đợi và retry
                            statusText.textContent = 'File chưa sẵn sàng, đang đợi...';
                            isDownloading = false;
                            setTimeout(() => {
                                downloadFile(url, retryCount + 1);
                            }, 3000);
                            return;
                        }
                        
                        // Content-type không rõ, thử download blob xem
                        console.warn('Unknown content-type:', contentType);
                    }
                    
                    // File đã sẵn sàng, download bằng blob
                    statusText.textContent = 'Đang tải file...';
                    
                    // Lấy blob và download
                    const blob = await downloadResponse.blob();
                    
                    // Kiểm tra blob size (phải > 0)
                    if (blob.size === 0) {
                        throw new Error('File rỗng');
                    }
                    
                    // Nếu không chắc chắn là Excel file, kiểm tra nội dung
                    if (!isExcelFile) {
                        // Đọc một phần đầu để kiểm tra
                        const firstBytes = await blob.slice(0, 100).text();
                        if (firstBytes.trim().startsWith('{') || firstBytes.trim().startsWith('[')) {
                            // Có thể là JSON, đọc toàn bộ và parse
                            const blobText = await blob.text();
                            try {
                                const jsonData = JSON.parse(blobText);
                                if (jsonData.message) {
                                    statusText.textContent = jsonData.message;
                                    isDownloading = false;
                                    setTimeout(() => {
                                        downloadFile(url, retryCount + 1);
                                    }, 3000);
                                    return;
                                }
                                if (jsonData.file_url) {
                                    // Có file_url mới, dùng nó
                                    statusText.textContent = 'Đang chuyển hướng...';
                                    isDownloading = false;
                                    setTimeout(() => {
                                        downloadFile(jsonData.file_url, 0);
                                    }, 500);
                                    return;
                                }
                            } catch (e) {
                                // Không phải JSON hợp lệ, có thể là Excel file
                                console.warn('Blob không phải JSON, tiếp tục download');
                            }
                        }
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
                    
                    // Nếu lỗi, retry sau 3 giây
                    if (retryCount < maxRetries - 1) {
                        statusText.textContent = `Lỗi: ${error.message}. Đang thử lại...`;
                        isDownloading = false;
                        setTimeout(() => {
                            downloadFile(url, retryCount + 1);
                        }, 3000);
                    } else {
                        statusText.textContent = `Lỗi: ${error.message}. Không thể tải file. Vui lòng thử export lại.`;
                        setTimeout(() => {
                            overlay.classList.remove('active');
                            isDownloading = false;
                        }, 5000);
                    }
                }
            }

            btnExport.addEventListener('click', async function() {
                // Lấy giá trị từ SlimSelect - SlimSelect vẫn cập nhật giá trị trong select element gốc
                let categoryIds = [];
                let brandIds = [];
                
                // Lấy category IDs - lấy trực tiếp từ select element (SlimSelect tự động sync)
                const categorySelectElement = document.getElementById('export-category-ids');
                if (categorySelectElement) {
                    // Lấy từ selectedOptions (SlimSelect tự động cập nhật selected attribute)
                    const selectedOptions = categorySelectElement.querySelectorAll('option[selected]');
                    if (selectedOptions.length > 0) {
                        categoryIds = Array.from(selectedOptions)
                            .map(opt => parseInt(opt.value))
                            .filter(id => !isNaN(id));
                    } else {
                        // Fallback: lấy từ selectedOptions property
                        categoryIds = Array.from(categorySelectElement.selectedOptions || [])
                            .map(opt => parseInt(opt.value))
                            .filter(id => !isNaN(id));
                    }
                }
                
                // Lấy brand IDs - lấy trực tiếp từ select element (SlimSelect tự động sync)
                const brandSelectElement = document.getElementById('export-brand-ids');
                if (brandSelectElement) {
                    // Lấy từ selectedOptions (SlimSelect tự động cập nhật selected attribute)
                    const selectedOptions = brandSelectElement.querySelectorAll('option[selected]');
                    if (selectedOptions.length > 0) {
                        brandIds = Array.from(selectedOptions)
                            .map(opt => parseInt(opt.value))
                            .filter(id => !isNaN(id));
                    } else {
                        // Fallback: lấy từ selectedOptions property
                        brandIds = Array.from(brandSelectElement.selectedOptions || [])
                            .map(opt => parseInt(opt.value))
                            .filter(id => !isNaN(id));
                    }
                }

                // Debug log để kiểm tra
                console.log('Export - Category IDs:', categoryIds);
                console.log('Export - Brand IDs:', brandIds);
                console.log('Category Select:', categorySelectElement?.value, categorySelectElement?.selectedOptions);
                console.log('Brand Select:', brandSelectElement?.value, brandSelectElement?.selectedOptions);

                // Chỉ hiển thị confirm nếu THỰC SỰ không chọn gì
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
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            category_ids: categoryIds,
                            brand_ids: brandIds
                        })
                    });

                    // Kiểm tra response có phải JSON không
                    const contentType = startResponse.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        const text = await startResponse.text();
                        console.error('Server trả về không phải JSON:', text.substring(0, 200));
                        throw new Error('Server trả dữ liệu không hợp lệ (không phải JSON). Vui lòng xem log server.');
                    }

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
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    session_id: exportSessionId,
                                    chunk: currentChunk,
                                    chunk_size: chunkSize
                                })
                            });

                            // Kiểm tra response có phải JSON không
                            const contentType = chunkResponse.headers.get('content-type');
                            if (!contentType || !contentType.includes('application/json')) {
                                const text = await chunkResponse.text();
                                console.error('Server trả về không phải JSON:', text.substring(0, 200));
                                throw new Error('Server trả dữ liệu không hợp lệ (không phải JSON). Vui lòng xem log server.');
                            }

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
                            const progressResponse = await fetch(`{{ route("admin.products.export-import.export.progress") }}?session_id=${exportSessionId}`, {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                }
                            });

                            // Kiểm tra response có phải JSON không
                            const contentType = progressResponse.headers.get('content-type');
                            if (!contentType || !contentType.includes('application/json')) {
                                const text = await progressResponse.text();
                                console.error('Server trả về không phải JSON:', text.substring(0, 200));
                                // Không throw error ở đây vì đây là polling, chỉ log
                                return;
                            }

                            const progressData = await progressResponse.json();

                            if (progressData.success) {
                                const progress = progressData.progress || 0;
                                progressFill.style.width = progress + '%';
                                progressFill.textContent = Math.round(progress) + '%';
                                statusText.textContent = `Đã xử lý: ${progressData.processed}/${progressData.total} sản phẩm`;

                                // Kiểm tra nếu có lỗi
                                if (progressData.status === 'error' || progressData.error) {
                                    clearInterval(progressInterval);
                                    clearInterval(chunkInterval);
                                    statusText.textContent = `Lỗi: ${progressData.error || progressData.message || 'Có lỗi xảy ra khi xuất file'}`;
                                    setTimeout(() => {
                                        overlay.classList.remove('active');
                                    }, 5000);
                                    return;
                                }

                                // Chỉ download khi status = 'completed' và có file_url
                                if (progressData.completed && progressData.status === 'completed' && progressData.file_url) {
                                    clearInterval(progressInterval);
                                    clearInterval(chunkInterval);
                                    progressFill.style.width = '100%';
                                    progressFill.textContent = '100%';
                                    statusText.textContent = 'Hoàn thành! Đang kiểm tra file...';
                                    
                                    // Đợi lâu hơn để đảm bảo file đã được tạo hoàn toàn (Job có thể cần thời gian)
                                    setTimeout(() => {
                                        downloadFile(progressData.file_url);
                                    }, 5000); // Tăng lên 5s để đảm bảo file đã được tạo xong
                                } else if (progressData.completed && !progressData.file_url) {
                                    // Completed nhưng chưa có file_url, đợi thêm
                                    statusText.textContent = 'Hoàn thành! Đang tạo file...';
                                }
                                
                                // Nếu đang finalize hoặc processing, tiếp tục đợi
                                if (progressData.status === 'finalizing' || progressData.status === 'processing' || progressData.status === 'queued') {
                                    statusText.textContent = progressData.message || `Đang xử lý... (${progressData.status})`;
                                }
                                
                                if (progressData.message && progressData.message.includes('Đang tạo file')) {
                                    statusText.textContent = progressData.message;
                                }

                                if (progressData.cancelled) {
                                    clearInterval(progressInterval);
                                    clearInterval(chunkInterval);
                                    statusText.textContent = 'Đã hủy xuất.';
                                    setTimeout(() => overlay.classList.remove('active'), 2000);
                                }
                            } else {
                                // Nếu không success, có thể là lỗi
                                if (progressData.error || progressData.message) {
                                    clearInterval(progressInterval);
                                    clearInterval(chunkInterval);
                                    statusText.textContent = `Lỗi: ${progressData.error || progressData.message}`;
                                    setTimeout(() => {
                                        overlay.classList.remove('active');
                                    }, 5000);
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
                    const cancelResponse = await fetch('{{ route("admin.products.export-import.export.cancel") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            session_id: exportSessionId
                        })
                    });

                    // Kiểm tra response có phải JSON không (không bắt buộc, chỉ log nếu lỗi)
                    const contentType = cancelResponse.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        const cancelData = await cancelResponse.json();
                        if (!cancelData.success) {
                            console.warn('Cancel response:', cancelData.message);
                        }
                    }

                    statusText.textContent = 'Đã hủy xuất.';
                    setTimeout(() => overlay.classList.remove('active'), 2000);
                } catch (error) {
                    console.error('Cancel error:', error);
                    overlay.classList.remove('active');
                }
            });
        });

        // Import Products với API
        // Đảm bảo chạy sau khi DOM ready
        function initImportProducts() {
            console.log('🔵 [DEBUG] Import Products script starting...');
            
            const btnImport = document.getElementById('btn-import-products');
            const fileInput = document.getElementById('import-excel-file');
            const dropZone = document.getElementById('import-file-drop-zone');
            const fileNameDisplay = document.getElementById('import-file-name');
            
            console.log('🔵 [DEBUG] Elements found:', {
                btnImport: !!btnImport,
                fileInput: !!fileInput,
                dropZone: !!dropZone,
                fileNameDisplay: !!fileNameDisplay
            });
            
            if (!btnImport || !fileInput) {
                console.error('🔴 [DEBUG] Missing elements:', {
                    btnImport: !!btnImport,
                    fileInput: !!fileInput
                });
                return;
            }

            // Kiểm tra trạng thái ban đầu của button
            console.log('🔵 [DEBUG] Initial button state:', {
                disabled: btnImport.disabled,
                className: btnImport.className,
                style: btnImport.style.cssText,
                computedStyle: window.getComputedStyle(btnImport).display
            });

            // Hàm để enable button với debug
            function enableImportButton(file) {
                console.log('🟢 [DEBUG] enableImportButton called with file:', file ? file.name : 'null');
                
                if (!file) {
                    console.warn('⚠️ [DEBUG] No file provided to enableImportButton');
                    return;
                }
                
                // Force enable button
                btnImport.disabled = false;
                btnImport.removeAttribute('disabled');
                btnImport.style.opacity = '1';
                btnImport.style.cursor = 'pointer';
                btnImport.style.pointerEvents = 'auto';
                
                // Hiển thị tên file
                if (fileNameDisplay) {
                    fileNameDisplay.textContent = `📄 ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
                    fileNameDisplay.style.display = 'block';
                }
                
                // Kiểm tra lại sau một chút
                setTimeout(function() {
                    const isStillDisabled = btnImport.disabled || btnImport.hasAttribute('disabled');
                    const computedStyle = window.getComputedStyle(btnImport);
                    console.log('🔵 [DEBUG] Button state after enable:', {
                        disabled: isStillDisabled,
                        hasDisabledAttr: btnImport.hasAttribute('disabled'),
                        opacity: computedStyle.opacity,
                        display: computedStyle.display,
                        visibility: computedStyle.visibility,
                        pointerEvents: computedStyle.pointerEvents
                    });
                    
                    if (isStillDisabled) {
                        console.warn('⚠️ [DEBUG] Button still disabled! Force enabling...');
                        btnImport.disabled = false;
                        btnImport.removeAttribute('disabled');
                        btnImport.style.opacity = '1';
                        btnImport.style.cursor = 'pointer';
                    }
                }, 100);
                
                console.log('🟢 [DEBUG] Button should be enabled now');
            }

            // Xử lý drag & drop
            if (dropZone && fileInput.parentElement) {
                const parentContainer = fileInput.parentElement;
                
                parentContainer.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (dropZone) {
                        dropZone.style.borderColor = '#007bff';
                        dropZone.style.backgroundColor = 'rgba(0, 123, 255, 0.1)';
                    }
                    console.log('🔵 [DEBUG] Drag over import area');
                });

                parentContainer.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (dropZone) {
                        dropZone.style.borderColor = 'transparent';
                        dropZone.style.backgroundColor = 'transparent';
                    }
                    console.log('🔵 [DEBUG] Drag leave import area');
                });

                parentContainer.addEventListener('drop', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (dropZone) {
                        dropZone.style.borderColor = 'transparent';
                        dropZone.style.backgroundColor = 'transparent';
                    }
                    console.log('🔵 [DEBUG] File dropped on import area');

                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        const file = files[0];
                        const fileName = file.name.toLowerCase();
                        
                        if (fileName.endsWith('.xlsx') || fileName.endsWith('.xls')) {
                            // Gán file vào input
                            const dataTransfer = new DataTransfer();
                            dataTransfer.items.add(file);
                            fileInput.files = dataTransfer.files;
                            
                            // Trigger change event
                            fileInput.dispatchEvent(new Event('change', { bubbles: true }));
                            console.log('🟢 [DEBUG] File assigned to input via drag & drop');
                        } else {
                            alert('Vui lòng chọn file Excel (.xlsx hoặc .xls)');
                            console.error('🔴 [DEBUG] Invalid file type dropped:', fileName);
                        }
                    }
                });
            }

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
                    <div class="import-errors" style="margin-top:12px; max-height:220px; overflow:auto; display:none; font-size:13px; background:#fff7ed; border:1px solid #fdba74; padding:10px; border-radius:6px;"></div>
                    <button type="button" class="btn btn-danger btn-cancel-export">Hủy nhập</button>
                </div>
            `;
            document.body.appendChild(importOverlay);

            const progressFill = importOverlay.querySelector('.export-progress-fill');
            const statusText = importOverlay.querySelector('.export-status');
            const btnCancel = importOverlay.querySelector('.btn-cancel-export');
            const errorsBox = importOverlay.querySelector('.import-errors');

            let importSessionId = null;
            let progressInterval = null;
            let chunkInterval = null;
            let isCancelled = false;
            let isProcessing = false;
            let isCompletedImport = false;

            // Enable/disable button khi chọn file
            fileInput.addEventListener('change', function(e) {
                console.log('🔵 [DEBUG] ========== File input change event fired ==========');
                console.log('🔵 [DEBUG] Event:', e);
                console.log('🔵 [DEBUG] this.files:', this.files);
                console.log('🔵 [DEBUG] this.files.length:', this.files ? this.files.length : 0);
                
                const file = this.files && this.files.length > 0 ? this.files[0] : null;
                console.log('🔵 [DEBUG] Extracted file:', file ? {
                    name: file.name,
                    size: file.size,
                    type: file.type
                } : 'null');
                
                if (file) {
                    const fileName = file.name.toLowerCase();
                    const isValidExtension = fileName.endsWith('.xlsx') || fileName.endsWith('.xls');
                    const isValidSize = file.size <= 10 * 1024 * 1024; // 10MB
                    
                    console.log('🔵 [DEBUG] File validation:', {
                        name: file.name,
                        size: (file.size / 1024 / 1024).toFixed(2) + ' MB',
                        isValidExtension: isValidExtension,
                        isValidSize: isValidSize,
                        fileName: fileName
                    });
                    
                    if (isValidExtension && isValidSize) {
                        console.log('🟢 [DEBUG] File is valid, enabling button...');
                        enableImportButton(file);
                    } else {
                        console.error('🔴 [DEBUG] File is invalid:', {
                            isValidExtension: isValidExtension,
                            isValidSize: isValidSize
                        });
                        
                        btnImport.disabled = true;
                        btnImport.setAttribute('disabled', 'disabled');
                        btnImport.style.opacity = '0.6';
                        btnImport.style.cursor = 'not-allowed';
                        
                        if (fileNameDisplay) {
                            fileNameDisplay.style.display = 'none';
                        }
                        
                        if (!isValidExtension) {
                            alert('Vui lòng chọn file Excel (.xlsx hoặc .xls)');
                            console.error('🔴 [DEBUG] Invalid file extension');
                        } else if (!isValidSize) {
                            alert('File quá lớn. Vui lòng chọn file nhỏ hơn 10MB.');
                            console.error('🔴 [DEBUG] File too large');
                        }
                    }
                } else {
                    console.log('🔵 [DEBUG] No file selected');
                    btnImport.disabled = true;
                    btnImport.setAttribute('disabled', 'disabled');
                    btnImport.style.opacity = '0.6';
                    btnImport.style.cursor = 'not-allowed';
                    
                    if (fileNameDisplay) {
                        fileNameDisplay.style.display = 'none';
                    }
                    
                    console.log('🔵 [DEBUG] Button disabled - no file');
                }
                
                console.log('🔵 [DEBUG] ========== File input change event ended ==========');
            });

            // Kiểm tra file đã có sẵn khi page load (nếu user refresh page sau khi chọn file)
            if (fileInput.files && fileInput.files.length > 0) {
                console.log('🔵 [DEBUG] File already selected on page load');
                const file = fileInput.files[0];
                const fileName = file.name.toLowerCase();
                const isValidExtension = fileName.endsWith('.xlsx') || fileName.endsWith('.xls');
                const isValidSize = file.size <= 10 * 1024 * 1024;
                
                if (isValidExtension && isValidSize) {
                    console.log('🟢 [DEBUG] Enabling button for existing file');
                    enableImportButton(file);
                }
            }

            // Monitor button state changes (debug)
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'disabled') {
                        console.log('🔵 [DEBUG] Button disabled attribute changed:', {
                            disabled: btnImport.disabled,
                            hasAttribute: btnImport.hasAttribute('disabled')
                        });
                    }
                });
            });
            observer.observe(btnImport, { attributes: true, attributeFilter: ['disabled'] });

            // Xử lý click button import
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
                    // Upload file và bắt đầu import với 10 workers song song
                    isCompletedImport = false;
                    btnCancel.textContent = 'Hủy nhập';
                    const formData = new FormData();
                    formData.append('excel_file', file);
                    formData.append('workers', 10); // 10 luồng song song

                    const startResponse = await fetch('{{ route("admin.products.export-import.import.start") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: formData
                    });

                    // Kiểm tra response trước khi parse JSON
                    if (!startResponse.ok) {
                        const errorText = await startResponse.text();
                        throw new Error(`Server trả về lỗi ${startResponse.status}: ${errorText.substring(0, 200)}`);
                    }

                    const contentType = startResponse.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        const errorText = await startResponse.text();
                        throw new Error(`Server trả về dữ liệu không hợp lệ. Response: ${errorText.substring(0, 200)}`);
                    }

                    const startData = await startResponse.json();
                    if (!startData.success) {
                        throw new Error(startData.message || 'Lỗi khi bắt đầu nhập');
                    }

                    // Lấy session_ids từ response (có thể là array hoặc single string)
                    const sessionIds = startData.session_ids || [startData.session_id];
                    const groupId = startData.group_id || null;
                    const totalRows = startData.total_rows || 0;
                    const workers = startData.workers || 1;
                    
                    importSessionId = sessionIds[0]; // Dùng session đầu tiên cho backward compatibility
                    statusText.textContent = `Tổng: ${totalRows} dòng. Đang xử lý với ${workers} luồng song song...`;

                    // Xử lý chunks song song cho tất cả workers
                    const chunkSize = 50;
                    const workerChunks = {}; // {sessionId: currentChunk}
                    const workerProcessing = {}; // {sessionId: isProcessing}
                    
                    // Khởi tạo chunks cho từng worker
                    sessionIds.forEach(sessionId => {
                        workerChunks[sessionId] = 0;
                        workerProcessing[sessionId] = false;
                    });

                    // Hàm xử lý chunk cho một worker
                    async function processNextChunkForWorker(sessionId) {
                        if (isCancelled || workerProcessing[sessionId]) return;

                        workerProcessing[sessionId] = true;
                        try {
                            const chunkResponse = await fetch('{{ route("admin.products.export-import.import.chunk") }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    session_id: sessionId,
                                    chunk: workerChunks[sessionId],
                                    chunk_size: chunkSize
                                })
                            });

                            // Kiểm tra response trước khi parse JSON
                            if (!chunkResponse.ok) {
                                const errorText = await chunkResponse.text();
                                throw new Error(`Server trả về lỗi ${chunkResponse.status}: ${errorText.substring(0, 200)}`);
                            }

                            const contentType = chunkResponse.headers.get('content-type');
                            if (!contentType || !contentType.includes('application/json')) {
                                const errorText = await chunkResponse.text();
                                throw new Error(`Server trả về dữ liệu không hợp lệ. Response: ${errorText.substring(0, 200)}`);
                            }

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
                                // Worker này đã hoàn thành, không tiếp tục
                                workerProcessing[sessionId] = false;
                                return;
                            }

                            // Chunk đã xử lý xong, tiếp tục chunk tiếp theo
                            workerChunks[sessionId]++;
                            workerProcessing[sessionId] = false;
                            
                            // Xử lý chunk tiếp theo sau 100ms (nhanh hơn vì có nhiều workers)
                            setTimeout(() => processNextChunkForWorker(sessionId), 100);
                        } catch (error) {
                            console.error(`Chunk error for worker ${sessionId}:`, error);
                            workerProcessing[sessionId] = false;
                            // Tiếp tục thử lại sau 1 giây
                            setTimeout(() => processNextChunkForWorker(sessionId), 1000);
                        }
                    }

                    // Bắt đầu xử lý chunks cho tất cả workers song song
                    sessionIds.forEach(sessionId => {
                        processNextChunkForWorker(sessionId);
                    });

                    // Khởi tạo totalRows từ startData để dùng cho progress
                    let totalRowsForProgress = totalRows;

                    // Polling progress để update UI (tổng hợp từ tất cả workers)
                    progressInterval = setInterval(async () => {
                        if (isCancelled || !sessionIds.length) return;

                        try {
                            let totalProcessed = 0;
                            let currentTotalRows = totalRowsForProgress; // Dùng giá trị từ startData hoặc từ worker đầu tiên
                            let totalErrors = 0;
                            let allCompleted = true;
                            let anyCancelled = false;
                            let aggregatedErrors = [];

                            // CHỈ lấy progress từ worker đầu tiên (backend đã tính tổng hợp từ tất cả workers)
                            // Không cần gọi tất cả workers vì backend đã tổng hợp rồi
                            try {
                                const progressResponse = await fetch(`{{ route("admin.products.export-import.import.progress") }}?session_id=${sessionIds[0]}`, {
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'Accept': 'application/json'
                                    }
                                });
                                
                                if (!progressResponse.ok) {
                                    allCompleted = false;
                                    return;
                                }
                                
                                const contentType = progressResponse.headers.get('content-type');
                                if (!contentType || !contentType.includes('application/json')) {
                                    const errorText = await progressResponse.text();
                                    console.error('Server trả về không phải JSON:', errorText.substring(0, 200));
                                    allCompleted = false;
                                    return;
                                }
                                
                                const progressData = await progressResponse.json();
                                
                                if (!progressData || !progressData.success) {
                                    allCompleted = false;
                                    return;
                                }
                                
                                // Backend đã tính tổng hợp từ tất cả workers rồi
                                totalProcessed = progressData.processed || 0;
                                currentTotalRows = progressData.total || totalRowsForProgress;
                                totalErrors = progressData.errors_count || 0;
                                
                                // Lấy errors từ backend (đã tổng hợp)
                                if (Array.isArray(progressData.errors) && progressData.errors.length) {
                                    aggregatedErrors = progressData.errors;
                                }
                                
                                // Kiểm tra completed: backend đã kiểm tra tất cả workers và trả về completed: true
                                if (progressData.completed) {
                                    allCompleted = true;
                                } else if (progressData.status !== 'completed_worker' && progressData.status !== 'completed') {
                                    allCompleted = false;
                                }
                                
                                if (progressData.cancelled) {
                                    anyCancelled = true;
                                }
                                
                                // Cập nhật totalRowsForProgress để dùng cho lần sau
                                if (progressData.total > 0) {
                                    totalRowsForProgress = progressData.total;
                                }
                            } catch (error) {
                                console.error('Progress error:', error);
                                allCompleted = false;
                                return;
                            }

                            // Tính progress từ dữ liệu backend (đã tổng hợp)
                            const overallProgress = currentTotalRows > 0 ? (totalProcessed / currentTotalRows) * 100 : 0;
                            progressFill.style.width = Math.min(overallProgress, 100) + '%'; // Giới hạn tối đa 100%
                            progressFill.textContent = Math.min(Math.round(overallProgress), 100) + '%';
                            
                            statusText.textContent = `Đã xử lý: ${totalProcessed}/${currentTotalRows} dòng${totalErrors > 0 ? ` (${totalErrors} lỗi)` : ''} - ${workers} luồng song song`;

                            if (allCompleted) {
                                clearInterval(progressInterval);
                                progressFill.style.width = '100%';
                                progressFill.textContent = '100%';
                                
                                if (totalErrors > 0) {
                                    statusText.textContent = `Hoàn thành! Có ${totalErrors} lỗi.`;
                                    errorsBox.style.display = 'block';
                                    errorsBox.innerHTML = aggregatedErrors.map((err, idx) => {
                                        const type = err.type || 'ERROR';
                                        const sku = err.sku || 'N/A';
                                        const message = err.message || '';
                                        const row = err.row ? ` (row ${err.row})` : '';
                                        return `<div style=\"margin-bottom:6px;\"><strong>${idx + 1}. [${type}]</strong> SKU: ${sku}${row} - ${message}</div>`;
                                    }).join('') || '<div>Không có chi tiết lỗi.</div>';
                                } else {
                                    statusText.textContent = 'Hoàn thành! Đã nhập thành công.';
                                    errorsBox.style.display = 'none';
                                    errorsBox.innerHTML = '';
                                }

                                // Giữ overlay mở để xem lỗi; đổi nút thành Đóng và đánh dấu đã hoàn thành
                                btnCancel.textContent = 'Đóng';
                                isCompletedImport = true;
                            }

                            if (anyCancelled) {
                                clearInterval(progressInterval);
                                statusText.textContent = 'Đã hủy nhập.';
                                setTimeout(() => importOverlay.classList.remove('active'), 2000);
                            }
                        } catch (error) {
                            console.error('Progress error:', error);
                        }
                    }, 1500); // Poll mỗi 1.5 giây (nhanh hơn vì có nhiều workers)

                } catch (error) {
                    clearInterval(chunkInterval);
                    clearInterval(progressInterval);
                    statusText.textContent = 'Lỗi: ' + error.message;
                    console.error('Import error:', error);
                }
            });

            btnCancel.addEventListener('click', async function() {
                if (isCompletedImport || !importSessionId) {
                    importOverlay.classList.remove('active');
                    errorsBox.style.display = 'none';
                    errorsBox.innerHTML = '';
                    fileInput.value = '';
                    btnImport.disabled = true;
                    btnCancel.textContent = 'Hủy nhập';
                    isCompletedImport = false;
                    return;
                }

                if (!confirm('Bạn có chắc muốn hủy nhập sản phẩm?')) {
                    return;
                }

                isCancelled = true;
                clearInterval(chunkInterval);
                clearInterval(progressInterval);

                try {
                    const cancelResponse = await fetch('{{ route("admin.products.export-import.import.cancel") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            session_id: importSessionId
                        })
                    });

                    // Kiểm tra response có phải JSON không (không bắt buộc, chỉ log nếu lỗi)
                    const contentType = cancelResponse.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        const cancelData = await cancelResponse.json();
                        if (!cancelData.success) {
                            console.warn('Cancel response:', cancelData.message);
                        }
                    }

                    statusText.textContent = 'Đã hủy nhập.';
                    setTimeout(() => importOverlay.classList.remove('active'), 2000);
                } catch (error) {
                    console.error('Cancel error:', error);
                    importOverlay.classList.remove('active');
                }
            });
        }

        // Gọi hàm init khi DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initImportProducts);
        } else {
            // DOM đã ready, gọi ngay
            initImportProducts();
        }
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
                    <div style="position: relative;">
                        <input type="file" id="import-excel-file" class="form-control" accept=".xlsx,.xls" style="position: relative; z-index: 1;">
                        <div id="import-file-drop-zone" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; border: 2px dashed transparent; border-radius: 4px; pointer-events: none; z-index: 0;"></div>
                    </div>
                    <small class="text-muted">Chỉ chấp nhận file .xlsx hoặc .xls (tối đa 10MB)</small>
                    <div id="import-file-name" style="margin-top: 8px; color: #007bff; font-weight: 600; display: none;"></div>
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
                        @foreach(\App\Models\Category::where('is_active', true)->withCount('products')->orderBy('name')->get() as $category)
                            <option value="{{ $category->id }}">
                                {{ $category->name }} ({{ $category->products_count }})
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Gõ để tìm kiếm danh mục</small>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Chọn hãng (có thể chọn nhiều)</label>
                    <select id="export-brand-ids" class="form-select" multiple>
                        @foreach(\App\Models\Brand::where('is_active', true)->withCount('products')->orderBy('name')->get() as $brand)
                            <option value="{{ $brand->id }}">
                                {{ $brand->name }} ({{ $brand->products_count }})
                            </option>
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

