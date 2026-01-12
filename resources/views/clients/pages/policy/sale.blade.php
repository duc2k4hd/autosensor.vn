@extends('clients.layouts.master')

@section('title', 'Chính sách bán hàng - ' . ($settings->site_name ?? $settings->subname ?? 'AutoSensor Việt Nam'))

@section('head')

    <meta name="description"

          content="Chính sách bán hàng {{ $settings->site_name ?? 'AutoSensor Việt Nam' }} - cam kết chất lượng thiết bị tự động hóa, giao hàng, ưu đãi và chăm sóc khách hàng chuyên nghiệp.">

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
        $pageTitle = 'Chính sách bán hàng';
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

                <span class="policy-tag">Sales Policy</span>

                <span class="policy-tag">Premium service</span>

            </div>

            <h1>Chính sách bán hàng</h1>

            <p>

                <strong>AutoSensor Việt Nam</strong> cam kết mang đến trải nghiệm mua sắm đẳng cấp: thiết bị tự động hóa chất lượng cao,

                thông tin minh bạch, dịch vụ tư vấn tận tâm và bảo chứng hậu mãi rõ ràng trên mọi kênh bán hàng.

            </p>

            <div class="policy-meta">

                <div class="policy-meta-card">

                    <span>Cam kết chất lượng</span>

                    <strong>100% thiết bị chính hãng</strong>

                </div>

                    <div class="policy-meta-card">

                    <span>Miễn phí giao hàng</span>

                    <strong>Từ 500.000đ</strong>

                </div>

                <div class="policy-meta-card">

                    <span>CSKH</span>

                    <strong>24/7</strong>

                </div>

            </div>

        </section>

        <section class="policy-section">

            <h2>Cam kết chất lượng thiết bị</h2>

            <ul class="policy-list">

                <li>Thiết bị được nhập khẩu chính hãng, đảm bảo chất lượng, không hàng giả, hàng nhái.</li>

                <li>Hình ảnh hiển thị khớp 95–100% với thiết bị thực tế (model, thông số kỹ thuật, kích thước).</li>

                <li>Thiết bị được kiểm định chất lượng, có nguồn gốc rõ ràng, không bán hàng lỗi, hàng kém chất lượng.</li>

                <li>Tư vấn chọn thiết bị phù hợp với ứng dụng và điều kiện môi trường làm việc của khách hàng.</li>

            </ul>

        </section>

        <section class="policy-section">

            <h2>Giao hàng & chăm sóc đơn</h2>

            <p><strong>AutoSensor Việt Nam</strong> giao hàng nhanh – an toàn trên toàn quốc.</p>

            <ul class="policy-list">

                <li><strong>Hà Nội – TP.HCM:</strong> 1 – 2 ngày.</li>

                <li><strong>Các tỉnh khác:</strong> 2 – 5 ngày.</li>

                <li>Cho phép kiểm hàng trước thanh toán (tùy khu vực hỗ trợ COD).</li>

                <li>Đóng gói chống sốc, chống ẩm kỹ lưỡng để đảm bảo thiết bị không bị hư hại.</li>

                <li>Miễn phí giao hàng cho đơn từ <strong>500.000đ</strong>.</li>

            </ul>

            <div class="policy-note">

                Phí ship dao động 20.000 – 50.000đ tùy tỉnh và kích thước thiết bị, hiển thị rõ ràng ở bước Checkout.

            </div>

        </section>

        <section class="policy-section">

            <h2>Ưu đãi & quyền lợi khách hàng</h2>

            <div class="policy-grid">

                <div class="policy-card">

                    <strong>Voucher khách hàng thân thiết</strong>

                    <p>Tặng mã giảm giá cho đơn kế tiếp và chương trình tích điểm đổi quà.</p>

                </div>

                <div class="policy-card">

                    <strong>Sinh nhật & khách hàng VIP</strong>

                    <p>Ưu đãi đặc biệt theo hạng thành viên và dịp sinh nhật, tặng kèm phụ kiện lắp đặt hoặc tài liệu kỹ thuật.</p>

                </div>

                <div class="policy-card">

                    <strong>Sự kiện mùa lễ & khuyến mãi</strong>

                    <p>Voucher riêng cho Tết, lễ hội, và các dịp đặc biệt về tự động hóa công nghiệp.</p>

                </div>

            </div>

        </section>

        <section class="policy-section">

            <h2>Tư vấn & hỗ trợ</h2>

            <ul class="policy-list">

                <li>Tư vấn chọn thiết bị phù hợp với ứng dụng và môi trường làm việc.</li>

                <li>Hỗ trợ xem thiết bị trực tiếp tại showroom, đổi thiết bị tương đương nếu không hài lòng.</li>

                <li>Giải đáp về cách lắp đặt, cài đặt thông số, vận hành và bảo trì thiết bị.</li>

                <li>Xử lý khiếu nại nhanh chóng, chuyên nghiệp.</li>

            </ul>

        </section>

        <section class="policy-section">

            <h2>Chính sách đổi trả</h2>

            <ul class="policy-list">

                <li>Đổi hàng trong vòng <strong>15 ngày</strong>.</li>

                <li>Thiết bị còn nguyên vẹn, chưa lắp đặt, không hư hỏng, không lỗi kỹ thuật.</li>

                <li>Đổi thiết bị cùng hoặc cao hơn giá trị (cùng model hoặc model tương đương).</li>

                <li>Không hoàn tiền trừ trường hợp lỗi kỹ thuật.</li>

                <li>Không áp dụng cho thiết bị giảm giá trên 30%, thiết bị đã lắp đặt, phụ kiện đã sử dụng.</li>

            </ul>

            <p style="margin-top: 12px; font-weight: 600;">Đổi do lỗi nhà cung cấp:</p>

            <ul class="policy-list">

                <li>Thiết bị hư hỏng do vận chuyển, bao bì vỡ, sai model, lỗi kỹ thuật phát hiện sớm.</li>

                <li>Đổi mới 100% trong 15 ngày và miễn phí vận chuyển.</li>

            </ul>

        </section>

        <section class="policy-contact">

            <h3>Liên hệ hỗ trợ</h3>

            <p>📞 Hotline: <a href="tel:{{ $settings->contact_phone ?? '' }}">{{ $settings->contact_phone ?? '' }}</a></p>

            <p>✉ Email: <a href="mailto:{{ $settings->contact_email ?? '' }}">{{ $settings->contact_email ?? '' }}</a></p>

            <p>🌐 Website: <a href="{{ $settings->site_url ?? '#' }}">{{ $settings->site_name ?? 'AutoSensor Việt Nam' }}</a></p>

            <p>🛒 Fanpage: <a href="{{ $settings->facebook_link ?? '#' }}" target="_blank">Facebook AutoSensor Việt Nam</a></p>

        </section>

        <p class="policy-updated">

            Cảm ơn bạn đã đồng hành cùng AutoSensor Việt Nam. Chính sách bán hàng hiệu lực từ 01/11/2025 và sẽ tiếp tục được cập nhật

            để nâng cao chất lượng dịch vụ.

        </p>

    </div>

@endsection
