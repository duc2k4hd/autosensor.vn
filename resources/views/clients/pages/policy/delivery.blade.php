@extends('clients.layouts.master')

@section('title', 'Chính sách giao hàng - ' . ($settings->site_name ?? ($settings->subname ?? 'AutoSensor Việt Nam')))

@section('head')

    <meta name="description"
        content="Chính sách giao hàng {{ $settings->site_name ?? 'AutoSensor Việt Nam' }} - thông tin phí vận chuyển, thời gian giao và hỗ trợ đổi trả thiết bị tự động hóa trên toàn quốc.">

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
        $pageTitle = 'Chính sách giao hàng';
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

                <span class="policy-tag">Delivery Policy</span>

                <span class="policy-tag">Nationwide shipping</span>

            </div>

            <h1>Chính sách giao hàng</h1>

            <p>

                <strong>AutoSensor Việt Nam</strong> phối hợp cùng các đối tác vận chuyển cao cấp để đảm bảo từng kiện hàng thiết bị tự động hóa được giao

                nhanh, an toàn và minh bạch trạng thái. Bạn luôn được theo dõi hành trình giao nhận theo thời gian thực và

                hỗ trợ 24/7.

            </p>

            <div class="policy-meta">

                <div class="policy-meta-card">

                    <span>Phủ sóng</span>

                    <strong>Toàn quốc</strong>

                </div>

                <div class="policy-meta-card">

                    <span>Miễn phí ship</span>

                    <strong>Từ 500.000đ</strong>

                </div>

                <div class="policy-meta-card">

                    <span>Hỗ trợ đổi hàng</span>

                    <strong>15 ngày</strong>

                </div>

            </div>

        </section>

        <section class="policy-section">

            <h2>Phương thức giao hàng</h2>

            <div class="policy-grid">

                <div class="policy-card">

                    <strong>COD toàn quốc</strong>

                    <p>Nhận hàng – kiểm tra thiết bị – thanh toán linh hoạt. Áp dụng cho mọi tỉnh thành.</p>

                </div>

                <div class="policy-card">

                    <strong>Vận chuyển tiêu chuẩn</strong>

                    <p>Kết nối các hãng GHN, GHTK, Viettel Post với bảo hiểm đơn hàng đầy đủ.</p>

                </div>

                <div class="policy-card">

                    <strong>Kiểm hàng trước khi trả</strong>

                    <p>Được mở niêm phong, kiểm tra thiết bị trước khi xác nhận thanh toán với shipper.</p>

                </div>

                <div class="policy-card">

                    <strong>Đơn khẩn</strong>

                    <p>Ưu tiên xử lý trong ngày với đơn đặt trước 16h tại các thành phố lớn.</p>

                </div>

            </div>

        </section>

        <section class="policy-section">

            <h2>Phí vận chuyển</h2>

            <ul class="policy-list">

                <li>Miễn phí ship trên toàn quốc cho đơn từ <strong>500.000đ</strong>.</li>

                <li>Đơn dưới 500.000đ áp dụng phí cố định từ <strong>20.000đ – 50.000đ</strong> tùy kích thước thiết bị và địa điểm.</li>

                <li>Trong các dịp khuyến mãi đặc biệt, phí ship có thể điều chỉnh theo thông báo tại trang thanh toán.</li>

            </ul>

            <div class="policy-note">

                Phí vận chuyển sẽ được hiển thị rõ ràng ở bước Checkout để khách hàng chủ động kiểm soát chi phí.

            </div>

        </section>

        <section class="policy-section">

            <h2>Thời gian giao hàng</h2>

            <div class="policy-timeline">

                <div class="policy-timeline-item">

                    <strong>Thành phố lớn (Hà Nội, TP.HCM, Đà Nẵng):</strong> 1 – 2 ngày làm việc (giao trong ngày nếu đặt trước 16h).

                </div>

                <div class="policy-timeline-item">

                    <strong>Khu vực lân cận & ngoại tỉnh:</strong> 2 – 5 ngày làm việc tuỳ tuyến vận chuyển.

                </div>

                <div class="policy-timeline-item">

                    <strong>Khu vực xa, hải đảo:</strong> 5 – 7 ngày làm việc.

                </div>

            </div>

            <div class="policy-note">

                Thời gian giao hàng có thể thay đổi vì yếu tố thời tiết, lễ Tết, giãn cách hoặc địa chỉ khó tìm. Bộ phận

                CSKH sẽ chủ động liên hệ khi có phát sinh chậm trễ.

            </div>

        </section>

        <section class="policy-section">

            <h2>Đóng gói & bảo quản</h2>

            <ul class="policy-list">

                <li>Thiết bị được đóng gói chống sốc, chống ẩm kỹ lưỡng.</li>

                <li>Bao bì, phụ kiện được bọc kỹ để tránh vỡ nứt trong quá trình vận chuyển.</li>

                <li>Đảm bảo thiết bị không bị hư hỏng, va đập trong quá trình vận chuyển.</li>

            </ul>

        </section>

        <section class="policy-section">

            <h2>Đổi hàng & xử lý sự cố</h2>

            <ul class="policy-list">

                <li>Đổi sản phẩm trong vòng <strong>15 ngày</strong> từ khi nhận hàng.</li>

                <li>Miễn phí đổi mới nếu lỗi phát sinh từ AutoSensor Việt Nam hoặc đơn vị vận chuyển.</li>

                <li>Trường hợp thiếu hàng, sai mẫu, thiết bị hư hỏng hoặc bao bì vỡ khi vận chuyển hãy liên hệ ngay để được xử lý trong 24h.</li>

            </ul>

        </section>

        <section class="policy-contact">

            <h3>Liên hệ hỗ trợ</h3>

            <p>📞 Hotline: <a href="tel:{{ $settings->contact_phone ?? '' }}">{{ $settings->contact_phone ?? '' }}</a></p>

            <p>✉ Email: <a href="mailto:{{ $settings->contact_email ?? '' }}">{{ $settings->contact_email ?? '' }}</a></p>

            <p>🌐 Website: <a href="{{ $settings->site_url ?? '#' }}">{{ $settings->site_name ?? 'AutoSensor Việt Nam' }}</a></p>

        </section>

        <p class="policy-updated">

            Chính sách giao hàng có hiệu lực từ ngày 01/11/2025 và sẽ được cập nhật định kỳ để nâng cao trải nghiệm của bạn.

        </p>

    </div>

@endsection
