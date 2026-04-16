@extends('admins.layouts.master')

@php
    $summary = $productData['summary'];
    $primaryImage = $productData['primary_image'];
    $lockInfo = $productData['lock'];
@endphp

@section('title', 'Chi tiết sản phẩm')
@section('page-title', 'Chi tiết sản phẩm')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/products-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .product-show-page {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .product-show-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(15, 23, 42, 0.06);
        }

        .product-show-hero {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 320px;
            gap: 24px;
            padding: 24px;
        }

        .product-show-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .product-show-title {
            margin: 14px 0 10px;
            font-size: 30px;
            line-height: 1.2;
            color: #0f172a;
        }

        .product-show-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 16px;
        }

        .product-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 12px;
            border-radius: 999px;
            background: #f8fafc;
            color: #334155;
            font-size: 13px;
            font-weight: 600;
        }

        .product-chip.is-active {
            background: #dcfce7;
            color: #166534;
        }

        .product-chip.is-inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .product-show-slug {
            margin: 0 0 12px;
            color: #475569;
            font-size: 14px;
        }

        .product-show-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 20px;
        }

        .product-show-actions .btn {
            border-radius: 10px;
            min-width: 140px;
        }

        .product-show-image-box {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 280px;
            padding: 16px;
            border-radius: 14px;
            background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
            border: 1px solid #e2e8f0;
        }

        .product-show-image-box img {
            max-width: 100%;
            max-height: 260px;
            object-fit: contain;
            border-radius: 12px;
            background: #fff;
        }

        .product-show-image-note {
            margin-top: 12px;
            font-size: 13px;
            color: #64748b;
            text-align: center;
        }

        .product-show-stats {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 16px;
        }

        .product-show-stat {
            padding: 18px 20px;
            border-radius: 16px;
            background: #fff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
        }

        .product-show-stat-label {
            display: block;
            margin-bottom: 8px;
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .product-show-stat-value {
            color: #0f172a;
            font-size: 28px;
            font-weight: 800;
            line-height: 1;
        }

        .product-show-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .product-show-section {
            padding: 22px 24px;
        }

        .product-show-section h3 {
            margin: 0 0 18px;
            color: #0f172a;
            font-size: 18px;
            font-weight: 700;
        }

        .product-show-info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px 18px;
        }

        .product-show-info-item {
            padding: 14px 16px;
            border-radius: 12px;
            background: #f8fafc;
        }

        .product-show-info-label {
            display: block;
            margin-bottom: 6px;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .product-show-info-value {
            color: #0f172a;
            font-size: 15px;
            line-height: 1.5;
            word-break: break-word;
        }

        .product-show-richtext {
            color: #1e293b;
            line-height: 1.8;
        }

        .product-show-richtext :first-child {
            margin-top: 0;
        }

        .product-show-richtext :last-child {
            margin-bottom: 0;
        }

        .product-show-empty {
            padding: 18px;
            border-radius: 12px;
            background: #f8fafc;
            color: #64748b;
            font-size: 14px;
        }

        .product-show-tag-list,
        .product-show-link-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .product-show-tag {
            display: inline-flex;
            align-items: center;
            padding: 8px 12px;
            border-radius: 999px;
            background: #eef2ff;
            color: #4338ca;
            font-size: 13px;
            font-weight: 600;
        }

        .product-show-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 12px;
            background: #f8fafc;
            color: #0f172a;
            text-decoration: none;
            border: 1px solid #e2e8f0;
        }

        .product-show-link:hover {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .product-show-alert {
            padding: 14px 18px;
            border-radius: 14px;
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            color: #1e3a8a;
        }

        .product-show-lazy {
            padding: 22px 24px;
        }

        .product-show-lazy-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 16px;
        }

        .product-show-lazy-head h3 {
            margin: 0;
        }

        .product-show-lazy-head p {
            margin: 0;
            color: #64748b;
            font-size: 14px;
        }

        .product-show-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 16px;
        }

        .product-show-tab {
            border: 1px solid #cbd5e1;
            border-radius: 999px;
            background: #fff;
            color: #334155;
            padding: 10px 14px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }

        .product-show-tab.active {
            background: #0f172a;
            border-color: #0f172a;
            color: #fff;
        }

        .product-show-panel {
            display: none;
        }

        .product-show-panel.active {
            display: block;
        }

        .product-show-loader {
            padding: 18px;
            border-radius: 14px;
            background: #f8fafc;
            color: #64748b;
        }

        @media (max-width: 1200px) {
            .product-show-hero {
                grid-template-columns: 1fr;
            }

            .product-show-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .product-show-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .product-show-title {
                font-size: 24px;
            }

            .product-show-info-grid,
            .product-show-stats {
                grid-template-columns: 1fr;
            }

            .product-show-actions .btn {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
    <div class="product-show-page">
        @if($lockInfo)
            <div class="product-show-alert">
                Sản phẩm hiện đang được chỉnh sửa bởi <strong>{{ $lockInfo['user_name'] }}</strong>
                lúc <strong>{{ $lockInfo['locked_at']->format('d/m/Y H:i') }}</strong>.
            </div>
        @endif

        <div class="product-show-card product-show-hero">
            <div>
                <span class="product-show-kicker">Sản phẩm #{{ $product->id }}</span>
                <h1 class="product-show-title">{{ $product->name }}</h1>

                <div class="product-show-meta">
                    <span class="product-chip">SKU: {{ $product->sku }}</span>
                    <span class="product-chip {{ $product->is_active ? 'is-active' : 'is-inactive' }}">
                        {{ $product->is_active ? 'Đang bán' : 'Tạm ẩn' }}
                    </span>
                    @if($product->is_featured)
                        <span class="product-chip">Nổi bật</span>
                    @endif
                    @if($product->primaryCategory)
                        <span class="product-chip">Danh mục: {{ $product->primaryCategory->name }}</span>
                    @endif
                    @if($product->brand)
                        <span class="product-chip">Hãng: {{ $product->brand->name }}</span>
                    @endif
                </div>

                <p class="product-show-slug">Slug: <strong>{{ $product->slug }}</strong></p>

                <div class="product-show-actions">
                    <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-primary">Chỉnh sửa</a>
                    <a href="{{ route('admin.products.inventory', $product) }}" class="btn btn-outline-secondary">Quản lý kho</a>
                    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">Quay lại danh sách</a>
                    <a href="{{ $productData['frontend_url'] }}" class="btn btn-outline-secondary" target="_blank" rel="noopener">Xem frontend</a>
                </div>
            </div>

            <div>
                <div class="product-show-image-box">
                    <img src="{{ $primaryImage['url'] }}" alt="{{ $primaryImage['alt'] }}">
                </div>
                <div class="product-show-image-note">
                    @if($primaryImage['filename'])
                        Ảnh chính: {{ $primaryImage['filename'] }}
                    @else
                        Chưa có ảnh sản phẩm.
                    @endif
                </div>
            </div>
        </div>

        <div class="product-show-stats">
            <div class="product-show-stat">
                <span class="product-show-stat-label">Ảnh</span>
                <span class="product-show-stat-value">{{ $summary['images'] }}</span>
            </div>
            <div class="product-show-stat">
                <span class="product-show-stat-label">FAQ</span>
                <span class="product-show-stat-value">{{ $summary['faqs'] }}</span>
            </div>
            <div class="product-show-stat">
                <span class="product-show-stat-label">How-To</span>
                <span class="product-show-stat-value">{{ $summary['how_tos'] }}</span>
            </div>
            <div class="product-show-stat">
                <span class="product-show-stat-label">Variants</span>
                <span class="product-show-stat-value">{{ $summary['variants'] }}</span>
            </div>
            <div class="product-show-stat">
                <span class="product-show-stat-label">Comments</span>
                <span class="product-show-stat-value">{{ $summary['comments'] }}</span>
            </div>
        </div>

        <div class="product-show-grid">
            <div class="product-show-card product-show-section">
                <h3>Thông tin chính</h3>
                <div class="product-show-info-grid">
                    <div class="product-show-info-item">
                        <span class="product-show-info-label">Giá bán</span>
                        <div class="product-show-info-value">{{ number_format((float) $product->price, 0, ',', '.') }}₫</div>
                    </div>
                    <div class="product-show-info-item">
                        <span class="product-show-info-label">Giá khuyến mãi</span>
                        <div class="product-show-info-value">
                            {{ $product->sale_price ? number_format((float) $product->sale_price, 0, ',', '.') . '₫' : 'Không có' }}
                        </div>
                    </div>
                    <div class="product-show-info-item">
                        <span class="product-show-info-label">Giá vốn</span>
                        <div class="product-show-info-value">
                            {{ $product->cost_price ? number_format((float) $product->cost_price, 0, ',', '.') . '₫' : 'Không có' }}
                        </div>
                    </div>
                    <div class="product-show-info-item">
                        <span class="product-show-info-label">Tồn kho</span>
                        <div class="product-show-info-value">{{ is_null($product->stock_quantity) ? 'Không giới hạn' : $product->stock_quantity }}</div>
                    </div>
                    <div class="product-show-info-item">
                        <span class="product-show-info-label">Ngày tạo</span>
                        <div class="product-show-info-value">{{ optional($product->created_at)->format('d/m/Y H:i') ?: '-' }}</div>
                    </div>
                    <div class="product-show-info-item">
                        <span class="product-show-info-label">Cập nhật</span>
                        <div class="product-show-info-value">{{ optional($product->updated_at)->format('d/m/Y H:i') ?: '-' }}</div>
                    </div>
                </div>
            </div>

            <div class="product-show-card product-show-section">
                <h3>Phân loại & liên kết</h3>
                <div class="product-show-info-grid">
                    <div class="product-show-info-item">
                        <span class="product-show-info-label">Danh mục chính</span>
                        <div class="product-show-info-value">{{ $product->primaryCategory?->name ?? 'Chưa gán' }}</div>
                    </div>
                    <div class="product-show-info-item">
                        <span class="product-show-info-label">Thương hiệu</span>
                        <div class="product-show-info-value">{{ $product->brand?->name ?? 'Chưa gán' }}</div>
                    </div>
                </div>

                <div style="margin-top: 18px;">
                    <span class="product-show-info-label">Tags</span>
                    @if(!empty($productData['tag_names']))
                        <div class="product-show-tag-list">
                            @foreach($productData['tag_names'] as $tagName)
                                <span class="product-show-tag">{{ $tagName }}</span>
                            @endforeach
                        </div>
                    @else
                        <div class="product-show-empty">Chưa có tag nào.</div>
                    @endif
                </div>

                <div style="margin-top: 18px;">
                    <span class="product-show-info-label">Catalog</span>
                    @if(!empty($productData['catalog_links']))
                        <div class="product-show-link-list">
                            @foreach($productData['catalog_links'] as $catalogLink)
                                <a href="{{ $catalogLink['url'] }}" class="product-show-link" target="_blank" rel="noopener">
                                    {{ $catalogLink['label'] }}
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="product-show-empty">Chưa có link catalog.</div>
                    @endif
                </div>

                <div style="margin-top: 18px;">
                    <span class="product-show-info-label">Video URL</span>
                    @if($product->video_url)
                        <a href="{{ $product->video_url }}" class="product-show-link" target="_blank" rel="noopener">{{ $product->video_url }}</a>
                    @else
                        <div class="product-show-empty">Chưa có video.</div>
                    @endif
                </div>
            </div>

            <div class="product-show-card product-show-section">
                <h3>Mô tả ngắn</h3>
                @if($product->short_description)
                    <div class="product-show-richtext">{!! $product->short_description !!}</div>
                @else
                    <div class="product-show-empty">Chưa có mô tả ngắn.</div>
                @endif
            </div>

            <div class="product-show-card product-show-section">
                <h3>Mô tả chi tiết</h3>
                @if($product->description)
                    <div class="product-show-richtext">{!! $product->description !!}</div>
                @else
                    <div class="product-show-empty">Chưa có mô tả chi tiết.</div>
                @endif
            </div>

            <div class="product-show-card product-show-section" style="grid-column: 1 / -1;">
                <h3>SEO</h3>
                <div class="product-show-info-grid">
                    <div class="product-show-info-item">
                        <span class="product-show-info-label">Meta Title</span>
                        <div class="product-show-info-value">{{ $product->meta_title ?: 'Chưa có' }}</div>
                    </div>
                    <div class="product-show-info-item">
                        <span class="product-show-info-label">Meta Canonical</span>
                        <div class="product-show-info-value">{{ $product->meta_canonical ?: 'Chưa có' }}</div>
                    </div>
                </div>

                <div style="margin-top: 18px;">
                    <span class="product-show-info-label">Meta Description</span>
                    <div class="product-show-empty" style="color: #334155;">
                        {{ $product->meta_description ?: 'Chưa có meta description.' }}
                    </div>
                </div>

                <div style="margin-top: 18px;">
                    <span class="product-show-info-label">Meta Keywords</span>
                    @if(!empty($productData['meta_keywords']))
                        <div class="product-show-tag-list">
                            @foreach($productData['meta_keywords'] as $keyword)
                                <span class="product-show-tag">{{ $keyword }}</span>
                            @endforeach
                        </div>
                    @else
                        <div class="product-show-empty">Chưa có meta keywords.</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="product-show-card product-show-lazy">
            <div class="product-show-lazy-head">
                <div>
                    <h3>Dữ liệu mở rộng</h3>
                    <p>Chỉ tải khối đang xem để trang chính phản hồi nhanh nhất.</p>
                </div>
            </div>

            <div class="product-show-tabs">
                <button type="button" class="product-show-tab active" data-section="images">Gallery ảnh</button>
                <button type="button" class="product-show-tab" data-section="faqs">FAQs</button>
                <button type="button" class="product-show-tab" data-section="how-tos">How-Tos</button>
                <button type="button" class="product-show-tab" data-section="variants">Variants</button>
            </div>

            <div class="product-show-panel active" data-panel="images">
                <div class="product-show-loader">Đang tải gallery ảnh...</div>
            </div>
            <div class="product-show-panel" data-panel="faqs">
                <div class="product-show-loader">Chọn tab FAQs để tải dữ liệu.</div>
            </div>
            <div class="product-show-panel" data-panel="how-tos">
                <div class="product-show-loader">Chọn tab How-Tos để tải dữ liệu.</div>
            </div>
            <div class="product-show-panel" data-panel="variants">
                <div class="product-show-loader">Chọn tab Variants để tải dữ liệu.</div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const routeTemplate = @json(route('admin.products.show-fragment', ['product' => $product->id, 'section' => '__SECTION__']));
            const tabs = document.querySelectorAll('.product-show-tab');
            const panels = document.querySelectorAll('.product-show-panel');
            const loadedSections = new Set();

            function setActiveSection(section) {
                tabs.forEach(function (tab) {
                    tab.classList.toggle('active', tab.dataset.section === section);
                });

                panels.forEach(function (panel) {
                    panel.classList.toggle('active', panel.dataset.panel === section);
                });
            }

            function loadSection(section) {
                const panel = document.querySelector('.product-show-panel[data-panel="' + section + '"]');
                if (!panel || loadedSections.has(section)) {
                    return;
                }

                panel.innerHTML = '<div class="product-show-loader">Đang tải dữ liệu...</div>';

                fetch(routeTemplate.replace('__SECTION__', section), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Không thể tải dữ liệu.');
                        }

                        return response.text();
                    })
                    .then(function (html) {
                        panel.innerHTML = html;
                        loadedSections.add(section);
                    })
                    .catch(function () {
                        panel.innerHTML = '<div class="product-show-empty">Không thể tải dữ liệu cho mục này.</div>';
                    });
            }

            tabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    const section = tab.dataset.section;
                    setActiveSection(section);
                    loadSection(section);
                });
            });

            loadSection('images');
        });
    </script>
@endpush
