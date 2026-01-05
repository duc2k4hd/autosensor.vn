<?php

use App\Models\Setting;

if (! function_exists('renderMeta')) {
    function renderMeta($text)
    {
        // Nếu chưa có settings trong config thì nạp vào
        if (! config()->has('settings')) {
            try {
                // Query DB 1 lần
                $settings = Setting::pluck('value', 'key')->toArray();
                config(['settings' => $settings]);
            } catch (\Exception $e) {
                // Nếu DB chưa migrate hoặc lỗi thì fallback rỗng
                config(['settings' => []]);
            }
        }

        // Lấy subname từ config, fallback mặc định
        $shopName = config('settings.subname', 'AutoSensor');

        return str_replace(
            [
                '[AUTOSENSOR]currentyear[AUTOSENSOR]',
                '[AUTOSENSOR]subname[AUTOSENSOR]',
            ],
            [
                date('n') >= 11 ? date('Y') + 1 : date('Y'),
                $shopName,
            ],
            $text
        );
    }
}
