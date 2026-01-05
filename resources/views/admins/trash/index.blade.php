@extends('admins.layouts.master')

@section('title', 'Thùng rác hệ thống')
@section('page-title', '🗑️ Thùng rác')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/trash-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .trash-container {
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 24px;
        }
        .trash-sidebar {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.05);
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        .trash-sidebar h4 {
            margin-bottom: 16px;
            font-size: 15px;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .trash-type-btn {
            width: 100%;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 16px;
            background: #fff;
            color: #0f172a;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .trash-type-btn:hover {
            border-color: #4f46e5;
            background: #f8fafc;
        }
        .trash-type-btn.active {
            border-color: #4f46e5;
            background: #eef2ff;
            color: #312e81;
            font-weight: 600;
        }
        .trash-type-btn span:first-child {
            flex: 1;
            text-align: left;
        }
        .trash-type-btn span:last-child {
            background: #e2e8f0;
            color: #475569;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 600;
            min-width: 28px;
            text-align: center;
        }
        .trash-type-btn.active span:last-child {
            background: #c7d2fe;
            color: #3730a3;
        }
        .trash-main {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.05);
        }
        .trash-header {
            margin-bottom: 24px;
        }
        .trash-header h3 {
            margin: 0 0 8px 0;
            font-size: 20px;
            font-weight: 600;
            color: #0f172a;
        }
        .trash-header p {
            margin: 0;
            color: #94a3b8;
            font-size: 13px;
        }
        .trash-filters {
            background: #f8fafc;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
        }
        .trash-filters form {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto auto;
            gap: 12px;
            align-items: end;
        }
        .trash-filters .form-group {
            display: flex;
            flex-direction: column;
        }
        .trash-filters label {
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 6px;
        }
        .trash-filters input,
        .trash-filters select {
            padding: 8px 12px;
            border: 1px solid #cbd5e0;
            border-radius: 8px;
            font-size: 14px;
        }
        .trash-bulk-actions {
            background: #f8fafc;
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }
        .trash-bulk-actions-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .trash-bulk-actions-right {
            display: flex;
            gap: 8px;
        }
        table.trash-table {
            width: 100%;
            border-collapse: collapse;
        }
        table.trash-table th,
        table.trash-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
            text-align: left;
        }
        table.trash-table th {
            text-transform: uppercase;
            font-size: 12px;
            color: #475569;
            letter-spacing: 0.05em;
            font-weight: 600;
            background: #f8fafc;
        }
        table.trash-table tr:hover td {
            background: #f8fafc;
        }
        table.trash-table td:first-child {
            width: 40px;
        }
        .trash-empty {
            text-align: center;
            color: #94a3b8;
            padding: 60px 20px;
        }
        .trash-empty-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }
        .trash-actions {
            display: flex;
            gap: 8px;
        }
        .trash-actions form {
            margin: 0;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-success {
            background: #10b981;
            color: white;
        }
        .btn-success:hover {
            background: #059669;
        }
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        .btn-danger:hover {
            background: #dc2626;
        }
        .btn-warning {
            background: #f59e0b;
            color: white;
        }
        .btn-warning:hover {
            background: #d97706;
        }
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        .btn-primary:hover {
            background: #2563eb;
        }
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        .btn-secondary:hover {
            background: #4b5563;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: 500;
        }
        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }
        .badge-success {
            background: #dcfce7;
            color: #166534;
        }
        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }
        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }
        @media (max-width: 992px) {
            .trash-container {
                grid-template-columns: 1fr;
            }
            .trash-sidebar {
                position: static;
            }
            .trash-filters form {
                grid-template-columns: 1fr;
            }
            .trash-bulk-actions {
                flex-direction: column;
                align-items: stretch;
            }
            .trash-bulk-actions-right {
                flex-direction: column;
            }
            table.trash-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
@endpush

@section('content')
    <div class="trash-container">
        <aside class="trash-sidebar">
            <h4>Danh mục dữ liệu</h4>
            @foreach ($trashables as $key => $config)
                <a href="{{ route('admin.trash.index', ['type' => $key]) }}"
                   class="trash-type-btn {{ $key === $currentType ? 'active' : '' }}">
                    <span>{{ $config['label'] }}</span>
                    <span>{{ $stats[$key] ?? 0 }}</span>
                </a>
            @endforeach
        </aside>

        <section class="trash-main">
            <div class="trash-header">
                <h3>{{ $trashables[$currentType]['label'] ?? 'Dữ liệu' }} đã xóa</h3>
                <p>Có thể tìm kiếm, lọc, khôi phục hoặc xóa vĩnh viễn từng bản ghi hoặc nhiều bản ghi cùng lúc.</p>
            </div>

            <div class="trash-filters">
                <form method="GET" action="{{ route('admin.trash.index') }}">
                    <input type="hidden" name="type" value="{{ $currentType }}">
                    <div class="form-group">
                        <label>Tìm kiếm</label>
                        <input type="text" name="q" value="{{ $search }}" placeholder="Từ khóa...">
                    </div>
                    <div class="form-group">
                        <label>Từ ngày xóa</label>
                        <input type="date" name="deleted_from" value="{{ $filters['deleted_from'] ?? '' }}">
                    </div>
                    <div class="form-group">
                        <label>Đến ngày xóa</label>
                        <input type="date" name="deleted_to" value="{{ $filters['deleted_to'] ?? '' }}">
                    </div>
                    <div class="form-group">
                        <label>Số bản ghi/trang</label>
                        <select name="per_page">
                            <option value="15" @selected(($filters['per_page'] ?? 15) == 15)>15</option>
                            <option value="30" @selected(($filters['per_page'] ?? 15) == 30)>30</option>
                            <option value="50" @selected(($filters['per_page'] ?? 15) == 50)>50</option>
                            <option value="100" @selected(($filters['per_page'] ?? 15) == 100)>100</option>
                        </select>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button type="submit" class="btn btn-primary btn-sm">🔍 Lọc</button>
                        <a href="{{ route('admin.trash.index', ['type' => $currentType]) }}" class="btn btn-secondary btn-sm">✖️ Xóa lọc</a>
                    </div>
                </form>
            </div>

            @if ($items->isEmpty())
                <div class="trash-empty">
                    <div class="trash-empty-icon">🗑️</div>
                    <h4>Không có bản ghi nào trong thùng rác</h4>
                    <p>Danh mục "{{ $trashables[$currentType]['label'] ?? 'Dữ liệu' }}" hiện tại không có bản ghi nào đã bị xóa.</p>
                </div>
            @else
                <form id="bulk-form" method="POST">
                    @csrf
                    <div class="trash-bulk-actions">
                        <div class="trash-bulk-actions-left">
                            <input type="checkbox" id="select-all" style="width: 18px; height: 18px; cursor: pointer;">
                            <label for="select-all" style="margin: 0; cursor: pointer;">
                                Chọn tất cả (<span id="selected-count">0</span>)
                            </label>
                            <select id="bulk-action" class="form-select" style="padding: 6px 12px; border-radius: 6px; border: 1px solid #cbd5e0;">
                                <option value="">Thao tác hàng loạt</option>
                                <option value="restore">Khôi phục đã chọn</option>
                                <option value="delete">Xóa vĩnh viễn đã chọn</option>
                            </select>
                            <button type="button" id="apply-bulk-action" class="btn btn-primary btn-sm" disabled>Áp dụng</button>
                        </div>
                        <div class="trash-bulk-actions-right">
                            <form action="{{ route('admin.trash.restore-all', $currentType) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Khôi phục TẤT CẢ {{ $trashables[$currentType]['label'] }} trong thùng rác?');">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm">↩️ Khôi phục tất cả</button>
                            </form>
                            <form action="{{ route('admin.trash.empty', $currentType) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Xóa VĨNH VIỄN TẤT CẢ {{ $trashables[$currentType]['label'] }} trong thùng rác? Hành động này KHÔNG THỂ hoàn tác!');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">🗑️ Xóa hết</button>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="trash-table">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" id="check-all-header" style="width: 18px; height: 18px; cursor: pointer;">
                                    </th>
                                    <th>ID</th>
                                    @foreach (($trashables[$currentType]['columns'] ?? []) as $field => $label)
                                        <th>{{ $label }}</th>
                                    @endforeach
                                    <th>Ngày xóa</th>
                                    <th style="text-align: center;">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $item)
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="ids[]" value="{{ $item->id }}" class="item-checkbox" style="width: 18px; height: 18px; cursor: pointer;">
                                        </td>
                                        <td><strong>#{{ $item->id }}</strong></td>
                                        @foreach (($trashables[$currentType]['columns'] ?? []) as $field => $label)
                                            @php
                                                $value = data_get($item, $field);
                                                if ($value instanceof \Illuminate\Support\Carbon) {
                                                    $value = $value->timezone(config('app.timezone'))->format('d/m/Y H:i');
                                                } elseif (is_bool($value)) {
                                                    $value = $value ? 'Có' : 'Không';
                                                } elseif (is_array($value) || is_object($value)) {
                                                    $value = json_encode($value);
                                                }
                                            @endphp
                                            <td>{!! $value !== null && $value !== '' ? e($value) : '<span style="color:#94a3b8;">-</span>' !!}</td>
                                        @endforeach
                                        <td>
                                            <span class="badge badge-info">
                                                {{ optional($item->deleted_at)->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
                                            </span>
                                        </td>
                                        <td style="text-align: center;">
                                            <div class="trash-actions">
                                                <form action="{{ route('admin.trash.restore', [$currentType, $item->id]) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Khôi phục bản ghi này?');">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-success btn-sm" title="Khôi phục">↩️</button>
                                                </form>
                                                <form action="{{ route('admin.trash.force-delete', [$currentType, $item->id]) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Xóa vĩnh viễn bản ghi này? Hành động không thể hoàn tác.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Xóa vĩnh viễn">🗑️</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div style="margin-top: 20px;">
                        {{ $items->links() }}
                    </div>
                </form>
            @endif
        </section>
    </div>
@endsection

@push('foot')
    <script>
        // Select all checkboxes
        const selectAllHeader = document.getElementById('check-all-header');
        const selectAll = document.getElementById('select-all');
        const itemCheckboxes = document.querySelectorAll('.item-checkbox');
        const selectedCountSpan = document.getElementById('selected-count');
        const bulkActionSelect = document.getElementById('bulk-action');
        const applyBulkActionBtn = document.getElementById('apply-bulk-action');
        const bulkForm = document.getElementById('bulk-form');

        function updateSelectedCount() {
            const checked = document.querySelectorAll('.item-checkbox:checked').length;
            selectedCountSpan.textContent = checked;
            applyBulkActionBtn.disabled = !checked || !bulkActionSelect.value;
        }

        function updateSelectAllState() {
            const allChecked = itemCheckboxes.length > 0 && Array.from(itemCheckboxes).every(cb => cb.checked);
            selectAllHeader.checked = allChecked;
            selectAll.checked = allChecked;
        }

        selectAllHeader?.addEventListener('change', function() {
            itemCheckboxes.forEach(cb => cb.checked = this.checked);
            selectAll.checked = this.checked;
            updateSelectedCount();
        });

        selectAll?.addEventListener('change', function() {
            itemCheckboxes.forEach(cb => cb.checked = this.checked);
            selectAllHeader.checked = this.checked;
            updateSelectedCount();
        });

        itemCheckboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                updateSelectedCount();
                updateSelectAllState();
            });
        });

        bulkActionSelect?.addEventListener('change', function() {
            updateSelectedCount();
        });

        applyBulkActionBtn?.addEventListener('click', function() {
            const checked = document.querySelectorAll('.item-checkbox:checked');
            if (checked.length === 0) {
                alert('Vui lòng chọn ít nhất một bản ghi.');
                return;
            }

            const action = bulkActionSelect.value;
            if (!action) {
                alert('Vui lòng chọn thao tác.');
                return;
            }

            const ids = Array.from(checked).map(cb => cb.value);
            const actionText = action === 'restore' ? 'khôi phục' : 'xóa vĩnh viễn';
            const confirmText = action === 'restore' 
                ? `Khôi phục ${ids.length} bản ghi đã chọn?`
                : `Xóa vĩnh viễn ${ids.length} bản ghi đã chọn? Hành động này KHÔNG THỂ hoàn tác!`;

            if (!confirm(confirmText)) {
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = action === 'restore' 
                ? '{{ route("admin.trash.bulk-restore", $currentType) }}'
                : '{{ route("admin.trash.bulk-delete", $currentType) }}';
            
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';
            form.appendChild(csrfInput);

            ids.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        });

        updateSelectedCount();
    </script>
@endpush
