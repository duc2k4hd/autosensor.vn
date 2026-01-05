@extends('admins.layouts.master')

@section('title', 'Chỉnh sửa Tag')
@section('page-title', '✏️ Chỉnh sửa Tag')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/tags-icon.png') }}" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slim-select@2.8.2/dist/slimselect.css">
@endpush

@section('content')
    <div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h2 style="margin:0;">Chỉnh sửa Tag: {{ $tag->name }}</h2>
            <a href="{{ route('admin.tags.index') }}" class="btn btn-outline-secondary">← Quay lại</a>
        </div>

        <form action="{{ route('admin.tags.update', $tag) }}" method="POST">
            @csrf
            @method('PUT')
            @include('admins.tags.partials.form', [
                'tag' => $tag,
                'entityTypes' => $entityTypes,
                'entities' => $entities ?? collect(),
            ])
            <div style="margin-top:20px;display:flex;gap:10px;">
                <button type="submit" class="btn btn-primary">💾 Cập nhật</button>
                <a href="{{ route('admin.tags.index') }}" class="btn btn-outline-secondary">Hủy</a>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/slim-select@2.8.2/dist/slimselect.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto generate slug from name
            const nameInput = document.querySelector('input[name="name"]');
            const slugInput = document.querySelector('input[name="slug"]');
            
            if (nameInput && slugInput) {
                nameInput.addEventListener('blur', function() {
                    if (!slugInput.value || slugInput.value === '{{ $tag->slug }}') {
                        const slug = this.value.toLowerCase()
                            .normalize('NFD')
                            .replace(/[\u0300-\u036f]/g, '')
                            .replace(/[^a-z0-9]+/g, '-')
                            .replace(/^-+|-+$/g, '');
                        slugInput.value = slug;
                    }
                });
            }

            // Entity type select
            const entityTypeSelect = document.querySelector('select[name="entity_type"]');
            if (entityTypeSelect) {
                new TomSelect(entityTypeSelect, {
                    placeholder: 'Chọn loại entity...',
                    allowEmptyOption: false,
                    create: false,
                });
            }

            // Entity select với slimSelect - tìm kiếm remote
            const entityIdSelect = document.getElementById('entity_id');
            const entityTypeSelectEl = document.querySelector('select[name="entity_type"]');
            let entitySlimSelect = null;
            const currentEntityId = {{ $tag->entity_id ?? 'null' }};
            
            if (entityIdSelect && entityTypeSelectEl) {
                function initEntitySelect() {
                    const entityType = entityTypeSelectEl.value;
                    if (!entityType) {
                        if (entitySlimSelect) {
                            entitySlimSelect.destroy();
                            entitySlimSelect = null;
                        }
                        entityIdSelect.disabled = true;
                        entityIdSelect.innerHTML = '<option value="">-- Chọn entity --</option>';
                        return;
                    }

                    // Destroy existing slimSelect nếu có
                    if (entitySlimSelect) {
                        entitySlimSelect.destroy();
                    }

                    // Load initial data
                    entityIdSelect.disabled = true;
                    entityIdSelect.innerHTML = '<option value="">Đang tải...</option>';

                    fetch(`{{ route('admin.tags.entities') }}?entity_type=${entityType}`)
                        .then(res => res.json())
                        .then(data => {
                            entityIdSelect.innerHTML = '<option value="">-- Chọn entity --</option>';
                            data.forEach(entity => {
                                const option = document.createElement('option');
                                option.value = entity.id;
                                const displayText = entity.sku ? `${entity.name} (${entity.sku})` : (entity.name || `ID: ${entity.id}`);
                                option.textContent = displayText;
                                if (entity.id == currentEntityId) {
                                    option.selected = true;
                                }
                                entityIdSelect.appendChild(option);
                            });
                            entityIdSelect.disabled = false;

                            // Khởi tạo slimSelect với remote search
                            let searchTimeout = null;
                            entitySlimSelect = new SlimSelect({
                                select: '#entity_id',
                                placeholder: 'Tìm kiếm bằng tên hoặc mã sản phẩm...',
                                searchText: 'Không tìm thấy',
                                searchPlaceholder: 'Nhập tên hoặc mã để tìm kiếm...',
                                searchFilter: function(option, search) {
                                    // Local search trong các options đã load
                                    if (!search) return true;
                                    const text = option.text.toLowerCase();
                                    return text.includes(search.toLowerCase());
                                },
                                ajax: function(search, callback) {
                                    // Clear previous timeout
                                    if (searchTimeout) {
                                        clearTimeout(searchTimeout);
                                    }

                                    // Debounce search để tránh quá nhiều requests
                                    searchTimeout = setTimeout(function() {
                                        // Remote search khi user nhập
                                        if (search.length < 1) {
                                            callback([]);
                                            return;
                                        }

                                        fetch(`{{ route('admin.tags.entities') }}?entity_type=${entityType}&keyword=${encodeURIComponent(search)}`)
                                            .then(res => res.json())
                                            .then(data => {
                                                const options = data.map(entity => ({
                                                    value: entity.id.toString(),
                                                    text: entity.sku ? `${entity.name} (${entity.sku})` : (entity.name || `ID: ${entity.id}`),
                                                }));
                                                callback(options);
                                            })
                                            .catch(() => callback([]));
                                    }, 300); // Debounce 300ms
                                },
                            });
                        })
                        .catch(() => {
                            entityIdSelect.innerHTML = '<option value="">Lỗi khi tải dữ liệu</option>';
                            entityIdSelect.disabled = true;
                        });
                }

                entityTypeSelectEl.addEventListener('change', initEntitySelect);
                
                // Load entities khi trang load (nếu đã có entity_type)
                if (entityTypeSelectEl.value) {
                    initEntitySelect();
                }
            }
        });
    </script>
@endpush

