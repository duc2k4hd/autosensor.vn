<!-- Call to action -->

<section class="xanhworld_main_newsletter_banner_section">
    <div class="xanhworld_main_newsletter_banner">
        <div class="xanhworld_main_newsletter_banner_content">
            <h2 class="xanhworld_main_newsletter_banner_title">
                Đăng ký nhận bản tin
            </h2>
            <p class="xanhworld_main_newsletter_banner_desc">
                Nhận thông tin mới nhất về sản phẩm, xu hướng TỰ ĐỘNG HÓA CÔNG NGHIỆP và ưu đãi độc quyền từ {{ $setting->site_name ?? 'AutoSensor Việt Nam' }}.
            </p>
            <form action="{{ route('client.newsletter.subscription') }}" method="POST" class="xanhworld_main_newsletter_banner_form">
                @csrf
                <input value="{{ old('xanhworld_main_newsletter_email') }}" type="email" name="xanhworld_main_newsletter_email" class="xanhworld_main_newsletter_banner_input" placeholder="Nhập email của bạn..." required>
                <small>@error('xanhworld_main_newsletter_email') {{ $message }} @enderror</small>
                <button type="submit" class="xanhworld_main_newsletter_banner_btn">
                    Đăng ký
                </button>
            </form>
        </div>
        <div class="xanhworld_main_newsletter_banner_img">
            <img
                loading="lazy"
                src="{{ asset('clients/assets/img/banners/dang-ky-nhan-ban-tin-AUTOSENSOR-VIET-NAM.jpg') }}"
                alt="Nhận thông tin mới nhất từ {{ $setting->site_name ?? 'AutoSensor Việt Nam' }}"
            >
        </div>
    </div>
</section>

