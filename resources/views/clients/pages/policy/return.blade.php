@extends('clients.layouts.master')

@section('title', 'Chính sách đổi trả - ' . ($settings->site_name ?? $settings->subname ?? 'AutoSensor Việt Nam'))

@section('head')
    <meta name="description"

          content="Chính sách đổi trả & bảo hành {{ $settings->site_name ?? 'AutoSensor Việt Nam' }} - điều kiện đổi hàng, thời gian áp dụng và cách liên hệ hỗ trợ nhanh chóng cho thiết bị tự động hóa.">

    <link rel="canonical" href="{{ url()->current() }}">

@endsection

@push('js_page')
    <script defer src="{{ asset('clients/assets/js/main.js') }}"></script>
@endpush

@push('styles')
    @include('clients.pages.policy.partials.styles')
@endpush

@section('schema')
    @php
        $siteUrl = $settings->site_url ?? url('/');
        $currentUrl = url()->current();
        $pageTitle = 'Chính sách đổi trả';
    @endphp
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      "itemListElement": [
        {
          "@type": "ListItem",
          "position": 1,
          "item": {
            "@id": "{{ $siteUrl }}",
            "name": "Trang chủ"
          }
        },
        {
          "@type": "ListItem",
          "position": 2,
          "item": {
            "@id": "{{ $currentUrl }}",
            "name": "{{ $pageTitle }}"
          }
        }
      ]
    }
    </script>
@endsection

@section('content')

    <div class="policy-page">

        <section class="policy-hero">

            <div class="policy-tags">

                <span class="policy-tag">Return & Warranty</span>

                <span class="policy-tag">Customer care</span>

            </div>

            <h1>Chính sách đổi trả & bảo hành</h1>

            <p>

                Chúng tôi muốn mọi trải nghiệm mua sắm đều an tâm. Chính sách đổi trả linh hoạt giúp bạn dễ dàng đổi sang thiết bị

                phù hợp, đồng thời bảo hành rõ ràng cho mọi đơn hàng mua tại cửa hàng và online.

            </p>

            <div class="policy-meta">

                <div class="policy-meta-card">

                    <span>Thời gian đổi</span>

                    <strong>15 ngày</strong>

                </div>

                <div class="policy-meta-card">

                    <span>Tình trạng thiết bị</span>

                    <strong>Còn nguyên vẹn</strong>

                </div>

                <div class="policy-meta-card">

                    <span>Hỗ trợ lỗi kỹ thuật</span>

                    <strong>Đổi mới 100%</strong>

                </div>

            </div>

        </section>

        <section class="policy-section">

            <h2>Điều kiện đổi hàng</h2>

            <ul class="policy-list">

                <li>Đổi trong vòng <strong>15 ngày</strong> kể từ ngày mua tại cửa hàng hoặc ngày nhận hàng online.</li>

                <li>Thiết bị còn nguyên vẹn, chưa lắp đặt, chưa sử dụng, không hư hỏng.</li>

                <li>Bao bì, phụ kiện còn nguyên vẹn, không vỡ nứt do tác động bên ngoài.</li>

                <li>Xuất trình hóa đơn hoặc mã đơn hàng khi đổi sản phẩm.</li>

            </ul>

            <div class="policy-grid" style="margin-top: 18px;">

                <div class="policy-card">

                    <strong>Hình thức đổi</strong>

                    <p>Đổi sang thiết bị cùng hoặc cao hơn giá trị. Nếu thấp hơn, phần chênh lệch được quy đổi thành voucher.</p>

                </div>

                <div class="policy-card">

                    <strong>Lưu ý khuyến mãi</strong>

                    <p>Đơn khuyến mãi chỉ đổi trong thời gian diễn ra chương trình và không áp dụng hoàn tiền.</p>

                </div>

                <div class="policy-card">

                    <strong>Không áp dụng</strong>

                    <p>Thiết bị giảm giá từ 30% trở lên, thiết bị đã lắp đặt, phụ kiện đã sử dụng.</p>

                </div>

            </div>

        </section>

        <section class="policy-section">

            <h2>Đổi hàng do lỗi kỹ thuật</h2>

            <p><strong>Áp dụng khi:</strong></p>

            <ul class="policy-list">

                <li>Thiết bị hư hỏng, lỗi kỹ thuật do vận chuyển hoặc đóng gói không đúng cách.</li>

                <li>Sai model thiết bị, sai thông số kỹ thuật so với đơn đặt hàng.</li>

                <li>Bao bì, phụ kiện bị vỡ, nứt do lỗi đóng gói hoặc vận chuyển.</li>

                <li>Thiết bị lỗi kỹ thuật phát hiện trong vòng 30 ngày đầu sau khi nhận hàng.</li>

            </ul>

            <div class="policy-note">

                Nếu lỗi thuộc về AutoSensor Việt Nam hoặc vận chuyển, chúng tôi đổi mới miễn phí 100% trong vòng 15 ngày.

            </div>

            <p style="margin-top: 12px; font-weight: 600;">Không áp dụng đổi trả với:</p>

            <ul class="policy-list">

                <li>Thiết bị đã qua sử dụng sai cách, hư hỏng do sử dụng sai thông số kỹ thuật hoặc đặt sai vị trí.</li>

                <li>Đã lắp đặt, sử dụng hoặc tháo dỡ không đúng cách.</li>

                <li>Mất hóa đơn, thất lạc mã đơn hàng, không đủ điều kiện theo quy định.</li>

            </ul>

        </section>

        <section class="policy-contact">

            <h3>Hỗ trợ đổi trả</h3>

            <p>📞 Hotline: <a href="tel:{{ $settings->contact_phone ?? '' }}">{{ $settings->contact_phone ?? '' }}</a></p>

            <p>✉ Email: <a href="mailto:{{ $settings->contact_email ?? '' }}">{{ $settings->contact_email ?? '' }}</a></p>

            <p>🌐 Website: <a href="{{ $settings->site_url ?? '#' }}">{{ $settings->site_name ?? 'AutoSensor Việt Nam' }}</a></p>

        </section>

        <p class="policy-updated">

            Chính sách có hiệu lực từ 01/11/2025 và sẽ được cập nhật khi cần để đảm bảo quyền lợi khách hàng.

        </p>

    </div>

@endsection
