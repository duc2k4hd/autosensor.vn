<?php

/**
 * Script thay thế các pattern còn sót: xworld-*, xanhworld trong ID/biến JS
 * Chạy: php replace-autosensor-patterns.php
 */

$baseDir = __DIR__;
$excludedDirs = ['vendor', 'node_modules', '.git', 'storage', 'bootstrap/cache'];
$extensions = ['.php', '.blade.php', '.js', '.css', '.html', '.vue', '.ts', '.scss', '.sass'];
$replacements = 0;
$filesProcessed = 0;
$errors = [];

/**
 * Kiểm tra xem file có nên được xử lý không
 */
function shouldProcessFile($filePath, $excludedDirs, $extensions) {
    foreach ($excludedDirs as $excludedDir) {
        if (strpos($filePath, DIRECTORY_SEPARATOR . $excludedDir . DIRECTORY_SEPARATOR) !== false ||
            strpos($filePath, DIRECTORY_SEPARATOR . $excludedDir) === strlen($filePath) - strlen(DIRECTORY_SEPARATOR . $excludedDir)) {
            return false;
        }
    }
    
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $basename = basename($filePath);
    
    foreach ($extensions as $allowedExt) {
        $allowedExt = ltrim($allowedExt, '.');
        if ($ext === $allowedExt || 
            ($allowedExt === 'blade.php' && strpos($basename, '.blade.php') !== false) ||
            ($allowedExt === 'vue' && $ext === 'vue') ||
            ($allowedExt === 'ts' && $ext === 'ts')) {
            return true;
        }
    }
    
    return false;
}

/**
 * Thay thế các pattern trong file
 */
function replaceInFile($filePath) {
    global $replacements, $errors;
    
    try {
        $content = file_get_contents($filePath);
        if ($content === false) {
            $errors[] = "Không thể đọc file: $filePath";
            return false;
        }
        
        $originalContent = $content;
        
        // 1. Thay thế xworld-* thành autosensor-*
        $content = preg_replace('/xworld-([a-zA-Z0-9_-]+)/i', 'autosensor-$1', $content);
        
        // 2. Thay thế xworld_* thành autosensor_*
        $content = preg_replace('/xworld_([a-zA-Z0-9_-]+)/i', 'autosensor_$1', $content);
        
        // 3. Thay thế .xworld-* thành .autosensor-*
        $content = preg_replace('/\.xworld-([a-zA-Z0-9_-]+)/i', '.autosensor-$1', $content);
        
        // 4. Thay thế .xworld_* thành .autosensor_*
        $content = preg_replace('/\.xworld_([a-zA-Z0-9_-]+)/i', '.autosensor_$1', $content);
        
        // 5. Thay thế xanhworld trong ID và biến JS (autosensorChatPopup, autosensorChatInput, etc.)
        $content = preg_replace('/xanhworld([A-Z][a-zA-Z0-9]*)/i', 'autosensor$1', $content);
        
        // 6. Thay thế xanhworld trong các biến JS (autosensorOverlay, autosensorCurrentIndex, etc.)
        $content = preg_replace('/(\$|const|let|var)\s+([a-zA-Z_$][a-zA-Z0-9_$]*?)xanhworld([a-zA-Z0-9_$]*)/i', '$1 $2autosensor$3', $content);
        
        // 7. Thay thế xanhworld trong querySelector/getElementById
        $content = preg_replace('/(getElementById|querySelector|querySelectorAll)\s*\(\s*["\']([^"\']*?)xanhworld([^"\']*?)(["\'])/i', '$1($2autosensor$3$4', $content);
        
        // 8. Thay thế autosensor-garden-journey.jpg thành autosensor-journey.jpg
        $content = preg_replace('/autosensor-garden-journey\.jpg/i', 'autosensor-journey.jpg', $content);
        
        // Đếm số lần thay thế
        $count = 0;
        $patterns = [
            '/xworld-/i',
            '/xworld_/i',
            '/\.xworld-/i',
            '/\.xworld_/i',
            '/xanhworld([A-Z])/i',
            '/autosensor-garden-journey/i',
        ];
        
        foreach ($patterns as $pattern) {
            $count += preg_match_all($pattern, $originalContent);
        }
        
        if ($count > 0 || $content !== $originalContent) {
            $replacements += $count;
            
            // Ghi file
            if (file_put_contents($filePath, $content) === false) {
                $errors[] = "Không thể ghi file: $filePath";
                return false;
            }
            
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        $errors[] = "Lỗi khi xử lý file $filePath: " . $e->getMessage();
        return false;
    }
}

/**
 * Quét thư mục đệ quy
 */
function scanDirectory($dir, $excludedDirs, $extensions) {
    $files = [];
    
    try {
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            
            if (is_dir($path)) {
                $shouldExclude = false;
                foreach ($excludedDirs as $excludedDir) {
                    if (basename($path) === $excludedDir) {
                        $shouldExclude = true;
                        break;
                    }
                }
                
                if (!$shouldExclude) {
                    $files = array_merge($files, scanDirectory($path, $excludedDirs, $extensions));
                }
            } elseif (is_file($path)) {
                if (shouldProcessFile($path, $excludedDirs, $extensions)) {
                    $files[] = $path;
                }
            }
        }
    } catch (Exception $e) {
        global $errors;
        $errors[] = "Lỗi khi quét thư mục $dir: " . $e->getMessage();
    }
    
    return $files;
}

// Bắt đầu xử lý
echo "🔍 Đang quét các file...\n";
$files = scanDirectory($baseDir, $excludedDirs, $extensions);
echo "📁 Tìm thấy " . count($files) . " file cần kiểm tra\n\n";

echo "🔄 Đang thay thế các pattern 'xworld-*' và 'xanhworld' trong ID/biến...\n";
foreach ($files as $file) {
    $relativePath = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $file);
    if (replaceInFile($file)) {
        echo "✅ Đã cập nhật: $relativePath\n";
        $filesProcessed++;
    }
}

echo "\n";
echo "═══════════════════════════════════════════════════════\n";
echo "📊 KẾT QUẢ:\n";
echo "═══════════════════════════════════════════════════════\n";
echo "📝 Số file đã xử lý: $filesProcessed\n";
echo "🔄 Tổng số lần thay thế: $replacements\n";

if (!empty($errors)) {
    echo "\n⚠️  CÁC LỖI:\n";
    foreach ($errors as $error) {
        echo "   - $error\n";
    }
} else {
    echo "✅ Hoàn thành không có lỗi!\n";
}

echo "\n💡 Lưu ý: Hãy kiểm tra lại các file đã thay đổi và test lại website.\n";
echo "💡 Nếu cần hoàn tác, hãy dùng git: git checkout .\n";

