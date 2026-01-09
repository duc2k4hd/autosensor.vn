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

<!-- Google Tag Manager - Defer để tránh ảnh hưởng LCP -->
@if(!empty($settings->google_tag_header))
    @php
        // Parse Google Tag Manager script từ settings
        $gtagScript = $settings->google_tag_header;
        
        // Extract GTM ID từ script (hỗ trợ cả gtag/js và GTM container)
        preg_match('/id=([A-Z0-9-]+)/', $gtagScript, $matches);
        $gtagId = $matches[1] ?? null;
    @endphp
    
    @if($gtagId)
        <!-- Defer load Google Tag Manager sau khi page render (sau LCP) -->
        <script>
            (function() {
                // Load GTM sau khi page đã render xong để tránh ảnh hưởng LCP
                function loadGTM(gtagId) {
                    // Load GTM script async
                    var script = document.createElement('script');
                    script.async = true;
                    script.src = 'https://www.googletagmanager.com/gtag/js?id=' + gtagId;
                    document.head.appendChild(script);
                    
                    // Initialize GTM sau khi script load
                    script.onload = function() {
                        window.dataLayer = window.dataLayer || [];
                        function gtag(){dataLayer.push(arguments);}
                        gtag('js', new Date());
                        gtag('config', gtagId);
                    };
                }
                
                // Sử dụng requestIdleCallback nếu có, nếu không thì dùng setTimeout
                if ('requestIdleCallback' in window) {
                    requestIdleCallback(function() {
                        loadGTM('{{ $gtagId }}');
                    }, { timeout: 2000 });
                } else {
                    // Fallback: Load sau khi DOM ready + một chút delay để đảm bảo LCP đã hoàn thành
                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', function() {
                            setTimeout(function() {
                                loadGTM('{{ $gtagId }}');
                            }, 1000);
                        });
                    } else {
                        setTimeout(function() {
                            loadGTM('{{ $gtagId }}');
                        }, 1000);
                    }
                }
            })();
        </script>
    @else
        <!-- Fallback: Nếu không parse được ID, load script gốc nhưng defer -->
        <script>
            (function() {
                function loadDeferredScript() {
                    var container = document.createElement('div');
                    container.innerHTML = {!! json_encode($gtagScript) !!};
                    var scripts = container.getElementsByTagName('script');
                    for (var i = 0; i < scripts.length; i++) {
                        var newScript = document.createElement('script');
                        if (scripts[i].src) {
                            newScript.async = true;
                            newScript.src = scripts[i].src;
                        } else {
                            newScript.textContent = scripts[i].textContent;
                        }
                        document.head.appendChild(newScript);
                    }
                }
                
                if ('requestIdleCallback' in window) {
                    requestIdleCallback(function() {
                        loadDeferredScript();
                    }, { timeout: 2000 });
                } else {
                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', function() {
                            setTimeout(function() {
                                loadDeferredScript();
                            }, 1000);
                        });
                    } else {
                        setTimeout(function() {
                            loadDeferredScript();
                        }, 1000);
                    }
                }
            })();
        </script>
    @endif
@endif