@php
    $siteUrl = rtrim($settings->site_url ?? 'https://autosensor.vn', '/');
    $productUrl = $product->canonical_url ?? ($siteUrl.'/'.($product->slug ?? 'san-pham'));
    $productUrl = rtrim($productUrl, '/'); // Đảm bảo không có dấu / cuối
    $sameAs = array_values(array_unique(array_filter([
        $settings->facebook_link ?? 'https://www.facebook.com/autosensor.vn',
        $settings->instagram_link ?? 'https://www.instagram.com/autosensor.vn',
        $settings->discord_link ?? 'https://discord.gg/autosensor',
    ])));
    $pageTitle = $product->meta_title
      ? $product->meta_title . ' | ' . ($settings->site_name ?? $settings->subname)
      : ($product->name ?? 'AutoSensor Việt Nam');

    $keywords = $product->meta_keywords;

    if (is_string($keywords)) {
        $keywords = array_map('trim', explode(',', $keywords));
    }

    if (!is_array($keywords) || empty($keywords)) {
        $keywords = [
            'cảm biến công nghiệp',
            'PLC',
            'HMI',
            'biến tần',
            'servo',
            'encoder',
            'rơ le',
            'thiết bị tự động hóa',
            'tự động hóa công nghiệp',
            'AutoSensor Việt Nam',
        ];
    }

    // Lọc keywords: chỉ giữ keyword ngắn, không có dấu ":", không phải title dài
    $keywords = array_filter($keywords, function($keyword) {
        $keyword = trim($keyword);
        // Bỏ qua keyword quá dài (> 50 ký tự) hoặc có dấu ":"
        if (empty($keyword) || mb_strlen($keyword) > 50 || strpos($keyword, ':') !== false) {
            return false;
        }
        return true;
    });

    // Thêm keyword từ slug (slug thường ngắn và không có dấu ":")
    $slugKeyword = $product->slug ?? null;
    if ($slugKeyword && mb_strlen($slugKeyword) <= 50 && strpos($slugKeyword, ':') === false) {
        $keywords[] = $slugKeyword;
    }

    $keywords = array_values(array_unique($keywords));
@endphp
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "Organization",
      "@id": "{{ $siteUrl }}#organization",
      "name": "{{ $settings->site_name ?? 'AutoSensor Việt Nam - Thiết bị tự động hóa công nghiệp' }}",
      "url": "{{ $siteUrl }}",
      "logo": "{{ asset('clients/assets/img/business/' . ($settings->site_logo ?? 'no-image.webp')) }}",
      "email": "{{ ($settings->contact_email ?? 'info@autosensor.vn') }}",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "{{ ($settings->contact_address ?? 'Xóm 3 - Xã Hà Đông - Thành Phố Hải Phòng') }}",
        "addressRegion": "{{ ($settings->city ?? 'Hải Phòng') }}",
        "postalCode": "{{ ($settings->postalCode ?? '180000') }}",
        "addressCountry": "VN",
        "addressLocality": "{{ ($settings->city ?? 'Hải Phòng') }}"
      },
      "contactPoint": [
        {
          "@type": "ContactPoint",
          "telephone": "{{ ($settings->contact_phone ?? '0827 786 198') }}",
          "contactType": "customer service"
        }
      ],
      "sameAs": {!! json_encode($sameAs, JSON_UNESCAPED_SLASHES) !!}
    },
    {
      "@type": "OnlineStore",
      "@id": "{{ $siteUrl }}#onlinestore",
      "name": "{{ $settings->site_name ?? 'AutoSensor Việt Nam' }}",
      "url": "{{ $siteUrl }}",
      "logo": "{{ asset('clients/assets/img/business/' . ($settings->site_logo ?? 'no-image.webp')) }}",
      "priceRange": "₫₫",
      "currenciesAccepted": "VND",
      "paymentAccepted": ["Cash", "BankTransfer", "COD"],
      "isPartOf": {
        "@id": "{{ $siteUrl }}#organization"
      }
    },
    {
      "@type": "WebPage",
      "@id": "{{ $productUrl }}#webpage",
      "url": "{{ $productUrl }}",
      "name": "{{ $pageTitle }}",
      "description": "{{ $product->meta_desc ?? 'AutoSensor Việt Nam: Thiết bị tự động hóa công nghiệp chính hãng. Cảm biến, PLC, HMI, biến tần, servo, encoder, rơ le và giải pháp tự động hóa chuyên nghiệp. Giao hàng nhanh, bảo hành chính hãng.' }}",
      "inLanguage": "{{ ($settings->site_language ?? 'vi') }}",
      "isPartOf": {
        "@id": "{{ $siteUrl }}#website"
      },
      "mainEntityOfPage": {
        "@id": "{{ $productUrl }}"
      }
    },
    {
      "@type": "LocalBusiness",
      "@id": "{{ $siteUrl }}#localbusiness",
      "name": "{{ $settings->site_name ?? 'AutoSensor Việt Nam' }}",
      "logo": {
        "@type": "ImageObject",
        "url": "{{ asset('clients/assets/img/business/' . ($settings->site_logo ?? 'no-image.webp')) }}"
      },
      "image": "{{ asset('clients/assets/img/banners/' . ($settings->site_banner ?? 'no-image.webp')) }}",
      "url": "{{ $siteUrl }}",
      "telephone": "{{ $settings->contact_phone ?? '0827786198' }}",
      "priceRange": "₫₫",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "{{ ($settings->contact_address ?? 'Xóm 3 - Xã Hà Đông - Thành Phố Hải Phòng') }}",
        "addressLocality": "{{ ($settings->city ?? 'Hải Phòng') }}",
        "addressRegion": "{{ ($settings->city ?? 'Hải Phòng') }}",
        "postalCode": "{{ ($settings->postalCode ?? '180000') }}",
        "addressCountry": "VN"
      },
      "geo": {
        "@type": "GeoCoordinates",
        "latitude": {{ ($settings->latitude ?? 20.86481) }},
        "longitude": {{ ($settings->longitude ?? 106.68345) }}
      },
      "openingHoursSpecification": [{
        "@type": "OpeningHoursSpecification",
          "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"],
        "opens": "08:00",
        "closes": "21:00"
      }],
      "parentOrganization": {
        "@id": "{{ $siteUrl }}#organization"
      }
    },
    {
      "@type": "BreadcrumbList",
      "itemListElement": [
        {
          "@type": "ListItem",
          "position": 1,
          "item": {
            "@id": "{{ $siteUrl }}",
            "name": "Trang chủ AutoSensor Việt Nam"
          }
        }
        @php
          $position = 2;
          $categoryBreadcrumb = $product->primaryCategory;
          $breadcrumbPath = collect();
          while ($categoryBreadcrumb) {
            $breadcrumbPath->prepend($categoryBreadcrumb);
            $categoryBreadcrumb = $categoryBreadcrumb->parent ?? null;
          }
        @endphp
        @foreach ($breadcrumbPath as $breadcrumb)
          ,{
            "@type": "ListItem",
            "position": {{ $position }},
            "item": {
              "@id": "{{ $siteUrl . '/' . $breadcrumb->slug }}",
              "name": "{{ $breadcrumb->name }}"
            }
          }
          @php $position++; @endphp
        @endforeach
        @if ($product->primaryCategory)
          @php $lastCategory = $product->extraCategories()->last(); @endphp
          @if ($lastCategory && !$breadcrumbPath->contains('id', $lastCategory->id))
            ,{
              "@type": "ListItem",
              "position": {{ $position }},
              "item": {
                "@id": "{{ $siteUrl . '/' . $lastCategory->slug }}",
                "name": "{{ $lastCategory->name }}"
              }
            }
            @php $position++; @endphp
          @endif
        @endif
        ,{
          "@type": "ListItem",
          "position": {{ $position }},
          "item": {
            "@id": "{{ $productUrl }}",
            "name": "{{ $pageTitle }}"
          }
        }
      ]
    },
  {
    "@type": "Product",
    "@id": "{{ $productUrl }}#product",
    "mainEntityOfPage": {
      "@id": "{{ $productUrl }}"
    },
    "name": "{{ $pageTitle }}",
    "image": {
      "@type": "ImageObject",
      "url": "{{ asset('clients/assets/img/clothes/' . (optional($product->primaryImage)->url ?? 'no-image.jpg')) }}",
      "width": 600,
      "height": 600
    },
    "description": "{{ $product->meta_description ?? 'AutoSensor Việt Nam: Thiết bị tự động hóa công nghiệp chính hãng, chất lượng cao. Cảm biến, PLC, HMI, biến tần, servo, encoder, rơ le. Giao hàng nhanh, hỗ trợ kỹ thuật & bảo hành chính hãng.' }}",
    "sku": "{{ ($product->sku ?? 'SKU-DEFAULT') }}",
    "mpn": "{{ ($product->sku ?? 'SKU-DEFAULT') }}",
    "brand": {
      "@type": "Brand",
      "@id": "{{ $siteUrl }}#brand-autosensor",
      "name": "{{ $settings->site_name ?? 'AutoSensor Việt Nam' }}"
    },
    "manufacturer": {
      "@type": "Organization",
      "@id": "{{ $siteUrl }}#manufacturer-autosensor",
      "name": "{{ $settings->site_name ?? 'AutoSensor Việt Nam' }}"
    },
      "countryOfOrigin": {
        "@type": "Country",
        "name": "{{ 'Việt Nam' }}"
      },
      @php
        $schemaRatingTotal = $ratingStats['total_comments'] ?? ($product->approved_comments_count ?? 0);
        $schemaRatingAvg = $ratingStats['average_rating'] ?? ($product->approved_rating_avg ?? null);
      @endphp
      @if($schemaRatingTotal > 0 && $schemaRatingAvg)
        "aggregateRating": {
          "@type": "AggregateRating",
          "ratingValue": "{{ round($schemaRatingAvg, 1) }}",
          "reviewCount": "{{ (int) $schemaRatingTotal }}"
        },
      @endif
      "isFamilyFriendly": true,
      "keywords": {!! json_encode($keywords, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
      "releaseDate": "{{ (($product->created_at ?? null) ? $product->created_at->format('Y-m-d') : now()->format('Y-m-d')) }}",
      "audience": {
        "@type": "PeopleAudience",
        "@id": "{{ $siteUrl }}#audience-{{ optional($product->brand)->slug ?? 'autosensor' }}",
        "audienceType": "Doanh nghiệp và kỹ sư tự động hóa công nghiệp"
      },
      "offers": {
        "@type": "Offer",
        "url": "{{ $productUrl }}",
        "priceCurrency": "VND",
        "price": {{ (int) ($product->price ?? 199000) }},
        "priceValidUntil": "{{ (\Carbon\Carbon::now()->addMonths(6)->format('Y-m-d')) }}",
        "availability": "{{ ($product->in_stock ?? true) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock' }}",
        "itemCondition": "https://schema.org/NewCondition",
        "seller": {
          "@type": "OnlineStore",
          "@id": "{{ $siteUrl }}#onlinestore",
          "name": "{{ $settings->site_name ?? 'AutoSensor Việt Nam' }}"
        },
        "shippingDetails": {
          "@type": "OfferShippingDetails",
          "shippingDestination": { "@type": "DefinedRegion", "addressCountry": "VN" },
          "shippingRate": { "@type": "MonetaryAmount", "value": "{{ ($product->shipping_fee ?? 30000) }}", "currency": "VND" },
          "deliveryTime": {
            "@type": "ShippingDeliveryTime",
            "handlingTime": { "@type": "QuantitativeValue", "minValue": 1, "maxValue": 2, "unitCode": "DAY" },
            "transitTime": { "@type": "QuantitativeValue", "minValue": 1, "maxValue": 3, "unitCode": "DAY" }
          }
        },
        "hasMerchantReturnPolicy": {
          "@type": "MerchantReturnPolicy",
          "returnPolicyCategory": "https://schema.org/MerchantReturnFiniteReturnWindow",
          "merchantReturnDays": {{ ($settings->return_days ?? 7) }},
          "returnMethod": "https://schema.org/ReturnByMail",
          "returnFees": "https://schema.org/FreeReturn",
          "refundType": "https://schema.org/FullRefund",
          "applicableCountry": "VN",
          "merchantReturnLink": "https://autosensor.vn/chinh-sach-doi-tra"
        }
      }@if(isset($latestReviews) && $latestReviews->count() > 0),
        "review": [
          @foreach($latestReviews as $review)
            {
              "@type": "Review",
              "author": {
                "@type": "Person",
                "name": "{{ $review->account->name ?? $review->name ?? 'Khách hàng' }}"
              },
              "reviewRating": {
                "@type": "Rating",
                "ratingValue": "{{ (int) ($review->rating ?? 5) }}",
                "bestRating": "5",
                "worstRating": "1"
              },
              "datePublished": "{{ optional($review->created_at)->format('Y-m-d') ?? now()->format('Y-m-d') }}",
              "reviewBody": {!! json_encode(Str::limit($review->content ?? '', 200), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
            }{{ !$loop->last ? ',' : '' }}
          @endforeach
      ]@endif
    },
    {
      "@type": "FAQPage",
      "name": "Câu hỏi thường gặp về {{ $product->name ?? 'thiết bị tự động hóa tại AutoSensor Việt Nam' }}",
      "mainEntity": [
        @if ($product->faqs && $product->faqs->count())
          @foreach ($product->faqs as $faq)
            {
              "@type": "Question",
              "name": "{{ $faq->question ?? 'Thiết bị có chính hãng, đúng model và đúng thông số kỹ thuật như mô tả không?' }}",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": {!! json_encode($faq->answer ?? '...', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
              }
            }{{ !$loop->last ? ',' : '' }}
          @endforeach
        @else
          {
            "@type": "Question",
            "name": "Thiết bị tại AutoSensor Việt Nam có đảm bảo chính hãng không?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "Tất cả thiết bị đều được nhập khẩu chính hãng, kiểm tra chất lượng trước khi đóng gói. AutoSensor Việt Nam cam kết thiết bị chính hãng, đúng model và thông số kỹ thuật."
            }
          },
          {
            "@type": "Question",
            "name": "Có hướng dẫn lắp đặt và vận hành sau khi mua không?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "AutoSensor Việt Nam cung cấp hướng dẫn lắp đặt và vận hành chi tiết cho từng thiết bị: sơ đồ đấu nối, cài đặt thông số, xử lý sự cố và bảo trì định kỳ."
            }
          },
          {
            "@type": "Question",
            "name": "Thiết bị có được đổi trả nếu lỗi kỹ thuật không?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "Có. Nếu thiết bị lỗi kỹ thuật hoặc hư hại do vận chuyển, AutoSensor Việt Nam hỗ trợ đổi mới hoặc hoàn tiền theo chính sách bảo hành."
            }
          },
          {
            "@type": "Question",
            "name": "Thời gian giao hàng là bao lâu?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "Nội thành giao nhanh trong ngày. Ngoại tỉnh từ 1–3 ngày làm việc. Thiết bị được đóng gói an toàn theo tiêu chuẩn AutoSensor Việt Nam."
            }
          },
          {
            "@type": "Question",
            "name": "Có hỗ trợ tư vấn kỹ thuật không?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "AutoSensor Việt Nam hỗ trợ tư vấn kỹ thuật miễn phí. Bạn có thể liên hệ hotline hoặc email để được hỗ trợ chọn thiết bị phù hợp và giải đáp thắc mắc kỹ thuật."
            }
          }
        @endif
      ]
    }
    
    @if (optional($product->howtos->first())->steps)
      ,{
        "@type": "HowTo",
        "name": "{{ ($product->howtos->first()->title ?? 'Hướng dẫn lắp đặt và vận hành thiết bị') }}",
        "description": "{{ ($product->howtos->first()->description ?? 'Các bước lắp đặt, cài đặt thông số và vận hành để thiết bị hoạt động ổn định và hiệu quả.') }}",
        "image": "{{ asset('clients/assets/img/clothes/' . (optional($product->primaryImage)->url ?? 'no-image.jpg')) }}",
        "totalTime": "PT15M",
        "estimatedCost": { "@type": "MonetaryAmount", "currency": "VND", "value": "10000" },

        @php
          $howto = data_get($product, 'howtos.0');
          $supplies = collect(data_get($howto, 'supplies', []))->filter()->values();
          $steps = collect(data_get($howto, 'steps', []))->filter()->values();
        @endphp

        @if($supplies->isNotEmpty())
          "supply": [
            @foreach($supplies as $supply)
                  {!! json_encode([
                '@type' => 'HowToSupply',
                'name' => is_array($supply)
                  ? ($supply['name'] ?? 'Dụng cụ lắp đặt cơ bản')
                  : ($supply ?? 'Dụng cụ lắp đặt cơ bản')
              ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}{{ !$loop->last ? ',' : '' }}
            @endforeach
          ]@if($steps->isNotEmpty()),@endif
        @endif

        @if($steps->isNotEmpty())
          "step": [
            @foreach($steps as $step)
                  {!! json_encode([
                '@type' => 'HowToStep',
                'name' => is_array($step)
                  ? ($step['name'] ?? 'Bước lắp đặt thiết bị')
                  : ($step ?? 'Bước lắp đặt thiết bị'),
                'text' => is_array($step)
                  ? ($step['text'] ?? 'Làm theo hướng dẫn để thiết bị hoạt động ổn định và hiệu quả.')
                  : 'Làm theo hướng dẫn để thiết bị hoạt động ổn định và hiệu quả.'
              ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}{{ !$loop->last ? ',' : '' }}
            @endforeach
          ]
        @endif
      }
    @endif

  ]
}
</script>