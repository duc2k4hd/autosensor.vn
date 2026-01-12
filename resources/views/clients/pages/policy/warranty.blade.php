@extends('clients.layouts.master')

@section('title', 'Chính sách bảo hành - ' . ($settings->site_name ?? $settings->subname ?? 'AutoSensor Việt Nam'))

@section('head')

    <meta name="description"

          content="Chính sách bảo hành {{ $settings->site_name ?? 'AutoSensor Việt Nam' }} - phạm vi áp dụng, điều kiện bảo hành và quy trình xử lý chi tiết cho thiết bị tự động hóa công nghiệp.">

    <link rel="canonical" href="{{ url()->current() }}">

@endsection

@section('schema')
    @php
        $siteUrl = $settings->site_url ?? url('/');
        $currentUrl = url()->current();
        $pageTitle = 'Chính sách bảo hành';
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

@push('js_page')
    <script defer src="{{ asset('clients/assets/js/main.js') }}"></script>
@endpush

@push('styles')

    @include('clients.pages.policy.partials.styles')

@endpush

@section('content')

    <div class="policy-page">

        <section class="policy-hero">

            <div class="policy-tags">

                <span class="policy-tag">Warranty</span>

                <span class="policy-tag">After-sale care</span>

            </div>

            <h1>Chính sách bảo hành</h1>

            <p>

                Cảm ơn bạn đã tin tưởng lựa chọn <strong>{{ $settings->site_name ?? $settings->subname ?? 'AutoSensor Việt Nam' }}</strong>.

                Chính sách này áp dụng cho tất cả đơn hàng mua tại showroom, website và các kênh chính thức của AutoSensor Việt Nam.

            </p>

        </section>

        <section class="policy-section">

            <h2>1. Phạm vi áp dụng</h2>

            <ul class="policy-list">

                <li>Thiết bị bị hư hỏng, lỗi kỹ thuật do lỗi vận chuyển hoặc đóng gói không đúng cách.</li>

                <li>Thiết bị lỗi kỹ thuật phát hiện trong vòng 30 ngày đầu sau khi nhận hàng.</li>

                <li>Bao bì, phụ kiện bị vỡ, nứt do lỗi đóng gói hoặc vận chuyển.</li>

                <li>Sai model thiết bị so với đơn đặt hàng.</li>

                <li>Thiết bị không hoạt động đúng, lỗi kỹ thuật do lỗi từ nhà sản xuất.</li>

            </ul>

        </section>

        <section class="policy-section">

            <h2>2. Thời hạn bảo hành</h2>

            <ul class="policy-list">

                <li><strong>12-24 tháng</strong> kể từ ngày mua trực tiếp tại showroom (tùy loại thiết bị).</li>

                <li><strong>12-24 tháng</strong> kể từ ngày nhận hàng online (tùy loại thiết bị).</li>

            </ul>

            <div class="policy-note">Vui lòng giữ hóa đơn hoặc mã đơn hàng để được hỗ trợ nhanh chóng.</div>

        </section>

        <section class="policy-section">

            <h2>3. Điều kiện bảo hành</h2>

            <ul class="policy-list">

                <li>Thiết bị còn nguyên vẹn, chưa lắp đặt hoặc sử dụng sai cách.</li>

                <li>Chưa qua sửa chữa, tháo dỡ hoặc can thiệp không đúng cách gây hư hại.</li>

                <li>Không bị hư hỏng do sử dụng sai thông số kỹ thuật hoặc môi trường không phù hợp.</li>

                <li>Không bị hư hỏng cơ khí, điện tử do tác động bên ngoài.</li>

                <li>Có hóa đơn mua hàng hoặc mã đơn hợp lệ.</li>

            </ul>

        </section>

        <section class="policy-section">

            <h2>4. Trường hợp không áp dụng</h2>

            <ul class="policy-list">

                <li>Thiết bị hư hỏng do khách hàng sử dụng sai thông số kỹ thuật hoặc môi trường không phù hợp.</li>

                <li>Thiết bị hư hỏng do đặt sai vị trí (quá nóng, quá ẩm, gần nguồn nhiệt, rung động mạnh).</li>

                <li>Tự ý lắp đặt, sửa chữa hoặc tháo dỡ không đúng cách.</li>

                <li>Thiết bị hư hỏng do môi trường làm việc của khách hàng không đảm bảo điều kiện kỹ thuật.</li>

                <li>Mất hóa đơn hoặc không xác minh được lịch sử mua.</li>

                <li>Thiết bị giảm giá trên 30%, thiết bị đã lắp đặt, phụ kiện đã sử dụng.</li>

            </ul>

        </section>

        <section class="policy-section">

            <h2>5. Quy trình tiếp nhận</h2>

            <div class="policy-timeline">

                <div class="policy-timeline-item"><strong>Bước 1:</strong> Liên hệ hotline/inbox/email mô tả tình trạng thiết bị.</div>

                <div class="policy-timeline-item"><strong>Bước 2:</strong> Xác minh đơn hàng và hướng dẫn gửi hình ảnh hoặc mang thiết bị đến showroom.</div>

                <div class="policy-timeline-item"><strong>Bước 3:</strong> Nhân viên kiểm tra tình trạng trong 1–2 ngày.</div>

                <div class="policy-timeline-item"><strong>Bước 4:</strong> Đổi thiết bị mới tương đương hoặc hoàn tiền nếu hết hàng.</div>

            </div>

        </section>

        <section class="policy-section">

            <h2>6. Chi phí & thời gian</h2>

            <ul class="policy-list">

                <li>Miễn phí 100% với lỗi từ AutoSensor Việt Nam hoặc vận chuyển.</li>

                <li>Khách chịu phí vận chuyển khi lỗi do chăm sóc sai cách hoặc quá thời hạn.</li>

                <li>Thời gian xử lý: tối thiểu 1 ngày, tối đa 3 ngày làm việc.</li>

            </ul>

        </section>

        <section class="policy-contact">

            <h3>Liên hệ hỗ trợ</h3>

            <p>📞 Hotline: <a href="tel:{{ $settings->contact_phone ?? '' }}">{{ $settings->contact_phone ?? '' }}</a></p>

            <p>✉ Email: <a href="mailto:{{ $settings->contact_email ?? '' }}">{{ $settings->contact_email ?? '' }}</a></p>

            <p>🌐 Website: <a href="{{ $settings->site_url ?? '#' }}">{{ $settings->site_name ?? 'AutoSensor Việt Nam' }}</a></p>

        </section>

        <p class="policy-updated">Chính sách bảo hành có hiệu lực từ ngày 01/11/2025 và sẽ được cập nhật để nâng cao quyền lợi khách hàng.</p>

    </div>

@endsection
