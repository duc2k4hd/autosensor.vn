@extends('admins.layouts.master')

@section('title', '📁 Backup & Restore Database')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/backup-icon.png') }}" type="image/x-icon">
@endpush


@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-database"></i> Backup & Restore Database</h2>
        <form action="{{ route('admin.backups.store') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-plus"></i> Tạo Backup
            </button>
        </form>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Tên file</th>
                            <th>Kích thước</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($backups as $backup)
                            <tr>
                                <td><code>{{ $backup['name'] }}</code></td>
                                <td>{{ number_format($backup['size'] / 1024, 2) }} KB</td>
                                <td>{{ $backup['created_at'] }}</td>
                                <td>
                                    <a href="{{ route('admin.backups.download', $backup['name']) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                    <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#restoreModal{{ $loop->index }}">
                                        <i class="fas fa-undo"></i> Restore
                                    </button>
                                    <form action="{{ route('admin.backups.destroy', $backup['name']) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa backup?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>

                            <!-- Restore Modal -->
                            <div class="modal fade" id="restoreModal{{ $loop->index }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="{{ route('admin.backups.restore', $backup['name']) }}" method="POST">
                                            @csrf
                                            <div class="modal-header">
                                                <h5 class="modal-title">Restore Database</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="alert alert-danger">
                                                    <strong>Cảnh báo!</strong> Thao tác này sẽ ghi đè toàn bộ dữ liệu hiện tại. Hãy chắc chắn bạn đã backup dữ liệu hiện tại.
                                                </div>
                                                <p>Bạn có chắc chắn muốn restore từ file: <code>{{ $backup['name'] }}</code>?</p>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="confirm" value="1" id="confirm{{ $loop->index }}" required>
                                                    <label class="form-check-label" for="confirm{{ $loop->index }}">
                                                        Tôi hiểu rủi ro và muốn tiếp tục
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                                <button type="submit" class="btn btn-danger">Restore</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">Chưa có backup nào</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

