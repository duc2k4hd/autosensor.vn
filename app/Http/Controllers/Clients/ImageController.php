<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Intervention\Image\ImageManagerStatic as Image;

class ImageController extends Controller
{
    public function resize(Request $request)
    {
        $url = $request->query('url');
        $width = (int) $request->query('width', 300);

        if (! $url || $width <= 0 || $width > 1900) {
            abort(404);
        }

        $path = public_path(parse_url($url, PHP_URL_PATH));
        if (! is_file($path)) {
            abort(404);
        }

        $cacheDir = public_path("clients/assets/img/clothes/resize/{$width}");
        if (! is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $filename = pathinfo($path, PATHINFO_FILENAME).'.webp';
        $cachePath = "{$cacheDir}/{$filename}";

        // CACHE HIT
        if (is_file($cachePath)) {
            return $this->imageResponse($cachePath);
        }

        // ❌ KHÔNG LOCK – KHÔNG SLEEP
        // 👉 Nếu miss → trả ảnh gốc trước

        dispatch(function () use ($path, $cachePath, $width) {
            Image::make($path)
                ->resize($width, null, function ($c) {
                    $c->aspectRatio();
                    $c->upsize();
                })
                ->encode('webp', 80)
                ->save($cachePath);
        })->afterResponse();

        return $this->imageResponse($path); // fallback
    }

    private function imageResponse(string $path)
    {
        return response()->file($path, [
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
