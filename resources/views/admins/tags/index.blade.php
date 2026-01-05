@extends('admins.layouts.master')

@section('title', 'Quản lý Tags')
@section('page-title', '🏷️ Quản lý Tags')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/tags-icon.png') }}" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slim-select@2.8.2/dist/slimselect.css">
    <style>
        .ss-main {
            width: 300px !important;
            max-width: 300px !important;
        }
    </style>
@endpush

@push('styles')
    <style>
        .tag-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .tag-table th, .tag-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #eef2f7;
            text-align: left;
            font-size: 13px;
        }
        .tag-table th {
            background: #f8fafc;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #475569;
            font-weight: 600;
        }
        .tag-table tr:hover td {
            background: #f1f5f9;
        }
        .filter-bar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 16px;
            padding: 16px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .filter-bar input,
        .filter-bar select {
            padding: 8px 12px;
            border: 1px solid #cbd5f5;
            border-radius: 6px;
            font-size: 13px;
        }
        .badge {
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-success {
            background: #dcfce7;
            color: #15803d;
        }
        .badge-secondary {
            background: #e2e8f0;
            color: #475569;
        }
        .badge-primary {
            background: #dbeafe;
            color: #1e40af;
        }
        .actions {
            display: flex;
            gap: 6px;
        }
        .btn-sm {
            padding: 4px 10px;
            font-size: 12px;
        }
    </style>
@endpush

@section('content')
    <div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h2 style="margin:0;">Danh sách Tags</h2>
            <div style="display:flex;gap:10px;">
                <a href="{{ route('admin.tags.create') }}" class="btn btn-primary">➕ Thêm Tag</a>
            </div>
        </div>

        <form class="filter-bar" method="GET" action="{{ route('admin.tags.index') }}">
            <input type="text" name="keyword" placeholder="Tìm tên, slug..." 
                   value="{{ request('keyword') }}" style="min-width:200px;">
            
            <select name="entity_type" id="filter_entity_type" style="min-width:150px;">
                <option value="">-- Loại entity --</option>
                @foreach($entityTypes as $type => $label)
                    <option value="{{ $type }}" {{ request('entity_type') === $type ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            
            <select name="entity_id" id="filter_entity_id" style="min-width:200px;" {{ !request('entity_type') ? 'disabled' : '' }}>
                <option value="">-- Chọn bài viết/sản phẩm --</option>
                @if(isset($currentEntity) && $currentEntity)
                    <option value="{{ $currentEntity->id }}" selected>
                        {{ $currentEntity->name ?? $currentEntity->title ?? "ID: {$currentEntity->id}" }}
                        @if(isset($currentEntity->sku) && $currentEntity->sku)
                            ({{ $currentEntity->sku }})
                        @endif
                    </option>
                @endif
            </select>
            
            <select name="status" style="min-width:120px;">
                <option value="">-- Trạng thái --</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
            
            <input type="number" name="usage_count_min" placeholder="Usage min" 
                   value="{{ request('usage_count_min') }}" style="width:100px;">
            <input type="number" name="usage_count_max" placeholder="Usage max" 
                   value="{{ request('usage_count_max') }}" style="width:100px;">
            
            <input type="date" name="created_from" value="{{ request('created_from') }}" 
                   placeholder="Từ ngày" style="width:150px;">
            <input type="date" name="created_to" value="{{ request('created_to') }}" 
                   placeholder="Đến ngày" style="width:150px;">
            
            <button type="submit" class="btn btn-primary">🔍 Lọc</button>
            <a href="{{ route('admin.tags.index') }}" class="btn btn-outline-secondary">🔄 Reset</a>
        </form>

        <div class="table-responsive">
            <table class="tag-table">
                <thead>
                <tr>
                    <th style="width:40px;">
                        <input type="checkbox" id="select-all-tags">
                    </th>
                    <th>ID</th>
                    <th>Tên</th>
                    <th>Slug</th>
                    <th>Entity Type</th>
                    <th>Entity</th>
                    <th>Usage</th>
                    <th>Status</th>
                    <th>Ngày tạo</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($tags as $tag)
                    <tr>
                        <td>
                            <input type="checkbox" class="tag-checkbox" value="{{ $tag->id }}">
                        </td>
                        <td>{{ $tag->id }}</td>
                        <td>
                            <strong>{{ $tag->name }}</strong>
                            @if($tag->description)
                                <br><small class="text-muted">{{ Str::limit($tag->description, 50) }}</small>
                            @endif
                        </td>
                        <td>
                            <code style="font-size:11px;">{{ $tag->slug }}</code>
                        </td>
                        <td>
                            <span class="badge badge-primary">{{ $tag->entity_type_label }}</span>
                        </td>
                        <td>
                            @if($tag->entity_name)
                                <a href="{{ $tag->entity_url }}" target="_blank" class="text-primary">
                                    {{ Str::limit($tag->entity_name, 30) }}
                                </a>
                                <br><small class="text-muted">ID: {{ $tag->entity_id }}</small>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $tag->usage_count }}</strong>
                        </td>
                        <td>
                            {!! $tag->status_badge !!}
                        </td>
                        <td>
                            {{ $tag->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('admin.tags.edit', $tag) }}" 
                                   class="btn btn-sm btn-outline-primary" title="Sửa">✏️</a>
                                <form action="{{ route('admin.tags.destroy', $tag) }}" 
                                      method="POST" 
                                      onsubmit="return confirm('Xóa tag này?')" 
                                      style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa">🗑️</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" style="text-align:center;padding:40px;color:#94a3b8;">
                            Không có tag nào
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:16px;display:flex;justify-content:space-between;align-items:center;">
            <div>
                <button type="button" id="bulk-delete-btn" class="btn btn-danger" disabled>
                    🗑️ Xóa đã chọn
                </button>
            </div>
            <div>
                {{ $tags->links() }}
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/slim-select@2.8.2/dist/slimselect.min.js"></script>
    <script>
        // Select all checkbox
        document.getElementById('select-all-tags')?.addEventListener('change', function() {
            document.querySelectorAll('.tag-checkbox').forEach(cb => {
                cb.checked = this.checked;
            });
            updateBulkDeleteButton();
        });

        // Update bulk delete button state
        function updateBulkDeleteButton() {
            const checked = document.querySelectorAll('.tag-checkbox:checked').length;
            const btn = document.getElementById('bulk-delete-btn');
            if (btn) {
                btn.disabled = checked === 0;
            }
        }

        document.querySelectorAll('.tag-checkbox').forEach(cb => {
            cb.addEventListener('change', updateBulkDeleteButton);
        });

        // Bulk delete
        document.getElementById('bulk-delete-btn')?.addEventListener('click', function() {
            const checked = Array.from(document.querySelectorAll('.tag-checkbox:checked'))
                .map(cb => cb.value);

            if (checked.length === 0) {
                alert('Vui lòng chọn ít nhất một tag');
                return;
            }

            if (!confirm(`Xóa ${checked.length} tag đã chọn?`)) {
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("admin.tags.bulk-delete") }}';
            
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            form.appendChild(csrf);

            checked.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        });

        // Entity filter với slimSelect
        const filterEntityType = document.getElementById('filter_entity_type');
        const filterEntityId = document.getElementById('filter_entity_id');
        let entityFilterSlimSelect = null;
        const currentEntityId = {{ request('entity_id') ?? 'null' }};

        if (filterEntityType && filterEntityId) {
            function initEntityFilter() {
                const entityType = filterEntityType.value;
                if (!entityType) {
                    if (entityFilterSlimSelect) {
                        entityFilterSlimSelect.destroy();
                        entityFilterSlimSelect = null;
                    }
                    filterEntityId.disabled = true;
                    filterEntityId.innerHTML = '<option value="">-- Chọn bài viết/sản phẩm --</option>';
                    return;
                }

                // Destroy existing slimSelect nếu có
                if (entityFilterSlimSelect) {
                    entityFilterSlimSelect.destroy();
                }

                // Load 10 entities ban đầu + entity hiện tại nếu có
                filterEntityId.disabled = true;
                filterEntityId.innerHTML = '<option value="">Đang tải...</option>';

                // Load 10 entities ban đầu
                fetch(`{{ route('admin.tags.entities') }}?entity_type=${entityType}&limit=10`)
                    .then(res => res.json())
                    .then(data => {
                        filterEntityId.innerHTML = '<option value="">-- Chọn bài viết/sản phẩm --</option>';
                        
                        // Nếu có entity hiện tại, load nó trước
                        if (currentEntityId) {
                            fetch(`{{ route('admin.tags.entities') }}?entity_type=${entityType}&id=${currentEntityId}`)
                                .then(res => res.json())
                                .then(currentData => {
                                    if (currentData.length > 0) {
                                        const entity = currentData[0];
                                        const option = document.createElement('option');
                                        option.value = entity.id;
                                        const displayText = entity.sku ? `${entity.name} (${entity.sku})` : (entity.name || `ID: ${entity.id}`);
                                        option.textContent = displayText;
                                        option.selected = true;
                                        filterEntityId.appendChild(option);
                                    }
                                    
                                    // Thêm 10 entities ban đầu (tránh trùng với entity hiện tại)
                                    data.forEach(entity => {
                                        if (entity.id != currentEntityId) {
                                            const option = document.createElement('option');
                                            option.value = entity.id;
                                            const displayText = entity.sku ? `${entity.name} (${entity.sku})` : (entity.name || `ID: ${entity.id}`);
                                            option.textContent = displayText;
                                            filterEntityId.appendChild(option);
                                        }
                                    });
                                    
                                    filterEntityId.disabled = false;
                                    initSlimSelect(entityType);
                                })
                                .catch(() => {
                                    // Nếu load entity hiện tại lỗi, vẫn show 10 entities ban đầu
                                    data.forEach(entity => {
                                        const option = document.createElement('option');
                                        option.value = entity.id;
                                        const displayText = entity.sku ? `${entity.name} (${entity.sku})` : (entity.name || `ID: ${entity.id}`);
                                        option.textContent = displayText;
                                        filterEntityId.appendChild(option);
                                    });
                                    filterEntityId.disabled = false;
                                    initSlimSelect(entityType);
                                });
                        } else {
                            // Không có entity hiện tại, chỉ show 10 entities ban đầu
                            data.forEach(entity => {
                                const option = document.createElement('option');
                                option.value = entity.id;
                                const displayText = entity.sku ? `${entity.name} (${entity.sku})` : (entity.name || `ID: ${entity.id}`);
                                option.textContent = displayText;
                                filterEntityId.appendChild(option);
                            });
                            filterEntityId.disabled = false;
                            initSlimSelect(entityType);
                        }
                    })
                    .catch(() => {
                        filterEntityId.innerHTML = '<option value="">Lỗi khi tải dữ liệu</option>';
                        filterEntityId.disabled = true;
                    });
            }

            function initSlimSelect(entityType) {
                // Khởi tạo slimSelect với remote search
                let searchTimeout = null;
                entityFilterSlimSelect = new SlimSelect({
                    select: '#filter_entity_id',
                    placeholder: 'Tìm kiếm bằng tên hoặc mã...',
                    searchText: 'Không tìm thấy',
                    searchPlaceholder: 'Nhập tên hoặc mã để tìm kiếm...',
                    searchFilter: function(option, search) {
                        // Local search trong các options đã load
                        if (!search) return true;
                        const text = option.text.toLowerCase();
                        return text.includes(search.toLowerCase());
                    },
                    ajax: function(search, callback) {
                        if (searchTimeout) {
                            clearTimeout(searchTimeout);
                        }

                        // Nếu không có search hoặc search rỗng, không gọi API
                        if (!search || search.length < 1) {
                            callback([]);
                            return;
                        }

                        searchTimeout = setTimeout(function() {
                            fetch(`{{ route('admin.tags.entities') }}?entity_type=${entityType}&keyword=${encodeURIComponent(search)}&limit=100`)
                                .then(res => res.json())
                                .then(data => {
                                    const options = data.map(entity => ({
                                        value: entity.id.toString(),
                                        text: entity.sku ? `${entity.name} (${entity.sku})` : (entity.name || `ID: ${entity.id}`),
                                    }));
                                    callback(options);
                                })
                                .catch(() => callback([]));
                        }, 400); // Debounce 400ms
                    },
                });
            }

            filterEntityType.addEventListener('change', function() {
                // Reset entity_id khi đổi entity_type
                filterEntityId.value = '';
                initEntityFilter();
            });

            // Load entities nếu đã có entity_type
            if (filterEntityType.value) {
                initEntityFilter();
            }
        }
    </script>
@endpush

