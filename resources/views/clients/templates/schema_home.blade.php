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
            '@id'   => $siteUrl.'/'.$product->slug,
            'url'   => $siteUrl.'/'.$product->slug,
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

        [
            '@type' => 'OnlineStore',
            '@id' => $siteUrl.'#onlinestore',
            'name' => $settings->site_name ?? 'AutoSensor Việt Nam',
            'url' => $siteUrl,
            'logo' => $logoUrl,
            'priceRange' => '₫₫',
            'currenciesAccepted' => 'VND',
            'paymentAccepted' => [
                'Cash',
                'BankTransfer',
                'COD'
            ],
            'isPartOf' => [
                '@id' => $siteUrl.'#organization',
            ],
        ],

        [
            '@type' => 'WebSite',
            '@id' => $siteUrl.'#website',
            'url' => $siteUrl,
            'name' => $settings->site_name,
            'publisher' => ['@id' => $siteUrl.'#organization'],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => $siteUrl.'/shop?search={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ],

        // LOCAL BUSINESS
        [
            '@type' => ['LocalBusiness','Store'],
            '@id' => $siteUrl.'#localbusiness',
            'name' => $settings->site_name,
            'url' => $siteUrl,
            'logo' => ['@type' => 'ImageObject','url' => $logoUrl],
            'image' => $bannerUrl,
            'telephone' => '+84-'.ltrim($settings->contact_phone,'0'),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $settings->contact_address,
                'addressLocality' => $settings->city,
                'addressRegion' => $settings->city,
                'postalCode' => $settings->postalCode,
                'addressCountry' => 'VN',
            ],
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => $settings->latitude,
                'longitude' => $settings->longitude,
            ],
            'openingHoursSpecification' => [[
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'],
                'opens' => '08:00',
                'closes' => '21:00',
            ]],
            'parentOrganization' => ['@id' => $siteUrl.'#organization'],
        ],

        // WEBPAGE
        [
            '@type' => 'WebPage',
            '@id' => $siteUrl.'#homepage',
            'url' => $siteUrl,
            'name' => 'Trang chủ – '.$settings->site_name,
            'isPartOf' => ['@id' => $siteUrl.'#website'],
            'publisher' => ['@id' => $siteUrl.'#organization'],
            'mainEntity' => ['@id' => $siteUrl.'#onlinestore'],
            'inLanguage' => 'vi-VN',
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
