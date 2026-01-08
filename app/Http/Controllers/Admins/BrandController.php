<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBrandRequest;
use App\Http\Requests\Admin\UpdateBrandRequest;
use App\Models\Brand;
use App\Services\ActivityLogService;
use App\Services\Admin\BrandService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function __construct(
        protected BrandService $brandService,
        protected ActivityLogService $activityLogService
    ) {}

    /**
     * Display a listing of brands
     */
    public function index(Request $request): View
    {
        $query = Brand::query()->withCount('products');

        // Search
        if ($keyword = $request->get('keyword')) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', '%'.$keyword.'%')
                    ->orWhere('slug', 'like', '%'.$keyword.'%')
                    ->orWhere('description', 'like', '%'.$keyword.'%');
            });
        }

        // Filter by status
        if ($status = $request->get('status')) {
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Sort
        $sortBy = $request->get('sort_by', 'order');
        $sortDir = $request->get('sort_dir', 'asc');

        if ($sortBy === 'name') {
            $query->orderBy('name', $sortDir);
        } elseif ($sortBy === 'created_at') {
            $query->orderBy('created_at', $sortDir);
        } else {
            $query->orderBy('order', $sortDir);
        }

        $query->orderBy('name', 'asc');

        // Pagination
        $perPage = (int) $request->get('per_page', 50);
        $perPage = in_array($perPage, [50, 100]) ? $perPage : 50;

        $brands = $query->paginate($perPage)->appends($request->query());

        return view('admins.brands.index', compact('brands'));
    }

    /**
     * Show the form for creating a new brand
     */
    public function create(): View
    {
        $brand = new Brand;

        return view('admins.brands.form', compact('brand'));
    }

    /**
     * Store a newly created brand
     */
    public function store(StoreBrandRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();
            $image = $request->hasFile('image') ? $request->file('image') : null;

            $brand = $this->brandService->create($data, $image);

            // Clear cache khi tạo brand mới
            Cache::forget('admin_brands_active');
            Cache::forget('import_brands_all');

            // Log activity
            $this->activityLogService->logCreate($brand, 'Tạo hãng mới: '.$brand->name);

            return redirect()
                ->route('admin.brands.index')
                ->with('success', 'Tạo hãng thành công.');
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified brand
     */
    public function show(Brand $brand): RedirectResponse
    {
        // Redirect to edit page instead of show page
        return redirect()->route('admin.brands.edit', $brand);
    }

    /**
     * Show the form for editing the specified brand
     */
    public function edit(Brand $brand): View
    {
        // Decode metadata if exists
        if ($brand->metadata && is_string($brand->metadata)) {
            $brand->metadata = json_decode($brand->metadata, true);
        }

        return view('admins.brands.form', compact('brand'));
    }

    /**
     * Update the specified brand
     */
    public function update(UpdateBrandRequest $request, Brand $brand): RedirectResponse
    {
        try {
            $data = $request->validated();
            $image = $request->hasFile('image') ? $request->file('image') : null;
            $deleteOldImage = $request->boolean('delete_image', false);

            $oldData = $brand->toArray();
            $this->brandService->update($brand, $data, $image);

            // Delete old image if requested
            if ($deleteOldImage && $brand->image) {
                $this->brandService->deleteImage($brand->image);
                $brand->update(['image' => null]);
            }

            // Clear cache khi cập nhật brand
            Cache::forget('admin_brands_active');
            Cache::forget('import_brands_all');

            // Log activity
            $this->activityLogService->logUpdate($brand->fresh(), $oldData, 'Cập nhật hãng: '.$brand->name);

            return redirect()
                ->route('admin.brands.index')
                ->with('success', 'Cập nhật hãng thành công.');
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified brand from storage
     */
    public function destroy(Brand $brand): RedirectResponse
    {
        try {
            $brandName = $brand->name;
            $this->brandService->delete($brand);

            // Clear cache khi xóa brand
            Cache::forget('admin_brands_active');
            Cache::forget('import_brands_all');

            // Log activity
            $this->activityLogService->logDelete($brand, 'Xóa hãng: '.$brandName);

            return redirect()
                ->route('admin.brands.index')
                ->with('success', 'Xóa hãng thành công.');
        } catch (\Throwable $e) {
            return back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }
}

