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
                                Ưu tiên tên thiết bị cụ thể trước.

                                Ví dụ nếu là cảm biến quang Omron E3F-DS30C4:
                                cảm biến quang
                                E3F-DS30C4
                                cảm biến quang Omron
                                Omron

                                Ví dụ nếu là PLC Siemens S7-1200:
                                PLC
                                S7-1200
                                PLC Siemens
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

            if (empty($keywords)) {
                Log::warning('No keywords extracted from Gemini response', ['text' => $text]);

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
     */
    protected function extractKeywordsFromText(string $text): array
    {
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
                '/^(ví dụ|example|v\.v\.|etc|yêu cầu|mô tả|đặc điểm|hình dáng|loại cây|trả về|chỉ|không|và|hoặc|ưu tiên|sau đó|mới đến)$/iu',
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
            if (mb_strlen($line) > 50) {
                continue;
            }

            // Lấy tất cả keywords, không chỉ những từ có "cây" (vì có thể là tên cây không có từ "cây")
            $keyword = mb_strtolower($line);
            // Loại bỏ các ký tự đặc biệt không cần thiết nhưng giữ lại dấu cách
            $keyword = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $keyword);
            $keyword = preg_replace('/\s+/', ' ', $keyword);
            $keyword = trim($keyword);

            // Chấp nhận keywords từ 2-50 ký tự
            if (mb_strlen($keyword) >= 2 && mb_strlen($keyword) <= 50) {
                // Ưu tiên keywords có chứa tên thiết bị cụ thể hoặc từ liên quan đến thiết bị tự động hóa
                $deviceRelated = [
                    'cảm biến', 'PLC', 'HMI', 'biến tần', 'servo', 'encoder', 'rơ le',
                    'sensor', 'controller', 'inverter', 'drive', 'automation', 'industrial',
                    'Omron', 'Siemens', 'Mitsubishi', 'Schneider', 'Yaskawa', 'Weintek',
                    'E3F', 'S7', 'FR-D', 'SGMAV', 'E6B2', 'RXM', 'MT8071',
                ];

                $isDeviceRelated = false;
                foreach ($deviceRelated as $term) {
                    if (str_contains($keyword, $term)) {
                        $isDeviceRelated = true;
                        break;
                    }
                }

                // Chấp nhận nếu là từ liên quan đến thiết bị HOẶC là keyword ngắn (có thể là tên thiết bị cụ thể)
                if ($isDeviceRelated || (mb_strlen($keyword) <= 25 && ! preg_match('/^(ví dụ|example|v\.v\.|etc|yêu cầu|mô tả|đặc điểm|hình dáng|loại thiết bị|trả về|chỉ|không|và|hoặc|ưu tiên|sau đó|mới đến|nếu|nếu là|đây là|trong ảnh|ảnh này|có thể|thường|thường là|quan trọng)$/iu', $keyword))) {
                    $keywords[] = $keyword;
                }
            }
        }

        // Nếu không tìm thấy keywords từ pattern, thử tách từ
        if (empty($keywords)) {
            // Tìm các từ có chứa "cây"
            preg_match_all('/\b[\p{L}]*cây[\p{L}]*\b/ui', $text, $matches);
            if (! empty($matches[0])) {
                $keywords = array_map(function ($match) {
                    $match = mb_strtolower(trim($match));
                    $match = preg_replace('/[^\p{L}\p{N}\s]/u', '', $match);

                    return trim($match);
                }, array_unique($matches[0]));
                $keywords = array_filter($keywords, fn ($k) => mb_strlen($k) >= 3 && mb_strlen($k) <= 50);
            }
        }

        // Loại bỏ trùng lặp và sắp xếp theo độ dài (từ ngắn đến dài để ưu tiên tên cây cụ thể)
        $keywords = array_values(array_unique($keywords));
        usort($keywords, function ($a, $b) {
            $lenA = mb_strlen($a);
            $lenB = mb_strlen($b);
            if ($lenA === $lenB) {
                return 0;
            }

            return $lenA > $lenB ? 1 : -1;
        });

        // Giới hạn số lượng keywords (ưu tiên keywords ngắn hơn, cụ thể hơn)
        $keywords = array_slice($keywords, 0, 8);

        Log::info('Extracted keywords from Gemini', [
            'original_text' => $text,
            'keywords' => $keywords,
        ]);

        return ! empty($keywords) ? $keywords : $this->getDefaultKeywords();
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
     * Keywords mặc định khi không có AI service
     */
    protected function getDefaultKeywords(): array
    {
        Log::warning('Using default keywords - Gemini API key not configured or invalid. Please configure GEMINI_API_KEY in .env file.');

        // Trả về mảng rỗng để không tìm kiếm với keywords chung chung
        // Người dùng sẽ thấy thông báo lỗi rõ ràng hơn
        return [];
    }
}
