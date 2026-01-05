<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class ProductComparisonController extends Controller
{
    /**
     * Add product to comparison
     */
    public function add(Request $request, $productId)
    {
        $product = Product::active()->findOrFail($productId);
        $comparison = Session::get('product_comparison', []);

        // Limit to 4 products max
        if (count($comparison) >= 4) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chỉ có thể so sánh tối đa 4 sản phẩm.',
            ], 400);
        }

        // Check if already added
        if (in_array($productId, $comparison)) {
            return response()->json([
                'success' => false,
                'message' => 'Sản phẩm này đã có trong danh sách so sánh.',
            ], 400);
        }

        $comparison[] = $productId;
        Session::put('product_comparison', $comparison);

        return response()->json([
            'success' => true,
            'message' => 'Đã thêm sản phẩm vào danh sách so sánh.',
            'count' => count($comparison),
        ]);
    }

    /**
     * Remove product from comparison
     */
    public function remove(Request $request, $productId)
    {
        $comparison = Session::get('product_comparison', []);
        $comparison = array_values(array_filter($comparison, fn ($id) => $id != $productId));
        Session::put('product_comparison', $comparison);

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa sản phẩm khỏi danh sách so sánh.',
            'count' => count($comparison),
        ]);
    }

    /**
     * Clear all products from comparison
     */
    public function clear()
    {
        Session::forget('product_comparison');

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa tất cả sản phẩm khỏi danh sách so sánh.',
        ]);
    }

    /**
     * Show comparison page
     */
    public function index()
    {
        $productIds = Session::get('product_comparison', []);

        if (empty($productIds)) {
            return view('clients.pages.comparison.index', [
                'products' => collect(),
            ]);
        }

        $products = Product::with(['primaryCategory'])
            ->withApprovedCommentsMeta()
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id')
            ->sortBy(function ($product) use ($productIds) {
                return array_search($product->id, $productIds);
            })
            ->values();

        return view('clients.pages.comparison.index', [
            'products' => $products,
        ]);
    }

    /**
     * Get comparison count (for AJAX)
     */
    public function count()
    {
        $count = count(Session::get('product_comparison', []));

        return response()->json(['count' => $count]);
    }
}
