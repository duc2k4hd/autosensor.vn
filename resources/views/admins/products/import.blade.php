@extends('admins.layouts.master')

@section('title', 'Import Sản Phẩm từ Excel')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/imports-excel.png') }}" type="image/x-icon">
@endpush

@push('styles')
<style>
    .import-progress {
        display: none;
        margin-top: 20px;
    }
    .progress-bar-container {
        background: #f0f0f0;
        border-radius: 10px;
        height: 30px;
        overflow: hidden;
        position: relative;
    }
    .progress-bar {
        background: linear-gradient(90deg, #28a745, #20c997);
        height: 100%;
        transition: width 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 12px;
    }
    .import-stats {
        margin-top: 15px;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 10px;
    }
    .stat-card {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        text-align: center;
    }
    .stat-card .stat-value {
        font-size: 24px;
        font-weight: bold;
        color: #333;
    }
    .stat-card .stat-label {
        font-size: 12px;
        color: #666;
        margin-top: 5px;
    }
    .errors-list {
        max-height: 300px;
        overflow-y: auto;
        margin-top: 10px;
    }
</style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Import Sản Phẩm từ Excel</h3>
                    </div>
                    <div class="card-body">
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

                        <form id="importForm" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <label for="excel_file">Chọn file Excel (.xlsx, .xls)</label>
                                <input type="file" class="form-control-file @error('excel_file') is-invalid @enderror" 
                                       id="excel_file" name="excel_file" accept=".xlsx,.xls" required>
                                @error('excel_file')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    File Excel phải có cấu trúc: 
                                    <ul>
                                        <li><strong>Cột A:</strong> SKU (Mã sản phẩm)</li>
                                        <li><strong>Cột B:</strong> Tên sản phẩm</li>
                                        <li><strong>Cột C:</strong> Mô tả ngắn (HTML)</li>
                                        <li><strong>Cột D:</strong> Mô tả chi tiết (HTML)</li>
                                        <li><strong>Cột E:</strong> Giá gốc</li>
                                        <li><strong>Cột F:</strong> Giá khuyến mãi</li>
                                        <li><strong>Cột G:</strong> Danh mục (slug)</li>
                                        <li><strong>Cột H:</strong> Thẻ (tags, cách nhau dấu phẩy)</li>
                                        <li><strong>Cột I:</strong> Hình ảnh (URL, cách nhau dấu phẩy)</li>
                                        <li><strong>Cột J:</strong> Thương hiệu</li>
                                        <li><strong>Cột K:</strong> Link tài liệu (catalog)</li>
                                    </ul>
                                    <strong>Lưu ý:</strong>
                                    <ul>
                                        <li>Nếu SKU đã tồn tại, sản phẩm sẽ được cập nhật</li>
                                        <li>Nếu SKU chưa tồn tại, sản phẩm mới sẽ được tạo</li>
                                        <li>Hình ảnh sẽ được tải về và đặt tên theo SKU</li>
                                        <li>Link tài liệu sẽ được tải về và đặt vào thư mục catalog</li>
                                        <li>Import sẽ được xử lý theo batch để tránh timeout</li>
                                    </ul>
                                </small>
                            </div>

                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-upload"></i> Import Sản Phẩm
                            </button>
                            <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Quay lại
                            </a>
                        </form>

                        <!-- Progress Section -->
                        <div class="import-progress" id="importProgress">
                            <h5>Đang xử lý import...</h5>
                            <div class="progress-bar-container">
                                <div class="progress-bar" id="progressBar" style="width: 0%">0%</div>
                            </div>
                            <div class="import-stats" id="importStats">
                                <div class="stat-card">
                                    <div class="stat-value" id="statCreated">0</div>
                                    <div class="stat-label">Đã tạo</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value" id="statUpdated">0</div>
                                    <div class="stat-label">Đã cập nhật</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value" id="statSkipped">0</div>
                                    <div class="stat-label">Đã bỏ qua</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value" id="statErrors">0</div>
                                    <div class="stat-label">Lỗi</div>
                                </div>
                            </div>
                            <div id="errorsContainer" style="display: none; margin-top: 15px;">
                                <h6>Chi tiết lỗi:</h6>
                                <div class="errors-list alert alert-warning" id="errorsList"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('importForm');
    const submitBtn = document.getElementById('submitBtn');
    const progressSection = document.getElementById('importProgress');
    const progressBar = document.getElementById('progressBar');
    const statCreated = document.getElementById('statCreated');
    const statUpdated = document.getElementById('statUpdated');
    const statSkipped = document.getElementById('statSkipped');
    const statErrors = document.getElementById('statErrors');
    const errorsContainer = document.getElementById('errorsContainer');
    const errorsList = document.getElementById('errorsList');

    let totalStats = {
        created: 0,
        updated: 0,
        skipped: 0,
        errors: 0
    };
    let allErrors = [];
    
    // Số lượng workers chạy song song (10 workers, mỗi worker có session riêng)
    const TOTAL_WORKERS = 10;
    let completedBatches = 0;
    let totalBatches = 0;
    let sessions = []; // Mảng chứa thông tin 10 sessions
    let isProcessing = false;
    let hasError = false;

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const fileInput = document.getElementById('excel_file');
        if (!fileInput.files.length) {
            alert('Vui lòng chọn file Excel');
            return;
        }

        const formData = new FormData();
        formData.append('excel_file', fileInput.files[0]);
        formData.append('_token', '{{ csrf_token() }}');

        // Disable form
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang upload...';
        progressSection.style.display = 'block';
        progressBar.style.width = '0%';
        progressBar.textContent = '0%';
        
        // Reset stats
        totalStats = { created: 0, updated: 0, skipped: 0, errors: 0 };
        allErrors = [];
        completedBatches = 0;
        hasError = false;
        updateStats();
        errorsContainer.style.display = 'none';
        errorsList.innerHTML = '';

        try {
            // Step 1: Upload file
            const uploadResponse = await fetch('{{ route("admin.products.import.upload") }}', {
                method: 'POST',
                body: formData
            });

            const uploadData = await uploadResponse.json();

            if (!uploadData.success) {
                throw new Error(uploadData.message || 'Lỗi upload file');
            }

            // Nhận 10 sessions từ server
            sessions = uploadData.sessions || [];
            if (sessions.length === 0) {
                throw new Error('Không có session nào được tạo');
            }

            // Tính tổng số batches từ tất cả workers
            totalBatches = sessions.reduce((sum, session) => sum + (session.total_batches || 0), 0);

            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
            isProcessing = true;

            // Step 2: Process batches song song với 10 workers
            await processBatchesInParallel();

        } catch (error) {
            console.error('Import error:', error);
            alert('Lỗi import: ' + error.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-upload"></i> Import Sản Phẩm';
            progressBar.style.background = '#dc3545';
            isProcessing = false;
        }
    });

    /**
     * Xử lý nhiều batch song song cùng lúc với 10 workers (mỗi worker có session riêng)
     */
    async function processBatchesInParallel() {
        const activePromises = [];

        // Hàm worker: xử lý batches của một session riêng biệt
        const worker = async (sessionInfo) => {
            const { session_id, worker_index, total_batches } = sessionInfo;
            let batchNumber = 1;

            while (isProcessing && batchNumber <= total_batches) {
                // Retry logic với exponential backoff
                let retries = 0;
                const MAX_RETRIES = 2;
                let success = false;

                while (retries <= MAX_RETRIES && !success) {
                    try {
                        const batchResponse = await fetch('{{ route("admin.products.import.batch") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                session_id: session_id,
                                batch_number: batchNumber
                            }),
                            // Tăng timeout lên 5 phút
                            signal: AbortSignal.timeout(300000)
                        });

                        // Kiểm tra response có phải JSON không
                        const contentType = batchResponse.headers.get('content-type');
                        let batchData;
                        
                        if (contentType && contentType.includes('application/json')) {
                            batchData = await batchResponse.json();
                        } else {
                            // Nếu không phải JSON, đọc text để debug
                            const text = await batchResponse.text();
                            console.error(`Worker ${worker_index} Batch ${batchNumber} response không phải JSON:`, text.substring(0, 500));
                            throw new Error(`Server trả về response không hợp lệ (status: ${batchResponse.status})`);
                        }
                        
                        // Kiểm tra HTTP status
                        if (!batchResponse.ok) {
                            // Nếu là timeout (500) và còn retry, thử lại
                            if (batchResponse.status === 500 && retries < MAX_RETRIES) {
                                const waitTime = Math.pow(2, retries) * 1000; // Exponential backoff
                                console.warn(`Worker ${worker_index} Batch ${batchNumber} timeout, retry ${retries + 1}/${MAX_RETRIES} sau ${waitTime}ms...`);
                                await new Promise(resolve => setTimeout(resolve, waitTime));
                                retries++;
                                continue;
                            }
                            throw new Error(batchData.message || `HTTP ${batchResponse.status}: ${batchResponse.statusText}`);
                        }

                        if (!batchData.success) {
                            console.error(`Worker ${worker_index} Batch ${batchNumber} lỗi:`, batchData.message);
                            hasError = true;
                            allErrors.push({
                                row: `Worker ${worker_index} Batch ${batchNumber}`,
                                sku: 'N/A',
                                message: batchData.message || `Lỗi xử lý batch ${batchNumber}`
                            });
                            totalStats.errors++;
                        } else {
                            // Update stats - đảm bảo số liệu chính xác
                            if (batchData.stats) {
                                const created = parseInt(batchData.stats.created || 0);
                                const updated = parseInt(batchData.stats.updated || 0);
                                const skipped = parseInt(batchData.stats.skipped || 0);
                                
                                totalStats.created += created;
                                totalStats.updated += updated;
                                totalStats.skipped += skipped;
                                
                                console.log(`Worker ${worker_index} Batch ${batchNumber} hoàn thành: +${created} tạo, +${updated} cập nhật, +${skipped} bỏ qua`);
                            }
                            if (batchData.errors && Array.isArray(batchData.errors) && batchData.errors.length > 0) {
                                totalStats.errors += batchData.errors.length;
                                allErrors = allErrors.concat(batchData.errors);
                            }
                        }
                        
                        success = true; // Đánh dấu thành công
                        batchNumber++; // Chuyển sang batch tiếp theo của worker này

                    } catch (error) {
                            // Nếu là timeout và còn retry, thử lại
                            if ((error.name === 'AbortError' || error.message.includes('timeout') || error.message.includes('Timeout')) && retries < MAX_RETRIES) {
                                const waitTime = Math.pow(2, retries) * 1000; // Exponential backoff
                                console.warn(`Worker ${worker_index} Batch ${batchNumber} timeout, retry ${retries + 1}/${MAX_RETRIES} sau ${waitTime}ms...`);
                                await new Promise(resolve => setTimeout(resolve, waitTime));
                                retries++;
                                continue;
                            }
                            
                            // Nếu hết retry hoặc lỗi khác, log và bỏ qua batch này
                            console.error(`Worker ${worker_index} Batch ${batchNumber} lỗi (retry ${retries}/${MAX_RETRIES}):`, error);
                            hasError = true;
                            allErrors.push({
                                row: `Worker ${worker_index} Batch ${batchNumber}`,
                                sku: 'N/A',
                                message: error.message || 'Lỗi không xác định'
                            });
                            totalStats.errors++;
                        success = true; // Đánh dấu để thoát khỏi retry loop
                        batchNumber++; // Chuyển sang batch tiếp theo dù có lỗi
                    }
                }
                
                // Cập nhật progress sau mỗi batch
                completedBatches++;
                updateProgress();
                updateStats();
            }
        };

        // Khởi động 10 workers song song (mỗi worker xử lý một session riêng)
        for (let i = 0; i < sessions.length; i++) {
            activePromises.push(worker(sessions[i]));
        }

        // Đợi tất cả workers hoàn thành
        await Promise.allSettled(activePromises);

        // Hoàn thành
        isProcessing = false;
        progressBar.style.width = '100%';
        progressBar.textContent = '100%';
        progressBar.style.background = hasError ? '#ffc107' : '#28a745';
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-check"></i> Hoàn thành';
        
        if (allErrors.length > 0) {
            errorsContainer.style.display = 'block';
            errorsList.innerHTML = '<ul class="mb-0">' + 
                allErrors.map(err => 
                    `<li><strong>Row ${err.row || 'N/A'}</strong> (SKU: ${err.sku || 'N/A'}): ${err.message || 'Unknown error'}</li>`
                ).join('') + 
                '</ul>';
        }

        // Show success message
        const message = `Import hoàn thành!\nTạo mới: ${totalStats.created}\nCập nhật: ${totalStats.updated}\nBỏ qua: ${totalStats.skipped}\nLỗi: ${totalStats.errors}`;
        alert(message);
    }

    /**
     * Cập nhật progress bar
     */
    function updateProgress() {
        if (totalBatches > 0) {
            const progress = Math.round((completedBatches / totalBatches) * 100);
            progressBar.style.width = progress + '%';
            progressBar.textContent = progress + '%';
        }
    }

    /**
     * Cập nhật thống kê
     */
    function updateStats() {
        statCreated.textContent = totalStats.created;
        statUpdated.textContent = totalStats.updated;
        statSkipped.textContent = totalStats.skipped;
        statErrors.textContent = totalStats.errors;
    }
});
</script>
@endpush
