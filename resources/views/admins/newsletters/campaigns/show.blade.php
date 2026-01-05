@extends('admins.layouts.master')

@section('title', 'Chi tiết chiến dịch Newsletter')
@section('page-title', '📨 Chi tiết chiến dịch Email')

@section('content')
    <div class="mb-3">
        <a href="{{ route('admin.newsletters.campaigns.index') }}" class="btn btn-link p-0">
            ← Quay lại danh sách chiến dịch
        </a>
    </div>

    <div class="row">
        <div class="col-md-7">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Thông tin chiến dịch</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Tên chiến dịch</dt>
                        <dd class="col-sm-8">{{ $campaign->name ?? 'Không đặt tên' }}</dd>

                        <dt class="col-sm-4">Tiêu đề email</dt>
                        <dd class="col-sm-8">{{ $campaign->subject }}</dd>

                        <dt class="col-sm-4">Trạng thái</dt>
                        <dd class="col-sm-8">
                            @php
                                $badgeClass = match($campaign->status) {
                                    'completed' => 'badge bg-success',
                                    'sending' => 'badge bg-warning',
                                    'failed' => 'badge bg-danger',
                                    default => 'badge bg-secondary',
                                };
                            @endphp
                            <span class="{{ $badgeClass }}">{{ ucfirst($campaign->status) }}</span>
                        </dd>

                        <dt class="col-sm-4">Thời gian tạo</dt>
                        <dd class="col-sm-8">{{ $campaign->created_at?->format('d/m/Y H:i') }}</dd>

                        <dt class="col-sm-4">Tổng đối tượng</dt>
                        <dd class="col-sm-8">{{ $campaign->total_target }}</dd>

                        <dt class="col-sm-4 text-success">Gửi thành công</dt>
                        <dd class="col-sm-8 text-success">{{ $campaign->sent_success }}</dd>

                        <dt class="col-sm-4 text-danger">Gửi thất bại</dt>
                        <dd class="col-sm-8 text-danger">{{ $campaign->sent_failed }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Nội dung email</h5>
                </div>
                <div class="card-body">
                    @if($campaign->content)
                        <div class="border rounded p-3" style="max-height: 500px; overflow:auto;">
                            {!! $campaign->content !!}
                        </div>
                    @else
                        <p class="text-muted mb-0">Chiến dịch này không có nội dung tuỳ chỉnh.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Thông tin CTA / Footer</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">CTA URL</dt>
                        <dd class="col-sm-8">
                            @if($campaign->cta_url)
                                <a href="{{ $campaign->cta_url }}" target="_blank">{{ $campaign->cta_url }}</a>
                            @else
                                <span class="text-muted">Không thiết lập</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4">CTA Text</dt>
                        <dd class="col-sm-8">{{ $campaign->cta_text ?? 'Không thiết lập' }}</dd>

                        <dt class="col-sm-4">Footer</dt>
                        <dd class="col-sm-8">
                            @if($campaign->footer)
                                <div class="border rounded p-2" style="white-space: pre-wrap;">{{ $campaign->footer }}</div>
                            @else
                                <span class="text-muted">Không thiết lập</span>
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Bộ lọc đã sử dụng</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Trạng thái</dt>
                        <dd class="col-sm-8">{{ $campaign->filter_status ?? 'Mặc định (subscribed)' }}</dd>

                        <dt class="col-sm-4">Nguồn</dt>
                        <dd class="col-sm-8">{{ $campaign->filter_source ?? 'Tất cả' }}</dd>

                        <dt class="col-sm-4">Từ ngày</dt>
                        <dd class="col-sm-8">
                            {{ $campaign->filter_date_from?->format('d/m/Y') ?? 'Không giới hạn' }}
                        </dd>

                        <dt class="col-sm-4">Đến ngày</dt>
                        <dd class="col-sm-8">
                            {{ $campaign->filter_date_to?->format('d/m/Y') ?? 'Không giới hạn' }}
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
@endsection


