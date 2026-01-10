<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\ProductWizardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductWizardController extends Controller
{
    protected ProductWizardService $wizardService;

    public function __construct(ProductWizardService $wizardService)
    {
        $this->wizardService = $wizardService;
    }

    /**
     * Hiển thị form wizard
     */
    public function index(Request $request): View
    {
        $categoryId = $request->get('category_id');
        $categorySlug = $request->get('category');
        
        // Lấy category từ ID hoặc slug
        if ($categoryId) {
            $category = Category::where('id', $categoryId)
                ->where('is_active', true)
                ->whereNull('parent_id')
                ->first();
        } elseif ($categorySlug) {
            $category = Category::where('slug', $categorySlug)
                ->where('is_active', true)
                ->whereNull('parent_id')
                ->first();
        } else {
            // Nếu không có category, lấy category đầu tiên
            $category = Category::where('is_active', true)
                ->whereNull('parent_id')
                ->orderBy('order')
                ->orderBy('name')
                ->first();
        }

        if (!$category) {
            abort(404, 'Danh mục không tồn tại');
        }

        $pageTitle = 'Hướng dẫn chọn ' . $category->name;

        return view('clients.pages.wizard.index', compact('category', 'pageTitle'));
    }

    /**
     * Xử lý câu trả lời và trả về kết quả
     */
    public function process(Request $request)
    {
        $request->validate([
            'category_id' => 'required|integer|exists:categories,id',
            'answers' => 'required|array',
        ]);

        $categoryId = $request->input('category_id');
        $answers = $request->input('answers');

        // Lọc sản phẩm dựa trên câu trả lời
        $recommendedProducts = $this->wizardService->filterProducts($answers, $categoryId);

        // Lưu phiên tư vấn
        $session = $this->wizardService->saveSession($answers, $categoryId, $recommendedProducts);

        return response()->json([
            'success' => true,
            'session_id' => $session->id,
            'products' => $recommendedProducts,
            'count' => count($recommendedProducts),
        ]);
    }

    /**
     * Hiển thị kết quả
     */
    public function result(Request $request, int $sessionId): View
    {
        $session = \App\Models\ProductWizardSession::findOrFail($sessionId);
        $products = $session->recommendedProducts();
        $category = $session->category;

        $pageTitle = $category ? 'Kết quả tư vấn chọn ' . $category->name : 'Kết quả tư vấn';

        return view('clients.pages.wizard.result', compact('session', 'products', 'pageTitle', 'category'));
    }
}
