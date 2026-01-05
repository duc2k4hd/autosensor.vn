<!-- 🌐 SCHEMA TRANG CỬA HÀNG / DANH MỤC - AutoSensor Việt Nam -->
@php
    $siteUrl = rtrim($settings->site_url ?? url('/'), '/');
    $currentUrl = url()->current();
@endphp
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [
  {
    "@type": "Organization",
    "@id": "{{ $siteUrl }}#organization",
    "name": "{{ $settings->site_name ?? 'AutoSensor Việt Nam' }}",
    "image": "{{ asset('clients/assets/img/banners/' . ($settings->site_banner ?? 'banner.jpg')) }}",
    "url": "{{ $siteUrl }}",
    "logo": {
      "@type": "ImageObject",
      "url": "{{ asset('clients/assets/img/business/' . ($settings->site_logo ?? 'no-image.webp')) }}",
      "width": 600,
      "height": 200
    },
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
    "contactPoint": [{
      "@type": "ContactPoint",
      "telephone": "{{ $settings->contact_phone ?? '' }}",
      "contactType": "customer service",
      "availableLanguage": ["Vietnamese"],
      "areaServed": "VN"
    }],
    "sameAs": [
      "{{ $settings->facebook_link ?? 'https://www.facebook.com' }}",
      "{{ $settings->instagram_link ?? 'https://www.instagram.com' }}",
      "{{ $settings->discord_link ?? 'https://discord.com' }}"
    ]
  },
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
  {
    "@type": "WebPage",
    "@id": "{{ $currentUrl }}#webpage",
    "url": "{{ $currentUrl }}",
    "name": {!! json_encode(!empty($category) && !empty($category->metadata['meta_title']) ? $category->metadata['meta_title'] : ($category->name ?? ($settings->site_name ?? 'AutoSensor Việt Nam')), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
    "description": {!! json_encode(!empty($category) && !empty($category->metadata['meta_description']) ? $category->metadata['meta_description'] : (!empty($category) ? strip_tags($category->description ?? 'Danh mục thiết bị tự động hóa công nghiệp: cảm biến, PLC, HMI, biến tần, servo, encoder, rơ le và giải pháp tự động hóa tại AutoSensor Việt Nam.') : 'Danh mục thiết bị tự động hóa công nghiệp: cảm biến, PLC, HMI, biến tần, servo, encoder, rơ le và giải pháp tự động hóa tại AutoSensor Việt Nam.'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
    "inLanguage": "vi",
    "isPartOf": { "@id": "{{ $siteUrl }}#website" },
    "about": { "@id": "{{ $siteUrl }}#organization" },
    "breadcrumb": { "@id": "{{ $siteUrl }}#breadcrumb" },
    "primaryImageOfPage": {
      "@type": "ImageObject",
      "url": "{{ asset('clients/assets/img/business/' . ($settings->site_logo ?? 'no-image.webp')) }}"
    },
    "datePublished": "{{ now()->toDateString() }}",
    "dateModified": "{{ now()->toDateString() }}"
  },
  {
    "@type": "LocalBusiness",
    "@id": "{{ $siteUrl }}#localbusiness",
    "name": "{{ $settings->site_name ?? 'AutoSensor Việt Nam' }}",
    "image": "{{ asset('clients/assets/img/banners/' . ($settings->site_banner ?? 'banner.jpg')) }}",
    "logo": {
      "@type": "ImageObject",
      "url": "{{ asset('clients/assets/img/business/' . ($settings->site_logo ?? 'no-image.webp')) }}"
    },
    "url": "{{ $siteUrl }}",
    "telephone": "{{ $settings->contact_phone ?? '' }}",
    "email": "{{ $settings->contact_email ?? '' }}",
    "priceRange": "₫₫",
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
    }],
    "sameAs": [
      "{{ $settings->facebook_link ?? 'https://www.facebook.com' }}",
      "{{ $settings->instagram_link ?? 'https://www.instagram.com' }}",
      "{{ $settings->discord_link ?? 'https://discord.com' }}"
    ]
  },
  {
    "@type": "BreadcrumbList",
    "@id": "{{ $siteUrl }}#breadcrumb",
    "itemListElement": [
      {
        "@type": "ListItem",
        "position": 1,
        "item": {
          "@id": "{{ $siteUrl }}",
          "name": "Trang chủ"
        }
      },
      {
        "@type": "ListItem",
        "position": 2,
        "item": {
          "@id": "{{ route('client.shop.index') }}",
          "name": "Cửa hàng thiết bị tự động hóa"
        }
      }
      @if(!empty($category))
      ,{
        "@type": "ListItem",
        "position": 3,
        "item": {
          "@id": "{{ url()->current() }}",
          "name": "{{ $category->name }}"
        }
      }
      @endif
    ]
  },
  {
    "@type": "ItemList",
    "@id": "{{ $currentUrl }}#itemlist",
    "url": "{{ $currentUrl }}",
    "name": {!! json_encode('Danh sách sản phẩm '.(!empty($category) && !empty($category->metadata['meta_title']) ? $category->metadata['meta_title'] : ($category->name ?? 'thiết bị tự động hóa công nghiệp')), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
    "itemListOrder": "https://schema.org/ItemListOrderAscending",
    "mainEntityOfPage": {
      "@id": "{{ $currentUrl }}#webpage"
    },
    "numberOfItems": {{ method_exists($products, 'total') ? $products->total() : $products->count() }},
    "itemListElement":[
      @foreach($products as $index => $product)
      @php
        $productUrl = $product->canonical_url ?? route('client.product.detail', ['slug' => $product->slug]);
      @endphp
      {
        "@type": "ListItem",
        "position": {{ $loop->iteration }},
        "url": {!! json_encode($productUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
        "name": {!! json_encode($product->name, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
      }{{ !$loop->last ? ',' : '' }}
      @endforeach
    ]
  }
]
}
</script>
<!-- 🌐 END SCHEMA CHUẨN TRANG DANH MỤC -->
