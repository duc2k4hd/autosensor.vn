<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\PopupContent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PopupContentController extends Controller
{
    public function index()
    {
        $items = PopupContent::orderBy('sort_order')->get();
        return view('admins.popup_contents.index', compact('items'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['is_active'] = $request->has('is_active');
        $data['sort_order'] = (PopupContent::max('sort_order') ?? 0) + 1;

        if ($request->hasFile('image')) {
            $data['image'] = $this->storeImage($request->file('image'), $data['title']);
        }

        PopupContent::create($data);

        return redirect()->route('admin.popup-contents.index')->with('success', 'Đã thêm popup.');
    }

    public function update(Request $request, PopupContent $popupContent)
    {
        $data = $this->validateData($request);
        $data['is_active'] = $request->has('is_active');

        if ($request->hasFile('image')) {
            $data['image'] = $this->storeImage($request->file('image'), $data['title']);
        }

        $popupContent->update($data);

        return redirect()->route('admin.popup-contents.index')->with('success', 'Đã cập nhật popup.');
    }

    public function destroy(PopupContent $popupContent)
    {
        $popupContent->delete();
        return redirect()->route('admin.popup-contents.index')->with('success', 'Đã xóa popup.');
    }

    public function reorder(Request $request)
    {
        $data = $request->validate([
            'orders' => ['required', 'array'],
            'orders.*.id' => ['required', 'integer', 'exists:popup_contents,id'],
            'orders.*.sort_order' => ['required', 'integer'],
        ]);

        foreach ($data['orders'] as $item) {
            PopupContent::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['success' => true]);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'button_text' => ['nullable', 'string', 'max:100'],
            'button_link' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:20'], // fallback, not stored but harmless
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'image' => ['nullable', 'image', 'max:3072'],
        ]);
    }

    private function storeImage($file, string $title): string
    {
        $safeTitle = Str::slug($title) ?: 'popup';
        $ext = $file->getClientOriginalExtension() ?: 'png';
        $filename = $safeTitle.'.'.$ext;
        $dest = public_path('clients/assets/img/popup');
        if (! is_dir($dest)) {
            mkdir($dest, 0755, true);
        }
        $counter = 1;
        $final = $filename;
        while (file_exists($dest.'/'.$final)) {
            $final = $safeTitle.'-'.$counter.'.'.$ext;
            $counter++;
        }
        $file->move($dest, $final);
        return $final;
    }
}

