@extends('admins.layouts.master')

@section('title', 'Lịch sử chiến dịch Newsletter')
@section('page-title', '📨 Lịch sử chiến dịch Email')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Danh sách chiến dịch</h5>
            <a href="{{ route('admin.newsletters.campaign') }}" class="btn btn-primary btn-sm">
                Tạo chiến dịch mới
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên chiến dịch</th>
                        <th>Tiêu đề</th>
                        <th>Tổng gửi</th>
                        <th>Thành công</th>
                        <th>Thất bại</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($campaigns as $campaign)
                        <tr>
                            <td>#{{ $campaign->id }}</td>
                            <td>{{ $campaign->name ?? 'Không đặt tên' }}</td>
                            <td>{{ $campaign->subject }}</td>
                            <td>{{ $campaign->total_target }}</td>
                            <td class="text-success">{{ $campaign->sent_success }}</td>
                            <td class="text-danger">{{ $campaign->sent_failed }}</td>
                            <td>
                                @php
                                    $badgeClass = match($campaign->status) {
                                        'completed' => 'badge bg-success',
                                        'sending' => 'badge bg-warning',
                                        'failed' => 'badge bg-danger',
                                        default => 'badge bg-secondary',
                                    };
                                @endphp
                                <span class="{{ $badgeClass }}">
                                    {{ ucfirst($campaign->status) }}
                                </span>
                            </td>
                            <td>{{ $campaign->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.newsletters.campaigns.show', $campaign->id) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    Xem
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                Chưa có chiến dịch nào.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($campaigns->hasPages())
            <div class="card-footer">
                {{ $campaigns->links() }}
            </div>
        @endif
    </div>
@endsection


