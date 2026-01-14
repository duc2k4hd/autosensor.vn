@php
    /** @var \App\Models\Product $product */
    $adminUser = \Illuminate\Support\Facades\Auth::user();
    $isAdminBarVisible = $adminUser && method_exists($adminUser, 'isAdminOrWriter') && $adminUser->isAdminOrWriter();
@endphp

@if ($isAdminBarVisible)
    <div class="autosensor_admin_bar" role="region" aria-label="Thanh quản trị nhanh">
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
            const bar = document.querySelector('.autosensor_admin_bar');
            if (!bar) return;

            // chừa khoảng trống để không che header khi fixed
            document.documentElement.style.setProperty('--autosensor-adminbar-height', bar.offsetHeight + 'px');
            document.body.classList.add('autosensor-has-adminbar');

            bar.addEventListener('click', async (e) => {
                const btn = e.target.closest('[data-copy]');
                if (!btn) return;
                const text = btn.getAttribute('data-copy') || '';
                try {
                    await navigator.clipboard.writeText(text);
                    btn.textContent = 'Đã copy';
                    setTimeout(() => (btn.textContent = 'Copy SKU'), 900);
                } catch (err) {
                    // fallback
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

