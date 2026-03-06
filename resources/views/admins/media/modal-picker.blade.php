@php($assignTargets = $uploadTargets ?? [])
<div class="modal fade" id="mediaAssignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gán ảnh vào đối tượng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="mediaAssignForm">
                    @csrf
                    <input type="hidden" name="media_id">
                    <input type="hidden" name="source">
                    <div class="mb-3">
                        <label class="form-label">Gán cho</label>
                        <select name="target_type" class="form-select" required>
                            @foreach($assignTargets as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ID đối tượng</label>
                        <input type="number" name="target_id" class="form-control" placeholder="Nhập ID cần gán" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Huỷ</button>
                <button type="button" class="btn btn-primary" id="mediaAssignSubmitBtn">Gán ảnh</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal chọn ảnh kiểu WordPress --}}
<div class="modal fade" id="mediaPickerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thư viện ảnh</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <!-- Context Menu -->
                <div id="mediaPickerContextMenu" class="media-picker-context-menu" style="display: none;">
                    <div class="media-picker-context-menu-item" data-action="preview">
                        <span>👁️ Xem chi tiết</span>
                    </div>
                    <div class="media-picker-context-menu-item" data-action="copy-url">
                        <span>📋 Copy URL</span>
                    </div>
                    <div class="media-picker-context-menu-item" data-action="copy-filename">
                        <span>📝 Copy tên file</span>
                    </div>
                    <div class="media-picker-context-menu-divider"></div>
                    <div class="media-picker-context-menu-item" data-action="delete">
                        <span>🗑️ Xóa ảnh</span>
                    </div>
                </div>
                <div class="media-picker d-flex">
                    <div class="media-picker-main flex-grow-1" id="mediaPickerDropZone" style="position: relative;">
                        {{-- Drag Overlay --}}
                        <div id="mediaPickerDragOverlay" style="
                            display: none;
                            position: absolute;
                            inset: 0;
                            background: rgba(59, 130, 246, 0.12);
                            border: 3px dashed #3b82f6;
                            border-radius: 8px;
                            z-index: 50;
                            align-items: center;
                            justify-content: center;
                            flex-direction: column;
                            gap: 12px;
                            pointer-events: none;
                        ">
                            <div style="font-size: 52px;">📤</div>
                            <div style="font-size: 18px; font-weight: 700; color: #1e40af;">Thả ảnh vào đây để upload</div>
                            <div style="font-size: 13px; color: #3b82f6;" id="mediaPickerDragFolderHint"></div>
                        </div>

                        <div class="p-3 border-bottom">
                            <div class="alert alert-info mb-3" style="padding: 12px; background: #e0f2fe; border: 1px solid #3b82f6; border-radius: 8px;">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <strong style="color: #1e40af;">📂 Chọn thư mục lưu ảnh:</strong>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text" style="background: #fff;">📁</span>
                                    <select class="form-select" id="mediaPickerFolder" style="font-weight: 500;">
                                        <option value="">-- Chọn folder --</option>
                                    </select>
                                </div>
                                <div class="form-text mt-1" style="color: #1e40af; font-size: 12px;">
                                    <strong>Lưu ý:</strong> Ảnh sẽ được lưu vào <code>/clients/assets/img/[folder]</code>. Bạn <strong>PHẢI</strong> chọn folder trước khi upload!
                                </div>
                            </div>
                            <div class="media-picker-toolbar d-flex align-items-center gap-2 flex-wrap">
                            <button class="btn btn-primary btn-sm" id="mediaPickerUploadBtn">📤 Upload</button>
                            <button class="btn btn-outline-secondary btn-sm" id="mediaPickerRefreshBtn">🔄 Reload</button>
                                <button class="btn btn-danger btn-sm d-none" id="mediaPickerBulkDeleteBtn">🗑️ Xóa đã chọn (<span id="mediaPickerSelectedCount">0</span>)</button>
                            <input type="text" class="form-control form-control-sm" style="max-width: 320px;" id="mediaPickerSearch" placeholder="Tìm kiếm theo tên/alt/title...">
                            <div class="ms-auto d-flex align-items-center gap-2">
                                <span class="small text-muted" id="mediaPickerCount"></span>
                                </div>
                            </div>
                        </div>
                        <div class="media-picker-grid p-3" id="mediaPickerGrid">
                            <div class="text-center text-muted py-5">Đang tải...</div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center px-3 pb-3">
                            <button class="btn btn-outline-secondary btn-sm" id="mediaPickerPrevPage">← Trang trước</button>
                            <button class="btn btn-outline-secondary btn-sm" id="mediaPickerNextPage">Trang tiếp →</button>
                        </div>
                    </div>
                    <div class="media-picker-preview border-start" style="width: 360px; min-width: 320px; max-width: 420px;">
                        <div class="p-3" id="mediaPickerPreviewEmpty">
                            <p class="text-muted mb-0">Chọn một ảnh để xem chi tiết</p>
                        </div>
                        <div class="p-3 d-none" id="mediaPickerPreview">
                            <div class="mb-3 text-center">
                                <img id="mediaPickerPreviewImg" src="" alt="" class="img-fluid rounded" style="max-height: 280px; object-fit: contain;">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small mb-1">Tên file</label>
                                <div class="form-control form-control-sm" id="mediaPickerFilename" readonly></div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small mb-1">Title</label>
                                <input type="text" class="form-control form-control-sm" id="mediaPickerTitle">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small mb-1">Alt</label>
                                <input type="text" class="form-control form-control-sm" id="mediaPickerAlt">
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-primary btn-sm flex-grow-1" id="mediaPickerUpdateMeta">Lưu alt/title</button>
                                <button class="btn btn-outline-danger btn-sm" id="mediaPickerDelete">Xoá</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <div class="text-muted small" id="mediaPickerSelectionInfo">Chưa chọn ảnh</div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary" id="mediaPickerUseBtn">Dùng ảnh này</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        (() => {
            const modalEl = document.getElementById('mediaPickerModal');
            if (!modalEl) return;

            const modal = new bootstrap.Modal(modalEl);
            
            // Fix aria-hidden accessibility issue
            modalEl.addEventListener('shown.bs.modal', function() {
                modalEl.removeAttribute('aria-hidden');
            });
            modalEl.addEventListener('hidden.bs.modal', function() {
                modalEl.setAttribute('aria-hidden', 'true');
            });
            
            const gridEl = document.getElementById('mediaPickerGrid');
            const searchEl = document.getElementById('mediaPickerSearch');
            const countEl = document.getElementById('mediaPickerCount');
            const prevBtn = document.getElementById('mediaPickerPrevPage');
            const nextBtn = document.getElementById('mediaPickerNextPage');
            const useBtn = document.getElementById('mediaPickerUseBtn');
            const refreshBtn = document.getElementById('mediaPickerRefreshBtn');
            const uploadBtn = document.getElementById('mediaPickerUploadBtn');
            const previewEmpty = document.getElementById('mediaPickerPreviewEmpty');
            const previewWrap = document.getElementById('mediaPickerPreview');
            const previewImg = document.getElementById('mediaPickerPreviewImg');
            const filenameEl = document.getElementById('mediaPickerFilename');
            const titleEl = document.getElementById('mediaPickerTitle');
            const altEl = document.getElementById('mediaPickerAlt');
            const updateMetaBtn = document.getElementById('mediaPickerUpdateMeta');
            const deleteBtn = document.getElementById('mediaPickerDelete');
            const selectionInfo = document.getElementById('mediaPickerSelectionInfo');
            const contextMenu = document.getElementById('mediaPickerContextMenu');
            const folderSelect = document.getElementById('mediaPickerFolder');
            const bulkDeleteBtn = document.getElementById('mediaPickerBulkDeleteBtn');
            const selectedCountSpan = document.getElementById('mediaPickerSelectedCount');

            const fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.multiple = true;
            fileInput.accept = 'image/*';

            let contextMenuFile = null;

            const state = {
                page: 1,
                perPage: 40,
                search: '',
                total: 0,
                files: [],
                selected: new Set(),
                current: null,
                mode: 'single',
                onSelect: null,
                scope: 'client',
                folder: '', // Folder mặc định
            };

            // Load folder list
            function loadFolders() {
                fetch(`{{ route('admin.media.folder-tree') }}?scope=${state.scope}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.success && data.tree) {
                            const select = folderSelect;
                            select.innerHTML = '<option value="">-- Chọn folder --</option>';
                            function addFolders(folders, prefix = '') {
                                folders.forEach(folder => {
                                    const option = document.createElement('option');
                                    option.value = folder.path;
                                    option.textContent = prefix + folder.name;
                                    select.appendChild(option);
                                    if (folder.children && folder.children.length > 0) {
                                        addFolders(folder.children, prefix + folder.name + ' / ');
                                    }
                                });
                            }
                            addFolders(data.tree);
                            if (state.folder) {
                                select.value = state.folder;
                            }
                        }
                    })
                    .catch(err => console.error('Load folders error:', err));
            }

            window.openMediaPicker = function (options = {}) {
                state.mode = options.mode === 'multiple' ? 'multiple' : 'single';
                state.onSelect = typeof options.onSelect === 'function' ? options.onSelect : null;
                state.scope = options.scope || 'client';
                state.folder = options.folder || ''; // Nhận folder từ options hoặc để trống
                state.selected.clear();
                state.current = null;
                selectionInfo.textContent = 'Chưa chọn ảnh';
                previewEmpty.classList.remove('d-none');
                previewWrap.classList.add('d-none');
                state.page = 1;
                state.search = '';
                searchEl.value = '';
                loadFolders();
                loadPage();
                modal.show();
            };

            function buildParams() {
                const params = new URLSearchParams();
                params.set('scope', state.scope);
                params.set('page', state.page);
                params.set('limit', state.perPage);
                if (state.search) params.set('search', state.search);
                if (state.folder) params.set('folder', state.folder);
                return params.toString();
            }

            function loadPage() {
                gridEl.innerHTML = '<div class="text-center text-muted py-5">Đang tải...</div>';
                
                // Nếu scope='client', dùng API products.media-images (đệ quy toàn bộ img hoặc folder cụ thể)
                // Nếu scope='admin', dùng API media.list (theo folder)
                let apiUrl;
                if (state.scope === 'client') {
                    const params = new URLSearchParams();
                    params.set('offset', (state.page - 1) * state.perPage);
                    params.set('limit', state.perPage);
                    if (state.search) params.set('search', state.search);
                    if (state.folder) params.set('folder', state.folder); // Truyền folder nếu có
                    apiUrl = `{{ route('admin.products.media-images') }}?${params.toString()}`;
                } else {
                    apiUrl = `{{ route('admin.media.list') }}?${buildParams()}`;
                }
                
                fetch(apiUrl)
                    .then(r => r.json())
                    .then(data => {
                        // Xử lý format khác nhau giữa 2 API
                        let files = [];
                        let total = 0;
                        
                        if (state.scope === 'client') {
                            // API products.media-images trả về { data: [...], total: ..., offset: ..., limit: ..., has_more: ... }
                            files = data.data || [];
                            total = data.total || files.length;
                            // Map format để giống với media.list
                            files = files.map(f => ({
                                filename: f.name || f.filename || '',
                                url: f.url || '',
                                path: f.path || f.url || '',
                                relative_path: f.relative_path || f.name || f.filename || '', // Path tương đối từ folder clothes
                                title: f.title || null,
                                alt: f.alt || null,
                                size: f.size || 0,
                                mime_type: f.mime_type || 'image/jpeg',
                            }));
                        } else {
                            // API media.list trả về { files: [...], pagination: { total: ... } }
                            files = data.files || [];
                            total = data.pagination?.total ?? files.length;
                        }
                        
                        state.total = total;
                        // Sort: ảnh mới nhất lên đầu (modified_at DESC)
                        state.files = files.sort((a, b) => {
                            const timeA = new Date(a.modified_at || a.created_at || 0).getTime();
                            const timeB = new Date(b.modified_at || b.created_at || 0).getTime();
                            return timeB - timeA; // Mới nhất lên đầu
                        });
                        renderGrid();
                        updatePager();
                    })
                    .catch((err) => {
                        console.error('Load media error:', err);
                        gridEl.innerHTML = '<div class="text-center text-danger py-4">Không thể tải dữ liệu</div>';
                    });
            }

            function renderGrid() {
                if (!state.files.length) {
                    gridEl.innerHTML = '<div class="text-center text-muted py-5">Không có ảnh</div>';
                    countEl.textContent = '0 ảnh';
                    return;
                }
                const frag = document.createDocumentFragment();
                state.files.forEach(file => {
                    const card = document.createElement('div');
                    card.className = 'media-picker-card';
                    card.style.height = 'fit-content';
                    card.dataset.path = file.path || file.url || file.filename;
                    const url = file.url || '';
                    const sizeText = file.size ? formatSize(file.size) : (file.mime_type || '');
                    const isSelected = state.selected.has(card.dataset.path);
                    card.innerHTML = `
                        <div class="media-picker-checkbox-wrapper" style="position: absolute; top: 8px; left: 8px; z-index: 10;">
                            <input type="checkbox" class="form-check-input media-picker-checkbox" ${isSelected ? 'checked' : ''} data-path="${card.dataset.path}" style="width: 20px; height: 20px; cursor: pointer;">
                        </div>
                        <div class="media-picker-thumb">
                            <img src="${url}" alt="${file.filename || ''}" onerror="this.style.display='none'">
                        </div>
                        <div class="media-picker-meta">
                            <div class="media-picker-name" title="${file.filename || ''}">${file.filename || file.name || ''}</div>
                            <div class="media-picker-size">${sizeText}</div>
                        </div>
                    `;
                    if (isSelected) {
                        card.classList.add('selected');
                    }
                    const checkbox = card.querySelector('.media-picker-checkbox');
                    checkbox.addEventListener('click', (e) => {
                        e.stopPropagation();
                        toggleSelect(card.dataset.path, file);
                    });
                    card.addEventListener('click', (e) => {
                        if (e.target !== checkbox && !checkbox.contains(e.target)) {
                            toggleSelect(card.dataset.path, file);
                        }
                    });
                    // Context menu (right-click)
                    card.addEventListener('contextmenu', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        contextMenuFile = file;
                        showContextMenu(e.clientX, e.clientY);
                    });
                    frag.appendChild(card);
                });
                gridEl.innerHTML = '';
                gridEl.appendChild(frag);
                countEl.textContent = `${state.total} ảnh`;
            }

            function toggleSelect(path, file) {
                if (state.mode === 'single') {
                    state.selected.clear();
                    state.selected.add(path);
                } else {
                    if (state.selected.has(path)) {
                        state.selected.delete(path);
                    } else {
                        state.selected.add(path);
                    }
                }
                state.current = file;
                renderGrid();
                updatePreview(file);
                const selectedCount = state.selected.size;
                selectionInfo.textContent = selectedCount > 0 ? `${selectedCount} ảnh đã chọn` : 'Chưa chọn ảnh';
                if (selectedCountSpan) {
                    selectedCountSpan.textContent = selectedCount;
                }
                if (bulkDeleteBtn) {
                    bulkDeleteBtn.classList.toggle('d-none', selectedCount === 0);
                }
            }

            function updatePreview(file) {
                if (!file) {
                    previewEmpty.classList.remove('d-none');
                    previewWrap.classList.add('d-none');
                    return;
                }
                previewEmpty.classList.add('d-none');
                previewWrap.classList.remove('d-none');
                const url = file.url || '';
                previewImg.src = url;
                previewImg.alt = file.alt || file.title || file.filename || '';
                filenameEl.textContent = file.filename || file.name || '';
                titleEl.value = file.title || '';
                altEl.value = file.alt || '';
            }

            function updatePager() {
                const totalPages = Math.max(1, Math.ceil(state.total / state.perPage));
                prevBtn.disabled = state.page <= 1;
                nextBtn.disabled = state.page >= totalPages;
            }

            function formatSize(bytes) {
                if (!bytes) return '';
                const k = 1024;
                const sizes = ['B', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return `${(bytes / Math.pow(k, i)).toFixed(1)} ${sizes[i]}`;
            }

            prevBtn.addEventListener('click', () => {
                if (state.page > 1) {
                    state.page -= 1;
                    loadPage();
                }
            });
            nextBtn.addEventListener('click', () => {
                const totalPages = Math.max(1, Math.ceil(state.total / state.perPage));
                if (state.page < totalPages) {
                    state.page += 1;
                    loadPage();
                }
            });
            refreshBtn.addEventListener('click', () => {
                loadPage();
            });
            let searchTimer;
            searchEl.addEventListener('input', (e) => {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => {
                    state.search = e.target.value.trim();
                    state.page = 1;
                    loadPage();
                }, 250);
            });

            uploadBtn.addEventListener('click', () => {
                const currentFolder = (folderSelect?.value || '').trim();
                if (!currentFolder) {
                    showCustomToast('Vui lòng chọn folder trước khi upload.', 'error');
                    if (folderSelect) {
                        folderSelect.focus();
                    }
                    return;
                }
                fileInput.click();
            });
            fileInput.addEventListener('change', () => {
                if (!fileInput.files?.length) return;
                const currentFolder = (folderSelect?.value || '').trim();
                if (!currentFolder) {
                    showCustomToast('Vui lòng chọn folder trước khi upload.', 'error');
                    fileInput.value = '';
                    if (folderSelect) {
                        folderSelect.focus();
                    }
                    return;
                }
                const formData = new FormData();
                formData.append('scope', state.scope);
                formData.append('folder', currentFolder);
                Array.from(fileInput.files).forEach(f => formData.append('files[]', f));
                console.log('[DEBUG] Upload request:', {
                    scope: state.scope,
                    folder: currentFolder,
                    files: Array.from(fileInput.files).map(f => f.name)
                });
                gridEl.innerHTML = '<div class="text-center text-muted py-5">Đang upload...</div>';
                fetch(`{{ route('admin.media.upload') }}`, {
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
                    body: formData
                }).then(async r => {
                    const rawText = await r.text();
                    console.log('[DEBUG] Upload HTTP status:', r.status, r.statusText);
                    console.log('[DEBUG] Upload response raw:', rawText);
                    if (!r.ok) {
                        showCustomToast('[DEBUG] Upload lỗi HTTP ' + r.status + ': ' + rawText.substring(0, 300), 'error');
                        gridEl.innerHTML = '<div class="text-center text-danger py-4">Upload thất bại: HTTP ' + r.status + '</div>';
                        return;
                    }
                    let data;
                    try {
                        data = JSON.parse(rawText);
                    } catch(e) {
                        showCustomToast('[DEBUG] Upload response không phải JSON: ' + rawText.substring(0, 300), 'error');
                        return;
                    }
                    console.log('[DEBUG] Upload response data:', data);
                    if (!data.success) {
                        showCustomToast('[DEBUG] Upload thất bại: ' + (data.error || data.message || JSON.stringify(data)), 'error');
                    }
                    fileInput.value = '';
                    state.folder = currentFolder;
                    loadPage();
                })
                    .catch(err => {
                        console.error('[DEBUG] Upload network error:', err);
                        showCustomToast('[DEBUG] Upload lỗi mạng: ' + err.message, 'error');
                        fileInput.value = '';
                        gridEl.innerHTML = '<div class="text-center text-danger py-4">Upload thất bại: ' + err.message + '</div>';
                    });
            });

            // Xử lý thay đổi folder
            if (folderSelect) {
                folderSelect.addEventListener('change', () => {
                    state.folder = (folderSelect.value || '').trim();
                    state.page = 1;
                    loadPage();
                });
            }

            // ======== DRAG & DROP UPLOAD ========
            const dropZone = document.getElementById('mediaPickerDropZone');
            const dragOverlay = document.getElementById('mediaPickerDragOverlay');
            const dragFolderHint = document.getElementById('mediaPickerDragFolderHint');
            let dragCounter = 0; // Đếm số lần dragenter để tránh flicker khi di chuyển qua con

            function showDragOverlay() {
                const currentFolder = (folderSelect?.value || '').trim();
                if (currentFolder) {
                    dragFolderHint.textContent = `📁 Folder: ${currentFolder}`;
                } else {
                    dragFolderHint.textContent = '⚠️ Vui lòng chọn folder trước khi thả';
                }
                dragOverlay.style.display = 'flex';
            }

            function hideDragOverlay() {
                dragOverlay.style.display = 'none';
            }

            async function handleDropUpload(files) {
                const currentFolder = (folderSelect?.value || '').trim();
                if (!currentFolder) {
                    // Highlight select box để người dùng chọn folder
                    if (folderSelect) {
                        folderSelect.style.boxShadow = '0 0 0 3px rgba(239,68,68,0.4)';
                        folderSelect.style.borderColor = '#ef4444';
                        folderSelect.focus();
                        setTimeout(() => {
                            folderSelect.style.boxShadow = '';
                            folderSelect.style.borderColor = '';
                        }, 2500);
                    }
                    showCustomToast('Vui lòng chọn folder trước khi upload ảnh.', 'error');
                    return;
                }
                const imageFiles = Array.from(files).filter(f => f.type.startsWith('image/'));
                if (!imageFiles.length) {
                    showCustomToast('Chỉ hỗ trợ upload file ảnh (image/*).', 'error');
                    return;
                }
                const formData = new FormData();
                formData.append('scope', state.scope);
                formData.append('folder', currentFolder);
                imageFiles.forEach(f => formData.append('files[]', f));
                console.log('[DEBUG] Drop upload:', { folder: currentFolder, files: imageFiles.map(f => f.name) });
                gridEl.innerHTML = `<div class="text-center text-muted py-5">⏳ Đang upload ${imageFiles.length} ảnh...</div>`;
                try {
                    const response = await fetch(`{{ route('admin.media.upload') }}`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: formData
                    });
                    const rawText = await response.text();
                    if (!response.ok) {
                        showCustomToast('Upload lỗi HTTP ' + response.status + ': ' + rawText.substring(0, 200), 'error');
                        loadPage();
                        return;
                    }
                    let data;
                    try { data = JSON.parse(rawText); } catch(e) {
                        showCustomToast('Upload response không phải JSON: ' + rawText.substring(0, 200), 'error');
                        loadPage();
                        return;
                    }
                    if (data.success) {
                        const successCount = data.files ? data.files.filter(f => f.success).length : imageFiles.length;
                        console.log('[DEBUG] Drop upload thành công:', successCount + '/' + imageFiles.length);
                    } else {
                        const errMsg = data.files ? data.files.filter(f => !f.success).map(f => f.error).join(', ') : (data.error || 'Unknown');
                        showCustomToast('Một số ảnh upload thất bại: ' + errMsg, 'error');
                    }
                    state.folder = currentFolder;
                    loadPage();
                } catch (err) {
                    console.error('[DEBUG] Drop upload error:', err);
                    showCustomToast('Upload lỗi mạng: ' + err.message, 'error');
                    loadPage();
                }
            }

            if (dropZone) {
                dropZone.addEventListener('dragenter', (e) => {
                    e.preventDefault();
                    dragCounter++;
                    if (dragCounter === 1) showDragOverlay();
                });
                dropZone.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'copy';
                });
                dropZone.addEventListener('dragleave', (e) => {
                    dragCounter--;
                    if (dragCounter === 0) hideDragOverlay();
                });
                dropZone.addEventListener('drop', (e) => {
                    e.preventDefault();
                    dragCounter = 0;
                    hideDragOverlay();
                    handleDropUpload(e.dataTransfer.files);
                });
            }

            // Reset drag counter khi modal đóng
            modalEl.addEventListener('hidden.bs.modal', () => {
                dragCounter = 0;
                hideDragOverlay();
            });
            // ======== /DRAG & DROP UPLOAD ========

            // Xử lý xóa hàng loạt
            if (bulkDeleteBtn) {
                bulkDeleteBtn.addEventListener('click', () => {
                    const selectedPaths = Array.from(state.selected);
                    if (selectedPaths.length === 0) {
                        showCustomToast('Chưa chọn ảnh nào để xóa.', 'error');
                        return;
                    }
                    if (!confirm(`Bạn có chắc muốn xóa ${selectedPaths.length} ảnh đã chọn?`)) {
                        return;
                    }
                    bulkDeleteBtn.disabled = true;
                    bulkDeleteBtn.textContent = 'Đang xóa...';
                    fetch(`{{ route('admin.media.bulk-delete') }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            paths: selectedPaths,
                            scope: state.scope
                        })
                    })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                state.selected.clear();
                                state.current = null;
                                selectionInfo.textContent = 'Chưa chọn ảnh';
                                previewEmpty.classList.remove('d-none');
                                previewWrap.classList.add('d-none');
                                if (selectedCountSpan) {
                                    selectedCountSpan.textContent = '0';
                                }
                                bulkDeleteBtn.classList.add('d-none');
                                loadPage();
                            } else {
                                showCustomToast('Xóa thất bại: ' + (data.error || 'Unknown error'), 'error');
                            }
                        })
                        .catch(err => {
                            console.error('Bulk delete error:', err);
                            showCustomToast('Xóa thất bại. Vui lòng thử lại.', 'error');
                        })
                        .finally(() => {
                            bulkDeleteBtn.disabled = false;
                            bulkDeleteBtn.innerHTML = '🗑️ Xóa đã chọn (<span id="mediaPickerSelectedCount">0</span>)';
                            selectedCountSpan = document.getElementById('mediaPickerSelectedCount');
                        });
                });
            }

            useBtn.addEventListener('click', () => {
                if (!state.selected.size || !state.onSelect) return;
                const selectedFiles = state.files.filter(f => state.selected.has(f.path || f.url || f.filename));
                const payload = selectedFiles.map(f => ({
                    url: f.url,
                    filename: f.filename || f.name,
                    alt: f.alt || '',
                    title: f.title || '',
                    path: f.path || '',
                    relative_path: f.relative_path || f.filename || f.name || '', // Path tương đối từ folder clothes
                }));
                state.onSelect(state.mode === 'single' ? payload[0] : payload);
                modal.hide();
            });

            updateMetaBtn.addEventListener('click', () => {
                if (!state.current) {
                    showCustomToast('[DEBUG] Chưa chọn ảnh (state.current = null)', 'error');
                    return;
                }
                const body = {
                    path: state.current.path || state.current.url || '',
                    alt: altEl.value || '',
                    title: titleEl.value || '',
                    scope: state.scope
                };
                console.log('[DEBUG] updateMeta request body:', body);
                const btnText = updateMetaBtn.textContent;
                updateMetaBtn.disabled = true;
                updateMetaBtn.textContent = 'Đang lưu...';
                fetch(`{{ route('admin.media.update-meta') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(body)
                }).then(async r => {
                    const rawText = await r.text();
                    console.log('[DEBUG] updateMeta HTTP status:', r.status, r.statusText);
                    console.log('[DEBUG] updateMeta response raw:', rawText);
                    if (!r.ok) {
                        showCustomToast('[DEBUG] updateMeta lỗi HTTP ' + r.status + ': ' + rawText.substring(0, 300), 'error');
                        return;
                    }
                    let data;
                    try {
                        data = JSON.parse(rawText);
                    } catch(e) {
                        showCustomToast('[DEBUG] updateMeta response không phải JSON: ' + rawText.substring(0, 300), 'error');
                        return;
                    }
                    console.log('[DEBUG] updateMeta response data:', data);
                    if (data.success && data.data) {
                        // Cập nhật state.current với data mới từ database
                        if (state.current) {
                            state.current.alt = data.data.alt || '';
                            state.current.title = data.data.title || '';
                        }
                        // Cập nhật preview với data mới
                        updatePreview(state.current);
                        showCustomToast('✅ Lưu thành công! alt="' + data.data.alt + '", title="' + data.data.title + '"', 'success');
                        // Reload grid để hiển thị alt/title mới
                        loadPage();
                    } else {
                        showCustomToast('❌ Lưu thất bại: ' + JSON.stringify(data), 'error');
                    }
                }).catch(err => {
                    console.error('[DEBUG] updateMeta network error:', err);
                    showCustomToast('❌ Lỗi mạng khi lưu alt/title: ' + err.message, 'error');
                }).finally(() => {
                    updateMetaBtn.disabled = false;
                    updateMetaBtn.textContent = btnText;
                });
            });

            function deleteFile(file) {
                const pathToDelete = file.path || file.url || '';
                if (!pathToDelete) return;
                if (!confirm('Bạn có chắc muốn xoá ảnh này?')) return;
                
                fetch(`{{ route('admin.media.delete') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        path: pathToDelete,
                        scope: state.scope
                    })
                }).then(r => r.json()).then(() => {
                    state.selected.delete(pathToDelete);
                    if (state.current && (state.current.path === pathToDelete || state.current.url === pathToDelete)) {
                    state.current = null;
                        previewEmpty.classList.remove('d-none');
                        previewWrap.classList.add('d-none');
                    }
                    const selectedCount = state.selected.size;
                    selectionInfo.textContent = selectedCount > 0 ? `${selectedCount} ảnh đã chọn` : 'Chưa chọn ảnh';
                    if (selectedCountSpan) {
                        selectedCountSpan.textContent = selectedCount;
                    }
                    if (bulkDeleteBtn) {
                        bulkDeleteBtn.classList.toggle('d-none', selectedCount === 0);
                    }
                    loadPage();
                });
            }

            deleteBtn.addEventListener('click', () => {
                if (!state.current) return;
                deleteFile(state.current);
            });

            // Context menu functions
            function showContextMenu(x, y) {
                if (!contextMenu || !contextMenuFile) return;
                contextMenu.style.display = 'block';
                contextMenu.style.left = x + 'px';
                contextMenu.style.top = y + 'px';
                
                // Đảm bảo menu không bị tràn ra ngoài màn hình
                setTimeout(() => {
                    const rect = contextMenu.getBoundingClientRect();
                    if (rect.right > window.innerWidth) {
                        contextMenu.style.left = (x - rect.width) + 'px';
                    }
                    if (rect.bottom > window.innerHeight) {
                        contextMenu.style.top = (y - rect.height) + 'px';
                    }
                }, 0);
            }

            function hideContextMenu() {
                if (contextMenu) {
                    contextMenu.style.display = 'none';
                }
                contextMenuFile = null;
            }

            function copyToClipboard(text) {
                navigator.clipboard.writeText(text).then(() => {
                    showCustomToast('Đã copy: ' + text, 'success');
                }).catch(() => {
                    // Fallback cho trình duyệt cũ
                    const textarea = document.createElement('textarea');
                    textarea.value = text;
                    textarea.style.position = 'fixed';
                    textarea.style.opacity = '0';
                    document.body.appendChild(textarea);
                    textarea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textarea);
                    showCustomToast('Đã copy: ' + text, 'success');
                });
            }

            // Context menu event listeners
            if (contextMenu) {
                contextMenu.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const action = e.target.closest('.media-picker-context-menu-item')?.dataset.action;
                    if (!action || !contextMenuFile) return;

                    switch (action) {
                        case 'preview':
                            toggleSelect(contextMenuFile.path || contextMenuFile.url || contextMenuFile.filename, contextMenuFile);
                            hideContextMenu();
                            break;
                        case 'copy-url':
                            copyToClipboard(contextMenuFile.url || '');
                            hideContextMenu();
                            break;
                        case 'copy-filename':
                            copyToClipboard(contextMenuFile.filename || contextMenuFile.name || '');
                            hideContextMenu();
                            break;
                        case 'delete':
                            deleteFile(contextMenuFile);
                            hideContextMenu();
                            break;
                    }
                });

                // Đóng menu khi click ra ngoài
                document.addEventListener('click', () => {
                    hideContextMenu();
                });
                document.addEventListener('contextmenu', (e) => {
                    if (!contextMenu.contains(e.target)) {
                        hideContextMenu();
                    }
                });
            }
        })();
    </script>
@endpush
