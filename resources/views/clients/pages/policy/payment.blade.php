@extends('clients.layouts.master')

@section('title', 'Chính sách thanh toán - ' . ($settings->site_name ?? $settings->subname ?? 'AutoSensor Việt Nam'))

@section('head')

    <meta name="description"

          content="Chính sách thanh toán {{ $settings->site_name ?? 'AutoSensor Việt Nam' }} - hướng dẫn COD, chuyển khoản, QR banking và quy trình hoàn tiền minh bạch.">

    <link rel="canonical" href="{{ url()->current() }}">

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

                <span class="policy-tag">Payment Policy</span>

                <span class="policy-tag">Secure checkout</span>

            </div>

            <h1>Chính sách thanh toán</h1>

            <p>

                AutoSensor Việt Nam mang đến trải nghiệm thanh toán đa phương thức, minh bạch và bảo mật theo chuẩn ngân hàng. Từ

                cửa hàng đến online, bạn luôn có thể chọn phương án thuận tiện nhất mà vẫn đảm bảo an tâm dữ liệu.

            </p>

            <div class="policy-meta">

                <div class="policy-meta-card">

                    <span>Phương thức hỗ trợ</span>

                    <strong>03+</strong>

                </div>

                <div class="policy-meta-card">

                    <span>Thời gian hoàn tiền</span>

                    <strong>1 - 3 ngày</strong>

                </div>

                <div class="policy-meta-card">

                    <span>Hỗ trợ trực tiếp</span>

                    <strong>07 ngày/tuần</strong>

                </div>

            </div>

        </section>

        <section class="policy-section">

            <h2>Hình thức thanh toán</h2>

            <div class="policy-grid">

                <div class="policy-card">

                    <strong>Thanh toán khi nhận hàng (COD)</strong>

                    <p>Áp dụng toàn quốc. Thanh toán cho shipper sau khi kiểm tra sản phẩm.</p>

                </div>

                <div class="policy-card">

                    <strong>Chuyển khoản ngân hàng</strong>

                    <p>Tối ưu cho khách muốn xử lý đơn nhanh hoặc đặt số lượng lớn. Nội dung chuyển khoản: "Tên + Số điện thoại + Mã đơn hàng".</p>

                </div>

                <div class="policy-card">

                    <strong>QR Banking</strong>

                    <p>Quét mã bằng mọi ứng dụng ngân hàng. Hệ thống tự điền số tài khoản và nội dung, hạn chế sai sót.</p>

                </div>

            </div>

        </section>

        <section class="policy-section">

            <h2>Thanh toán tại cửa hàng</h2>

            <ul class="policy-list">

                <li>Tiền mặt tại quầy.</li>

                <li>Chuyển khoản/ quét QR trực tiếp với nhân viên.</li>

                <li>Ví điện tử (theo chi nhánh hỗ trợ).</li>

            </ul>

        </section>

        <section class="policy-section">

            <h2>Quy trình thanh toán đơn online</h2>

            <div class="policy-timeline">

                <div class="policy-timeline-item"><strong>Bước 1:</strong> Đặt hàng trên website hoặc fanpage.</div>

                <div class="policy-timeline-item"><strong>Bước 2:</strong> CSKH xác nhận qua SMS/Call/Zalo.</div>

                <div class="policy-timeline-item"><strong>Bước 3:</strong> Chọn hình thức thanh toán mong muốn.</div>

                <div class="policy-timeline-item"><strong>Bước 4:</strong> Đơn hàng được đóng gói và bàn giao cho đơn vị vận chuyển.</div>

                <div class="policy-timeline-item"><strong>Bước 5:</strong> Khách kiểm tra sản phẩm và thanh toán (nếu COD).</div>

            </div>

        </section>

        <section class="policy-section">

            <h2>Chính sách hoàn tiền</h2>

            <ul class="policy-list">

                <li>Hoàn 100% với khách đã thanh toán trước khi đơn gặp sự cố (hết hàng, lỗi sản phẩm, đổi trả hợp lệ).</li>

                <li>Tiền sẽ chuyển về tài khoản ngân hàng mà khách cung cấp.</li>

                <li>Thời gian xử lý: <strong>1 – 3 ngày làm việc</strong>.</li>

                <li>Không hoàn qua COD hoặc ví điện tử khi chưa phát sinh giao dịch tương ứng.</li>

            </ul>

        </section>

        <section class="policy-section">

            <h2>Bảo mật thông tin thanh toán</h2>

            <ul class="policy-list">

                <li>Mã hóa dữ liệu theo chuẩn ngân hàng, hạn chế truy cập trái phép.</li>

                <li>Không chia sẻ thông tin cho bên thứ ba nếu chưa được sự đồng ý của khách hàng.</li>

                <li>Sử dụng cổng thanh toán có chứng nhận bảo mật cao.</li>

            </ul>

        </section>

        <section class="policy-contact">

            <h3>Liên hệ hỗ trợ thanh toán</h3>

            <p>📞 Hotline: <a href="tel:{{ $settings->contact_phone ?? '' }}">{{ $settings->contact_phone ?? '' }}</a></p>

            <p>✉ Email: <a href="mailto:{{ $settings->contact_email ?? '' }}">{{ $settings->contact_email ?? '' }}</a></p>

            <p>🌐 Website: <a href="{{ $settings->site_url ?? '#' }}">{{ $settings->site_name ?? 'AutoSensor Việt Nam' }}</a></p>

        </section>

        <p class="policy-updated">

            Chính sách thanh toán có hiệu lực từ 01/11/2025 và sẽ được cập nhật để nâng cao trải nghiệm của bạn.

        </p>

    </div>

@endsection

