<!-- 🌐 SCHEMA TRANG CỬA HÀNG / DANH MỤC - AutoSensor Việt Nam -->
@php
    $siteUrl = rtrim($settings->site_url ?? url('/'), '/');
    $currentUrl = url()->current();
@endphp
<script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@graph": [
  
      /* ================= ORGANIZATION ================= */
      {
        "@type": "Organization",
        "@id": "{{ $siteUrl }}#organization",
        "name": "{{ $settings->site_name ?? 'AutoSensor Việt Nam' }}",
        "url": "{{ $siteUrl }}",
        "logo": "{{ asset('clients/assets/img/business/' . ($settings->site_logo ?? 'no-image.webp')) }}",
        "email": "{{ $settings->contact_email ?? '' }}",
        "telephone": "{{ $settings->contact_phone ?? '' }}",
        "address": {
          "@type": "PostalAddress",
          "streetAddress": "{{ $settings->contact_address ?? '' }}",
          "addressLocality": "{{ $settings->city ?? '' }}",
          "addressRegion": "{{ $settings->city ?? '' }}",
          "postalCode": "{{ $settings->postalCode ?? '' }}",
          "addressCountry": "VN"
        },
        "sameAs": [
          "{{ $settings->facebook_link ?? '' }}",
          "{{ $settings->instagram_link ?? '' }}",
          "{{ $settings->discord_link ?? '' }}"
        ]
      },
  
      /* ================= ONLINE STORE (NEW) ================= */
      {
        "@type": "OnlineStore",
        "@id": "{{ $siteUrl }}#onlinestore",
        "name": "{{ $settings->site_name ?? 'AutoSensor Việt Nam' }}",
        "url": "{{ $siteUrl }}",
        "logo": "{{ asset('clients/assets/img/business/' . ($settings->site_logo ?? 'no-image.webp')) }}",
        "priceRange": "₫₫",
        "currenciesAccepted": "VND",
        "paymentAccepted": ["Cash","BankTransfer","COD"],
        "isPartOf": { "@id": "{{ $siteUrl }}#organization" }
      },
  
      /* ================= WEBSITE ================= */
      {
        "@type": "WebSite",
        "@id": "{{ $siteUrl }}#website",
        "url": "{{ $siteUrl }}",
        "name": "{{ $settings->site_name ?? 'AutoSensor Việt Nam' }}",
        "publisher": { "@id": "{{ $siteUrl }}#organization" },
        "potentialAction": {
          "@type": "SearchAction",
          "target": "{{ $siteUrl }}/tim-kiem/{search_term_string}",
          "query-input": "required name=search_term_string"
        }
      },
  
      /* ================= WEBPAGE (CATEGORY PAGE) ================= */
      {
        "@type": "WebPage",
        "@id": "{{ $currentUrl }}#webpage",
        "url": "{{ $currentUrl }}",
        "name": {!! json_encode(
          !empty($category) && !empty($category->metadata['meta_title'])
            ? $category->metadata['meta_title']
            : ($category->name ?? 'Danh mục sản phẩm'),
          JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) !!},
        "description": {!! json_encode(
          !empty($category) && !empty($category->metadata['meta_description'])
            ? $category->metadata['meta_description']
            : strip_tags($category->description ?? 'Danh mục thiết bị tự động hóa công nghiệp tại AutoSensor Việt Nam.'),
          JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) !!},
        "inLanguage": "vi-VN",
        "isPartOf": { "@id": "{{ $siteUrl }}#website" },
        "about": { "@id": "{{ $siteUrl }}#onlinestore" },
        "mainEntity": { "@id": "{{ $currentUrl }}#itemlist" }
      },
  
      /* ================= LOCAL BUSINESS (PHYSICAL STORE – CLEAN) ================= */
      {
        "@type": ["LocalBusiness","Store"],
        "@id": "{{ $siteUrl }}#localbusiness",
        "name": "{{ $settings->site_name ?? 'AutoSensor Việt Nam' }}",
        "url": "{{ $siteUrl }}",
        "logo": "{{ asset('clients/assets/img/business/' . ($settings->site_logo ?? 'no-image.webp')) }}",
        "image": "{{ asset('clients/assets/img/banners/' . ($settings->site_banner ?? 'banner.jpg')) }}",
        "telephone": "{{ $settings->contact_phone ?? '' }}",
        "email": "{{ $settings->contact_email ?? '' }}",
        "address": {
          "@type": "PostalAddress",
          "streetAddress": "{{ $settings->contact_address ?? '' }}",
          "addressLocality": "{{ $settings->city ?? '' }}",
          "addressRegion": "{{ $settings->city ?? '' }}",
          "postalCode": "{{ $settings->postalCode ?? '' }}",
          "addressCountry": "VN"
        },
        "geo": {
          "@type": "GeoCoordinates",
          "latitude": "{{ $settings->latitude ?? 20.86481 }}",
          "longitude": "{{ $settings->longitude ?? 106.68345 }}"
        },
        "openingHoursSpecification": [{
          "@type": "OpeningHoursSpecification",
          "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"],
          "opens": "08:00",
          "closes": "17:30"
        }]
      },
  
      /* ================= BREADCRUMB ================= */
      {
        "@type": "BreadcrumbList",
        "@id": "{{ $siteUrl }}#breadcrumb",
        "itemListElement": [
          {
            "@type": "ListItem",
            "position": 1,
            "item": { "@id": "{{ $siteUrl }}", "name": "Trang chủ" }
          },
          {
            "@type": "ListItem",
            "position": 2,
            "item": { "@id": "{{ route('client.shop.index') }}", "name": "Cửa hàng" }
          }
          @if(!empty($category))
          ,{
            "@type": "ListItem",
            "position": 3,
            "item": { "@id": "{{ $currentUrl }}", "name": "{{ $category->name }}" }
          }
          @endif
        ]
      },
  
      /* ================= ITEM LIST (CATEGORY PRODUCTS) ================= */
      {
        "@type": "ItemList",
        "@id": "{{ $currentUrl }}#itemlist",
        "url": "{{ $currentUrl }}",
        "name": {!! json_encode(
          'Danh sách sản phẩm ' . ($category->name ?? ''),
          JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) !!},
        "itemListOrder": "https://schema.org/ItemListOrderAscending",
        "numberOfItems": {{ method_exists($products,'total') ? $products->total() : $products->count() }},
        "mainEntityOfPage": { "@id": "{{ $currentUrl }}#webpage" },
        "itemListElement": [
          @foreach($products as $index => $product)
          {
            "@type": "ListItem",
            "position": {{ $loop->iteration }},
            "url": "{{ $product->canonical_url ?? route('client.product.detail', ['slug' => $product->slug]) }}",
            "name": {!! json_encode($product->name, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
          }{{ !$loop->last ? ',' : '' }}
          @endforeach
        ]
      }
  
    ]
  }
  </script>