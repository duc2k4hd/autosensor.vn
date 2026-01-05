@extends('admins.layouts.master')

@section('title', 'Quản lý Email Templates')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-envelope"></i> Quản lý Email Templates</h2>
        <a href="{{ route('admin.email-templates.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tạo Template
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Key</th>
                            <th>Tên</th>
                            <th>Subject</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($templates as $template)
                            <tr>
                                <td><code>{{ $template->key }}</code></td>
                                <td>{{ $template->name }}</td>
                                <td>{{ $template->subject }}</td>
                                <td>
                                    <span class="badge bg-{{ $template->is_active ? 'success' : 'secondary' }}">
                                        {{ $template->is_active ? 'Hoạt động' : 'Tắt' }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.email-templates.edit', $template) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('admin.email-templates.destroy', $template) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa template?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">Chưa có template nào</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

