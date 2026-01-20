<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImageRecognitionService
{
    /**
     * Phân tích ảnh để trích xuất keywords
     *
     * @param  string  $imagePath  Đường dẫn đến file ảnh
     * @return array Mảng các keywords
     */
    public function analyzeImage(string $imagePath): array
    {
        // Ưu tiên sử dụng Gemini Vision API (đã có sẵn trong dự án)
        // Thử lấy API key từ AiAssistantService (đang hoạt động)
        try {
            $aiService = app(\App\Services\AiAssistantService::class);
            $apiKey = config('services.gemini.key');

            // Kiểm tra API key có hợp lệ không
            if (! empty($apiKey) && strlen($apiKey) > 20) {
                return $this->analyzeWithGeminiVision($imagePath, $apiKey);
            }
        } catch (\Exception $e) {
            Log::warning('Gemini Vision API failed, falling back to default keywords', [
                'error' => $e->getMessage(),
            ]);
        }

        Log::warning('Gemini API key not configured or invalid, using default keywords', [
            'api_key_length' => strlen($apiKey ?? ''),
        ]);

        // Option 1: Sử dụng Google Vision API
        if (config('services.google_vision.enabled', false)) {
            return $this->analyzeWithGoogleVision($imagePath);
        }

        // Option 2: Sử dụng AWS Rekognition
        if (config('services.aws_rekognition.enabled', false)) {
            return $this->analyzeWithAWSRekognition($imagePath);
        }

        // Option 3: Sử dụng local AI model
        if (config('services.local_ai.enabled', false)) {
            return $this->analyzeWithLocalAI($imagePath);
        }

        // Fallback: Trả về keywords mặc định
        Log::warning('Image recognition service not configured, using default keywords');

        return $this->getDefaultKeywords();
    }

    /**
     * Phân tích ảnh với Gemini Vision API
     */
    protected function analyzeWithGeminiVision(string $imagePath, string $apiKey): array
    {
        try {
            // Đọc ảnh và encode base64
            $imageData = file_get_contents($imagePath);
            $base64Image = base64_encode($imageData);
            $mimeType = mime_content_type($imagePath) ?: 'image/jpeg';

            $model = config('services.gemini.model', 'gemini-1.5-flash');
            $endpoint = sprintf(
                'https://generativelanguage.googleapis.com/v1/models/%s:generateContent?key=%s',
                $model,
                $apiKey
            );

            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => 'Bạn là chuyên gia về thiết bị tự động hóa công nghiệp với nhiều năm kinh nghiệm.

                                NHIỆM VỤ: Phân tích KỸ LƯỠNG hình ảnh và xác định CHÍNH XÁC loại thiết bị được hiển thị.

                                DANH SÁCH CÁC LOẠI THIẾT BỊ HỢP LỆ (CHỈ được trả về các loại này):
                                1. Cảm biến (các loại cụ thể: cảm biến quang, cảm biến tiệm cận, cảm biến từ, cảm biến vùng, cảm biến nhiệt độ, cảm biến áp suất, cảm biến siêu âm, cảm biến hồng ngoại, cảm biến laser, cảm biến màu, cảm biến khoảng cách)
                                2. PLC
                                3. HMI
                                4. Màn hình
                                5. Biến tần
                                6. Servo
                                7. Encoder
                                8. Rơ le
                                9. Nguồn công nghiệp
                                10. Thiết bị điều khiển
                                11. Contactor
                                12. Timer
                                13. Counter
                                14. Switch (công tắc)
                                15. Button (nút nhấn)

                                QUY TẮC:
                                - QUAN SÁT KỸ hình ảnh: Xem xét hình dáng, kích thước, các thành phần, nhãn hiệu, ký hiệu trên thiết bị
                                - PHẢI trả về ít nhất 1 loại thiết bị từ danh sách trên
                                - Nếu không chắc chắn loại cụ thể, trả về loại chung (ví dụ: "cảm biến" thay vì "cảm biến quang")
                                - KHÔNG trả về mã sản phẩm (ví dụ: E3F-DS30C4, S7-1200, FR-D720)
                                - KHÔNG trả về hãng sản xuất (ví dụ: Omron, Siemens, Mitsubishi)
                                - KHÔNG giải thích, KHÔNG mô tả, KHÔNG dùng tiếng Anh
                                - Mỗi dòng CHỈ chứa 1 loại thiết bị bằng tiếng Việt
                                - TỐI ĐA 2 dòng
                                - Dòng 1: Loại thiết bị chung nhất (BẮT BUỘC phải có)
                                - Dòng 2: Loại thiết bị cụ thể hơn (nếu xác định được)

                                CÁCH PHÂN TÍCH:
                                1. Quan sát hình dáng tổng thể: hình trụ, hình hộp, hình chữ nhật, v.v.
                                2. Xác định các thành phần đặc trưng:
                                   - Cảm biến: hình trụ hoặc hình hộp nhỏ, có đèn LED, có dây cáp, có đầu dò
                                   - PLC: hình hộp, có nhiều cổng I/O, có đèn LED trạng thái, có màn hình nhỏ
                                   - HMI: có màn hình LCD/TFT lớn, có nút bấm, có cổng kết nối
                                   - Biến tần: hình hộp lớn, có quạt tản nhiệt, có cổng nguồn AC, có màn hình nhỏ
                                   - Servo: hình trụ hoặc hình hộp, có encoder, có cổng kết nối
                                   - Encoder: hình trụ nhỏ, có trục quay, có cổng kết nối
                                   - Rơ le: hình hộp nhỏ, có nhiều chân cắm, có đèn LED
                                3. Đọc nhãn hiệu hoặc ký hiệu trên thiết bị (nhưng KHÔNG trả về tên hãng)
                                4. So sánh với các loại thiết bị trong danh sách hợp lệ
                                5. Chọn loại thiết bị CHÍNH XÁC NHẤT

                                VÍ DỤ ĐÚNG:
                                Nếu thấy thiết bị hình trụ, có đèn LED, có dây cáp → cảm biến quang:
                                cảm biến
                                cảm biến quang

                                Nếu thấy thiết bị có màn hình LCD/TFT lớn, có nút bấm → HMI:
                                HMI

                                Nếu thấy thiết bị hình hộp, có nhiều cổng I/O, có đèn LED → PLC:
                                PLC

                                Nếu thấy thiết bị hình hộp lớn, có quạt tản nhiệt → biến tần:
                                biến tần

                                Nếu thấy thiết bị hình trụ nhỏ, có trục quay → encoder:
                                encoder

                                Nếu KHÔNG chắc chắn loại cụ thể, chỉ trả về loại chung:
                                cảm biến

                                VÍ DỤ SAI (TUYỆT ĐỐI KHÔNG ĐƯỢC TRẢ VỀ):
                                - Omron E3F-DS30C4 (có mã sản phẩm)
                                - Cảm biến quang Omron (có hãng)
                                - Photoelectric sensor (tiếng Anh)
                                - Thiết bị này là cảm biến quang (có giải thích)
                                - Sensor (tiếng Anh)
                                - (để trống, không trả về gì)

                                QUAN TRỌNG: PHẢI trả về ít nhất 1 loại thiết bị. Nếu không chắc chắn, trả về loại chung nhất.

                                BẮT ĐẦU PHÂN TÍCH:',
                            ],
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => $base64Image,
                                ],
                            ],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.1, // Giảm temperature để tăng độ chính xác
                    'topK' => 10, // Giảm topK để tập trung hơn
                    'topP' => 0.8, // Giảm topP để chính xác hơn
                    'maxOutputTokens' => 50, // Giảm max tokens vì chỉ cần 1-2 dòng
                ],
            ];

            $response = Http::timeout(30)
                ->acceptJson()
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($endpoint, $payload);

            if (! $response->successful()) {
                Log::warning('Gemini Vision API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return $this->getDefaultKeywords();
            }

            $data = $response->json();
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

            if (empty($text)) {
                Log::warning('Gemini Vision API returned empty response');

                return $this->getDefaultKeywords();
            }

            // Trích xuất keywords từ response
            $keywords = $this->extractKeywordsFromText($text);

            // Validation: chỉ chấp nhận nếu có ít nhất loại thiết bị hợp lệ (không cần mã)
            if (!$this->validateKeywords($keywords)) {
                Log::warning('Gemini response không hợp lệ - không có loại thiết bị hợp lệ', [
                    'keywords' => $keywords,
                    'original_text' => $text,
                    'hasValidCategory' => $this->hasValidCategoryInKeywords($keywords),
                ]);

                return $this->getDefaultKeywords();
            }

            Log::info('Gemini Vision API extracted keywords', [
                'keywords' => $keywords,
                'original_text' => $text,
            ]);

            return $keywords;
        } catch (\Exception $e) {
            Log::error('Gemini Vision API error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->getDefaultKeywords();
        }
    }

    /**
     * Trích xuất keywords từ text response của Gemini
     * CHỈ trả về các loại thiết bị hợp lệ từ danh sách
     */
    protected function extractKeywordsFromText(string $text): array
    {
        // Danh sách các loại thiết bị hợp lệ (category types) - CHỈ chấp nhận các loại này
        $validCategoryTypes = [
            'cảm biến', 'cảm biến quang', 'cảm biến tiệm cận', 'cảm biến từ', 'cảm biến vùng',
            'cảm biến nhiệt độ', 'cảm biến áp suất', 'cảm biến siêu âm', 'cảm biến hồng ngoại',
            'cảm biến laser', 'cảm biến màu', 'cảm biến khoảng cách',
            'PLC', 'HMI', 'màn hình', 'biến tần', 'servo', 'encoder', 'rơ le',
            'nguồn công nghiệp', 'thiết bị điều khiển', 'thiết bị tự động hóa',
            'contactor', 'timer', 'counter', 'công tắc', 'nút nhấn', 'Aptomat', 'MCCB', 'ACB', 'RCCB', 'RCBO', 'Bộ nguôn'
        ];

        // Tách text thành các dòng và lọc
        $lines = preg_split('/[\r\n]+/', $text);
        $keywords = [];

        foreach ($lines as $line) {
            $line = trim($line);

            // Loại bỏ số thứ tự, dấu gạch đầu dòng, v.v.
            $line = preg_replace('/^[\d\.\-\*\:\s]+/', '', $line);
            $line = trim($line);

            if (empty($line) || mb_strlen($line) < 2) {
                continue;
            }

            // Loại bỏ các từ không liên quan
            $skipPatterns = [
                '/^(ví dụ|example|v\.v\.|etc|yêu cầu|mô tả|đặc điểm|hình dáng|loại thiết bị|trả về|chỉ|không|và|hoặc|ưu tiên|sau đó|mới đến)$/iu',
                '/^(nếu|nếu là|đây là|trong ảnh|ảnh này|có thể|thường|thường là|quan trọng|bắt đầu|phân tích)$/iu',
            ];

            $shouldSkip = false;
            foreach ($skipPatterns as $pattern) {
                if (preg_match($pattern, $line)) {
                    $shouldSkip = true;
                    break;
                }
            }

            if ($shouldSkip) {
                continue;
            }

            // Loại bỏ các câu giải thích dài
            if (mb_strlen($line) > 50) {
                continue;
            }

            // Loại bỏ mã sản phẩm (dạng: chữ cái + số + dấu gạch)
            $lineUpper = mb_strtoupper($line);
            if (preg_match('/^[A-Z][A-Z0-9\-]{2,15}$/u', $lineUpper)) {
                continue; // Bỏ qua mã sản phẩm
            }

            // Loại bỏ hãng sản xuất
            if (preg_match('/\b(omron|siemens|mitsubishi|schneider|yaskawa|weintek|abb|rockwell|phoenix|bosch|festo|smc|keyence|panasonic|ls electric)\b/iu', $line)) {
                continue; // Bỏ qua hãng
            }

            // Clean keyword
            $keyword = mb_strtolower($line);
            $keyword = preg_replace('/[^\p{L}\p{N}\s\-]/u', ' ', $keyword);
            $keyword = preg_replace('/\s+/', ' ', $keyword);
            $keyword = trim($keyword);

            if (mb_strlen($keyword) < 2 || mb_strlen($keyword) > 50) {
                continue;
            }

            // CHỈ chấp nhận keyword nếu nó khớp với một trong các loại thiết bị hợp lệ
            $matchedCategoryType = null;
            $bestMatchScore = 0;
            
            foreach ($validCategoryTypes as $categoryType) {
                $categoryTypeLower = mb_strtolower($categoryType);
                $matchScore = 0;
                
                // So khớp chính xác (ưu tiên cao nhất)
                if ($keyword === $categoryTypeLower) {
                    $matchScore = 100;
                }
                // Keyword chứa category type (ưu tiên cao)
                elseif (str_contains($keyword, $categoryTypeLower)) {
                    $matchScore = 80;
                }
                // Category type chứa keyword (ưu tiên trung bình)
                elseif (str_contains($categoryTypeLower, $keyword)) {
                    $matchScore = 60;
                }
                // So khớp từng từ (ưu tiên thấp)
                else {
                    $keywordWords = explode(' ', $keyword);
                    $categoryWords = explode(' ', $categoryTypeLower);
                    $matchedWords = 0;
                    foreach ($keywordWords as $kw) {
                        foreach ($categoryWords as $cw) {
                            if ($kw === $cw || str_contains($cw, $kw) || str_contains($kw, $cw)) {
                                $matchedWords++;
                                break;
                            }
                        }
                    }
                    if ($matchedWords > 0) {
                        $matchScore = 40 * ($matchedWords / max(count($keywordWords), count($categoryWords)));
                    }
                }
                
                // Chọn match tốt nhất, ưu tiên loại cụ thể hơn nếu cùng điểm
                if ($matchScore > $bestMatchScore || 
                    ($matchScore === $bestMatchScore && mb_strlen($categoryType) > mb_strlen($matchedCategoryType ?? ''))) {
                    $bestMatchScore = $matchScore;
                    $matchedCategoryType = $categoryType;
                }
            }

            // Chấp nhận nếu có match (score >= 40)
            if ($matchedCategoryType !== null && $bestMatchScore >= 40) {
                // Kiểm tra xem đã có trong danh sách chưa (case-insensitive)
                $alreadyExists = false;
                $matchedCategoryTypeLower = mb_strtolower($matchedCategoryType);
                foreach ($keywords as $existingKeyword) {
                    if (mb_strtolower(trim($existingKeyword)) === $matchedCategoryTypeLower) {
                        $alreadyExists = true;
                        break;
                    }
                }
                
                if (!$alreadyExists) {
                    $keywords[] = $matchedCategoryType; // Dùng tên chuẩn từ danh sách
                }
            }
        }

        $allKeywords = $keywords;

        // Loại bỏ trùng lặp nhưng giữ thứ tự
        $uniqueKeywords = [];
        foreach ($allKeywords as $keyword) {
            $keywordLower = mb_strtolower(trim($keyword));
            if (!empty($keywordLower) && !in_array($keywordLower, array_map('mb_strtolower', $uniqueKeywords))) {
                $uniqueKeywords[] = $keyword;
            }
        }

        // Giới hạn số lượng keywords
        $uniqueKeywords = array_slice($uniqueKeywords, 0, 10);

        // Fallback: Nếu không có keyword nào hợp lệ, thử tìm keyword chung nhất từ text gốc
        if (empty($uniqueKeywords)) {
            // Tìm keyword chung nhất từ text gốc
            $commonTerms = [
                'cảm biến' => ['cảm biến', 'sensor', 'detector'],
                'PLC' => ['plc', 'programmable', 'controller'],
                'HMI' => ['hmi', 'human', 'machine', 'interface'],
                'biến tần' => ['biến tần', 'inverter', 'frequency'],
                'servo' => ['servo', 'motor'],
                'encoder' => ['encoder', 'position'],
                'rơ le' => ['rơ le', 'relay'],
            ];

            foreach ($commonTerms as $term => $patterns) {
                foreach ($patterns as $pattern) {
                    if (stripos($text, $pattern) !== false) {
                        $uniqueKeywords[] = $term;
                        break 2; // Break cả 2 vòng lặp
                    }
                }
            }
        }

        Log::info('Extracted keywords from Gemini', [
            'original_text' => $text,
            'keywords' => $uniqueKeywords,
        ]);

        return !empty($uniqueKeywords) ? $uniqueKeywords : $this->getDefaultKeywords();
    }

    /**
     * Parse chuỗi thiết bị thành tên, hãng, mã
     * Ví dụ: "Cảm biến quang Omron E3Z-T61 2M" -> ['name' => 'Cảm biến quang', 'brand' => 'Omron', 'code' => 'E3Z-T61']
     */
    protected function parseDeviceString(string $text): array
    {
        $result = [
            'name' => '',
            'brand' => '',
            'code' => '',
        ];

        // Danh sách các hãng phổ biến
        $brands = [
            'Omron', 'Siemens', 'Mitsubishi', 'Schneider', 'Yaskawa', 'Weintek',
            'ABB', 'Schneider Electric', 'Rockwell', 'Allen-Bradley', 'Phoenix Contact',
            'Bosch Rexroth', 'Festo', 'SMC', 'Keyence', 'Panasonic', 'LS Electric',
        ];

        $text = trim($text);
        if (empty($text) || mb_strlen($text) < 5) {
            return $result;
        }

        // Tìm mã sản phẩm (thường có dạng: chữ cái + số + dấu gạch + số, ví dụ: E3Z-T61, S7-1200, FR-D720)
        // Pattern: bắt đầu bằng chữ cái, có số, có thể có dấu gạch ngang
        if (preg_match('/\b([A-Z][A-Z0-9\-]{2,15})\b/u', $text, $codeMatches)) {
            $result['code'] = $codeMatches[1];
        }

        // Tìm hãng
        foreach ($brands as $brand) {
            if (stripos($text, $brand) !== false) {
                $result['brand'] = $brand;
                break;
            }
        }

        // Tìm tên thiết bị (phần còn lại sau khi loại bỏ hãng và mã)
        $nameText = $text;
        
        // Loại bỏ mã
        if (!empty($result['code'])) {
            $nameText = preg_replace('/\b'.preg_quote($result['code'], '/').'\b/iu', '', $nameText);
        }
        
        // Loại bỏ hãng
        if (!empty($result['brand'])) {
            $nameText = preg_replace('/\b'.preg_quote($result['brand'], '/').'\b/iu', '', $nameText);
        }
        
        // Loại bỏ các số đơn lẻ (như "2M" ở cuối)
        $nameText = preg_replace('/\b\d+[A-Z]?\b/iu', '', $nameText);
        
        // Làm sạch và lấy tên
        $nameText = preg_replace('/\s+/', ' ', $nameText);
        $nameText = trim($nameText);
        
        // Loại bỏ các từ chung chung ở đầu
        $nameText = preg_replace('/^(thiết bị|cảm biến|PLC|HMI|biến tần|servo|encoder|rơ le)\s+/i', '', $nameText);
        
        if (!empty($nameText) && mb_strlen($nameText) >= 3) {
            // Thêm lại loại thiết bị nếu có
            if (preg_match('/\b(cảm biến|PLC|HMI|biến tần|servo|encoder|rơ le)\b/i', $text, $typeMatches)) {
                $result['name'] = trim($typeMatches[1] . ' ' . $nameText);
            } else {
                $result['name'] = $nameText;
            }
        }

        return $result;
    }

    /**
     * Validate keywords - chỉ chấp nhận nếu có ít nhất loại thiết bị hợp lệ (không cần mã)
     */
    protected function validateKeywords(array $keywords): bool
    {
        if (empty($keywords)) {
            return false;
        }

        // Danh sách các loại thiết bị hợp lệ (category types)
        $validCategoryTypes = [
            'cảm biến', 'cảm biến quang', 'cảm biến tiệm cận', 'cảm biến từ', 'cảm biến vùng',
            'cảm biến nhiệt độ', 'cảm biến áp suất', 'cảm biến siêu âm', 'cảm biến hồng ngoại',
            'cảm biến laser', 'cảm biến màu', 'cảm biến khoảng cách',
            'PLC', 'HMI', 'màn hình', 'biến tần', 'servo', 'encoder', 'rơ le',
            'nguồn công nghiệp', 'thiết bị điều khiển', 'thiết bị tự động hóa',
            'contactor', 'timer', 'counter', 'công tắc', 'nút nhấn',
        ];

        $hasValidCategory = false;

        foreach ($keywords as $keyword) {
            $keywordLower = mb_strtolower(trim($keyword));
            
            // Loại bỏ mã sản phẩm (không tính là hợp lệ)
            $keywordUpper = mb_strtoupper($keyword);
            if (preg_match('/^[A-Z][A-Z0-9\-]{2,15}$/u', $keywordUpper)) {
                continue; // Bỏ qua mã sản phẩm
            }
            
            // Loại bỏ hãng (không tính là hợp lệ)
            if (preg_match('/\b(omron|siemens|mitsubishi|schneider|yaskawa|weintek|abb|rockwell|phoenix|bosch|festo|smc|keyence|panasonic|ls electric)\b/iu', $keyword)) {
                continue; // Bỏ qua hãng
            }

            // Kiểm tra loại thiết bị (chứa category type hợp lệ)
            foreach ($validCategoryTypes as $categoryType) {
                if (str_contains($keywordLower, mb_strtolower($categoryType)) || 
                    str_contains(mb_strtolower($categoryType), $keywordLower)) {
                    $hasValidCategory = true;
                    break;
                }
            }
            
            // Nếu không match chính xác, kiểm tra có chứa từ khóa chung không
            if (!$hasValidCategory && (
                str_contains($keywordLower, 'cảm biến') ||
                str_contains($keywordLower, 'plc') ||
                str_contains($keywordLower, 'hmi') ||
                str_contains($keywordLower, 'biến tần') ||
                str_contains($keywordLower, 'servo') ||
                str_contains($keywordLower, 'encoder') ||
                str_contains($keywordLower, 'rơ le') ||
                str_contains($keywordLower, 'màn hình')
            )) {
                $hasValidCategory = true;
            }
        }

        // Chấp nhận nếu có ít nhất 1 loại thiết bị hợp lệ
        return $hasValidCategory;
    }

    /**
     * Helper method để kiểm tra có loại thiết bị hợp lệ trong keywords
     */
    protected function hasValidCategoryInKeywords(array $keywords): bool
    {
        $validCategoryTypes = [
            'cảm biến', 'cảm biến quang', 'cảm biến tiệm cận', 'cảm biến từ', 'cảm biến vùng',
            'cảm biến nhiệt độ', 'cảm biến áp suất', 'cảm biến siêu âm', 'cảm biến hồng ngoại',
            'PLC', 'HMI', 'màn hình', 'biến tần', 'servo', 'encoder', 'rơ le',
            'nguồn công nghiệp', 'thiết bị điều khiển', 'thiết bị tự động hóa',
        ];

        foreach ($keywords as $keyword) {
            $keywordLower = mb_strtolower(trim($keyword));
            
            // Loại bỏ mã sản phẩm
            $keywordUpper = mb_strtoupper($keyword);
            if (preg_match('/^[A-Z][A-Z0-9\-]{2,15}$/u', $keywordUpper)) {
                continue;
            }
            
            // Loại bỏ hãng
            if (preg_match('/\b(omron|siemens|mitsubishi|schneider|yaskawa|weintek|abb|rockwell|phoenix|bosch|festo|smc|keyence|panasonic|ls electric)\b/iu', $keyword)) {
                continue;
            }
            
            foreach ($validCategoryTypes as $categoryType) {
                if (str_contains($keywordLower, mb_strtolower($categoryType)) || 
                    str_contains(mb_strtolower($categoryType), $keywordLower)) {
                    return true;
                }
            }
            
            // Kiểm tra từ khóa chung
            if (str_contains($keywordLower, 'cảm biến') ||
                str_contains($keywordLower, 'plc') ||
                str_contains($keywordLower, 'hmi') ||
                str_contains($keywordLower, 'biến tần') ||
                str_contains($keywordLower, 'servo') ||
                str_contains($keywordLower, 'encoder') ||
                str_contains($keywordLower, 'rơ le') ||
                str_contains($keywordLower, 'màn hình')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Phân tích ảnh với Google Vision API
     */
    protected function analyzeWithGoogleVision(string $imagePath): array
    {
        try {
            // Cần cài đặt: composer require google/cloud-vision
            // Và cấu hình GOOGLE_APPLICATION_CREDENTIALS trong .env

            // $vision = new \Google\Cloud\Vision\V1\ImageAnnotatorClient();
            // $image = file_get_contents($imagePath);
            // $response = $vision->labelDetection($image);
            // $labels = $response->getLabelAnnotations();

            // $keywords = [];
            // foreach ($labels as $label) {
            //     $keywords[] = $label->getDescription();
            // }

            // // Lọc keywords liên quan đến thiết bị tự động hóa
            // return $this->filterDeviceKeywords($keywords);

            Log::info('Google Vision API not implemented yet');

            return $this->getDefaultKeywords();
        } catch (\Exception $e) {
            Log::error('Google Vision API error: '.$e->getMessage());

            return $this->getDefaultKeywords();
        }
    }

    /**
     * Phân tích ảnh với AWS Rekognition
     */
    protected function analyzeWithAWSRekognition(string $imagePath): array
    {
        try {
            // Cần cài đặt: composer require aws/aws-sdk-php
            // Và cấu hình AWS credentials trong .env

            // $rekognition = new \Aws\Rekognition\RekognitionClient([
            //     'version' => 'latest',
            //     'region' => config('services.aws_rekognition.region'),
            // ]);

            // $image = file_get_contents($imagePath);
            // $result = $rekognition->detectLabels([
            //     'Image' => ['Bytes' => $image],
            //     'MaxLabels' => 10,
            //     'MinConfidence' => 70,
            // ]);

            // $keywords = [];
            // foreach ($result['Labels'] as $label) {
            //     $keywords[] = $label['Name'];
            // }

            // return $this->filterDeviceKeywords($keywords);

            Log::info('AWS Rekognition not implemented yet');

            return $this->getDefaultKeywords();
        } catch (\Exception $e) {
            Log::error('AWS Rekognition error: '.$e->getMessage());

            return $this->getDefaultKeywords();
        }
    }

    /**
     * Phân tích ảnh với local AI model
     */
    protected function analyzeWithLocalAI(string $imagePath): array
    {
        try {
            // Có thể sử dụng các model như:
            // - TensorFlow Lite
            // - ONNX Runtime
            // - PyTorch Mobile
            // - Custom model trained for industrial device recognition

            Log::info('Local AI not implemented yet');

            return $this->getDefaultKeywords();
        } catch (\Exception $e) {
            Log::error('Local AI error: '.$e->getMessage());

            return $this->getDefaultKeywords();
        }
    }

    /**
     * Lọc keywords liên quan đến thiết bị tự động hóa
     */
    protected function filterDeviceKeywords(array $keywords): array
    {
        $deviceKeywords = [
            'cảm biến', 'PLC', 'HMI', 'biến tần', 'servo', 'encoder', 'rơ le',
            'sensor', 'controller', 'inverter', 'drive', 'automation', 'industrial',
            'thiết bị tự động hóa', 'thiết bị công nghiệp', 'thiết bị điều khiển',
        ];

        $filtered = [];
        foreach ($keywords as $keyword) {
            $keywordLower = mb_strtolower($keyword);
            foreach ($deviceKeywords as $deviceKeyword) {
                if (str_contains($keywordLower, $deviceKeyword) ||
                    str_contains($deviceKeyword, $keywordLower)) {
                    $filtered[] = $keyword;
                    break;
                }
            }
        }

        return ! empty($filtered) ? $filtered : $this->getDefaultKeywords();
    }

    /**
     * Keywords mặc định từ dữ liệu dự án khi không có AI service hoặc response không hợp lệ
     */
    protected function getDefaultKeywords(): array
    {
        Log::info('Using fallback keywords from project data');

        try {
            // Lấy các sản phẩm phổ biến nhất (ngẫu nhiên để tránh luôn trả về cùng 1 sản phẩm)
            $popularProducts = \App\Models\Product::query()
                ->active()
                ->whereNotNull('sku')
                ->where('sku', '!=', '')
                ->inRandomOrder()
                ->limit(20)
                ->get(['sku', 'name']);

            $keywords = [];

            foreach ($popularProducts as $product) {
                // Thêm SKU nếu có
                if (!empty($product->sku) && preg_match('/^[A-Z][A-Z0-9\-]{2,15}$/iu', mb_strtoupper($product->sku))) {
                    $keywords[] = $product->sku;
                }

                // Parse tên sản phẩm để lấy keywords
                $parsed = $this->parseDeviceString($product->name);
                if (!empty($parsed['code'])) {
                    $keywords[] = $parsed['code'];
                }
                if (!empty($parsed['name']) && mb_strlen($parsed['name']) >= 3) {
                    $keywords[] = $parsed['name'];
                }
                if (!empty($parsed['brand'])) {
                    $keywords[] = $parsed['brand'];
                }
            }

            // Loại bỏ trùng lặp
            $keywords = array_values(array_unique($keywords));

            // Giới hạn số lượng
            $keywords = array_slice($keywords, 0, 10);

            if (!empty($keywords)) {
                Log::info('Fallback keywords from products', ['count' => count($keywords)]);
                return $keywords;
            }
        } catch (\Exception $e) {
            Log::error('Error getting fallback keywords: '.$e->getMessage());
        }

        // Fallback cuối cùng: keywords chung chung
        return [
            'cảm biến quang',
            'PLC',
            'HMI',
            'biến tần',
            'servo',
            'encoder',
            'rơ le',
        ];
    }
}
