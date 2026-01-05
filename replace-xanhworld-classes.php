<?php

/**
 * Script thay thế toàn bộ class CSS từ "xanhworld" thành "autosensor"
 * Chạy: php replace-autosensor-classes.php
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
    // Kiểm tra thư mục bị loại trừ
    foreach ($excludedDirs as $excludedDir) {
        if (strpos($filePath, DIRECTORY_SEPARATOR . $excludedDir . DIRECTORY_SEPARATOR) !== false ||
            strpos($filePath, DIRECTORY_SEPARATOR . $excludedDir) === strlen($filePath) - strlen(DIRECTORY_SEPARATOR . $excludedDir)) {
            return false;
        }
    }
    
    // Kiểm tra extension
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $basename = basename($filePath);
    
    // Kiểm tra các extension được phép
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
 * Thay thế xanhworld thành autosensor trong nội dung file
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
        
        // Thay thế các pattern phổ biến:
        // 1. class="autosensor_..." hoặc class='autosensor_...'
        $content = preg_replace('/(class\s*=\s*["\'])([^"\']*?)xanhworld([^"\']*?)(["\'])/i', '$1$2autosensor$3$4', $content);
        
        // 2. class: "autosensor_..." hoặc class: 'autosensor_...' (cho Vue/JS)
        $content = preg_replace('/(class\s*:\s*["\'])([^"\']*?)xanhworld([^"\']*?)(["\'])/i', '$1$2autosensor$3$4', $content);
        
        // 3. className="autosensor_..." hoặc className='autosensor_...'
        $content = preg_replace('/(className\s*=\s*["\'])([^"\']*?)xanhworld([^"\']*?)(["\'])/i', '$1$2autosensor$3$4', $content);
        
        // 4. .xanhworld_... (trong CSS)
        $content = preg_replace('/(\.)xanhworld([_\-][a-zA-Z0-9_-]+)/i', '$1autosensor$2', $content);
        
        // 5. xanhworld_... (standalone trong code)
        $content = preg_replace('/([^a-zA-Z0-9_-])xanhworld([_\-][a-zA-Z0-9_-]+)/i', '$1autosensor$2', $content);
        
        // 6. 'xanhworld_...' hoặc "xanhworld_..." (string literals)
        $content = preg_replace('/(["\'])([^"\']*?)xanhworld([_\-][a-zA-Z0-9_-]+)([^"\']*?)(["\'])/i', '$1$2autosensor$3$4$5', $content);
        
        // 7. autosensor_main_... (pattern phổ biến)
        $content = preg_replace('/autosensor_main_/i', 'autosensor_main_', $content);
        
        // 8. xanhworld_... trong các biến PHP
        $content = preg_replace('/(\$[a-zA-Z0-9_]*?)(xanhworld)([_\-][a-zA-Z0-9_-]+)/i', '$1autosensor$3', $content);
        
        // Đếm số lần thay thế
        $count = substr_count($originalContent, 'xanhworld') - substr_count($content, 'xanhworld');
        if ($count > 0) {
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
    global $filesProcessed;
    
    $files = [];
    
    try {
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            
            if (is_dir($path)) {
                // Bỏ qua thư mục bị loại trừ
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

echo "🔄 Đang thay thế 'xanhworld' thành 'autosensor'...\n";
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

