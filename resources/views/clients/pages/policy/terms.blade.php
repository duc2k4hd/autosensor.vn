@extends('clients.layouts.master')

@section('title', 'Điều khoản sử dụng - ' . ($settings->site_name ?? $settings->subname ?? 'AutoSensor Việt Nam'))

@section('head')

    <meta name="description"

          content="Điều khoản sử dụng {{ $settings->site_name ?? 'AutoSensor Việt Nam' }} - quy định quyền và nghĩa vụ của khách hàng khi truy cập và mua sắm.">

    <link rel="canonical" href="{{ url()->current() }}">

@endsection

@section('schema')
    @php
        $siteUrl = $settings->site_url ?? url('/');
        $currentUrl = url()->current();
        $pageTitle = 'Điều khoản sử dụng';
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

                <span class="policy-tag">Terms of Use</span>

                <span class="policy-tag">User agreement</span>

            </div>

            <h1>Điều khoản sử dụng</h1>

            <p>

                Chào mừng bạn đến với <strong>{{ $settings->site_name ?? $settings->subname ?? 'AutoSensor Việt Nam' }}</strong>.

                Khi truy cập và mua sắm, bạn đồng ý tuân thủ các điều khoản dưới đây để đảm bảo quyền lợi cho đôi bên.

            </p>

        </section>

        <section class="policy-section">

            <h2>1. Chấp nhận điều khoản</h2>

            <p>Việc sử dụng website đồng nghĩa bạn chấp thuận mọi điều khoản hiện hành. AutoSensor Việt Nam có thể cập nhật nội dung mà không cần thông báo; tiếp tục sử dụng sau cập nhật đồng nghĩa bạn đồng ý với phiên bản mới.</p>

        </section>

        <section class="policy-section">

            <h2>2. Quyền & trách nhiệm người dùng</h2>

            <ul class="policy-list">

                <li>Cung cấp thông tin chính xác khi đặt hàng hoặc tạo tài khoản.</li>

                <li>Không sử dụng website vào mục đích gian lận, phá hoại, truyền tải nội dung trái pháp luật.</li>

                <li>Không sao chép, sử dụng nội dung cho mục đích thương mại khi chưa được phép.</li>

                <li>Tự chịu trách nhiệm bảo mật tài khoản và mật khẩu.</li>

            </ul>

        </section>

        <section class="policy-section">

            <h2>3. Quyền & nghĩa vụ của AutoSensor Việt Nam</h2>

            <ul class="policy-list">

                <li>Cung cấp thông tin sản phẩm minh bạch, chính xác.</li>

                <li>Bảo mật dữ liệu theo <a href="{{ route('client.policy.privacy') }}">Chính sách bảo mật</a>.</li>

                <li>Thông báo xác nhận đơn, khuyến mãi hoặc hỗ trợ kỹ thuật khi cần.</li>

                <li>Có quyền từ chối/hủy đơn khi thông tin sai, có dấu hiệu gian lận, hết hàng hoặc lỗi giá.</li>

            </ul>

        </section>

        <section class="policy-section">

            <h2>4. Thông tin sản phẩm & giá cả</h2>

            <p>AutoSensor Việt Nam luôn cố gắng cập nhật giá chính xác. Nếu xảy ra sai sót:</p>

            <ul class="policy-list">

                <li>Thông báo lại khách hàng để xác nhận đơn mới.</li>

                <li>Khách có quyền đồng ý hoặc hủy.</li>

                <li>Không bắt buộc giao theo mức giá bị sai.</li>

            </ul>

        </section>

        <section class="policy-section">

            <h2>5. Đơn hàng & thanh toán</h2>

            <ul class="policy-list">

                <li>Đơn xác nhận khi hệ thống gửi mã hoặc CSKH liên hệ.</li>

                <li>Hỗ trợ COD, chuyển khoản ngân hàng, cổng thanh toán online (nếu có).</li>

                <li>Nếu không thể giao hàng vì nguyên nhân khách quan, AutoSensor Việt Nam có quyền hủy đơn.</li>

            </ul>

        </section>

        <section class="policy-section">

            <h2>6. Quyền sở hữu trí tuệ</h2>

            <ul class="policy-list">

                <li>Tất cả hình ảnh, thiết kế, nội dung, logo thuộc sở hữu của AutoSensor Việt Nam.</li>

                <li>Không sử dụng/sao chép cho mục đích thương mại khi chưa có văn bản đồng ý.</li>

                <li>Hành vi vi phạm sẽ được xử lý theo pháp luật Việt Nam.</li>

            </ul>

        </section>

        <section class="policy-section">

            <h2>7. Liên kết website khác</h2>

            <p>Website có thể liên kết đến các kênh bán hàng khác. AutoSensor Việt Nam không chịu trách nhiệm về nội dung và chất lượng dịch vụ của các bên thứ ba.</p>

        </section>

        <section class="policy-section">

            <h2>8. Chính sách bảo mật</h2>

            <p>Mọi thông tin cá nhân được xử lý theo <a href="{{ route('client.policy.privacy') }}">Chính sách bảo mật</a> của chúng tôi.</p>

        </section>

        <section class="policy-section">

            <h2>9. Giới hạn trách nhiệm</h2>

            <ul class="policy-list">

                <li>Không chịu trách nhiệm đối với thiệt hại do lỗi mạng, hệ thống hoặc yếu tố bất khả kháng.</li>

                <li>Không chịu trách nhiệm khi khách hàng chia sẻ tài khoản cho người khác.</li>

                <li>Tranh chấp sẽ giải quyết theo pháp luật Việt Nam.</li>

            </ul>

        </section>

        <section class="policy-section">

            <h2>10. Thông tin liên hệ</h2>

            <ul class="policy-list">

                <li>📞 Hotline: <a href="tel:{{ $settings->contact_phone ?? '' }}">{{ $settings->contact_phone ?? '' }}</a></li>

                <li>✉ Email: <a href="mailto:{{ $settings->contact_email ?? '' }}">{{ $settings->contact_email ?? '' }}</a></li>

                <li>🌐 Website: <a href="{{ $settings->site_url ?? '#' }}">{{ $settings->site_name ?? 'AutoSensor Việt Nam' }}</a></li>

            </ul>

        </section>

        <p class="policy-updated">

            Điều khoản sử dụng có hiệu lực từ 01/11/2025 và sẽ được cập nhật định kỳ để phù hợp pháp luật cũng như quyền lợi khách hàng.

        </p>

    </div>

@endsection

