<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ImageRecognitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImageSearchController extends Controller
{
    public function __construct(
        private ImageRecognitionService $imageRecognitionService
    ) {}

    /**
     * Xử lý tìm kiếm bằng hình ảnh
     */
    public function search(Request $request)
    {
        try {
            $request->validate([
                'image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'], // Max 5MB
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ảnh không hợp lệ. Vui lòng chọn ảnh định dạng JPG, PNG hoặc WEBP, kích thước tối đa 5MB.',
                'errors' => $e->errors(),
            ], 422);
        }

        $imagePath = null;
        try {
            // Lưu ảnh tạm thời
            $image = $request->file('image');
            $imagePath = $image->store('temp/image-search', 'public');
            $fullPath = Storage::disk('public')->path($imagePath);

            if (!file_exists($fullPath)) {
                throw new \Exception('Không thể lưu file ảnh tạm thời.');
            }

            // Phân tích ảnh để tìm kiếm
            $keywords = $this->analyzeImage($fullPath);

            // Kiểm tra nếu không có keywords (API key chưa được cấu hình hoặc không tìm thấy keywords cụ thể)
            if (empty($keywords)) {
                if ($imagePath) {
                    Storage::disk('public')->delete($imagePath);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Không thể phân tích hình ảnh. Vui lòng đảm bảo API key Gemini đã được cấu hình đúng hoặc thử lại với ảnh khác.',
                    'keywords' => [],
                    'products' => [],
                ], 400);
            }

            // Tìm kiếm products dựa trên keywords và lấy category keywords đã được xử lý
            $result = $this->searchProductsByKeywords($keywords);
            $products = $result['products'] ?? [];
            $categoryKeywords = $result['categoryKeywords'] ?? [];

            // Xóa ảnh tạm
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            // Loại bỏ trùng lặp trong chính keyword (ví dụ: "Biến tần Biến tần" -> "Biến tần")
            $finalKeywords = [];
            foreach ($categoryKeywords as $keyword) {
                // Tách keyword thành các từ và loại bỏ trùng lặp
                $words = preg_split('/\s+/u', trim($keyword));
                $uniqueWords = [];
                $seenWords = [];
                foreach ($words as $word) {
                    $wordLower = mb_strtolower($word);
                    if (!in_array($wordLower, $seenWords, true)) {
                        $seenWords[] = $wordLower;
                        $uniqueWords[] = $word;
                    }
                }
                $cleanedKeyword = implode(' ', $uniqueWords);
                
                // Chỉ thêm nếu keyword không rỗng và chưa có trong danh sách (case-insensitive)
                if (!empty($cleanedKeyword)) {
                    $cleanedKeywordLower = mb_strtolower(trim($cleanedKeyword));
                    $alreadyExists = false;
                    foreach ($finalKeywords as $existingKeyword) {
                        if (mb_strtolower(trim($existingKeyword)) === $cleanedKeywordLower) {
                            $alreadyExists = true;
                            break;
                        }
                    }
                    if (!$alreadyExists) {
                        $finalKeywords[] = $cleanedKeyword;
                    }
                }
            }
            
            // Trả về category keywords đã được xử lý (không trùng lặp) thay vì keywords gốc từ AI
            return response()->json([
                'success' => true,
                'keywords' => array_values($finalKeywords), // Trả về keywords đã được clean và loại bỏ trùng lặp
                'products' => $products,
                'message' => count($products) > 0 
                    ? 'Đã tìm thấy '.count($products).' sản phẩm phù hợp.' 
                    : 'Đang chuyển đến trang kết quả tìm kiếm...',
            ]);
        } catch (\Illuminate\Http\Exceptions\ThrottleRequestsException $e) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Bạn đã tìm kiếm quá nhiều lần. Vui lòng đợi 1 phút rồi thử lại.',
            ], 429);
        } catch (\Exception $e) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            Log::error('Image search error: '.$e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            $errorMessage = 'Có lỗi xảy ra khi tìm kiếm.';
            if (config('app.debug')) {
                $errorMessage .= ' Chi tiết: '.$e->getMessage();
            } else {
                $errorMessage .= ' Vui lòng thử lại sau.';
            }

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
            ], 500);
        }
    }

    /**
     * Phân tích ảnh để trích xuất keywords
     * Sử dụng ImageRecognitionService
     */
    protected function analyzeImage(string $imagePath): array
    {
        return $this->imageRecognitionService->analyzeImage($imagePath);
    }

    /**
     * Tìm kiếm sản phẩm dựa trên keywords
     * Trả về array với keys: 'products' và 'categoryKeywords'
     */
    protected function searchProductsByKeywords(array $keywords): array
    {
        if (empty($keywords)) {
            return [];
        }

        Log::info('Image search keywords', ['keywords' => $keywords]);

        // Ưu tiên tìm kiếm theo tên thiết bị cụ thể trước
        $generalTerms = [
            'thiết bị tự động hóa', 'cảm biến', 'PLC', 'HMI', 'biến tần',
            'servo', 'encoder', 'rơ le', 'nguồn công nghiệp', 'thiết bị điều khiển',
            'tự động hóa', 'công nghiệp', 'điều khiển', 'đo lường',
        ];

        // Danh sách các tên thiết bị cụ thể (không loại bỏ)
        $specificDeviceNames = [
            'cảm biến quang', 'cảm biến tiệm cận', 'cảm biến vùng', 'cảm biến nhiệt độ',
            'cảm biến áp suất', 'cảm biến siêu âm', 'cảm biến từ', 'cảm biến hồng ngoại',
        ];

        $specificKeywords = array_filter($keywords, function ($keyword) use ($generalTerms, $specificDeviceNames) {
            $keywordLower = mb_strtolower(trim($keyword));

            // Giữ lại các tên thiết bị cụ thể
            foreach ($specificDeviceNames as $specificName) {
                if (str_contains($keywordLower, $specificName) || str_contains($specificName, $keywordLower)) {
                    return true;
                }
            }

            // Loại bỏ các từ chung chung (chỉ loại bỏ nếu CHÍNH XÁC bằng, không loại bỏ nếu CHỨA)
            foreach ($generalTerms as $term) {
                if ($keywordLower === $term ||
                    $keywordLower === str_replace('cây ', '', $term) ||
                    $keywordLower === str_replace(' ', '', $term)) {
                    return false;
                }
            }

            // Loại bỏ các từ quá chung
            if (preg_match('/^(đặc điểm|hình dáng|kích thước|màu sắc|chức năng|công dụng|ứng dụng)(\s|$)/i', $keyword)) {
                return false;
            }

            // Chỉ giữ lại keywords có vẻ là tên thiết bị cụ thể (không quá dài, không quá ngắn)
            return mb_strlen($keyword) >= 3 && mb_strlen($keyword) <= 40;
        });

        // Nếu không có keywords cụ thể, thử tách tên thiết bị từ keywords
        if (empty($specificKeywords)) {
            foreach ($keywords as $keyword) {
                $keywordLower = mb_strtolower(trim($keyword));

                // Tìm keywords có dạng "thiết bị [tên cụ thể]" hoặc "cảm biến [tên cụ thể]"
                if (preg_match('/^(thiết bị|cảm biến|PLC|HMI|biến tần|servo|encoder|rơ le)\s+([a-zàáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ\s]{3,30})$/iu', $keywordLower, $matches)) {
                    $deviceName = trim($matches[2] ?? $matches[1]);
                    // Kiểm tra tên thiết bị không phải là từ chung
                    $isGeneral = false;
                    foreach ($generalTerms as $term) {
                        $termClean = mb_strtolower($term);
                        if ($deviceName === $termClean || str_contains($deviceName, $termClean)) {
                            $isGeneral = true;
                            break;
                        }
                    }
                    if (! $isGeneral) {
                        $specificKeywords[] = $deviceName;
                    }
                } elseif (! preg_match('/^(đặc điểm|hình dáng|kích thước|màu sắc|chức năng|công dụng|ứng dụng)(\s|$)/i', $keywordLower) &&
                          mb_strlen($keyword) >= 3 && mb_strlen($keyword) <= 25 &&
                          ! in_array($keywordLower, array_map('mb_strtolower', $generalTerms))) {
                    // Keywords ngắn có thể là tên thiết bị cụ thể
                    $specificKeywords[] = $keyword;
                }
            }
        }

        // Nếu vẫn không có keywords cụ thể, thử giữ lại keywords có vẻ là tên thiết bị
        if (empty($specificKeywords)) {
            foreach ($keywords as $keyword) {
                $keywordLower = mb_strtolower(trim($keyword));
                // Giữ lại keywords có vẻ là tên thiết bị (không phải từ chung chung)
                // Đặc biệt giữ lại các tên thiết bị cụ thể như "cảm biến quang", "cảm biến tiệm cận"
                $isGeneralTerm = false;
                foreach ($generalTerms as $term) {
                    if ($keywordLower === mb_strtolower($term)) {
                        $isGeneralTerm = true;
                        break;
                    }
                }
                if (! $isGeneralTerm && mb_strlen($keyword) >= 3) {
                    $specificKeywords[] = $keyword;
                }
            }
        }

        // Nếu vẫn không có keywords cụ thể, trả về mảng rỗng để không tìm kiếm với keywords chung chung
        $searchKeywords = ! empty($specificKeywords) ? array_values(array_unique($specificKeywords)) : [];

        Log::info('Filtered search keywords', ['searchKeywords' => $searchKeywords]);

        $query = Product::query()
            ->active()
            ->with('primaryCategory');

        // Loại bỏ mã sản phẩm và hãng, chỉ giữ lại loại sản phẩm (category types)
        $categoryKeywords = [];
        
        // Danh sách các loại sản phẩm hợp lệ (category types)
        $validCategoryTypes = [
            'cảm biến', 'cảm biến quang', 'cảm biến tiệm cận', 'cảm biến từ', 'cảm biến vùng',
            'cảm biến nhiệt độ', 'cảm biến áp suất', 'cảm biến siêu âm', 'cảm biến hồng ngoại',
            'cảm biến laser', 'cảm biến màu', 'cảm biến khoảng cách',
            'PLC', 'HMI', 'màn hình', 'biến tần', 'servo', 'encoder', 'rơ le',
            'nguồn công nghiệp', 'thiết bị điều khiển', 'thiết bị tự động hóa',
            'contactor', 'timer', 'counter', 'công tắc', 'nút nhấn',
        ];
        
        foreach ($searchKeywords as $keyword) {
            $keywordLower = mb_strtolower(trim($keyword));
            $keywordUpper = mb_strtoupper($keyword);
            
            // Loại bỏ mã sản phẩm (dạng: chữ cái + số + dấu gạch)
            if (preg_match('/^[A-Z][A-Z0-9\-]{2,15}$/u', $keywordUpper)) {
                continue; // Bỏ qua mã sản phẩm
            }
            
            // Loại bỏ hãng sản xuất
            if (preg_match('/\b(omron|siemens|mitsubishi|schneider|yaskawa|weintek|abb|rockwell|phoenix|bosch|festo|smc|keyence|panasonic|ls electric)\b/iu', $keyword)) {
                continue; // Bỏ qua hãng
            }
            
            // Chỉ giữ lại các loại sản phẩm hợp lệ
            $isValidCategory = false;
            $matchedCategoryType = null;
            
            foreach ($validCategoryTypes as $categoryType) {
                $categoryTypeLower = mb_strtolower($categoryType);
                if (str_contains($keywordLower, $categoryTypeLower) || 
                    str_contains($categoryTypeLower, $keywordLower)) {
                    $isValidCategory = true;
                    // Ưu tiên loại cụ thể hơn (ví dụ: "cảm biến quang" thay vì chỉ "cảm biến")
                    if (mb_strlen($categoryType) > mb_strlen($keyword)) {
                        $matchedCategoryType = $categoryType;
                    } else {
                        $matchedCategoryType = $keyword;
                    }
                    break;
                }
            }
            
            // Chỉ thêm vào nếu đã match và chưa có trong danh sách
            if ($isValidCategory && $matchedCategoryType) {
                $matchedCategoryTypeLower = mb_strtolower(trim($matchedCategoryType));
                // Kiểm tra xem đã có trong danh sách chưa (case-insensitive)
                $alreadyExists = false;
                foreach ($categoryKeywords as $existingKeyword) {
                    if (mb_strtolower(trim($existingKeyword)) === $matchedCategoryTypeLower) {
                        $alreadyExists = true;
                        break;
                    }
                }
                if (!$alreadyExists) {
                    $categoryKeywords[] = $matchedCategoryType;
                }
            }
            
            // Nếu không match với danh sách, nhưng có vẻ là loại thiết bị (chứa từ khóa chung)
            if (!$isValidCategory && (
                str_contains($keywordLower, 'cảm biến') ||
                str_contains($keywordLower, 'plc') ||
                str_contains($keywordLower, 'hmi') ||
                str_contains($keywordLower, 'biến tần') ||
                str_contains($keywordLower, 'servo') ||
                str_contains($keywordLower, 'encoder') ||
                str_contains($keywordLower, 'rơ le') ||
                str_contains($keywordLower, 'màn hình')
            )) {
                // Kiểm tra xem đã có trong danh sách chưa (case-insensitive)
                $keywordLowerTrimmed = mb_strtolower(trim($keyword));
                $alreadyExists = false;
                foreach ($categoryKeywords as $existingKeyword) {
                    if (mb_strtolower(trim($existingKeyword)) === $keywordLowerTrimmed) {
                        $alreadyExists = true;
                        break;
                    }
                }
                if (!$alreadyExists) {
                    $categoryKeywords[] = $keyword;
                }
            }
        }
        
        // Loại bỏ trùng lặp case-insensitive và sắp xếp theo độ cụ thể (từ cụ thể đến chung)
        $uniqueCategoryKeywords = [];
        $seenLowercase = [];
        
        foreach ($categoryKeywords as $keyword) {
            $keywordLower = mb_strtolower(trim($keyword));
            // Chỉ thêm nếu chưa thấy (case-insensitive)
            if (!in_array($keywordLower, $seenLowercase, true)) {
                $seenLowercase[] = $keywordLower;
                $uniqueCategoryKeywords[] = $keyword;
            }
        }
        
        $categoryKeywords = array_values($uniqueCategoryKeywords);
        
        // Ưu tiên loại cụ thể hơn (ví dụ: "cảm biến quang" trước "cảm biến")
        usort($categoryKeywords, function($a, $b) {
            $aLen = mb_strlen($a);
            $bLen = mb_strlen($b);
            if ($aLen === $bLen) {
                return 0;
            }
            return $aLen > $bLen ? -1 : 1; // Dài hơn = cụ thể hơn = ưu tiên hơn
        });
        
        $allSearchKeywords = $categoryKeywords;
        
        if (!empty($allSearchKeywords)) {
            $primaryKeyword = $allSearchKeywords[0];
            $primaryKeywordLower = mb_strtolower($primaryKeyword);

            // Tìm kiếm: keyword đầu tiên PHẢI có trong tên HOẶC mô tả (bắt buộc)
            // Chỉ tìm kiếm theo loại sản phẩm, không tìm theo mã
            $query->where(function ($q) use ($primaryKeyword, $primaryKeywordLower) {
                $q->where(function ($subQ) use ($primaryKeyword, $primaryKeywordLower) {
                    // Tìm trong tên sản phẩm
                    $subQ->whereRaw('LOWER(name) LIKE ?', ['%'.$primaryKeywordLower.'%'])
                        // Tìm trong mô tả
                        ->orWhereRaw('LOWER(description) LIKE ?', ['%'.$primaryKeywordLower.'%'])
                        ->orWhereRaw('LOWER(short_description) LIKE ?', ['%'.$primaryKeywordLower.'%']);
                });
            });
            
            // Nếu có nhiều keywords, thêm điều kiện OR cho các keywords khác
            if (count($allSearchKeywords) > 1) {
                $query->orWhere(function ($q) use ($allSearchKeywords) {
                    foreach (array_slice($allSearchKeywords, 1, 2) as $keyword) { // Chỉ lấy thêm 2 keywords nữa
                        $keywordLower = mb_strtolower($keyword);
                        $q->orWhere(function ($subQ) use ($keywordLower) {
                            $subQ->whereRaw('LOWER(name) LIKE ?', ['%'.$keywordLower.'%'])
                                ->orWhereRaw('LOWER(description) LIKE ?', ['%'.$keywordLower.'%'])
                                ->orWhereRaw('LOWER(short_description) LIKE ?', ['%'.$keywordLower.'%']);
                        });
                    }
                });
            }
        } else {
            // Fallback: tìm kiếm với tất cả keywords ban đầu (nếu không có category keywords)
            $query->where(function ($q) use ($keywords) {
                foreach (array_slice($keywords, 0, 3) as $keyword) {
                    $keywordLower = mb_strtolower($keyword);
                    // Loại bỏ mã sản phẩm trong fallback
                    if (!preg_match('/^[A-Z][A-Z0-9\-]{2,15}$/u', mb_strtoupper($keyword))) {
                        $q->orWhere(function ($subQ) use ($keywordLower) {
                            $subQ->whereRaw('LOWER(name) LIKE ?', ['%'.$keywordLower.'%'])
                                ->orWhereRaw('LOWER(description) LIKE ?', ['%'.$keywordLower.'%'])
                                ->orWhereRaw('LOWER(short_description) LIKE ?', ['%'.$keywordLower.'%']);
                        });
                    }
                }
            });
        }

        // Sắp xếp theo độ liên quan: ưu tiên tên chứa loại sản phẩm
        if (!empty($allSearchKeywords)) {
            $primaryKeyword = $allSearchKeywords[0];
            $primaryKeywordLower = mb_strtolower($primaryKeyword);

            $orderConditions = [];
            $orderParams = [];

            // Priority 1: Tên bắt đầu bằng keyword chính xác
            $orderConditions[] = 'WHEN LOWER(name) LIKE LOWER(?) THEN 1';
            $orderParams[] = $primaryKeywordLower.'%';

            // Priority 2: Tên chứa keyword chính xác như một từ riêng biệt
            $orderConditions[] = 'WHEN LOWER(name) LIKE LOWER(?) THEN 2';
            $orderParams[] = '% '.$primaryKeywordLower.' %';

            // Priority 3: Tên chứa keyword chính xác
            $orderConditions[] = 'WHEN LOWER(name) LIKE LOWER(?) THEN 3';
            $orderParams[] = '%'.$primaryKeywordLower.'%';

            // Priority 4: Mô tả ngắn chứa keyword
            $orderConditions[] = 'WHEN LOWER(short_description) LIKE LOWER(?) THEN 4';
            $orderParams[] = '%'.$primaryKeywordLower.'%';

            // Priority 5: Mô tả dài chứa keyword
            $orderConditions[] = 'WHEN LOWER(description) LIKE LOWER(?) THEN 5';
            $orderParams[] = '%'.$primaryKeywordLower.'%';

            // Priority cuối: Các sản phẩm khác
            $orderConditions[] = 'ELSE 99';

            $orderBySql = 'CASE '.implode(' ', $orderConditions).' END';
            $query->orderByRaw($orderBySql, $orderParams);
        }

        // Thêm sắp xếp phụ theo tên để có kết quả nhất quán
        $query->orderBy('name', 'asc');

        $products = $query->limit(20)->get();

        // Preload images để tránh N+1 query (primaryImage là accessor, không phải relationship)
        Product::preloadImages($products);

        // Format kết quả
        $formattedProducts = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'price' => $product->price,
                'sale_price' => $product->sale_price,
                'image' => $product->primaryImage?->url
                    ? asset('clients/assets/img/clothes/resize/230x230/'.$product->primaryImage->url)
                    : asset('clients/assets/img/clothes/no-image.webp'),
                'category' => $product->primaryCategory?->name,
                'url' => route('client.product.detail', $product->slug),
            ];
        })->toArray();

        // Trả về cả products và categoryKeywords đã được xử lý (không trùng lặp)
        return [
            'products' => $formattedProducts,
            'categoryKeywords' => $allSearchKeywords ?? [],
        ];
    }
}
