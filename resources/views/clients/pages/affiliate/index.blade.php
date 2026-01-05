@extends('clients.layouts.master')

@section('title', 'Chương trình Affiliate | ' . ($settings->site_name ?? 'AutoSensor Việt Nam'))

@push('css_page')
    <link rel="stylesheet" href="{{ asset('clients/assets/css/affiliate.css') }}">
@endpush

@section('content')
<div class="autosensor_affiliate">
    <div class="autosensor_affiliate_container">
        <div class="autosensor_affiliate_header">
            <h1 class="autosensor_affiliate_title">Chương trình Affiliate</h1>
            <p class="autosensor_affiliate_subtitle">Kiếm hoa hồng khi giới thiệu khách hàng mua sản phẩm</p>
        </div>

        @if($affiliate)
            <div class="autosensor_affiliate_dashboard">
                <!-- Stats Cards -->
                <div class="autosensor_affiliate_stats">
                    <div class="autosensor_affiliate_stat_card">
                        <div class="autosensor_affiliate_stat_icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="32" height="32">
                                <path d="M288 32c0-17.7-14.3-32-32-32s-32 14.3-32 32V240c0 8.8-7.2 16-16 16s-16-7.2-16-16V64c0-17.7-14.3-32-32-32s-32 14.3-32 32V240c0 35.3 28.7 64 64 64s64-28.7 64-64V32zm64 160c0-17.7-14.3-32-32-32s-32 14.3-32 32v64c0 17.7 14.3 32 32 32s32-14.3 32-32V192zM48 128c-26.5 0-48 21.5-48 48V432c0 26.5 21.5 48 48 48H464c26.5 0 48-21.5 48-48V176c0-26.5-21.5-48-48-48H48zM464 160c8.8 0 16 7.2 16 16V432c0 8.8-7.2 16-16 16H48c-8.8 0-16-7.2-16-16V176c0-8.8 7.2-16 16-16H464z"/>
                            </svg>
                        </div>
                        <div class="autosensor_affiliate_stat_content">
                            <div class="autosensor_affiliate_stat_value">{{ number_format($stats['clicks'], 0, ',', '.') }}</div>
                            <div class="autosensor_affiliate_stat_label">Lượt click</div>
                        </div>
                    </div>

                    <div class="autosensor_affiliate_stat_card">
                        <div class="autosensor_affiliate_stat_icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" width="32" height="32">
                                <path d="M0 24C0 10.7 10.7 0 24 0H69.5c22 0 41.5 12.8 50.6 32h411c26.3 0 45.5 25 38.6 50.4l-41 152.3c-8.5 31.4-37 53.3-69 53.3H170.7l5.4 28.5c2.2 11.3 12.1 19.5 23.6 19.5H488c13.3 0 24 10.7 24 24s-10.7 24-24 24H199.7c-34.6 0-64.3-24.6-70.7-58.5L77.4 54.5c-.7-3.8-4-6.5-7.9-6.5H24C10.7 48 0 37.3 0 24zM128 464a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zm336-48a48 48 0 1 1 0 96 48 48 0 1 1 0-96z"/>
                            </svg>
                        </div>
                        <div class="autosensor_affiliate_stat_content">
                            <div class="autosensor_affiliate_stat_value">{{ number_format($stats['conversions'], 0, ',', '.') }}</div>
                            <div class="autosensor_affiliate_stat_label">Đơn hàng</div>
                        </div>
                    </div>

                    <div class="autosensor_affiliate_stat_card">
                        <div class="autosensor_affiliate_stat_icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="32" height="32">
                                <path d="M123.6 391.3c12.2-9.3 29.7-9.3 41.9 0l90.5 68.7V352c0-17.7 14.3-32 32-32H464c17.7 0 32-14.3 32-32V192c0-17.7-14.3-32-32-32H288c-17.7 0-32-14.3-32-32V48c0-17.7-14.3-32-32-32H64c-17.7 0-32 14.3-32 32V352c0 17.7 14.3 32 32 32h32.1l27.5 7.3z"/>
                            </svg>
                        </div>
                        <div class="autosensor_affiliate_stat_content">
                            <div class="autosensor_affiliate_stat_value">{{ number_format($stats['conversion_rate'], 2) }}%</div>
                            <div class="autosensor_affiliate_stat_label">Tỷ lệ chuyển đổi</div>
                        </div>
                    </div>

                    <div class="autosensor_affiliate_stat_card highlight">
                        <div class="autosensor_affiliate_stat_icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" width="32" height="32">
                                <path d="M160 0c17.7 0 32 14.3 32 32V99.9c1.1 .1 2.1 .2 3.2 .2s2.1-.1 3.2-.2V32c0-17.7 14.3-32 32-32s32 14.3 32 32V99.9c20.2 2.2 38.4 10.1 53.4 22.7l12.2-12.2c12.5-12.5 32.8-12.5 45.3 0s12.5 32.8 0 45.3l-12.2 12.2c12.6 15 20.5 33.2 22.7 53.4H288c17.7 0 32 14.3 32 32s-14.3 32-32 32H253.9c-2.2 20.2-10.1 38.4-22.7 53.4l12.2 12.2c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0l-12.2-12.2c-15 12.6-33.2 20.5-53.4 22.7V480c0 17.7-14.3 32-32 32s-32-14.3-32-32V412.1c-20.2-2.2-38.4-10.1-53.4-22.7L50.7 401.6c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3l12.2-12.2c-12.6-15-20.5-33.2-22.7-53.4H32c-17.7 0-32-14.3-32-32s14.3-32 32-32H66.1c2.2-20.2 10.1-38.4 22.7-53.4L76.7 189.1c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l12.2 12.2c15-12.6 33.2-20.5 53.4-22.7V32c0-17.7 14.3-32 32-32zM128 256a32 32 0 1 0 0-64 32 32 0 1 0 0 64zm64 32a32 32 0 1 0 -64 0 32 32 0 1 0 64 0z"/>
                            </svg>
                        </div>
                        <div class="autosensor_affiliate_stat_content">
                            <div class="autosensor_affiliate_stat_value">{{ number_format($stats['total_commission'], 0, ',', '.') }} đ</div>
                            <div class="autosensor_affiliate_stat_label">Tổng hoa hồng</div>
                        </div>
                    </div>
                </div>

                <!-- Referral Link Section -->
                <div class="autosensor_affiliate_referral">
                    <div class="autosensor_affiliate_referral_header">
                        <h2 class="autosensor_affiliate_referral_title">Link giới thiệu của bạn</h2>
                        <p class="autosensor_affiliate_referral_subtitle">Chia sẻ link này để nhận hoa hồng khi có người mua hàng</p>
                    </div>
                    <div class="autosensor_affiliate_referral_code">
                        <div class="autosensor_affiliate_referral_code_display">
                            <span class="autosensor_affiliate_referral_code_label">Mã giới thiệu:</span>
                            <span class="autosensor_affiliate_referral_code_value">{{ $affiliate->code }}</span>
                        </div>
                        <div class="autosensor_affiliate_referral_link">
                            <input type="text" 
                                   id="affiliateLink" 
                                   class="autosensor_affiliate_referral_link_input" 
                                   value="{{ $affiliate->referral_url }}" 
                                   readonly>
                            <button type="button" 
                                    class="autosensor_affiliate_referral_link_copy" 
                                    onclick="copyAffiliateLink()">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="18" height="18">
                                    <path d="M502.6 70.63l-61.25-61.25C435.4 3.371 427.2 0 418.7 0H255.1c-35.35 0-64 28.66-64 64l.0195 229.5c0 35.34 28.66 64 64 64H448c35.2 0 64-28.8 64-64V93.25C512 84.77 508.6 76.63 502.6 70.63zM464 320c0 8.836-7.164 16-16 16H255.1c-8.836 0-16-7.164-16-16V64.13c0-8.836 7.164-16 16-16h128L384 96c0 17.67 14.33 32 32 32h47.1V320zM272 448c0 8.836-7.164 16-16 16H63.1c-8.836 0-16-7.164-16-16V224.1c0-8.836 7.164-16 16-16H256V448zM224 192H63.1C28.66 192 0 220.7 0 256l.0195 192c0 35.34 28.66 64 64 64H224c35.2 0 64-28.8 64-64V256C288 220.7 259.3 192 224 192z"/>
                                </svg>
                                Copy
                            </button>
                        </div>
                        <div class="autosensor_affiliate_referral_commission">
                            <span class="autosensor_affiliate_referral_commission_label">Hoa hồng:</span>
                            <span class="autosensor_affiliate_referral_commission_value">{{ number_format($affiliate->commission_rate, 2) }}%</span>
                        </div>
                    </div>
                </div>

                <!-- Commission Details -->
                <div class="autosensor_affiliate_commission">
                    <div class="autosensor_affiliate_commission_header">
                        <h2 class="autosensor_affiliate_commission_title">Chi tiết hoa hồng</h2>
                    </div>
                    <div class="autosensor_affiliate_commission_summary">
                        <div class="autosensor_affiliate_commission_item">
                            <span class="autosensor_affiliate_commission_item_label">Đang chờ thanh toán:</span>
                            <span class="autosensor_affiliate_commission_item_value pending">{{ number_format($stats['pending_commission'], 0, ',', '.') }} đ</span>
                        </div>
                        <div class="autosensor_affiliate_commission_item">
                            <span class="autosensor_affiliate_commission_item_label">Đã thanh toán:</span>
                            <span class="autosensor_affiliate_commission_item_value paid">{{ number_format($stats['paid_commission'], 0, ',', '.') }} đ</span>
                        </div>
                        <div class="autosensor_affiliate_commission_item">
                            <span class="autosensor_affiliate_commission_item_label">Tổng doanh thu:</span>
                            <span class="autosensor_affiliate_commission_item_value">{{ number_format($stats['total_revenue'], 0, ',', '.') }} đ</span>
                        </div>
                    </div>
                </div>

                <!-- Commission History -->
                @if($commissions->count() > 0)
                    <div class="autosensor_affiliate_history">
                        <div class="autosensor_affiliate_history_header">
                            <h2 class="autosensor_affiliate_history_title">Lịch sử hoa hồng</h2>
                        </div>
                        <div class="autosensor_affiliate_history_table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Mã đơn hàng</th>
                                        <th>Ngày</th>
                                        <th>Giá trị đơn hàng</th>
                                        <th>Hoa hồng</th>
                                        <th>Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($commissions as $order)
                                        @php
                                            $commission = $order->final_price * ($affiliate->commission_rate / 100);
                                            $statusClass = match($order->status) {
                                                'completed' => 'completed',
                                                'pending', 'processing', 'shipped' => 'pending',
                                                default => 'cancelled'
                                            };
                                        @endphp
                                        <tr>
                                            <td>
                                                <a href="{{ route('client.order.show', $order->code) }}" class="autosensor_affiliate_history_link">
                                                    {{ $order->code }}
                                                </a>
                                            </td>
                                            <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                                            <td>{{ number_format($order->final_price, 0, ',', '.') }} đ</td>
                                            <td class="autosensor_affiliate_history_commission">{{ number_format($commission, 0, ',', '.') }} đ</td>
                                            <td>
                                                <span class="autosensor_affiliate_history_status {{ $statusClass }}">
                                                    @if($order->status === 'completed')
                                                        Đã thanh toán
                                                    @elseif(in_array($order->status, ['pending', 'processing', 'shipped']))
                                                        Đang chờ
                                                    @else
                                                        Đã hủy
                                                    @endif
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="autosensor_affiliate_history_pagination">
                            {{ $commissions->links() }}
                        </div>
                    </div>
                @endif
            </div>
        @else
            <div class="autosensor_affiliate_empty">
                <p>Bạn chưa có affiliate code. Vui lòng liên hệ admin để đăng ký.</p>
            </div>
        @endif
    </div>
</div>

@push('js_page')
<script>
function copyAffiliateLink() {
    const linkInput = document.getElementById('affiliateLink');
    linkInput.select();
    linkInput.setSelectionRange(0, 99999); // For mobile devices
    
    try {
        document.execCommand('copy');
        
        // Show toast notification
        if (typeof showCustomToast === 'function') {
            showCustomToast('Đã copy link giới thiệu!', 'success', 3000);
        } else {
            alert('Đã copy link giới thiệu!');
        }
    } catch (err) {
        console.error('Failed to copy:', err);
        alert('Không thể copy. Vui lòng copy thủ công.');
    }
}
</script>
@endpush
@endsection

