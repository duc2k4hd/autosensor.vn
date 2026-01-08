@extends('clients.layouts.master')

@section('title', 'Giới thiệu ' .($settings->site_name ?? $settings->site_name ?? 'AutoSensor Việt Nam'). ' | Thuộc Hải Phòng Tech – Giải pháp tự động hóa công nghiệp toàn diện')

@section('head')
    <meta name="robots" content="index, follow" />
    <meta name="description"
        content="{{ $settings->site_name ?? $settings->site_name ?? 'AutoSensor Việt Nam' }} là thương hiệu thuộc Hải Phòng Tech (haiphongtech.vn), tập trung vào cảm biến, PLC, HMI, biến tần, servo, encoder và các giải pháp tự động hóa công nghiệp trọn gói cho doanh nghiệp Việt Nam." />
    <meta property="og:title" content="Giới thiệu {{ $settings->site_name ?? $settings->site_name ?? 'AutoSensor Việt Nam' }} – Thương hiệu tự động hóa thuộc Hải Phòng Tech" />
    <meta property="og:description"
        content="Tìm hiểu về {{ $settings->site_name ?? $settings->site_name ?? 'AutoSensor Việt Nam' }}, thương hiệu thuộc hệ sinh thái Hải Phòng Tech: tư vấn – cung cấp – triển khai giải pháp tự động hóa công nghiệp, đồng hành cùng nhà máy Việt Nam trên hành trình chuyển đổi số." />
    <meta property="og:image"
        content="{{ asset('clients/assets/img/business/' . ($settings->site_banner ?? $settings->site_logo ?? 'no-image.webp')) }}" />
    <meta property="og:url" content="{{ route('client.introduction.index') }}" />
    <link rel="canonical" href="{{ route('client.introduction.index') }}">
@endsection

@push('js_page')
    <script defer src="{{ asset('clients/assets/js/main.js') }}"></script>
@endpush

@section('content')
    <section class="autosensor-hero">
        <div class="autosensor-hero__content">
            <p class="eyebrow">{{ $settings->site_name ?? $settings->site_name ?? 'AutoSensor Việt Nam' }} • Hải Phòng Tech Ecosystem</p>
            <h1>{{ ($settings->site_name ?? 'AutoSensor Việt Nam') . ' – Thương hiệu tự động hóa thuộc Hải Phòng Tech' }}</h1>
            <p>
                Từ cảm biến công nghiệp, PLC, HMI đến biến tần, servo, encoder và các giải pháp điều khiển thông minh,
                {{ $settings->site_name ?? $settings->site_name ?? 'AutoSensor Việt Nam' }} thuộc hệ sinh thái
                <strong><a href="https://haiphongtech.vn" target="_blank" rel="noopener">Hải Phòng Tech</a> (haiphongtech.vn)</strong> đồng hành cùng doanh nghiệp tối ưu dây chuyền,
                giảm downtime và chuẩn hóa quy trình sản xuất theo định hướng Công nghiệp 4.0.
            </p>
            <p style="font-size:16px; line-height:1.8; color: white; margin-bottom:16px; margin-top:16px;">
                <strong style="color:#1b7f5a;">{{ $settings->site_name ?? 'AutoSensor Việt Nam' }}</strong> là thương hiệu
                chuyên về thiết bị và giải pháp tự động hóa công nghiệp do <strong>Hải Phòng Tech</strong> vận hành và phát triển.
                Dựa trên nền tảng công nghệ và kinh nghiệm triển khai thực tế tại nhiều nhà máy, chúng tôi không chỉ bán thiết bị,
                mà còn tư vấn bài bản từ khâu khảo sát, lựa chọn cấu hình, đến vận hành và tối ưu hệ thống.
            </p>
            
            <p style="font-size:16px; line-height:1.8; color: white; margin-bottom:16px;">
                Hệ sinh thái sản phẩm của chúng tôi bao gồm:
                <strong>cảm biến công nghiệp</strong> (quang, tiệm cận, vùng, áp suất, nhiệt độ), 
                <strong>PLC &amp; HMI</strong>, <strong>biến tần, servo, encoder</strong>,
                <strong>rơ le, nguồn công nghiệp, thiết bị an toàn</strong> và nhiều thiết bị điều khiển khác.
                Đội ngũ kỹ sư của Hải Phòng Tech phụ trách tư vấn, cấu hình, lập trình và đồng hành kỹ thuật lâu dài với khách hàng.
            </p>
            
            <p style="font-size:16px; line-height:1.8; color: white; margin-bottom:16px;">
                Website chính thức của <strong>{{ $settings->site_name ?? 'AutoSensor Việt Nam' }}</strong> hiện đang hoạt động tại:
                <a href="{{ $settings->site_url ?? '#' }}" 
                   target="_blank" 
                   rel="noopener"
                   style="color:#1b7f5a; font-weight:600; text-decoration:none;">
                    {{ $settings->site_url ?? 'autosensor.vn' }}
                </a>
            </p>
            
            <p style="font-size:16px; line-height:1.8; color: white;">
                Thuộc hệ sinh thái <strong>Hải Phòng Tech</strong>, {{ $settings->site_name ?? 'AutoSensor Việt Nam' }} kế thừa
                văn hóa làm việc <strong>“Thẳng – Tín – Tinh – Tâm”</strong>: tư vấn trung thực, giải pháp đúng nhu cầu,
                sản phẩm chính hãng, dịch vụ nhanh và rõ ràng. Mục tiêu cuối cùng của chúng tôi là giúp doanh nghiệp Việt Nam
                làm chủ công nghệ tự động hóa, vận hành ổn định và tăng trưởng bền vững.
            </p>            
            <div class="hero-actions">
                <a class="btn primary" href="{{ route('client.shop.index') }}">Khám phá sản phẩm</a>
                <a class="btn ghost" href="{{ route('client.contact.index') }}">Đặt lịch tư vấn</a>
            </div>
            <ul class="hero-stats">
                <li>
                    <strong>500+</strong>
                    <span>Dự án tự động hóa đã triển khai</span>
                </li>
                <li>
                    <strong>24h</strong>
                    <span>Hỗ trợ kỹ thuật & tư vấn toàn quốc</span>
                </li>
                <li>
                    <strong>50+</strong>
                    <span>Thương hiệu thiết bị đối tác</span>
                </li>
            </ul>
        </div>
        <div class="autosensor-hero__media">
            <img src="{{ asset('clients/assets/img/business/' . ($settings->site_banner ?? $settings->site_logo ?? 'no-image.webp')) }}"
                alt="Không gian trưng bày {{ $settings->site_name ?? $settings->site_name ?? 'AutoSensor Việt Nam' }}" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('clients/assets/img/clothes/no-image.webp') }}'" />
            <div class="media-badge">
                <p>INDUSTRIAL AUTOMATION</p>
                <h4>Tư vấn - Cung cấp - Lắp đặt</h4>
                <span>Đội ngũ kỹ sư tự động hóa giàu kinh nghiệm</span>
            </div>
        </div>
    </section>

    <section class="autosensor-panels">
        <article class="panel highlight">
            <p class="eyebrow">Sứ mệnh</p>
            <h3>Nâng cao hiệu quả sản xuất công nghiệp Việt Nam</h3>
            <p>Đưa công nghệ tự động hóa trở thành động lực phát triển, giúp doanh nghiệp nâng cao năng suất và chất lượng sản phẩm.</p>
        </article>
        <article class="panel">
            <p class="eyebrow">Tầm nhìn</p>
            <h3>Trở thành đối tác tự động hóa chiến lược hàng đầu</h3>
            <p>Kết nối chuỗi cung ứng thiết bị công nghiệp chất lượng cao, dịch vụ kỹ thuật chuyên nghiệp và giải pháp tự động hóa toàn diện.</p>
        </article>
        <article class="panel">
            <p class="eyebrow">Giá trị cốt lõi</p>
            <ul>
                <li>Chất lượng sản phẩm chính hãng, đảm bảo CO/CQ.</li>
                <li>Tư vấn kỹ thuật chuyên sâu, hỗ trợ 24/7.</li>
                <li>Giá cả cạnh tranh, giao hàng nhanh chóng.</li>
            </ul>
        </article>
    </section>

    <section class="autosensor-journey">
        <div class="journey-content">
            <h2>Hành trình {{ $settings->site_name ?? $settings->site_name ?? 'AutoSensor Việt Nam' }} cùng Hải Phòng Tech</h2>
            <div class="timeline">
                <div class="timeline-item">
                    <span class="year">2018</span>
                    <p>Đội ngũ sáng lập Hải Phòng Tech bắt đầu tham gia các dự án tủ điện, điều khiển và tự động hóa tại khu vực phía Bắc.</p>
                </div>
                <div class="timeline-item">
                    <span class="year">2021</span>
                    <p>Ra mắt thương hiệu {{ $settings->site_name ?? 'AutoSensor Việt Nam' }}, tập trung vào cảm biến và thiết bị tự động hóa công nghiệp, xây dựng kho hàng và hệ thống dữ liệu sản phẩm.</p>
                </div>
                <div class="timeline-item">
                    <span class="year">2024</span>
                    <p>Hải Phòng Tech mở rộng hợp tác với nhiều hãng thiết bị tự động hóa quốc tế, tối ưu chuỗi cung ứng và dịch vụ kỹ thuật cho khách hàng trên toàn quốc.</p>
                </div>
                <div class="timeline-item">
                    <span class="year">2025</span>
                    <p>{{ $settings->site_name ?? 'AutoSensor Việt Nam' }} đẩy mạnh các giải pháp nhà máy thông minh, kết nối IoT, giám sát năng lượng và tối ưu vận hành cho doanh nghiệp sản xuất.</p>
                </div>
            </div>
        </div>
        <div class="journey-media">
            <img src="{{ asset('clients/assets/img/business/' . ($settings->site_banner ?? $settings->site_logo ?? 'no-image.webp')) }}" alt="Hành trình phát triển tự động hóa công nghiệp"
                loading="lazy" onerror="this.onerror=null;this.src='{{ asset('clients/assets/img/clothes/no-image.webp') }}'">
            <div class="media-caption">
                <strong>Technical Lab</strong>
                <span>Nghiên cứu và phát triển giải pháp tự động hóa tùy chỉnh theo nhu cầu thực tế của doanh nghiệp.</span>
            </div>
        </div>
    </section>

    <section class="autosensor-grid">
        <article>
            <h3>Tư vấn giải pháp tự động hóa</h3>
            <p>Khảo sát, thiết kế hệ thống tự động hóa chi tiết, lựa chọn thiết bị phù hợp với quy trình sản xuất.</p>
        </article>
        <article>
            <h3>Cảm biến công nghiệp</h3>
            <p>Cảm biến quang, tiệm cận, vùng, áp suất, nhiệt độ, độ ẩm với độ chính xác cao, chịu được môi trường khắc nghiệt.</p>
        </article>
        <article>
            <h3>PLC & HMI</h3>
            <p>Hệ thống điều khiển lập trình và giao diện người máy đa dạng thương hiệu, hỗ trợ lập trình và tích hợp.</p>
        </article>
        <article>
            <h3>Bảo hành & hỗ trợ kỹ thuật</h3>
            <p>Bảo hành chính hãng, hỗ trợ kỹ thuật 24/7, đào tạo vận hành và bảo trì thiết bị định kỳ.</p>
        </article>
    </section>

    <section class="autosensor-network">
        <div class="network-card">
            <p class="eyebrow">Hệ sinh thái tự động hóa</p>
            <h2>Showroom & trung tâm kỹ thuật</h2>
            <ul>
                <li>Trung tâm kỹ thuật tại TP.HCM, Hà Nội, Đà Nẵng.</li>
                <li>Phòng demo thiết bị, tư vấn giải pháp tự động hóa.</li>
                <li>Khu trải nghiệm thiết bị mới, đào tạo vận hành và bảo trì.</li>
            </ul>
        </div>
        <div class="network-card gradient">
            <h3>Kết nối đa kênh</h3>
            <div class="channel">
                <span>Website</span>
                <a href="{{ $settings->site_url ?? '#' }}" target="_blank">{{ $settings->site_url ?? 'autosensor.vn' }}</a>
            </div>
            <div class="channel">
                <span>Kênh bán hàng</span>
                <p>Website chính thức, Hotline, Email, Zalo Business</p>
            </div>
            <div class="channel">
                <span>Giải pháp doanh nghiệp</span>
                <p>Tư vấn thiết kế hệ thống, cung cấp thiết bị, lắp đặt và bảo hành trọn gói.</p>
            </div>
        </div>
    </section>

    <section class="autosensor-impact">
        <div class="impact-card">
            <p class="eyebrow">Cam kết chất lượng</p>
            <h3>Trách nhiệm với khách hàng</h3>
            <ul>
                <li>Sản phẩm chính hãng 100%, có chứng nhận CO/CQ đầy đủ.</li>
                <li>Hợp tác với các nhà sản xuất uy tín, đảm bảo nguồn gốc xuất xứ rõ ràng.</li>
                <li>Chương trình hỗ trợ kỹ thuật miễn phí cho các dự án tự động hóa.</li>
            </ul>
        </div>
        <div class="impact-card list">
            <h3>Dịch vụ nổi bật</h3>
            <div class="impact-item">
                <strong>Industrial Automation</strong>
                <span>Thiết kế và triển khai hệ thống tự động hóa cho nhà máy sản xuất.</span>
            </div>
            <div class="impact-item">
                <strong>Smart Factory</strong>
                <span>Giải pháp nhà máy thông minh với IoT và công nghệ 4.0.</span>
            </div>
            <div class="impact-item">
                <strong>Technical Support</strong>
                <span>Hỗ trợ kỹ thuật 24/7, đào tạo vận hành và bảo trì thiết bị.</span>
            </div>
        </div>
    </section>

    <section class="autosensor-map">
        <div class="map-info">
            <p class="eyebrow">Trụ sở chính</p>
            <h2>{{ $settings->contact_address ?? 'Xóm 3 - Xã Hà Đông - Thành Phố Hải Phòng' }}</h2>
            <p>
                Đặt lịch trước để được tư vấn giải pháp tự động hóa, khảo sát hiện trạng và báo giá thiết bị phù hợp với nhu cầu.
            </p>
            <div class="contact">
                <span>Hotline</span>
                <a href="tel:{{ $settings->contact_phone ?? '' }}">{{ $settings->contact_phone ?? '0909 988 889' }}</a>
            </div>
            <div class="author">
                <span>Author</span>
                <a href="{{ $settings->facebook_link ?? '#' }}" target="_blank">Nguyễn Minh Đức</a>
            </div>
            <div class="contact">
                <span>Email</span>
                <a href="mailto:{{ $settings->contact_email ?? '' }}">{{ $settings->contact_email ?? 'info@autosensor.vn' }}</a>
            </div>
        </div>
        <div class="map-frame">
            <iframe width="100%" height="100%"
                src="{{ $settings->source_map ?? 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3974.074551510752!2d106.45132018840634!3d20.838643175148697!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x313589331558271b%3A0x3a5a70f9ba3d5718!2zTmjDoCBWxrDhu51uIFRo4bqvbmcgVGjhuq9t!5e1!3m2!1svi!2s!4v1766629712920!5m2!1svi!2s' }}"
                loading="lazy" allowfullscreen></iframe>
        </div>
    </section>

    <section class="autosensor-cta">
        <div class="cta-card">
            <div>
                <p class="eyebrow">Kết nối cùng {{ $settings->site_name ?? $settings->site_name ?? 'AutoSensor Việt Nam' }}</p>
                <h3>Đăng ký nhận bản tin kỹ thuật & mời tham dự workshop tự động hóa công nghiệp</h3>
            </div>
            <a class="btn secondary" href="{{ route('client.contact.index') }}">Đăng ký ngay</a>
        </div>
    </section>

    

    <div class="autosensor-products">
        @include('clients.templates.product_new')
    </div>

    <style>
        :root {
            --intro-dark: #05170f;
            --intro-green: #0f5132;
            --intro-border: rgba(0, 78, 56, 0.12);
            --intro-light: #f2fff8;
        }

        .eyebrow {
            text-transform: uppercase;
            letter-spacing: 0.28em;
            font-size: 11px;
            color: #1fe3a8;
            margin-bottom: 12px;
        }

        .autosensor-hero {
            width: 92%;
            margin: 40px auto;
            padding: 48px;
            border-radius: 36px;
            color: #f6fffb;
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(0, 0.9fr);
            gap: 32px;
            background: radial-gradient(circle at top right, rgba(18, 255, 197, 0.35), transparent 55%),
                linear-gradient(135deg, #062a18, #0d2a1c 60%, #07110a);
        }

        .autosensor-hero__content h1 {
            font-size: clamp(36px, 4vw, 58px);
            line-height: 1.15;
            margin-bottom: 16px;
        }

        .autosensor-hero__content p {
            color: rgba(255, 255, 255, 0.8);
        }

        .hero-actions {
            margin: 26px 0;
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
        }

        .btn {
            border-radius: 999px;
            padding: 14px 26px;
            font-weight: 600;
            border: 1px solid transparent;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.25s ease;
        }

        .btn.primary {
            background: linear-gradient(90deg, #1fe3a8, #0bbf82);
            color: #052418;
        }

        .btn.ghost {
            border-color: rgba(255, 255, 255, 0.35);
            color: #f6fffb;
        }

        .btn.secondary {
            background: #052414;
            color: #dfffea;
            border-color: rgba(255, 255, 255, 0.1);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.25);
        }

        .hero-stats {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 14px;
        }

        .hero-stats li {
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 18px;
            padding: 16px;
            backdrop-filter: blur(6px);
        }

        .hero-stats strong {
            display: block;
            font-size: 28px;
        }

        .autosensor-hero__media {
            position: relative;
        }

        .autosensor-hero__media img {
            width: 100%;
            height: 100%;
            max-height: 420px;
            border-radius: 32px;
            object-fit: cover;
        }

        .media-badge {
            position: absolute;
            bottom: 20px;
            right: 20px;
            padding: 18px;
            background: rgba(5, 12, 10, 0.75);
            border-radius: 18px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            max-width: 80%;
        }

        .autosensor-panels {
            width: 92%;
            margin: 35px auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
        }

        .panel {
            padding: 28px;
            border-radius: 26px;
            border: 1px solid var(--intro-border);
            background: #fff;
            box-shadow: 0 20px 40px rgba(5, 12, 10, 0.08);
        }

        .panel.highlight {
            background: linear-gradient(130deg, #041c12, #093124);
            color: #e9fff6;
            border: none;
        }

        .panel ul {
            padding-left: 18px;
            margin: 0;
            color: #4b5d57;
            line-height: 1.6;
        }

        .panel.highlight ul {
            color: #c7ffeb;
        }

        .autosensor-journey {
            width: 92%;
            margin: 40px auto;
            border-radius: 34px;
            overflow: hidden;
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(0, 0.95fr);
            background: #fff;
            box-shadow: 0 30px 60px rgba(5, 17, 15, 0.15);
        }

        .journey-content {
            padding: 42px;
        }

        .timeline {
            margin-top: 26px;
            border-left: 2px solid rgba(5, 17, 15, 0.1);
            padding-left: 28px;
            display: flex;
            flex-direction: column;
            gap: 22px;
        }

        .timeline-item {
            position: relative;
        }

        .timeline-item::before {
            content: "";
            position: absolute;
            left: -34px;
            top: 5px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1fe3a8, #00a87a);
            box-shadow: 0 0 0 6px rgba(31, 227, 168, 0.2);
        }

        .journey-media {
            position: relative;
        }

        .journey-media img {
            width: 100%;
            height: 100%;
            min-height: 320px;
            object-fit: cover;
        }

        .media-caption {
            position: absolute;
            bottom: 20px;
            left: 20px;
            background: rgba(3, 11, 9, 0.8);
            color: #eafff6;
            border-radius: 16px;
            padding: 18px;
            max-width: 70%;
        }

        .autosensor-grid,
        .autosensor-network,
        .autosensor-impact,
        .autosensor-map,
        .autosensor-products,
        .autosensor-cta {
            width: 92%;
            margin: 40px auto;
        }

        .autosensor-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
        }

        .autosensor-grid article {
            border-radius: 24px;
            padding: 24px;
            border: 1px dashed rgba(31, 227, 168, 0.5);
            background: rgba(31, 227, 168, 0.05);
        }

        .autosensor-network {
            display: grid;
            grid-template-columns: minmax(0, 0.9fr) minmax(0, 1.1fr);
            gap: 22px;
        }

        .network-card {
            border-radius: 30px;
            padding: 32px;
            background: #fff;
            border: 1px solid var(--intro-border);
            box-shadow: 0 25px 55px rgba(5, 12, 10, 0.1);
        }

        .network-card.gradient {
            background: linear-gradient(135deg, #041910, #082a1d);
            color: #dcfff1;
            border: none;
        }

        .network-card ul {
            margin-top: 16px;
            padding-left: 20px;
            line-height: 1.7;
        }

        .channel {
            margin-top: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .channel:last-child {
            border-bottom: 0;
        }

        .channel span {
            font-size: 13px;
            opacity: 0.7;
        }

        .channel a {
            color: inherit;
            text-decoration: none;
            font-weight: 600;
        }

        .autosensor-impact {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 20px;
        }

        .impact-card {
            border-radius: 30px;
            padding: 30px;
            background: #fff;
            border: 1px solid var(--intro-border);
        }

        .impact-card.list {
            background: rgba(0, 80, 56, 0.05);
        }

        .impact-item {
            border-bottom: 1px dashed rgba(5, 12, 10, 0.1);
            padding: 12px 0;
        }

        .impact-item:last-child {
            border-bottom: 0;
        }

        .autosensor-map {
            display: grid;
            grid-template-columns: minmax(0, 0.9fr) minmax(0, 1.1fr);
            gap: 24px;
        }

        .map-info {
            background: #02150d;
            color: #d8ffef;
            border-radius: 28px;
            padding: 32px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .map-info a {
            color: #85ffd9;
            text-decoration: none;
            font-weight: 600;
        }

        .map-frame {
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(5, 12, 10, 0.2);
        }

        .autosensor-cta .cta-card {
            border-radius: 32px;
            padding: 34px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            background: linear-gradient(120deg, #051a11, #0e3726);
            color: #e7fff6;
        }

        .autosensor-products {
            margin-bottom: 60px;
        }

        @media (max-width: 1100px) {
            .autosensor-hero,
            .autosensor-journey,
            .autosensor-network,
            .autosensor-impact,
            .autosensor-map {
                grid-template-columns: 1fr;
            }

            .media-badge {
                position: relative;
                inset: auto;
                margin-top: 16px;
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .autosensor-hero,
            .autosensor-panels,
            .autosensor-grid,
            .autosensor-network,
            .autosensor-impact,
            .autosensor-map,
            .autosensor-products,
            .autosensor-cta {
                width: 94%;
            }

            .autosensor-hero {
                padding: 32px 24px;
            }

            .hero-actions,
            .autosensor-cta .cta-card {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
@endsection
