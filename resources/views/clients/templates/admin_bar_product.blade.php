@php
    /** @var \App\Models\Product $product */
    $adminUser = \Illuminate\Support\Facades\Auth::user();
    $isAdminBarVisible = $adminUser && method_exists($adminUser, 'isAdminOrWriter') && $adminUser->isAdminOrWriter();
@endphp

@if ($isAdminBarVisible)
    <link rel="stylesheet" href="{{ asset('clients/assets/css/admin-bar.css?v='.$v) }}">

    {{-- Nút toggle chỉ hiện trên mobile — nằm fixed bên trái giữa màn hình --}}

    <button
        class="autosensor_admin_toggle"
        type="button"
        aria-label="Mở thanh quản trị"
        aria-expanded="false"
        aria-controls="autosensor-adminbar"
    >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <path fill="currentColor" d="M12 15.5A3.5 3.5 0 0 1 8.5 12 3.5 3.5 0 0 1 12 8.5a3.5 3.5 0 0 1 3.5 3.5 3.5 3.5 0 0 1-3.5 3.5m7.43-2.92c.04-.3.07-.61.07-.92s-.03-.63-.07-.93l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.3-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.04.3-.07.62-.07.94s.03.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.62l1.92 3.32c.12.22.37.3.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.04.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.62l-2.01-1.58z"/>
        </svg>
    </button>

    <div class="autosensor_admin_bar" id="autosensor-adminbar" role="region" aria-label="Thanh quản trị nhanh">
        <div class="autosensor_admin_bar__left">
            <span class="autosensor_admin_bar__badge">ADMIN</span>
            <span class="autosensor_admin_bar__meta">
                <strong>#{{ $product->id }}</strong>
                <span class="autosensor_admin_bar__sep">•</span>
                <span>SKU: <strong>{{ $product->sku ?? 'N/A' }}</strong></span>
            </span>
        </div>

        <div class="autosensor_admin_bar__right">
            <a class="autosensor_admin_bar__link" href="{{ route('admin.dashboard') }}" target="_blank" rel="noopener">Dashboard</a>
            <a class="autosensor_admin_bar__link" href="{{ route('admin.products.index') }}" target="_blank" rel="noopener">Sản phẩm</a>
            <a class="autosensor_admin_bar__link autosensor_admin_bar__link--primary" href="{{ route('admin.products.edit', $product->id) }}" target="_blank" rel="noopener">Sửa sản phẩm</a>
            <a class="autosensor_admin_bar__link" href="{{ route('admin.products.inventory', $product->id) }}" target="_blank" rel="noopener">Tồn kho</a>
            <a class="autosensor_admin_bar__link" href="{{ route('admin.featured-products.index') }}" target="_blank" rel="noopener">Nổi bật</a>
            <a class="autosensor_admin_bar__link" href="{{ route('admin.categories.index') }}" target="_blank" rel="noopener">Danh mục</a>
            <a class="autosensor_admin_bar__link" href="{{ route('admin.brands.index') }}" target="_blank" rel="noopener">Hãng</a>
            <a class="autosensor_admin_bar__link" href="{{ route('admin.orders.index') }}" target="_blank" rel="noopener">Đơn hàng</a>
            <a class="autosensor_admin_bar__link" href="{{ route('admin.tools.index') }}" target="_blank" rel="noopener">Tools</a>

            <button class="autosensor_admin_bar__btn" type="button" data-copy="{{ $product->sku ?? '' }}" title="Copy SKU">
                Copy SKU
            </button>
        </div>
    </div>

    <script>
        (function () {
            const bar    = document.getElementById('autosensor-adminbar');
            const toggle = document.querySelector('.autosensor_admin_toggle');
            if (!bar) return;

            // ── Desktop: đẩy nội dung xuống theo chiều cao bar ──────────
            function applyBarHeight() {
                const isMobile = window.matchMedia('(max-width: 767px)').matches;
                if (!isMobile) {
                    document.documentElement.style.setProperty('--autosensor-adminbar-height', bar.offsetHeight + 'px');
                    document.body.classList.add('autosensor-has-adminbar');
                } else {
                    document.documentElement.style.setProperty('--autosensor-adminbar-height', '0px');
                    document.body.classList.remove('autosensor-has-adminbar');
                }
            }
            applyBarHeight();
            window.addEventListener('resize', applyBarHeight);

            // ── Mobile toggle ────────────────────────────────────────────
            if (toggle) {
                toggle.addEventListener('click', function () {
                    const isOpen = bar.classList.toggle('autosensor_admin_bar--open');
                    toggle.setAttribute('aria-expanded', String(isOpen));
                    toggle.classList.toggle('autosensor_admin_toggle--active', isOpen);
                });

                // Đóng khi nhấn bên ngoài
                document.addEventListener('click', function (e) {
                    if (!bar.contains(e.target) && !toggle.contains(e.target)) {
                        bar.classList.remove('autosensor_admin_bar--open');
                        toggle.setAttribute('aria-expanded', 'false');
                        toggle.classList.remove('autosensor_admin_toggle--active');
                    }
                });
            }

            // ── Copy SKU ─────────────────────────────────────────────────
            bar.addEventListener('click', async (e) => {
                const btn = e.target.closest('[data-copy]');
                if (!btn) return;
                const text = btn.getAttribute('data-copy') || '';
                try {
                    await navigator.clipboard.writeText(text);
                    btn.textContent = 'Đã copy';
                    setTimeout(() => (btn.textContent = 'Copy SKU'), 900);
                } catch {
                    const ta = document.createElement('textarea');
                    ta.value = text;
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    ta.remove();
                    btn.textContent = 'Đã copy';
                    setTimeout(() => (btn.textContent = 'Copy SKU'), 900);
                }
            });
        })();
    </script>
@endif
