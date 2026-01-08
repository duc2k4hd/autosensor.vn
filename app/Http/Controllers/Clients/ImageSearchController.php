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

            // Tìm kiếm products dựa trên keywords
            $products = $this->searchProductsByKeywords($keywords);

            // Xóa ảnh tạm
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            // Trả về keywords và products để frontend có thể redirect hoặc hiển thị kết quả
            return response()->json([
                'success' => true,
                'keywords' => $keywords,
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

        // Phân loại keywords: mã, tên, hãng
        $codeKeywords = [];
        $nameKeywords = [];
        $brandKeywords = [];
        
        foreach ($searchKeywords as $keyword) {
            $keywordUpper = mb_strtoupper($keyword);
            // Kiểm tra xem có phải mã sản phẩm không (dạng: chữ cái + số + dấu gạch, ví dụ: E3Z-T61, S7-1200)
            if (preg_match('/^[A-Z][A-Z0-9\-]{2,15}$/u', $keywordUpper)) {
                $codeKeywords[] = $keyword;
            } elseif (preg_match('/\b(omron|siemens|mitsubishi|schneider|yaskawa|weintek|abb|rockwell|phoenix|bosch|festo|smc|keyence|panasonic)\b/iu', $keyword)) {
                $brandKeywords[] = $keyword;
            } else {
                $nameKeywords[] = $keyword;
            }
        }

        // Nếu không có code keywords, thử tìm trong tất cả keywords
        if (empty($codeKeywords) && !empty($searchKeywords)) {
            foreach ($searchKeywords as $keyword) {
                // Tìm mã trong keyword (có thể có thêm text xung quanh)
                if (preg_match('/\b([A-Z][A-Z0-9\-]{2,15})\b/u', mb_strtoupper($keyword), $matches)) {
                    $codeKeywords[] = $matches[1];
                }
            }
        }

        // Ưu tiên tìm kiếm: tên -> mã -> hãng
        $allSearchKeywords = array_merge($nameKeywords, $codeKeywords, $brandKeywords);
        
        if (!empty($allSearchKeywords)) {
            $primaryKeyword = $allSearchKeywords[0];
            $isCode = in_array($primaryKeyword, $codeKeywords);
            
            // Danh sách các tên thiết bị cụ thể (không loại bỏ prefix)
            $specificDeviceNames = [
                'cảm biến quang', 'cảm biến tiệm cận', 'cảm biến vùng', 'cảm biến nhiệt độ',
                'cảm biến áp suất', 'cảm biến siêu âm', 'cảm biến từ', 'cảm biến hồng ngoại',
            ];
            
            // Loại bỏ các prefix chung ở đầu (chỉ với name keywords, trừ các tên thiết bị cụ thể)
            $primaryKeywordLower = mb_strtolower($primaryKeyword);
            $isSpecificDevice = false;
            foreach ($specificDeviceNames as $specificName) {
                if (str_contains($primaryKeywordLower, $specificName) || str_contains($specificName, $primaryKeywordLower)) {
                    $isSpecificDevice = true;
                    break;
                }
            }
            
            $primaryKeywordClean = $isCode 
                ? $primaryKeyword 
                : ($isSpecificDevice 
                    ? $primaryKeyword 
                    : preg_replace('/^(thiết bị|cảm biến|PLC|HMI|biến tần|servo|encoder|rơ le)\s+/i', '', $primaryKeyword));

            // Tìm kiếm: keyword đầu tiên PHẢI có trong tên HOẶC mô tả (bắt buộc)
            $query->where(function ($q) use ($primaryKeyword, $primaryKeywordClean, $isCode) {
                if ($isCode) {
                    // Nếu là mã, tìm chính xác hơn
                    $q->where(function ($subQ) use ($primaryKeyword) {
                        $subQ->whereRaw('UPPER(name) LIKE ?', ['%'.mb_strtoupper($primaryKeyword).'%'])
                            ->orWhereRaw('UPPER(sku) LIKE ?', ['%'.mb_strtoupper($primaryKeyword).'%'])
                            ->orWhereRaw('UPPER(description) LIKE ?', ['%'.mb_strtoupper($primaryKeyword).'%'])
                            ->orWhereRaw('UPPER(short_description) LIKE ?', ['%'.mb_strtoupper($primaryKeyword).'%']);
                    });
                } else {
                    // Nếu là tên, tìm linh hoạt hơn
                    $q->where(function ($subQ) use ($primaryKeyword, $primaryKeywordClean) {
                        $subQ->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($primaryKeyword).'%'])
                            ->orWhereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($primaryKeywordClean).'%'])
                            ->orWhereRaw('LOWER(name) LIKE ?', ['% '.mb_strtolower($primaryKeywordClean).' %'])
                            ->orWhereRaw('LOWER(description) LIKE ?', ['%'.mb_strtolower($primaryKeyword).'%'])
                            ->orWhereRaw('LOWER(short_description) LIKE ?', ['%'.mb_strtolower($primaryKeyword).'%']);
                    });
                }
            });
        } else {
            // Fallback: tìm kiếm với tất cả keywords (ít chính xác hơn)
            $query->where(function ($q) use ($keywords) {
                foreach (array_slice($keywords, 0, 3) as $keyword) {
                    $keywordClean = preg_replace('/^(thiết bị|cảm biến|PLC|HMI|biến tần|servo|encoder|rơ le)\s+/i', '', $keyword);
                    $q->orWhere(function ($subQ) use ($keyword, $keywordClean) {
                        $subQ->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($keyword).'%'])
                            ->orWhereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($keywordClean).'%'])
                            ->orWhereRaw('LOWER(description) LIKE ?', ['%'.mb_strtolower($keyword).'%'])
                            ->orWhereRaw('LOWER(short_description) LIKE ?', ['%'.mb_strtolower($keyword).'%']);
                    });
                }
            });
        }

        // Sắp xếp theo độ liên quan: ưu tiên tên -> mã -> hãng
        if (!empty($allSearchKeywords)) {
            $primaryKeyword = $allSearchKeywords[0];
            $isCode = in_array($primaryKeyword, $codeKeywords);
            
            // Danh sách các tên thiết bị cụ thể (không loại bỏ prefix khi sắp xếp)
            $specificDeviceNames = [
                'cảm biến quang', 'cảm biến tiệm cận', 'cảm biến vùng', 'cảm biến nhiệt độ',
                'cảm biến áp suất', 'cảm biến siêu âm', 'cảm biến từ', 'cảm biến hồng ngoại',
            ];
            
            $primaryKeywordLower = mb_strtolower($primaryKeyword);
            $isSpecificDevice = false;
            foreach ($specificDeviceNames as $specificName) {
                if (str_contains($primaryKeywordLower, $specificName) || str_contains($specificName, $primaryKeywordLower)) {
                    $isSpecificDevice = true;
                    break;
                }
            }
            
            $primaryKeywordClean = $isCode 
                ? $primaryKeyword 
                : ($isSpecificDevice 
                    ? $primaryKeyword 
                    : preg_replace('/^(thiết bị|cảm biến|PLC|HMI|biến tần|servo|encoder|rơ le)\s+/i', '', $primaryKeyword));

            $orderConditions = [];
            $orderParams = [];

            if ($isCode) {
                // Priority 1: SKU chứa mã chính xác
                $orderConditions[] = 'WHEN UPPER(sku) LIKE UPPER(?) THEN 1';
                $orderParams[] = '%'.mb_strtoupper($primaryKeyword).'%';
                
                // Priority 2: Tên chứa mã chính xác (bắt đầu)
                $orderConditions[] = 'WHEN UPPER(name) LIKE UPPER(?) THEN 2';
                $orderParams[] = mb_strtoupper($primaryKeyword).'%';
                
                // Priority 3: Tên chứa mã chính xác (có khoảng trắng trước)
                $orderConditions[] = 'WHEN UPPER(name) LIKE UPPER(?) THEN 3';
                $orderParams[] = '% '.mb_strtoupper($primaryKeyword).'%';
                
                // Priority 4: Tên chứa mã chính xác (bất kỳ đâu)
                $orderConditions[] = 'WHEN UPPER(name) LIKE UPPER(?) THEN 4';
                $orderParams[] = '%'.mb_strtoupper($primaryKeyword).'%';
                
                // Priority 5: Mô tả chứa mã
                $orderConditions[] = 'WHEN UPPER(description) LIKE UPPER(?) THEN 5';
                $orderParams[] = '%'.mb_strtoupper($primaryKeyword).'%';
            } else {
                // Priority 1: Tên bắt đầu bằng keyword chính xác
                $orderConditions[] = 'WHEN LOWER(name) LIKE LOWER(?) THEN 1';
                $orderParams[] = mb_strtolower($primaryKeyword).'%';

                // Priority 2: Tên chứa keyword chính xác như một từ riêng biệt
                $orderConditions[] = 'WHEN LOWER(name) LIKE LOWER(?) THEN 2';
                $orderParams[] = '% '.mb_strtolower($primaryKeyword).' %';

                // Priority 3: Tên chứa keyword chính xác
                $orderConditions[] = 'WHEN LOWER(name) LIKE LOWER(?) THEN 3';
                $orderParams[] = '%'.mb_strtolower($primaryKeyword).'%';

                // Priority 4-6: Tên chứa keyword không có prefix (nếu có)
                if ($primaryKeywordClean !== $primaryKeyword) {
                    $orderConditions[] = 'WHEN LOWER(name) LIKE LOWER(?) THEN 4';
                    $orderParams[] = mb_strtolower($primaryKeywordClean).'%';

                    $orderConditions[] = 'WHEN LOWER(name) LIKE LOWER(?) THEN 5';
                    $orderParams[] = '% '.mb_strtolower($primaryKeywordClean).' %';

                    $orderConditions[] = 'WHEN LOWER(name) LIKE LOWER(?) THEN 6';
                    $orderParams[] = '%'.mb_strtolower($primaryKeywordClean).'%';
                }

                // Priority 7-8: Mô tả ngắn chứa keyword
                $orderConditions[] = 'WHEN LOWER(short_description) LIKE LOWER(?) THEN 7';
                $orderParams[] = '%'.mb_strtolower($primaryKeyword).'%';

                if ($primaryKeywordClean !== $primaryKeyword) {
                    $orderConditions[] = 'WHEN LOWER(short_description) LIKE LOWER(?) THEN 8';
                    $orderParams[] = '%'.mb_strtolower($primaryKeywordClean).'%';
                }

                // Priority 9-10: Mô tả dài chứa keyword
                $orderConditions[] = 'WHEN LOWER(description) LIKE LOWER(?) THEN 9';
                $orderParams[] = '%'.mb_strtolower($primaryKeyword).'%';

                if ($primaryKeywordClean !== $primaryKeyword) {
                    $orderConditions[] = 'WHEN LOWER(description) LIKE LOWER(?) THEN 10';
                    $orderParams[] = '%'.mb_strtolower($primaryKeywordClean).'%';
                }
            }

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
        return $products->map(function ($product) {
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
    }
}
