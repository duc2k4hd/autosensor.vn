@extends('admins.layouts.master')

@section('title', 'Import Bài viết từ Excel (Batch Mode)')
@section('page-title', '📥 Import Bài viết quy mô lớn')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/imports-excel.png') }}" type="image/x-icon">
    <script src="https://cdn.sheetjs.com/xlsx-0.20.2/package/dist/xlsx.full.min.js"></script>
@endpush

@push('styles')
    <style>
        .container { max-width: 900px; margin: 0 auto; background: white; border-radius:8px; padding:30px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
        .subtitle { color:#64748b; margin-bottom:24px; }
        .drop-zone { border: 2px dashed #e2e8f0; border-radius: 12px; padding: 40px; text-align: center; background: #f8fafc; transition: all 0.3s; cursor: pointer; }
        .drop-zone:hover { border-color: #3b82f6; background: #eff6ff; }
        .drop-zone.active { border-color: #3b82f6; background: #eff6ff; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
        .btn { padding:10px 18px; border-radius:8px; font-weight:600; display: inline-flex; align-items:center; gap:8px; transition: all 0.2s; }
        .btn-primary { background: #2563eb; color: white; border: none; }
        .btn-primary:hover { background: #1d4ed8; transform: translateY(-1px); }
        .btn-secondary { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .btn-secondary:hover { background: #e2e8f0; }
        .info-box { background:#f0f9ff; border-left:4px solid #0ea5e9; padding:16px; border-radius:8px; margin-bottom: 24px; }
        
        /* Progress Styles */
        #import-progress-container { display: none; margin-top: 30px; border-top: 1px solid #f1f5f9; pt: 30px; }
        .progress-wrapper { height: 12px; background: #f1f5f9; border-radius: 10px; overflow: hidden; margin-bottom: 8px; }
        .progress-bar { height: 100%; background: linear-gradient(90deg, #3b82f6, #2563eb); width: 0%; transition: width 0.3s ease; }
        .status-text { font-size: 14px; color: #64748b; margin-bottom: 20px; display: flex; justify-content: space-between; }
        #import-log { height: 250px; overflow-y: auto; background: #1e293b; color: #cbd5e1; padding: 15px; border-radius: 8px; font-family: 'Fira Code', monospace; font-size: 12px; line-height: 1.6; }
        .log-error { color: #f87171; }
        .log-success { color: #4ade80; }
        .log-warning { color: #fbbf24; }
    </style>
@endpush

@section('content')
    <div class="container">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 24px;">
            <div>
                <h1 style="font-size: 24px; font-weight: 700; color: #1e293b; margin-bottom: 4px;">📥 Import Bài viết quy mô lớn</h1>
                <p class="subtitle">Sử dụng Batch Processing để xử lý hàng ngàn bài viết mà không bị timeout.</p>
            </div>
            <div style="display:flex; gap:10px;">
                <a href="{{ route('admin.posts.index') }}" class="btn btn-secondary">↩️ Quay lại</a>
                <a href="{{ route('admin.posts.export-template') }}" class="btn btn-primary">⬇️ File mẫu</a>
                <a href="{{ route('admin.posts.export') }}" class="btn btn-primary">⬇️ Export tất cả</a>
            </div>
        </div>

        <div class="info-box">
            <strong style="color: #0369a1;">💡 Hướng dẫn:</strong>
            <ul style="margin: 8px 0 0 20px; color: #475569; font-size: 14px;">
                <li>Hệ thống sẽ chia nhỏ dữ liệu thành các đợt (50 dòng/đợt) để xử lý.</li>
                <li>Bạn có thể import tệp tin lên đến 10,000 dòng.</li>
                <li>Vui lòng không đóng trình duyệt cho đến khi quá trình hoàn tất.</li>
            </ul>
        </div>

        <div id="upload-section">
            <div class="drop-zone" id="drop-zone">
                <div style="font-size: 40px; margin-bottom: 12px;">📄</div>
                <h3 style="font-size: 18px; font-weight: 600; color: #334155;">Kéo thả file Excel vào đây</h3>
                <p style="color: #94a3b8; font-size: 14px; margin-bottom: 20px;">Hoặc click để chọn tệp tin từ máy tính (.xlsx, .xls)</p>
                <input type="file" id="excel_file_input" accept=".xlsx,.xls" style="display: none;">
                <button type="button" class="btn btn-primary" onclick="document.getElementById('excel_file_input').click()">Chọn File</button>
            </div>
        </div>

        <div id="import-progress-container">
            <div class="status-text">
                <span id="overall-status">Đang chuẩn bị dữ liệu...</span>
                <span id="progress-percent">0%</span>
            </div>
            <div class="progress-wrapper">
                <div class="progress-bar" id="progress-bar"></div>
            </div>
            <div style="display: flex; gap: 20px; font-size: 13px; margin: 15px 0; color: #475569; font-weight: 500;">
                <span>✅ Thành công: <span id="stats-success" style="color: #16a34a;">0</span></span>
                <span>⚠️ Lỗi: <span id="stats-error" style="color: #dc2626;">0</span></span>
                <span>📦 Đã xử lý: <span id="stats-processed">0</span>/<span id="stats-total">0</span></span>
            </div>
            <div id="import-log"></div>
            
            <div id="completion-actions" style="display: none; margin-top: 20px; text-align: center;">
                <button class="btn btn-primary" onclick="location.reload()">🔄 Import file mới</button>
                <a href="{{ route('admin.posts.index') }}" class="btn btn-secondary">📄 Xem danh sách bài viết</a>
            </div>
        </div>
    </div>

    <script>
        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('excel_file_input');
        const progressContainer = document.getElementById('import-progress-container');
        const uploadSection = document.getElementById('upload-section');
        const logContainer = document.getElementById('import-log');
        const progressBar = document.getElementById('progress-bar');
        const progressPercent = document.getElementById('progress-percent');
        const overallStatus = document.getElementById('overall-status');

        const BATCH_SIZE = 50;
        let isImporting = false;

        // AJAX CSRF Token
        const csrfToken = '{{ csrf_token() }}';

        // Stats
        let stats = {
            total: 0,
            processed: 0,
            success: 0,
            error: 0,
            created: 0,
            updated: 0,
            skipped: 0
        };

        // File Selection
        dropZone.addEventListener('click', () => fileInput.click());
        
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('active');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('active');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('active');
            if (e.dataTransfer.files.length) {
                handleFile(e.dataTransfer.files[0]);
            }
        });

        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length) {
                handleFile(e.target.files[0]);
            }
        });

        function addLog(message, type = '') {
            const time = new Date().toLocaleTimeString();
            const div = document.createElement('div');
            div.className = type ? `log-${type}` : '';
            div.innerHTML = `<span style="color: #64748b;">[${time}]</span> ${message}`;
            logContainer.appendChild(div);
            logContainer.scrollTop = logContainer.scrollHeight;
        }

        function updateStats() {
            document.getElementById('stats-success').textContent = stats.success;
            document.getElementById('stats-error').textContent = stats.error;
            document.getElementById('stats-processed').textContent = stats.processed;
            document.getElementById('stats-total').textContent = stats.total;

            const percent = stats.total > 0 ? Math.round((stats.processed / stats.total) * 100) : 0;
            progressBar.style.width = percent + '%';
            progressPercent.textContent = percent + '%';
        }

        function normalizeRows(rows) {
            return rows.map(row => {
                const newRow = {};
                for (let key in row) {
                    // Chuyển key về lowercase, bỏ khoảng trắng và chuẩn hóa
                    const normalizedKey = key.toLowerCase().trim().replace(/\s+/g, '_');
                    newRow[normalizedKey] = row[key];
                }
                return newRow;
            });
        }

        function handleFile(file) {
            if (isImporting) return;
            
            const reader = new FileReader();
            reader.onload = (e) => {
                try {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, { type: 'array' });
                    const firstSheetName = workbook.SheetNames[0];
                    const worksheet = workbook.Sheets[firstSheetName];
                    
                    // Convert to JSON
                    let rows = XLSX.utils.sheet_to_json(worksheet);
                    
                    if (rows.length === 0) {
                        alert('File Excel không có dữ liệu.');
                        return;
                    }

                    // Chuẩn hóa keys trước khi gửi
                    rows = normalizeRows(rows);

                    startImport(rows);
                } catch (error) {
                    console.error(error);
                    alert('Lỗi khi đọc file Excel: ' + error.message);
                }
            };
            reader.readAsArrayBuffer(file);
        }

        async function startImport(rows) {
            isImporting = true;
            stats.total = rows.length;
            stats.processed = 0;
            stats.success = 0;
            stats.error = 0;
            stats.skipped = 0;
            stats.created = 0;
            stats.updated = 0;

            uploadSection.style.display = 'none';
            progressContainer.style.display = 'block';
            
            addLog(`Bắt đầu xử lý tệp tin: ${stats.total} dòng được tìm thấy.`, 'info');

            // Split into batches
            const batches = [];
            for (let i = 0; i < rows.length; i += BATCH_SIZE) {
                batches.push(rows.slice(i, i + BATCH_SIZE));
            }

            for (let i = 0; i < batches.length; i++) {
                overallStatus.textContent = `Đang xử lý đợt ${i + 1}/${batches.length}...`;
                
                try {
                    const result = await sendBatch(batches[i], i + 1);
                    if (result.success) {
                        stats.processed += batches[i].length;
                        stats.created += (result.created || 0);
                        stats.updated += (result.updated || 0);
                        stats.skipped += (result.skipped || 0);
                        stats.success = stats.created + stats.updated;
                        
                        // Add error counts from result
                        if (result.errors && result.errors.length > 0) {
                            stats.error += result.errors.length;
                            result.errors.forEach(err => addLog(`Batch ${i+1}: ${err}`, 'error'));
                        }

                        addLog(`Đợt ${i + 1}: +${result.created || 0} mới, +${result.updated || 0} cập nhật, ${result.skipped || 0} bỏ qua.`, 'success');
                    } else {
                        throw new Error(result.message || 'Lỗi không xác định từ server');
                    }
                } catch (error) {
                    stats.error += batches[i].length;
                    stats.processed += batches[i].length;
                    addLog(`Lỗi tại đợt ${i + 1}: ${error.message}`, 'error');
                }
                
                updateStats();
            }

            finishImport();
        }

        async function sendBatch(batchRows, batchIndex) {
            const response = await fetch('{{ route("admin.posts.import.batch") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ rows: batchRows })
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
            }

            return await response.json();
        }

        function finishImport() {
            isImporting = false;
            overallStatus.textContent = 'Import hoàn tất!';
            addLog('--- QUÁ TRÌNH IMPORT KẾT THÚC ---', 'info');
            addLog(`Tổng cộng xử lý: ${stats.processed} dòng.`, 'success');
            addLog(`- Tạo mới: ${stats.created}`, 'success');
            addLog(`- Cập nhật: ${stats.updated}`, 'success');
            addLog(`- Bỏ qua: ${stats.skipped}`, 'warning');
            addLog(`- Lỗi: ${stats.error}`, stats.error > 0 ? 'error' : 'success');

            document.getElementById('completion-actions').style.display = 'block';
        }
    </script>
@endsection
