@extends('clients.layouts.master')

@section('title', 'Liên hệ ' .($settings->site_name ?? $settings->site_name ?? 'AutoSensor Việt Nam'). ' - Giải pháp tự động hóa công nghiệp | '. $settings->site_name)

@section('head')

<meta name="robots" content="index,follow" />

<meta name="description" content="{{ $settings->site_name ?? $settings->site_name ?? 'AutoSensor Việt Nam' }} tư vấn, cung cấp và hỗ trợ kỹ thuật thiết bị tự động hóa công nghiệp: cảm biến, PLC, HMI, biến tần, servo, encoder, rơ le cho doanh nghiệp." />

<meta property="og:title" content="Liên hệ {{ $settings->site_name ?? $settings->site_name ?? 'AutoSensor Việt Nam' }} - Giải pháp tự động hóa công nghiệp" />

<meta property="og:description" content="Đặt lịch với chuyên gia {{ $settings->site_name ?? $settings->site_name ?? 'AutoSensor Việt Nam' }} để nhận giải pháp tự động hóa phù hợp cho dây chuyền sản xuất và hệ thống công nghiệp." />

<meta property="og:image" content="{{ asset('clients/assets/img/business/' . ($settings->site_banner ?? $settings->site_logo ?? 'no-image.webp')) }}" />

<meta property="og:url" content="{{ route('client.contact.index') }}" />

    <link rel="canonical" href="{{ route('client.contact.index') }}">

@endsection

@push('js_page')
    <script defer src="{{ asset('clients/assets/js/main.js') }}"></script>
@endpush

@section('content')
<section class="garden-hero contact-block">
    <div class="garden-hero__text">
        <p class="garden-eyebrow">{{ $settings->site_name ?? $settings->site_name ?? 'AutoSensor Việt Nam' }} Support</p>
        <h1>Đồng hành nâng tầm tự động hóa công nghiệp</h1>
        <p>
            Chúng tôi tư vấn, cung cấp và hỗ trợ kỹ thuật thiết bị tự động hóa công nghiệp:
            cảm biến, PLC, HMI, biến tần, servo, encoder, rơ le cho nhà máy, xưởng sản xuất và hệ thống công nghiệp.
        </p>
        <div class="garden-hero__actions">
            <a href="tel:{{ $settings->contact_phone ?? '' }}" class="btn primary">Gọi tư vấn</a>
            <a href="mailto:{{ $settings->contact_email ?? '' }}" class="btn ghost">Gửi email</a>
            </div>
        <ul class="garden-highlights">
            <li>
                <strong>500+</strong>
                <span>Dự án tự động hóa đã triển khai</span>
            </li>
            <li>
                <strong>24h</strong>
                <span>Phản hồi và hỗ trợ kỹ thuật toàn quốc</span>
            </li>
            <li>
                    <strong>12+</strong>
                <span>Dòng thiết bị tự động hóa chính hãng</span>
            </li>
        </ul>
                </div>

    <div class="garden-hero__card">
        <div class="card-badge">Gói doanh nghiệp</div>
        <h3>Bảo trì thiết bị định kỳ</h3>
        <p>
            Đội ngũ kỹ thuật viên {{ $settings->site_name ?? $settings->site_name ?? 'AutoSensor Việt Nam' }} hỗ trợ tận nơi: kiểm tra, bảo trì,
            hiệu chuẩn thiết bị, thay thế linh kiện miễn phí khi hư hỏng trong thời hạn bảo hành.
        </p>
                <ul>
            <li>Miễn phí khảo sát & lập phương án kỹ thuật</li>
            <li>Báo cáo tình trạng thiết bị hàng tháng</li>
            <li>Bảo hành thiết bị theo hợp đồng</li>
                </ul>
        </div>
    </section>

<section class="garden-contact-grid contact-block">
        <div class="contact-info">
        <article class="info-card">
            <header>
                <span>Trung tâm {{ $settings->site_name ?? $settings->site_name ?? 'AutoSensor Việt Nam' }}</span>
                <small>Hoạt động 8h00 – 21h00</small>
            </header>
            <h4>{{ $settings->contact_address ?? 'Đang cập nhật địa chỉ' }}</h4>
                <div class="info-divider"></div>

            <dl>
                    <div>
                    <dt>Hotline</dt>
                    <dd><a href="tel:{{ $settings->contact_phone ?? '' }}">{{ $settings->contact_phone ?? '090.xxx.xxxx' }}</a></dd>
                    </div>
                    <div>
                    <dt>Email</dt>
                    <dd><a href="mailto:{{ $settings->contact_email ?? '' }}">{{ $settings->contact_email ?? 'info@autosensor.vn' }}</a></dd>
                    </div>
                <div>
                    <dt>Kho hàng & vận chuyển</dt>
                    <dd>Hà Nội – Đà Nẵng – TP.HCM</dd>
                </div>
            </dl>
        </article>

        <article class="info-card channels">
            <h4>Kênh hỗ trợ ưu tiên</h4>
            <div class="channel-grid">
                <a href="{{ $settings->facebook_link ?? '#' }}" target="_blank">
                        <span>Facebook Support</span>
                    <small>Phản hồi trong 15 phút</small>
                    </a>
                <a href="{{ $settings->tiktok_link ?? '#' }}" target="_blank">
                    <span>TikTok Automation Lab</span>
                    <small>Livestream tư vấn kỹ thuật</small>
                    </a>
                <a href="{{ $settings->telegram_link ?? '#' }}" target="_blank">
                    <span>Telegram dự án</span>
                    <small>Chat báo giá nhanh</small>
                    </a>
                <a href="{{ $settings->instagram_link ?? '#' }}" target="_blank">
                    <span>Instagram Solutions</span>
                    <small>Giải pháp tự động hóa công nghiệp</small>
                    </a>
                </div>
        </article>

        <article class="info-card highlight">
            <p class="eyebrow">Dịch vụ nổi bật</p>
            <h4>Thiết kế & triển khai hệ thống tự động hóa</h4>
            <p>
                {{ $settings->site_name ?? $settings->site_name ?? 'AutoSensor Việt Nam' }} phụ trách toàn bộ quy trình: khảo sát, thiết kế hệ thống, lắp đặt,
                bảo hành thiết bị tự động hóa cho nhà máy, xưởng sản xuất và hệ thống công nghiệp.
            </p>
            <ul>
                <li>Đề xuất giải pháp tự động hóa phù hợp ngành nghề</li>
                <li>Quản lý tiến độ & nghiệm thu chuẩn kỹ thuật</li>
                <li>Bảo trì sau bàn giao linh hoạt</li>
            </ul>
        </article>
            </div>

    <div class="contact-form">
        <div class="form-header">
            <p class="eyebrow">Đăng ký tư vấn</p>
            <h3>Chia sẻ nhu cầu của bạn</h3>
            <p>Đội ngũ {{ $settings->site_name ?? $settings->site_name ?? 'AutoSensor Việt Nam' }} sẽ liên hệ trong vòng 24 giờ.</p>
                </div>

        @if (session('success'))
            <div class="form-alert success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="form-alert error">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="form-alert error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

            <form id="contact-form" action="{{ route('client.contact.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="form-grid">
                <label>
                        <span>Họ và tên *</span>
                    <input type="text" name="name" value="{{ old('name') }}" required>
                    @error('name')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                    </label>
                <label>
                        <span>Email *</span>
                    <input type="email" name="email" value="{{ old('email') }}" required>
                    @error('email')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                    </label>
                </div>

                <div class="form-grid">
                <label>
                    <span>Số điện thoại *</span>
                    <input type="tel" name="phone" value="{{ old('phone') }}" required>
                    @error('phone')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                    </label>
                <label>
                    <span>Phân khúc quan tâm *</span>
                    <select name="subject" required>
                        <option value="Cảm biến công nghiệp" @selected(old('subject') === 'Cảm biến công nghiệp')>Cảm biến công nghiệp</option>
                        <option value="PLC & HMI lập trình" @selected(old('subject') === 'PLC & HMI lập trình')>PLC & HMI lập trình</option>
                        <option value="Biến tần & Servo điều khiển" @selected(old('subject') === 'Biến tần & Servo điều khiển')>Biến tần & Servo điều khiển</option>
                        <option value="Giải pháp tự động hóa toàn diện" @selected(old('subject') === 'Giải pháp tự động hóa toàn diện')>Giải pháp tự động hóa toàn diện</option>
                    </select>
                    @error('subject')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                    </label>
                </div>

            <label>
                <span>Mô tả nhu cầu *</span>
                <textarea name="message" rows="5" minlength="20" placeholder="Mô tả ứng dụng / Yêu cầu kỹ thuật / Môi trường làm việc..." required>{{ old('message') }}</textarea>
                @error('message')
                    <small class="field-error">{{ $message }}</small>
                @enderror
                </label>

            <label class="file-field">
                <span>Đính kèm mặt bằng / ảnh hiện trạng (tùy chọn)</span>
                <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                @error('attachment')
                    <small class="field-error">{{ $message }}</small>
                @enderror
                </label>

            <button type="submit" class="btn primary full" id="contact-submit-btn">
                    <span id="contact-submit-text">Gửi yêu cầu</span>
                    <span id="contact-submit-loading" style="display:none;">Đang gửi...</span>
                </button>
            </form>
        </div>
    </section>

<section class="garden-services contact-block">
    <article>
        <h4>Giải pháp tự động hóa nhà máy</h4>
        <p>
            Thiết bị tự động hóa, lắp đặt, lập trình và bảo trì định kỳ —
            tất cả trong một hợp đồng.
        </p>
        <a href="tel:{{ $settings->contact_phone ?? '' }}">Đặt lịch khảo sát</a>
    </article>

    <article>
        <h4>Hệ thống điều khiển công nghiệp</h4>
        <p>PLC, HMI, biến tần, servo và encoder cho dây chuyền sản xuất hiện đại.</p>
        <a href="{{ route('client.shop.index') }}">Xem danh mục sản phẩm</a>
    </article>

    <article>
        <h4>Tư vấn kỹ thuật chuyên sâu</h4>
        <p>Hỗ trợ chọn thiết bị, thiết kế hệ thống và giải pháp tự động hóa tùy chỉnh.</p>
            <a href="mailto:{{ $settings->contact_email ?? '' }}">Nhận báo giá</a>
    </article>
    </section>

<section class="garden-map contact-block">
        <div class="map-info">
        <p class="eyebrow">Showroom {{ $settings->site_name ?? $settings->site_name ?? 'AutoSensor Việt Nam' }}</p>
        <h3>Trải nghiệm hơn 200+ thiết bị tự động hóa</h3>
        <p>
            Đặt lịch trực tiếp để được chuyên gia kỹ thuật hướng dẫn
            chọn thiết bị phù hợp, đo đạc thông số và thiết kế hệ thống tự động hóa.
        </p>
            <ul>
            <li>Miễn phí khảo sát kỹ thuật</li>
            <li>Workshop tự động hóa cuối tuần</li>
            <li>Dịch vụ cho thuê thiết bị thử nghiệm</li>
            </ul>
        </div>

        <div class="map-frame">
            <iframe width="100%" height="100%" src="{{ $settings->source_map }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
    </section>

<section class="garden-product contact-block">
    <header>
        <span class="eyebrow">Gợi ý tư vấn</span>
        <h3>Thiết bị tự động hóa mới cập nhật</h3>
        <p>Những thiết bị giúp nâng cao hiệu suất và tự động hóa quy trình sản xuất.</p>
    </header>

        @include('clients.templates.product_new')
</section>


    <style>
        :root {
        --garden-green: #0f5132;
        --garden-light: #f0fdf4;
        --garden-border: #d1fae5;
        --garden-shadow: rgba(15, 81, 50, 0.15);
        }

    .contact-block {
        width: min(1100px, 92%);
        margin: 40px auto;
    }

    .garden-hero {
            padding: 48px;
            border-radius: 32px;
        background: linear-gradient(135deg, #043720, #0b2519 70%, #02150c);
        color: #ecfdf5;
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.8fr);
            gap: 32px;
        }

    .garden-eyebrow {
        text-transform: uppercase;
        letter-spacing: 0.25em;
            font-size: 12px;
            opacity: 0.8;
        }

    .garden-hero__actions {
        margin: 24px 0 32px;
            display: flex;
        flex-wrap: wrap;
            gap: 16px;
        }

    .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border-radius: 999px;
            padding: 14px 24px;
            font-weight: 600;
            transition: 0.3s;
            border: 1px solid transparent;
        text-decoration: none;
        }

    .btn.primary {
        background: linear-gradient(90deg, #11b981, #34d399);
        color: #022c22;
        }

    .btn.ghost {
            border-color: rgba(255, 255, 255, 0.4);
        color: #ecfdf5;
        }

    .btn.full {
            width: 100%;
        }

    .garden-highlights {
        list-style: none;
        padding: 0;
        margin: 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
        }

    .garden-highlights li {
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 16px;
        padding: 16px;
        }

    .garden-hero__card {
        background: rgba(2, 20, 12, 0.85);
            border-radius: 28px;
        border: 1px solid rgba(255, 255, 255, 0.12);
            padding: 32px;
        box-shadow: 0 30px 80px rgba(0, 0, 0, 0.35);
        }

    .garden-contact-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(0, 0.9fr);
            gap: 32px;
        }

        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .info-card {
        background: #fff;
            padding: 28px;
            border-radius: 24px;
        border: 1px solid rgba(15, 81, 50, 0.08);
        box-shadow: 0 20px 45px var(--garden-shadow);
        }

    .info-card header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
        color: #064e3b;
        letter-spacing: 0.12em;
        }

    .channels .channel-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }

    .channels a {
        text-decoration: none;
            padding: 14px 16px;
            border-radius: 16px;
        border: 1px solid var(--garden-border);
        background: var(--garden-light);
        color: var(--garden-green);
        display: block;
        }

    .highlight {
        background: linear-gradient(140deg, #062b1c, #0c3d2a);
        color: #e2fcef;
        border: none;
        }

    .highlight ul {
            list-style: none;
            padding: 0;
        margin: 16px 0 0;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

    .contact-form {
            background: #fff;
        padding: 36px;
            border-radius: 32px;
        border: 1px solid rgba(15, 81, 50, 0.08);
        box-shadow: 0 25px 55px var(--garden-shadow);
        }

    .contact-form form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

    .contact-form label {
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 14px;
        color: #0f172a;
        }

    .contact-form input,
    .contact-form select,
    .contact-form textarea {
        border-radius: 16px;
        border: 1px solid rgba(15, 81, 50, 0.2);
            padding: 12px 14px;
            font-size: 15px;
        }

    .garden-services {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
        }

    .garden-services article {
            padding: 24px;
            border-radius: 20px;
        border: 1px solid var(--garden-border);
            background: #fff;
        box-shadow: 0 15px 35px var(--garden-shadow);
        }

    .garden-map {
            display: grid;
            grid-template-columns: minmax(0, 0.8fr) minmax(0, 1.2fr);
            gap: 32px;
            align-items: stretch;
        }

        .map-info {
        background: var(--garden-green);
        color: #ecfdf5;
            border-radius: 28px;
            padding: 32px;
        }

        .map-frame {
            border-radius: 28px;
            overflow: hidden;
        box-shadow: 0 25px 60px var(--garden-shadow);
        }

    .garden-product header {
        text-align: center;
        margin-bottom: 24px;
        }

        @media (max-width: 1024px) {

        .garden-hero,
        .garden-contact-grid,
        .garden-map {
                grid-template-columns: 1fr;
            }

        .garden-hero__actions {
                flex-direction: column;
                align-items: flex-start;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }

    @media (max-width: 768px) {

        .garden-hero,
        .garden-contact-grid,
        .garden-services,
        .garden-map,
        .garden-product {
            width: 94%;
            margin-inline: auto;
        }
    }

    .form-alert {
        padding: 12px 16px;
        border-radius: 10px;
        margin-bottom: 16px;
        font-size: 15px;
    }

    .form-alert.success {
        background: #e8f5e9;
        color: #0f5132;
        border: 1px solid #c8e6c9;
    }

    .form-alert.error {
        background: #fdecea;
        color: #c62828;
        border: 1px solid #f5c6cb;
    }

    .field-error {
        color: #c62828;
        font-size: 13px;
        margin-top: 6px;
        display: block;
    }

    </style>
@endsection

@section('foot')
    <script>
    document.addEventListener('DOMContentLoaded', () => {
            const contactForm = document.getElementById('contact-form');
            const messageDiv = document.getElementById('contact-form-message');
            const submitBtn = document.getElementById('contact-submit-btn');
            const submitText = document.getElementById('contact-submit-text');
            const submitLoading = document.getElementById('contact-submit-loading');

        if (!contactForm || contactForm.dataset.ajax !== 'true') return;

        contactForm.addEventListener('submit', async event => {
            event.preventDefault();

                    submitBtn.disabled = true;
                    submitText.style.display = 'none';
                    submitLoading.style.display = 'inline';
                    messageDiv.style.display = 'none';

                    const formData = new FormData(contactForm);

                    try {
                        const response = await fetch(contactForm.action, {
                    method: 'POST'
                    , body: formData
                    , headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    , }
                , });

                        const data = await response.json();
                        messageDiv.style.display = 'block';
                        
                        if (data.success) {
                    // SUCCESS MESSAGE STYLE
                            messageDiv.style.background = '#d1fae5';
                            messageDiv.style.color = '#065f46';
                            messageDiv.style.border = '1px solid #10b981';

                    messageDiv.innerHTML =
                        '<strong>Thành công!</strong> ' +
                        (data.message ? ? 'Cảm ơn bạn, chúng tôi sẽ liên hệ sớm nhất.');
                            
                            contactForm.reset();

                        } else {
                    // ERROR MESSAGE STYLE
                            messageDiv.style.background = '#fee2e2';
                            messageDiv.style.color = '#991b1b';
                            messageDiv.style.border = '1px solid #ef4444';
                            
                    let errorMessage = data.message ? ? 'Có lỗi xảy ra, vui lòng thử lại.';

                            if (data.errors) {
                                const errorList = Object.values(data.errors).flat().join('<br>');
                        errorMessage = '<strong>Lỗi:</strong><br>' + errorList;
                            }

                            messageDiv.innerHTML = errorMessage;
                        }

                    } catch (error) {
                        messageDiv.style.display = 'block';
                        messageDiv.style.background = '#fee2e2';
                        messageDiv.style.color = '#991b1b';
                        messageDiv.style.border = '1px solid #ef4444';
                        messageDiv.textContent = 'Có lỗi xảy ra. Vui lòng thử lại sau.';
                        console.error('Contact form error:', error);

                    } finally {
                        submitBtn.disabled = false;
                        submitText.style.display = 'inline';
                        submitLoading.style.display = 'none';
                messageDiv.scrollIntoView({
                    behavior: 'smooth'
                    , block: 'nearest'
                });
            }
        });
    });

    </script>
@endsection
