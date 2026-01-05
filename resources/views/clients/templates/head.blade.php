<meta name="author" content="{{ $settings->seo_author ?? 'AutoSensor Việt Nam' }}">

<!-- Favicon cơ bản (Google Search ưu tiên) -->
<link rel="icon" href="{{ $settings->site_url ?? 'https://autosensor.vn' }}/clients/assets/img/business/{{ $settings->site_favicon }}" type="image/x-icon">

<link rel="apple-touch-icon" href="{{ $settings->site_url ?? 'https://autosensor.vn' }}/clients/assets/img/business/apple-touch-icon.png">

<!-- Web App Manifest -->
{{-- <link rel="manifest"
      href="{{ $settings->site_url ?? 'https://autosensor.vn' }}/clients/assets/img/business/site.webmanifest"> --}}

<meta name="theme-color" content="#ffffff">

<meta http-equiv="Strict-Transport-Security" content="max-age=31536000; includeSubDomains">
<meta http-equiv="X-Content-Type-Options" content="nosniff">
<meta http-equiv="X-XSS-Protection" content="1; mode=block">
<meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">
{!! $settings->site_pinterest ?? '' !!}

{{-- Google Tag Manager - Optimized for Performance (không ảnh hưởng LCP/CLS) --}}
@if(!empty($settings->google_tag_header))
    @php
        // Extract GTM ID từ script
        $gtmId = null;
        if (preg_match('/GTM-[A-Z0-9]+/', $settings->google_tag_header, $matches)) {
            $gtmId = $matches[0];
        }
    @endphp

    @if($gtmId)
        {{-- Preconnect để chuẩn bị kết nối sớm (không block rendering) --}}
        <link rel="preconnect" href="https://www.googletagmanager.com" crossorigin>
        <link rel="dns-prefetch" href="https://www.googletagmanager.com">
        
        {{-- Initialize dataLayer sớm (không block) --}}
        <script>
            window.dataLayer = window.dataLayer || [];
        </script>

        {{-- Load GTM sau khi page đã render (defer) - không ảnh hưởng LCP/CLS --}}
        <script>
            (function() {
                let gtmLoaded = false;
                
                function loadGTM() {
                    if (gtmLoaded) return;
                    gtmLoaded = true;
                    
                    (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
                    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
                    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
                    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
                    })(window,document,'script','dataLayer','{{ $gtmId }}');
                }
                
                // Load ngay nếu là Googlebot (để không ảnh hưởng crawling)
                if (/Googlebot|bingbot|Slurp|DuckDuckBot|Baiduspider|YandexBot|Sogou|Exabot|facebot|ia_archiver/i.test(navigator.userAgent)) {
                    loadGTM();
                    return;
                }
                
                // Load sau khi DOM ready + đợi LCP hoàn thành
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', function() {
                        // Đợi LCP (thường < 100ms sau DOMContentLoaded)
                        setTimeout(loadGTM, 500);
                    });
                } else {
                    setTimeout(loadGTM, 500);
                }
                
                // Load khi user tương tác (sớm hơn)
                ['mousedown', 'touchstart', 'scroll', 'keydown'].forEach(function(event) {
                    document.addEventListener(event, function() {
                        loadGTM();
                    }, { once: true, passive: true });
                });
                
                // Fallback: load sau 2.5 giây
                setTimeout(loadGTM, 500);
            })();
        </script>
    @else
        {{-- Nếu không extract được GTM ID, load như cũ nhưng với defer --}}
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(function() {
                    {!! $settings->google_tag_header !!}
                }, 500);
            });
        </script>
    @endif
@endif