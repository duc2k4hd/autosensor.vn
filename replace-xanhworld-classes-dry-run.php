<?php

/**
 * Script DRY-RUN: Chỉ hiển thị các file sẽ được thay đổi, không thực sự thay đổi
 * Chạy: php replace-xanhworld-classes-dry-run.php
 */

$baseDir = __DIR__;
$excludedDirs = ['vendor', 'node_modules', '.git', 'storage', 'bootstrap/cache'];
$extensions = ['.php', '.blade.php', '.js', '.css', '.html', '.vue', '.ts', '.scss', '.sass'];
$filesFound = [];
$totalMatches = 0;

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
 * Tìm các pattern xanhworld trong file
 */
function findMatches($filePath) {
    global $totalMatches;
    
    try {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return [];
        }
        
        $matches = [];
        $lines = explode("\n", $content);
        
        foreach ($lines as $lineNum => $line) {
            if (stripos($line, 'xanhworld') !== false) {
                // Tìm các vị trí cụ thể
                preg_match_all('/xanhworld[_\-][a-zA-Z0-9_-]+/i', $line, $foundMatches, PREG_OFFSET_CAPTURE);
                
                if (!empty($foundMatches[0])) {
                    foreach ($foundMatches[0] as $match) {
                        $matches[] = [
                            'line' => $lineNum + 1,
                            'match' => $match[0],
                            'context' => trim($line)
                        ];
                        $totalMatches++;
                    }
                }
            }
        }
        
        return $matches;
    } catch (Exception $e) {
        return [];
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
        // Ignore
    }
    
    return $files;
}

// Bắt đầu quét
echo "🔍 Đang quét các file (DRY-RUN mode)...\n";
$files = scanDirectory($baseDir, $excludedDirs, $extensions);
echo "📁 Tìm thấy " . count($files) . " file cần kiểm tra\n\n";

echo "🔎 Đang tìm các class 'xanhworld'...\n\n";

foreach ($files as $file) {
    $matches = findMatches($file);
    if (!empty($matches)) {
        $relativePath = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $file);
        $filesFound[$relativePath] = $matches;
    }
}

if (empty($filesFound)) {
    echo "✅ Không tìm thấy class 'xanhworld' nào!\n";
} else {
    echo "═══════════════════════════════════════════════════════\n";
    echo "📋 CÁC FILE SẼ ĐƯỢC THAY ĐỔI:\n";
    echo "═══════════════════════════════════════════════════════\n\n";
    
    foreach ($filesFound as $file => $matches) {
        echo "📄 $file\n";
        foreach ($matches as $match) {
            echo "   Dòng {$match['line']}: {$match['match']}\n";
            echo "   Context: {$match['context']}\n";
        }
        echo "\n";
    }
    
    echo "═══════════════════════════════════════════════════════\n";
    echo "📊 TỔNG KẾT:\n";
    echo "═══════════════════════════════════════════════════════\n";
    echo "📝 Số file sẽ được thay đổi: " . count($filesFound) . "\n";
    echo "🔄 Tổng số pattern sẽ được thay thế: $totalMatches\n";
    echo "\n💡 Để thực hiện thay đổi, chạy: php replace-autosensor-classes.php\n";
}

