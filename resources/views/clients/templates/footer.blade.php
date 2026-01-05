@if ($settings->is_demo == true)
<div style="
    position: fixed;
    left: 16px;
    right: 16px;
    bottom: 16px;
    z-index: 9999;
    max-width: 420px;
    margin: auto;
    background: rgba(255, 240, 240, 0.95);
    border: 1px solid #ff7c7c;
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    padding: 14px 18px;
    border-radius: 10px;
    font-size: 15px;
    line-height: 1.5;
    font-weight: 500;
    color: #333;
    text-align: center;
    backdrop-filter: blur(6px);
">
    Đây là web
    <span style="color: #28a745; font-weight: 700;">DEMO</span>
    vẫn còn nhiều lỗi, nếu có thắc mắc vui lòng liên hệ
    <a href="https://www.facebook.com/ducnobi2004" style="color: #007bff; text-decoration: none; font-weight: 600;">
        Đức Nobi
    </a>
</div>
@endif
{{-- <div class="xanhworld_context_menu" id="contextMenu">
    <ul>
        <li class="xanhworld_context_menu_item">🛒 Thêm vào giỏ hàng</li>
        <li class="xanhworld_context_menu_item">❤️ Thêm vào yêu thích</li>
        <li class="xanhworld_context_menu_item">🔍 Xem nhanh</li>
        <li class="xanhworld_context_menu_item">📊 So sánh sản phẩm</li>
        <li class="xanhworld_context_menu_divider"></li>
        <li class="xanhworld_context_menu_item">🔗 Sao chép liên kết</li>
        <li class="xanhworld_context_menu_item">📤 Chia sẻ Facebook</li>
        <li class="xanhworld_context_menu_item">🐦 Chia sẻ Twitter</li>
    </ul>
</div> --}}


{{-- <script>
    const menu = document.getElementById("contextMenu");

    document.addEventListener("contextmenu", function(e) {
        e.preventDefault();

        // lấy kích thước menu
        menu.style.display = "block";
        const menuWidth = menu.offsetWidth;
        const menuHeight = menu.offsetHeight;
        const pageWidth = window.innerWidth;
        const pageHeight = window.innerHeight;

        let posX = e.clientX;
        let posY = e.clientY;

        // kiểm tra tràn phải
        if (posX + menuWidth > pageWidth) {
            posX = pageWidth - menuWidth - 5; // cách 5px
        }

        // kiểm tra tràn dưới
        if (posY + menuHeight > pageHeight) {
            posY = pageHeight - menuHeight - 5;
        }

        menu.style.left = posX + "px";
        menu.style.top = posY + "px";
    });

    document.addEventListener("click", function() {
        menu.style.display = "none";
    });
</script> --}}

<footer class="xanhworld_footer">
    <div class="xanhworld_footer_content">
        <div class="xanhworld_footer_content_business">
            <img loading="lazy" width="180px" height="55px"
                src="{{ asset('clients/assets/img/business/' . $settings->site_logo ?? '' ) }}"
                alt="Shop {{ $settings->subname ?? '' }}"
                title="Shop {{ $settings->site_name ?? '' }}">
            <h2 class="xanhworld_footer_content_business_title">{{ $settings->site_name ?? '' }}</h2>
            <p class="xanhworld_footer_content_business_desc">Chúng tôi cung cấp thiết bị tự động hóa công nghiệp chính hãng với giá cả cạnh tranh và dịch vụ kỹ thuật chuyên nghiệp.</p>
            <p class="xanhworld_footer_content_business_address"><strong>Địa chỉ</strong>: {{
                $settings->contact_address ?? '' }}</p>
            <p class="xanhworld_footer_content_business_phone"><strong>Điện thoại</strong>:
                {{ preg_replace('/^(\d{4})(\d{3})(\d{3})$/', '$1.$2.$3', preg_replace('/\D/', '',
                $settings->contact_phone ?? '' )) }}
            </p>
            <p class="xanhworld_footer_content_business_email"><strong>Email</strong>: {{ $settings->contact_email ??
                '' }}</p>
            <p class="xanhworld_footer_content_business_hours"><strong>Giờ làm việc</strong>: 8:00 - 17:00 từ thứ 2
                đến thứ 7</p>
            <div class="xanhworld_footer_content_business_socials">
                @if ($settings->facebook_link)
                <a aria-label="Facebook" href="{{ $settings->facebook_link ?? '#'  }}"><img width="40" height="40" loading="lazy"
                        src="{{ asset('clients/assets/img/clothes/resize/40x40/icon-facebook.webp') }}"
                        alt="Facebook"></a>
                @endif
                @if ($settings->instagram_link)
                <a aria-label="Intagram" href="{{ $settings->instagram_link ?? '#'  }}"><img width="40" height="40" loading="lazy"
                        src="{{ asset('clients/assets/img/clothes/resize/40x40/icon-Instagram.webp') }}" alt="Instagram"></a>
                @endif
                @if ($settings->twitter_link)
                <a aria-label="Twitter" href="{{ $settings->twitter_link ?? '#'  }}"><img width="40" height="40" loading="lazy"
                        src="{{ asset('clients/assets/img/clothes/resize/40x40/icon-twitter.webp') }}" alt="Twitter"></a>
                @endif
            </div>
            <a href="{{ $settings->bo_cong_thuong ?? '#'  }}">
                <img loading="lazy" style="object-fit: cover; height: 68px;"
                    src="{{ asset('clients/assets/img/business/setting-bo_cong_thuong-1757497818.webp') }}"
                    alt="Bộ công thương">
            </a>
        </div>

        <div class="xanhworld_footer_content_company">
            <p class="xanhworld_footer_content_company_title">Chính sách bán hàng</p>
            <div class="xanhworld_footer_content_company_links">
                <a href="{{ route('client.introduction.index') }}">Giới thiệu</a>
                <a href="{{ route('client.contact.index') }}">Liên hệ</a>
                <a href="{{ route('client.policy.privacy') }}">Chính sách bảo mật</a>
                <a href="{{ route('client.policy.terms') }}">Điều khoản sử dụng</a>
                <a href="{{ route('client.policy.return') }}">Chính sách đổi trả</a>
                <a href="{{ route('client.policy.delivery') }}">Chính sách vận chuyển</a>
                <a href="{{ route('client.policy.warranty') }}">Chính sách bảo hành</a>
                <a href="{{ route('client.policy.payment') }}">Chính sách thanh toán</a>
                <a href="{{ route('client.policy.privacy') }}">Chính sách bảo mật thông tin</a>
                <a href="{{ route('client.policy.privacy') }}">Chính sách bảo mật dữ liệu</a>
                <a href="{!! $settings->dmca ?? '#'  !!}" title="DMCA.com Protection Status" class="dmca-badge"> <img
                        loading="lazy" src="{!! $settings->dmca_logo ?? ''  !!}" alt="DMCA.com Protection Status" /></a>
                <script defer src="https://images.dmca.com/Badges/DMCABadgeHelper.min.js"> </script>
                {{-- <a href="{{ route('client.policy.sale') }}"> --}}
            </div>
        </div>

        <div class="xanhworld_footer_content_accounts">
            <p class="xanhworld_footer_content_accounts_title">Tài khoản</p>
            <div class="xanhworld_footer_content_accounts_links">
                <a href="{{ route('client.auth.login') }}">Đăng nhập</a>
                <a href="{{ route('client.auth.register') }}">Đăng ký</a>
                <a href="{{ route('client.auth.forgot-password') }}">Quên mật khẩu</a>
                <a href="@auth
                    {{ route('client.profile.index') }}
                @else
                    {{ route('client.auth.login') }}
                @endauth">Thông tin tài khoản</a>
                <a href="@auth
                    {{-- {{ route('client.order.index') }} --}}
                @else
                    {{ route('client.auth.login') }}
                @endauth">Lịch sử đơn hàng</a>
                <a href="@auth
                    {{ route('client.wishlist.index') }}
                @else
                    {{ route('client.auth.login') }}
                @endauth">Danh sách yêu thích</a>
                <a href="@auth
                    {{ route('client.profile.index') }}
                @else
                    {{ route('client.auth.login') }}
                @endauth">Địa chỉ giao hàng</a>
                <a href="@auth
                    {{ route('client.profile.index') }}
                @else
                @endauth">Thông tin thanh toán</a>
                {{-- <a href="{{ route('client.blog.index') }}">Tin tức</a> --}}
                <a style="margin-top: 10px;" href="https://www.dmca.com/compliance/autosensor.vn" title="DMCA Compliance information for autosensor.vn"><img loading="lazy" src="https://www.dmca.com/img/dmca-compliant-white-bg.png" alt="DMCA compliant image" /></a>
            </div>
        </div>

        <div class="xanhworld_footer_content_corporate">
            <p class="xanhworld_footer_content_corporate_title">Doanh nghiệp</p>
            <div class="xanhworld_footer_content_corporate_links">
                <a href="{{ route('client.introduction.index') }}">Giới thiệu doanh nghiệp</a>
                <a href="{{ route('client.contact.index') }}">Liên hệ doanh nghiệp</a>
                <a href="{{ route('client.policy.privacy') }}">Chính sách bảo mật doanh nghiệp</a>
                <a href="{{ route('client.policy.terms') }}">Điều khoản sử dụng doanh nghiệp</a>
                <a href="{{ route('client.policy.return') }}">Chính sách đổi trả doanh nghiệp</a>
                <a href="{{ route('client.policy.delivery') }}">Chính sách vận chuyển doanh nghiệp</a>
                <a href="{{ route('client.policy.warranty') }}">Chính sách bảo hành doanh nghiệp</a>
                <a href="{{ route('client.policy.payment') }}">Chính sách thanh toán doanh nghiệp</a>
                <a href="{{ route('client.policy.privacy') }}">Chính sách bảo mật thông tin doanh nghiệp</a>
                <a href="{{ route('client.policy.privacy') }}">Chính sách bảo mật dữ liệu doanh nghiệp</a>
            </div>
        </div>

        <div class="xanhworld_footer_content_services">
            <p class="xanhworld_footer_content_services_title">Dịch vụ</p>
            <div class="xanhworld_footer_content_services_links">
                <a href="{{ route('client.contact.index') }}">Hỗ trợ khách hàng</a>
                <a href="{{ route('client.contact.index') }}">Trung tâm hỗ trợ</a>
                <a href="#">Câu hỏi thường gặp</a>
                <a href="#">Hướng dẫn sử dụng</a>
                <a href="{{ route('client.policy.payment') }}">Hướng dẫn thanh toán</a>
                <a href="{{ route('client.policy.delivery') }}">Hướng dẫn vận chuyển</a>
                <a href="{{ route('client.policy.return') }}">Hướng dẫn đổi trả</a>
                <a href="{{ route('client.policy.warranty') }}">Hướng dẫn bảo hành</a>
                <a href="{{ route('client.policy.privacy') }}">Hướng dẫn bảo mật thông tin</a>
                <a href="{{ route('client.policy.privacy') }}">Hướng dẫn bảo mật dữ liệu</a>
                <a href="{{ route('client.sitemap.landing') }}">🗺️ Sitemap</a>
                <img loading="lazy" style="object-fit: contain;" width="250" height="70" src="{{ asset('clients/assets/img/other/footer_trustbadge.jpg') }}"
                    alt="Các phương thức thanh toán được tin cậy bởi autosensor.vn">
            </div>
        </div>
    </div>
    <hr>
    <div class="xanhworld_footer_bottom">
        {!! Blade::render($settings->copyright ?? '' ) !!}
    </div>
</footer>