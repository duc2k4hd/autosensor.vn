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
                                'text' => 'Bạn là chuyên gia về thiết bị tự động hóa công nghiệp. Phân tích kỹ ảnh này và xác định CHÍNH XÁC loại thiết bị.

                                QUAN TRỌNG - Trả về CHỈ tên thiết bị cụ thể:
                                1. Tên thiết bị CỤ THỂ nhất (ví dụ: cảm biến quang E3F-DS30C4, PLC Siemens S7-1200, HMI Weintek MT8071iE, biến tần Mitsubishi FR-D720, servo Yaskawa SGMAV, encoder Omron E6B2, rơ le Schneider RXM, v.v.)
                                2. Loại thiết bị (ví dụ: cảm biến quang, cảm biến tiệm cận, PLC, HMI, biến tần, servo, encoder, rơ le)
                                3. Hãng sản xuất nếu có thể nhận diện (ví dụ: Omron, Siemens, Mitsubishi, Schneider, Yaskawa, Weintek)

                                Trả về CHỈ các từ khóa tiếng Việt, mỗi từ khóa trên một dòng, KHÔNG giải thích.
                                QUAN TRỌNG: Ưu tiên tên thiết bị (ví dụ: cảm biến quang, cảm biến tiệm cận, cảm biến vùng) trước, sau đó mới đến mã sản phẩm.

                                Ví dụ nếu là cảm biến quang Omron E3F-DS30C4:
                                cảm biến quang
                                cảm biến quang Omron
                                E3F-DS30C4
                                Omron

                                Ví dụ nếu là cảm biến tiệm cận Omron E2E-X10D1-N:
                                cảm biến tiệm cận
                                cảm biến tiệm cận Omron
                                E2E-X10D1-N
                                Omron

                                Ví dụ nếu là PLC Siemens S7-1200:
                                PLC
                                PLC Siemens
                                S7-1200
                                Siemens',
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
                    'temperature' => 0.2,
                    'topK' => 20,
                    'topP' => 0.9,
                    'maxOutputTokens' => 200,
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

            // Validation: chỉ chấp nhận nếu có ít nhất mã hoặc tên thiết bị hợp lệ
            if (!$this->validateKeywords($keywords)) {
                Log::warning('Gemini response không hợp lệ - không có mã hoặc tên thiết bị', [
                    'keywords' => $keywords,
                    'original_text' => $text,
                    'hasValidCode' => $this->hasValidCodeInKeywords($keywords),
                    'hasValidName' => $this->hasValidNameInKeywords($keywords),
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
     * Tách thành: tên, hãng, mã (ví dụ: "Cảm biến quang Omron E3Z-T61 2M" -> ["E3Z-T61", "Cảm biến quang", "Omron"])
     */
    protected function extractKeywordsFromText(string $text): array
    {
        // Tách text thành các dòng và lọc
        $lines = preg_split('/[\r\n]+/', $text);
        $keywords = [];
        $parsedKeywords = [];

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
                '/^(nếu|nếu là|đây là|trong ảnh|ảnh này|có thể|thường|thường là|quan trọng)$/iu',
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
            if (mb_strlen($line) > 100) {
                continue;
            }

            // Thử parse chuỗi thành tên, hãng, mã
            $parsed = $this->parseDeviceString($line);
            if (!empty($parsed)) {
                // Ưu tiên: tên -> mã -> hãng
                if (!empty($parsed['name'])) {
                    $parsedKeywords[] = $parsed['name'];
                }
                if (!empty($parsed['code'])) {
                    $parsedKeywords[] = $parsed['code'];
                }
                if (!empty($parsed['brand'])) {
                    $parsedKeywords[] = $parsed['brand'];
                }
            } else {
                // Nếu không parse được, thêm vào keywords thông thường
                $keyword = mb_strtolower($line);
                $keyword = preg_replace('/[^\p{L}\p{N}\s\-]/u', ' ', $keyword);
                $keyword = preg_replace('/\s+/', ' ', $keyword);
                $keyword = trim($keyword);

                if (mb_strlen($keyword) >= 2 && mb_strlen($keyword) <= 50) {
                    $deviceRelated = [
                        'cảm biến', 'PLC', 'HMI', 'biến tần', 'servo', 'encoder', 'rơ le',
                        'sensor', 'controller', 'inverter', 'drive', 'automation', 'industrial',
                    ];

                    $isDeviceRelated = false;
                    foreach ($deviceRelated as $term) {
                        if (str_contains($keyword, $term)) {
                            $isDeviceRelated = true;
                            break;
                        }
                    }

                    if ($isDeviceRelated || (mb_strlen($keyword) <= 25 && !preg_match('/^(ví dụ|example|v\.v\.|etc|yêu cầu|mô tả|đặc điểm|hình dáng|loại thiết bị|trả về|chỉ|không|và|hoặc|ưu tiên|sau đó|mới đến|nếu|nếu là|đây là|trong ảnh|ảnh này|có thể|thường|thường là|quan trọng)$/iu', $keyword))) {
                        $keywords[] = $keyword;
                    }
                }
            }
        }

        // Ưu tiên parsed keywords (tên, mã, hãng) trước
        $allKeywords = array_merge($parsedKeywords, $keywords);

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

        Log::info('Extracted keywords from Gemini', [
            'original_text' => $text,
            'keywords' => $uniqueKeywords,
            'parsed' => !empty($parsedKeywords),
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
     * Validate keywords - chỉ chấp nhận nếu có ít nhất mã hoặc tên thiết bị hợp lệ
     */
    protected function validateKeywords(array $keywords): bool
    {
        if (empty($keywords)) {
            return false;
        }

        // Danh sách các loại thiết bị hợp lệ
        $validDeviceTypes = [
            'cảm biến', 'PLC', 'HMI', 'biến tần', 'servo', 'encoder', 'rơ le',
            'cảm biến quang', 'cảm biến tiệm cận', 'cảm biến nhiệt độ', 'cảm biến áp suất',
            'sensor', 'controller', 'inverter', 'drive', 'automation', 'industrial',
        ];

        // Danh sách các hãng hợp lệ
        $validBrands = [
            'omron', 'siemens', 'mitsubishi', 'schneider', 'yaskawa', 'weintek',
            'abb', 'rockwell', 'phoenix', 'bosch', 'festo', 'smc', 'keyence', 'panasonic',
        ];

        $hasValidCode = false;
        $hasValidName = false;
        $hasValidBrand = false;

        foreach ($keywords as $keyword) {
            $keywordUpper = mb_strtoupper($keyword);
            $keywordLower = mb_strtolower($keyword);

            // Kiểm tra mã sản phẩm (dạng: chữ cái + số + dấu gạch, ví dụ: E3Z-T61, S7-1200)
            if (preg_match('/^[A-Z][A-Z0-9\-]{2,15}$/u', $keywordUpper)) {
                $hasValidCode = true;
            }

            // Kiểm tra tên thiết bị (chứa loại thiết bị hợp lệ)
            foreach ($validDeviceTypes as $deviceType) {
                if (str_contains($keywordLower, $deviceType)) {
                    $hasValidName = true;
                    break;
                }
            }

            // Kiểm tra hãng
            foreach ($validBrands as $brand) {
                if (str_contains($keywordLower, $brand)) {
                    $hasValidBrand = true;
                    break;
                }
            }
        }

        // Chấp nhận nếu có ít nhất:
        // - Mã sản phẩm, HOẶC
        // - Tên thiết bị hợp lệ (không cần hãng), HOẶC
        // - Tên thiết bị + hãng, HOẶC
        // - Tên thiết bị + ít nhất 1 keyword khác
        return $hasValidCode || $hasValidName || ($hasValidName && $hasValidBrand) || ($hasValidName && count($keywords) >= 2);
    }

    /**
     * Helper method để kiểm tra có mã hợp lệ trong keywords
     */
    protected function hasValidCodeInKeywords(array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            $keywordUpper = mb_strtoupper($keyword);
            if (preg_match('/^[A-Z][A-Z0-9\-]{2,15}$/u', $keywordUpper)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Helper method để kiểm tra có tên thiết bị hợp lệ trong keywords
     */
    protected function hasValidNameInKeywords(array $keywords): bool
    {
        $validDeviceTypes = [
            'cảm biến', 'PLC', 'HMI', 'biến tần', 'servo', 'encoder', 'rơ le',
            'cảm biến quang', 'cảm biến tiệm cận', 'cảm biến nhiệt độ', 'cảm biến áp suất',
            'sensor', 'controller', 'inverter', 'drive', 'automation', 'industrial',
        ];

        foreach ($keywords as $keyword) {
            $keywordLower = mb_strtolower($keyword);
            foreach ($validDeviceTypes as $deviceType) {
                if (str_contains($keywordLower, $deviceType)) {
                    return true;
                }
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
