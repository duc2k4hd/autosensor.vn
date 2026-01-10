@extends('admins.layouts.master')

@section('title', 'Chi tiết Lead Tư vấn Nhanh')
@section('page-title', '💬 Chi tiết Lead Tư vấn Nhanh')

@section('content')
    <div class="container-fluid">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Thông tin Lead</h5>
                <a href="{{ route('admin.quick-consultation-leads.index') }}" class="btn btn-sm btn-outline-secondary">
                    ← Quay lại
                </a>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3">Thông tin khách hàng</h6>
                        <table class="table table-bordered">
                            <tr>
                                <th width="150">Họ tên:</th>
                                <td>{{ $lead->name ?? 'Không có' }}</td>
                            </tr>
                            <tr>
                                <th>Số điện thoại:</th>
                                <td>
                                    <strong>{{ $lead->phone }}</strong>
                                    <a href="tel:{{ $lead->phone }}" class="btn btn-sm btn-outline-primary ms-2">📞 Gọi</a>
                                </td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td>
                                    {{ $lead->email ?? 'Không có' }}
                                    @if($lead->email)
                                        <a href="mailto:{{ $lead->email }}" class="btn btn-sm btn-outline-primary ms-2">✉️ Gửi email</a>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Tin nhắn:</th>
                                <td>{{ $lead->message ?? 'Không có' }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3">Thông tin sản phẩm & Hành vi</h6>
                        <table class="table table-bordered">
                            <tr>
                                <th width="150">Sản phẩm:</th>
                                <td>
                                    @if($lead->product)
                                        <a href="{{ route('client.product.detail', ['slug' => $lead->product->slug]) }}" target="_blank">
                                            <strong>{{ $lead->product->name }}</strong>
                                        </a>
                                        <div class="text-muted small">SKU: {{ $lead->product->sku }}</div>
                                    @else
                                        <span class="text-muted">Sản phẩm đã bị xóa</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Loại trigger:</th>
                                <td>
                                    @php
                                        $triggerLabels = [
                                            'view_time' => '⏱️ Xem lâu (quá 2 phút)',
                                            'multiple_products' => '📦 Xem nhiều sản phẩm cùng nhóm',
                                            'manual' => '✋ Thủ công',
                                        ];
                                    @endphp
                                    <span class="badge bg-info">
                                        {{ $triggerLabels[$lead->trigger_type] ?? $lead->trigger_type }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Behavior Data:</th>
                                <td>
                                    @if($lead->behavior_data)
                                        <pre class="small bg-light p-2 rounded">{{ json_encode($lead->behavior_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    @else
                                        <span class="text-muted">Không có</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Trạng thái:</th>
                                <td>
                                    @if($lead->is_contacted)
                                        <span class="badge bg-success">Đã liên hệ</span>
                                        @if($lead->contacted_at)
                                            <div class="text-muted small mt-1">
                                                Thời gian: {{ $lead->contacted_at->format('d/m/Y H:i:s') }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="badge bg-warning">Chưa liên hệ</span>
                                        <form method="POST" action="{{ route('admin.quick-consultation-leads.mark-contacted', $lead) }}" class="d-inline ms-2">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">Đánh dấu đã liên hệ</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <h6 class="text-muted mb-3">Thông tin kỹ thuật</h6>
                        <table class="table table-bordered">
                            <tr>
                                <th width="150">Session ID:</th>
                                <td><code>{{ $lead->session_id }}</code></td>
                            </tr>
                            <tr>
                                <th>IP Address:</th>
                                <td><code>{{ $lead->ip_address }}</code></td>
                            </tr>
                            <tr>
                                <th>User Agent:</th>
                                <td><small class="text-muted">{{ $lead->user_agent }}</small></td>
                            </tr>
                            <tr>
                                <th>Thời gian tạo:</th>
                                <td>{{ $lead->created_at->format('d/m/Y H:i:s') }}</td>
                            </tr>
                            <tr>
                                <th>Thời gian cập nhật:</th>
                                <td>{{ $lead->updated_at->format('d/m/Y H:i:s') }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    @if(!$lead->is_contacted)
                        <form method="POST" action="{{ route('admin.quick-consultation-leads.mark-contacted', $lead) }}">
                            @csrf
                            <button type="submit" class="btn btn-success">✓ Đánh dấu đã liên hệ</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('admin.quick-consultation-leads.destroy', $lead) }}" onsubmit="return confirm('Xóa lead này?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">🗑️ Xóa</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
