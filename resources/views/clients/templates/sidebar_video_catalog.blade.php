@php
    $catalogs = $product->link_catalog ?? [];
    if (!is_array($catalogs)) {
        $catalogs = [];
    }
    $catalogs = array_values(array_filter($catalogs));
    $videoUrl = $product->video_url ?? null;
    
    // Convert YouTube/Vimeo URL to embed format nếu cần
    if ($videoUrl) {
        // YouTube: https://youtu.be/VIDEO_ID hoặc https://www.youtube.com/watch?v=VIDEO_ID
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $videoUrl, $matches)) {
            $videoUrl = 'https://www.youtube.com/embed/' . $matches[1];
        }
        // Vimeo: https://vimeo.com/VIDEO_ID
        elseif (preg_match('/vimeo\.com\/(\d+)/', $videoUrl, $matches)) {
            $videoUrl = 'https://player.vimeo.com/video/' . $matches[1];
        }
        // Nếu đã là embed URL thì giữ nguyên
    }
@endphp

<div class="autosensor_sidebar_video_catalog" style="margin-top: 30px;">
    <h3 class="autosensor_single_desc_tabs_describe_product_new_title">📹 Video & Catalog</h3>
    <div style="display: flex; align-items: center; justify-content: center; margin: 1rem 0;">
        <hr style="flex: 1; height: 2px; background-color: #e6525e; border: none; margin: 0;">
        <span style="padding: 0 12px; color: #f74a4a; font-weight: bold;">Video & Catalog</span>
        <hr style="flex: 1; height: 2px; background-color: #e6525e; border: none; margin: 0;">
    </div>
    
    <div class="autosensor_sidebar_video_catalog_content">
        @if($videoUrl)
            <div class="autosensor_sidebar_video" style="margin-bottom: 20px;">
                {{-- Placeholder để tránh ảnh hưởng LCP --}}
                <div class="video-placeholder" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 8px; background: #f0f0f0; cursor: pointer;" data-video-url="{{ $videoUrl }}">
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center;">
                        <div style="font-size: 48px; margin-bottom: 10px;">▶️</div>
                        <div style="font-size: 14px; color: #666;">Nhấn để xem video</div>
                    </div>
                </div>
                {{-- Iframe sẽ được load sau khi user click hoặc sau khi page load (lazy) --}}
                <div class="video-iframe-container" style="display: none; position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 8px;">
                    <iframe 
                        class="lazy-video-iframe"
                        data-src="{{ $videoUrl }}?autoplay=1"
                        style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0;"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen
                        loading="lazy">
                    </iframe>
                </div>
            </div>
        @endif
        
        @if(!empty($catalogs))
            <div class="autosensor_sidebar_catalog_list">
                @foreach($catalogs as $idx => $catalog)
                    @php
                        $fileName = basename($catalog);
                        $label = $fileName ?: 'Catalog ' . ($idx + 1);
                    @endphp
                    <div class="autosensor_sidebar_catalog_item" style="display: flex; align-items: center; gap: 10px; padding: 12px; background: #f8f9fa; border-radius: 8px; margin-bottom: 10px;">
                        <div style="font-size: 24px;">📄</div>
                        <div style="flex: 1;">
                            <div style="font-weight: 600; font-size: 14px; margin-bottom: 4px;">{{ $label }}</div>
                            <div style="font-size: 12px; color: #666;">Tài liệu kỹ thuật</div>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <a href="{{ asset($catalog) }}" target="_blank" rel="noopener" style="padding: 6px 12px; background: #2563EB; color: white; border-radius: 4px; text-decoration: none; font-size: 12px;">Xem</a>
                            <a href="{{ asset($catalog) }}" download style="padding: 6px 12px; background: #10b981; color: white; border-radius: 4px; text-decoration: none; font-size: 12px;">⬇ Tải</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @elseif(!$videoUrl)
            <div style="text-align: center; color: #999; padding: 20px;">
                <p>Chưa có video hoặc catalog cho sản phẩm này.</p>
                <a href="https://zalo.me/{{ $settings->contact_zalo ?? '0827786198' }}" target="_blank" style="display: inline-block; margin-top: 10px; padding: 8px 16px; background: #2563EB; color: white; border-radius: 4px; text-decoration: none; font-size: 13px;">Liên hệ CSKH</a>
            </div>
        @endif
    </div>
</div>

{{-- Script để lazy load video và tránh ảnh hưởng LCP --}}
@if($videoUrl)
<script>
(function() {
    const videoPlaceholder = document.querySelector('.video-placeholder');
    const videoContainer = document.querySelector('.video-iframe-container');
    const videoIframe = document.querySelector('.lazy-video-iframe');
    
    if (!videoPlaceholder || !videoContainer || !videoIframe) return;
    
    let videoLoaded = false;
    
    // Load video khi user click vào placeholder
    videoPlaceholder.addEventListener('click', function() {
        if (!videoLoaded) {
            loadVideo();
        }
    });
    
    // Hoặc load video sau khi page đã render xong (sau LCP)
    function loadVideoOnIdle() {
        // Đợi page load xong và user có thể đã scroll
        if (document.readyState === 'complete') {
            // Sử dụng Intersection Observer để load khi video vào viewport
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting && !videoLoaded) {
                        // Delay thêm 2 giây sau khi vào viewport để đảm bảo LCP đã hoàn thành
                        setTimeout(function() {
                            loadVideo();
                        }, 2000);
                        observer.disconnect();
                    }
                });
            }, {
                rootMargin: '100px' // Load trước khi vào viewport 100px
            });
            
            observer.observe(videoPlaceholder);
        } else {
            window.addEventListener('load', loadVideoOnIdle);
        }
    }
    
    function loadVideo() {
        if (videoLoaded) return;
        videoLoaded = true;
        
        // Ẩn placeholder
        videoPlaceholder.style.display = 'none';
        
        // Hiển thị container và load iframe
        videoContainer.style.display = 'block';
        videoIframe.src = videoIframe.dataset.src;
    }
    
    // Bắt đầu observe sau khi DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadVideoOnIdle);
    } else {
        loadVideoOnIdle();
    }
})();
</script>
@endif

