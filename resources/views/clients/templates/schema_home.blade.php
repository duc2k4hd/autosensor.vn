@php
    $siteUrl   = rtrim($settings->site_url ?? 'https://autosensor.vn', '/');
    $logoUrl   = asset('clients/assets/img/business/'.($settings->site_logo ?? 'no-image.webp'));
    $bannerUrl = asset('clients/assets/img/banners/'.($settings->site_banner ?? 'no-image.webp'));

    // Social links – loại trùng & rỗng
    $socialLinks = array_values(array_unique(array_filter([
        optional($settings)->facebook_link,
        optional($settings)->instagram_link,
        optional($settings)->discord_link,
    ])));

    // Sản phẩm nổi bật
    $featuredProducts = ($productsFeatured ?? collect())->take(10);
    $featuredItems = [];

    foreach ($featuredProducts as $index => $product) {

        $productItem = [
            '@type' => 'Product',
            '@id'   => $siteUrl.'/san-pham/'.$product->slug,
            'url'   => $siteUrl.'/san-pham/'.$product->slug,
            'name'  => $product->name,
            'image' => asset('clients/assets/img/clothes/'.($product->primaryImage->url ?? 'no-image.webp')),
            'sku'   => $product->sku,
            'inLanguage' => 'vi',
            'offers' => [
                '@type' => 'Offer',
                'priceCurrency' => 'VND',
                'price' => (string) $product->resolveCartPrice(),
                'priceValidUntil' => date('Y-12-31', strtotime('+1 year')),
                'availability' => ($product->stock_quantity ?? 0) > 0
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
                'seller' => ['@id' => $siteUrl.'#localbusiness'],
            ],
        ];

        // 👉 CHỈ thêm aggregateRating khi có review
        if (
            ($product->approved_comments_count ?? 0) > 0 &&
            !empty($product->approved_rating_avg)
        ) {
            $productItem['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => round((float) $product->approved_rating_avg, 1),
                'ratingCount' => (int) $product->approved_comments_count,
                'reviewCount' => (int) $product->approved_comments_count,
            ];
        }

        $featuredItems[] = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'item' => $productItem,
        ];
    }
@endphp

<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [

        // ORGANIZATION
        [
            '@type' => 'Organization',
            '@id' => $siteUrl.'#organization',
            'name' => $settings->site_name ?? 'AutoSensor Việt Nam',
            'legalName' => 'CÔNG TY AutoSensor Việt Nam',
            'foundingDate' => '2025',
            'url'  => $siteUrl,
            'logo' => $logoUrl,
            'email' => $settings->contact_email ?? 'info@autosensor.vn',
            'brand' => [
                '@type' => 'Brand',
                'name' => $settings->site_name ?? 'AutoSensor Việt Nam',
            ],
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $settings->contact_address ?? 'Xóm 3 - Xã Hà Đông - Thành Phố Hải Phòng',
                'addressLocality' => $settings->city ?? 'Hải Phòng',
                'addressRegion' => $settings->city ?? 'Hải Phòng',
                'postalCode' => $settings->postalCode ?? '180000',
                'addressCountry' => 'VN',
            ],
            'contactPoint' => [[
                '@type' => 'ContactPoint',
                'telephone' => '+84-'.ltrim($settings->contact_phone ?? '0827786198', '0'),
                'contactType' => 'customer service',
            ]],
            'sameAs' => $socialLinks,
        ],

        // WEBSITE
        [
            '@type' => 'WebSite',
            '@id' => $siteUrl.'#website',
            'url' => $siteUrl,
            'name' => $settings->site_name ?? ($settings->subname ?? 'Xanh World'),
            'publisher' => ['@id' => $siteUrl.'#organization'],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => $siteUrl.'/shop?search={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ],

        // LOCAL BUSINESS
        [
            '@type' => ['LocalBusiness', 'Store'],
            '@id' => $siteUrl.'#localbusiness',
            'name' => $settings->site_name ?? 'AutoSensor Việt Nam',
            'logo' => [
                '@type' => 'ImageObject',
                'url' => $logoUrl,
            ],
            'image' => $bannerUrl,
            'url' => $siteUrl,
            'telephone' => '+84-'.ltrim($settings->contact_phone ?? '0827786198', '0'),
            'priceRange' => '₫₫',
            'paymentAccepted' => 'Cash, Bank Transfer',
            'parentOrganization' => ['@id' => $siteUrl.'#organization'],
            'areaServed' => [
                '@type' => 'AdministrativeArea',
                'name' => $settings->city ?? 'Hải Phòng',
            ],
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $settings->contact_address ?? 'Xóm 3 - Xã Hà Đông - Thành Phố Hải Phòng',
                'addressLocality' => $settings->city ?? 'Hải Phòng',
                'addressRegion' => $settings->city ?? 'Hải Phòng',
                'postalCode' => $settings->postalCode ?? '180000',
                'addressCountry' => 'VN',
            ],
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => $settings->latitude ?? 20.86481,
                'longitude' => $settings->longitude ?? 106.68345,
            ],
            'openingHoursSpecification' => [[
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'],
                'opens' => '08:00',
                'closes' => '21:00',
            ]],
            'sameAs' => $socialLinks,
            'hasOfferCatalog' => [
                '@type' => 'OfferCatalog',
                'name' => 'Danh mục thiết bị tự động hóa AutoSensor Việt Nam',
            ],
        ],

        // WEBPAGE
        [
            '@type' => 'WebPage',
            '@id' => $siteUrl.'#webpage',
            'url' => $siteUrl,
            'name' => $settings->site_name ?? 'Trang chủ Xanh World',
            'description' => $settings->site_description
                ?? 'AutoSensor Việt Nam – Nhà cung cấp thiết bị tự động hóa công nghiệp: cảm biến, PLC, HMI, biến tần, servo, encoder, rơ le và giải pháp tự động hóa chuyên nghiệp.',
            'inLanguage' => $settings->site_language ?? 'vi-VN',
            'isPartOf' => ['@id' => $siteUrl.'#website'],
            'publisher' => ['@id' => $siteUrl.'#organization'],
            'mainEntityOfPage' => ['@id' => $siteUrl],
        ],

        // FEATURED PRODUCTS
        [
            '@type' => 'ItemList',
            '@id' => $siteUrl.'#featured-products',
            'name' => 'Sản phẩm nổi bật',
            'itemListOrder' => 'https://schema.org/ItemListOrderAscending',
            'numberOfItems' => count($featuredItems),
            'itemListElement' => $featuredItems,
            'mainEntityOfPage' => ['@id' => $siteUrl],
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
