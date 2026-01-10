<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductWizardSession;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProductWizardService
{
    /**
     * Lọc sản phẩm dựa trên câu trả lời của wizard
     */
    public function filterProducts(array $answers, int $categoryId): array
    {
        $query = Product::query()
            ->where('is_active', true)
            ->with(['primaryCategory', 'brand', 'variants']);

        // Lọc theo category
        $query->where(function ($q) use ($categoryId) {
            $q->where('primary_category_id', $categoryId)
                ->orWhereJsonContains('category_ids', (int) $categoryId)
                ->orWhereJsonContains('category_ids', (string) $categoryId);
        });

        // Lọc theo môi trường
        if (!empty($answers['environment'])) {
            $this->filterByEnvironment($query, $answers['environment']);
        }

        // Lọc theo khoảng cách đo (cho cảm biến)
        if (!empty($answers['distance'])) {
            $this->filterByDistance($query, $answers['distance']);
        }

        // Lọc theo vật liệu (cho cảm biến)
        if (!empty($answers['material'])) {
            $this->filterByMaterial($query, $answers['material']);
        }

        // Lọc theo loại ngõ ra
        if (!empty($answers['output_type'])) {
            $this->filterByOutputType($query, $answers['output_type']);
        }

        // Lọc theo điện áp
        if (!empty($answers['voltage'])) {
            $this->filterByVoltage($query, $answers['voltage']);
        }

        // Lọc theo chuẩn kết nối
        if (!empty($answers['connection'])) {
            $this->filterByConnection($query, $answers['connection']);
        }

        // Lọc theo công suất (cho biến tần)
        if (!empty($answers['power'])) {
            $this->filterByPower($query, $answers['power']);
        }

        // Lọc theo số ngõ vào/ra (cho PLC)
        if (!empty($answers['io_count'])) {
            $this->filterByIOCount($query, $answers['io_count']);
        }

        $products = $query->orderBy('is_featured', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        Product::preloadImages($products);

        return $products->take(5)->values()->all();
    }


    /**
     * Lọc theo môi trường
     */
    protected function filterByEnvironment($query, string $environment): void
    {
        // Môi trường: normal, dusty, humid, high_temp, outdoor
        $keywords = [
            'normal' => ['thường', 'bình thường'],
            'dusty' => ['bụi', 'chống bụi', 'ip65', 'ip67'],
            'humid' => ['ẩm', 'chống ẩm', 'ip65', 'ip67', 'ip68'],
            'high_temp' => ['nhiệt độ cao', 'chịu nhiệt', 'nhiệt độ'],
            'outdoor' => ['ngoài trời', 'outdoor', 'ip65', 'ip67', 'ip68'],
        ];

        if (isset($keywords[$environment])) {
            $query->where(function ($q) use ($keywords, $environment) {
                foreach ($keywords[$environment] as $keyword) {
                    $q->orWhere('name', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%")
                        ->orWhere('short_description', 'like', "%{$keyword}%");
                }
            });
        }
    }

    /**
     * Lọc theo khoảng cách đo (cho cảm biến)
     */
    protected function filterByDistance($query, string $distance): void
    {
        // Khoảng cách: close (<10cm), medium (10-50cm), far (50-200cm), very_far (>200cm)
        $ranges = [
            'close' => ['tiệm cận', 'gần', '<10', '10mm', '20mm'],
            'medium' => ['trung bình', '10-50', '50cm', '30cm'],
            'far' => ['xa', '50-200', '100cm', '200cm'],
            'very_far' => ['rất xa', '>200', '500cm', '1000cm'],
        ];

        if (isset($ranges[$distance])) {
            $query->where(function ($q) use ($ranges, $distance) {
                foreach ($ranges[$distance] as $keyword) {
                    $q->orWhere('name', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%");
                }
            });
        }
    }

    /**
     * Lọc theo vật liệu (cho cảm biến)
     */
    protected function filterByMaterial($query, string $material): void
    {
        // Vật liệu: metal, plastic, glass, liquid, transparent
        $keywords = [
            'metal' => ['kim loại', 'metal', 'sắt', 'thép'],
            'plastic' => ['nhựa', 'plastic', 'pvc'],
            'glass' => ['thủy tinh', 'glass', 'kính'],
            'liquid' => ['chất lỏng', 'liquid', 'nước'],
            'transparent' => ['trong suốt', 'transparent', 'trong'],
        ];

        if (isset($keywords[$material])) {
            $query->where(function ($q) use ($keywords, $material) {
                foreach ($keywords[$material] as $keyword) {
                    $q->orWhere('name', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%");
                }
            });
        }
    }

    /**
     * Lọc theo loại ngõ ra
     */
    protected function filterByOutputType($query, string $outputType): void
    {
        // Ngõ ra: npn, pnp, relay, analog, digital
        $keywords = [
            'npn' => ['npn'],
            'pnp' => ['pnp'],
            'relay' => ['relay', 'rơ le'],
            'analog' => ['analog', '4-20ma', '0-10v'],
            'digital' => ['digital', 'số'],
        ];

        if (isset($keywords[$outputType])) {
            $query->where(function ($q) use ($keywords, $outputType) {
                foreach ($keywords[$outputType] as $keyword) {
                    $q->orWhere('name', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%")
                        ->orWhere('sku', 'like', "%{$keyword}%");
                }
            });
        }
    }

    /**
     * Lọc theo điện áp
     */
    protected function filterByVoltage($query, string $voltage): void
    {
        // Điện áp: 12v, 24v, 220v, dc, ac
        $keywords = [
            '12v' => ['12v', '12 v', '12VDC'],
            '24v' => ['24v', '24 v', '24VDC'],
            '220v' => ['220v', '220 v', '220VAC', 'AC'],
            'dc' => ['DC', 'dc', 'một chiều'],
            'ac' => ['AC', 'ac', 'xoay chiều'],
        ];

        if (isset($keywords[$voltage])) {
            $query->where(function ($q) use ($keywords, $voltage) {
                foreach ($keywords[$voltage] as $keyword) {
                    $q->orWhere('name', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%")
                        ->orWhere('sku', 'like', "%{$keyword}%");
                }
            });
        }
    }

    /**
     * Lọc theo chuẩn kết nối
     */
    protected function filterByConnection($query, string $connection): void
    {
        // Kết nối: cable, connector, m12, m8, terminal
        $keywords = [
            'cable' => ['cáp', 'cable', 'dây'],
            'connector' => ['connector', 'đầu nối'],
            'm12' => ['M12', 'm12'],
            'm8' => ['M8', 'm8'],
            'terminal' => ['terminal', 'đấu dây'],
        ];

        if (isset($keywords[$connection])) {
            $query->where(function ($q) use ($keywords, $connection) {
                foreach ($keywords[$connection] as $keyword) {
                    $q->orWhere('name', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%");
                }
            });
        }
    }

    /**
     * Lọc theo công suất (cho biến tần)
     */
    protected function filterByPower($query, string $power): void
    {
        // Công suất: low (<1kW), medium (1-5kW), high (5-15kW), very_high (>15kW)
        $ranges = [
            'low' => ['<1', '0.5', '0.75', '1kW'],
            'medium' => ['1-5', '2.2', '3.7', '5kW'],
            'high' => ['5-15', '7.5', '11', '15kW'],
            'very_high' => ['>15', '18.5', '22', '30kW'],
        ];

        if (isset($ranges[$power])) {
            $query->where(function ($q) use ($ranges, $power) {
                foreach ($ranges[$power] as $keyword) {
                    $q->orWhere('name', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%")
                        ->orWhere('sku', 'like', "%{$keyword}%");
                }
            });
        }
    }

    /**
     * Lọc theo số ngõ vào/ra (cho PLC)
     */
    protected function filterByIOCount($query, string $ioCount): void
    {
        // Số ngõ: small (<16), medium (16-32), large (32-64), very_large (>64)
        $ranges = [
            'small' => ['8', '10', '12', '14', '16'],
            'medium' => ['16', '20', '24', '32'],
            'large' => ['32', '40', '48', '64'],
            'very_large' => ['64', '80', '96', '128'],
        ];

        if (isset($ranges[$ioCount])) {
            $query->where(function ($q) use ($ranges, $ioCount) {
                foreach ($ranges[$ioCount] as $keyword) {
                    $q->orWhere('name', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%")
                        ->orWhere('sku', 'like', "%{$keyword}%");
                }
            });
        }
    }

    /**
     * Lưu phiên tư vấn
     */
    public function saveSession(array $answers, int $categoryId, array $recommendedProducts = []): ProductWizardSession
    {
        return ProductWizardSession::create([
            'category_id' => $categoryId,
            'answers' => $answers,
            'recommended_product_ids' => array_column($recommendedProducts, 'id'),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Lấy thống kê phiên tư vấn
     */
    public function getStatistics(int $days = 30): array
    {
        $startDate = now()->subDays($days);

        return [
            'total_sessions' => ProductWizardSession::where('created_at', '>=', $startDate)->count(),
            'by_category' => ProductWizardSession::where('created_at', '>=', $startDate)
                ->selectRaw('category_id, COUNT(*) as count')
                ->groupBy('category_id')
                ->pluck('count', 'category_id')
                ->toArray(),
            'by_environment' => ProductWizardSession::where('created_at', '>=', $startDate)
                ->whereJsonContains('answers->environment', 'dusty')
                ->count(),
        ];
    }
}
