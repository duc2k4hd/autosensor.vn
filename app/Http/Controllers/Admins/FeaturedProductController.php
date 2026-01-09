<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeaturedProductController extends Controller
{
    /**
     * Hiển thị trang quản lý sản phẩm phổ biến
     */
    public function index(): View
    {
        $featuredProducts = Product::query()
            ->where('is_featured', true)
            ->with(['primaryCategory', 'brand'])
            ->orderBy('id', 'desc')
            ->get();

        // Preload images để tránh N+1 query
        Product::preloadImages($featuredProducts);

        return view('admins.products.featured', compact('featuredProducts'));
    }

    /**
     * API: Tìm kiếm sản phẩm để thêm vào danh sách phổ biến
     */
    public function search(Request $request): JsonResponse
    {
        $keyword = $request->input('keyword', '');
        $limit = $request->input('limit', 20);

        if (empty(trim($keyword))) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng nhập từ khóa tìm kiếm',
            ]);
        }

        $products = Product::query()
            ->where('is_active', true)
            ->where('is_featured', false) // Chỉ tìm sản phẩm chưa phổ biến
            ->where(function ($query) use ($keyword) {
                $query->where('name', 'like', "%{$keyword}%")
                    ->orWhere('sku', 'like', "%{$keyword}%");
            })
            ->with(['primaryCategory', 'brand'])
            ->orderBy('name')
            ->limit($limit)
            ->get();

        // Preload images
        Product::preloadImages($products);

        return response()->json([
            'success' => true,
            'data' => $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'price' => number_format($product->price ?? 0, 0, ',', '.'),
                    'image' => $product->primaryImage?->url 
                        ? asset('clients/assets/img/clothes/' . $product->primaryImage->url)
                        : asset('clients/assets/img/clothes/no-image.webp'),
                    'category' => $product->primaryCategory?->name,
                    'brand' => $product->brand?->name,
                ];
            }),
        ]);
    }

    /**
     * Thêm sản phẩm vào danh sách phổ biến
     */
    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'required|integer|exists:products,id',
        ]);

        $productIds = $request->input('product_ids');
        $count = Product::whereIn('id', $productIds)
            ->where('is_featured', false)
            ->update(['is_featured' => true]);

        return response()->json([
            'success' => true,
            'message' => "Đã thêm {$count} sản phẩm vào danh sách phổ biến",
            'count' => $count,
        ]);
    }

    /**
     * Xóa sản phẩm khỏi danh sách phổ biến
     */
    public function remove(Request $request): JsonResponse
    {
        $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'required|integer|exists:products,id',
        ]);

        $productIds = $request->input('product_ids');
        $count = Product::whereIn('id', $productIds)
            ->where('is_featured', true)
            ->update(['is_featured' => false]);

        return response()->json([
            'success' => true,
            'message' => "Đã xóa {$count} sản phẩm khỏi danh sách phổ biến",
            'count' => $count,
        ]);
    }
}
