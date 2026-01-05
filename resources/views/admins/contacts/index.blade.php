@extends('admins.layouts.master')

@section('title', 'Quản lý liên hệ')
@section('page-title', '📨 Quản lý liên hệ')

@section('content')
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase mb-2">Tổng liên hệ</h6>
                        <h3 class="mb-0">{{ number_format($stats['total'] ?? 0) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase mb-2">Mới</h6>
                        <h3 class="mb-0 text-primary">{{ number_format($stats['new'] ?? 0) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase mb-2">Đang xử lý</h6>
                        <h3 class="mb-0 text-warning">{{ number_format($stats['processing'] ?? 0) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase mb-2">Đã xử lý</h6>
                        <h3 class="mb-0 text-success">{{ number_format($stats['done'] ?? 0) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.contacts.index') }}" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Từ khóa</label>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control"
                               placeholder="Tên, email, điện thoại...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Trạng thái</label>
                        <select name="status" class="form-select">
                            <option value="">Tất cả</option>
                            <option value="new" @selected(($filters['status'] ?? '') === 'new')>Mới</option>
                            <option value="processing" @selected(($filters['status'] ?? '') === 'processing')>Đang xử lý</option>
                            <option value="done" @selected(($filters['status'] ?? '') === 'done')>Đã xử lý</option>
                            <option value="spam" @selected(($filters['status'] ?? '') === 'spam')>Spam</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Nguồn</label>
                        <input type="text" name="source" value="{{ $filters['source'] ?? '' }}" class="form-control"
                               placeholder="contact_form...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Từ ngày</label>
                        <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Đến ngày</label>
                        <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">/trang</label>
                        <input type="number" name="per_page" value="{{ $filters['per_page'] ?? 20 }}" min="5" max="100"
                               class="form-control">
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary">Lọc</button>
                        <a href="{{ route('admin.contacts.index') }}" class="btn btn-outline-secondary">Xóa lọc</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.contacts.bulk-action') }}">
                    @csrf
                    <div class="d-flex justify-content-between mb-3">
                        <div>
                            <select name="action" class="form-select form-select-sm d-inline-block w-auto">
                                <option value="">Bulk action</option>
                                <option value="mark_spam">Đánh dấu spam</option>
                                <option value="mark_processing">Đang xử lý</option>
                                <option value="mark_done">Đã xử lý</option>
                                <option value="delete">Xóa</option>
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline-primary ms-1"
                                    onclick="return confirm('Thực hiện thao tác với các liên hệ đã chọn?')">
                                Áp dụng
                            </button>
                        </div>
                        <div>
                            {{ $contacts->total() }} bản ghi
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                            <tr>
                                <th width="30">
                                    <input type="checkbox" id="check-all">
                                </th>
                                <th>Khách hàng</th>
                                <th>Thông tin</th>
                                <th>Trạng thái</th>
                                <th>Nguồn</th>
                                <th>Thời gian</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($contacts as $contact)
                                <tr>
                                    <td>
                                        <input type="checkbox" name="contact_ids[]" value="{{ $contact->id }}" class="contact-checkbox">
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.contacts.show', $contact) }}">
                                            <strong>{{ $contact->name ?? 'Không tên' }}</strong>
                                        </a>
                                        <div class="text-muted small">
                                            {{ $contact->email }}<br>
                                            {{ $contact->phone }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small text-muted">
                                            <strong>{{ $contact->subject }}</strong><br>
                                            {{ \Illuminate\Support\Str::limit($contact->message, 80) }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="{{ $contact->status_badge_class }}">
                                            {{ $contact->status_label }}
                                        </span>
                                        @if(! $contact->is_read)
                                            <span class="badge bg-info ms-1">Chưa đọc</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            {{ $contact->source ?? 'contact_form' }}
                                        </span>
                                    </td>
                                    <td class="small text-muted">
                                        {{ optional($contact->created_at)->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.contacts.show', $contact) }}" class="btn btn-sm btn-outline-secondary">
                                            Xem
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        Không có liên hệ nào.
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </form>

                <div class="mt-3">
                    {{ $contacts->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@section('foot')
    <script>
        document.getElementById('check-all')?.addEventListener('change', function (e) {
            document.querySelectorAll('.contact-checkbox').forEach(cb => cb.checked = e.target.checked);
        });
    </script>
@endsection

