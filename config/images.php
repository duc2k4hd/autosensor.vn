<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Image Resize Sizes
    |--------------------------------------------------------------------------
    |
    | Các kích thước resize cho ảnh sản phẩm.
    | Format: [width, height]
    | Nếu mảng rỗng, hệ thống sẽ không resize ảnh.
    |
    */

    // Kích thước cho ảnh chính (primary image)
    'main_sizes' => [
        // [500, 500],
        // [150, 150],
        // [300, 300],
    ],

    // Kích thước cho ảnh phụ (gallery images)
    'gallery_sizes' => [
        // [150, 150],
    ],

    // Kích thước chung cho import (dùng khi import từ Excel)
    'import_sizes' => [
        // [500, 500],
        // [300, 300],
        // [150, 150],
    ],
];
