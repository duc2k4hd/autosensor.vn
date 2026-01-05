<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class BackupController extends Controller
{
    /**
     * Danh sách backups
     */
    public function index()
    {
        $backupDir = storage_path('app/backups');
        $backups = [];

        if (is_dir($backupDir)) {
            $files = glob($backupDir.'/backup_*.sql');
            foreach ($files as $file) {
                $backups[] = [
                    'name' => basename($file),
                    'path' => $file,
                    'size' => filesize($file),
                    'created_at' => date('Y-m-d H:i:s', filemtime($file)),
                ];
            }

            // Sort by created_at desc
            usort($backups, function ($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
        }

        return view('admins.backups.index', compact('backups'));
    }

    /**
     * Tạo backup mới
     */
    public function store(Request $request)
    {
        try {
            Artisan::call('db:backup');
            $output = Artisan::output();

            return back()->with('success', 'Đã tạo backup thành công. '.$output);
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi khi tạo backup: '.$e->getMessage());
        }
    }

    /**
     * Download backup file
     */
    public function download(string $fileName)
    {
        $filePath = storage_path('app/backups/'.$fileName);

        if (! file_exists($filePath)) {
            return back()->with('error', 'File backup không tồn tại.');
        }

        return response()->download($filePath);
    }

    /**
     * Xóa backup
     */
    public function destroy(string $fileName)
    {
        $filePath = storage_path('app/backups/'.$fileName);

        if (! file_exists($filePath)) {
            return back()->with('error', 'File backup không tồn tại.');
        }

        unlink($filePath);

        return back()->with('success', 'Đã xóa backup.');
    }

    /**
     * Restore database
     */
    public function restore(Request $request, string $fileName)
    {
        $request->validate([
            'confirm' => ['required', 'accepted'],
        ]);

        $filePath = storage_path('app/backups/'.$fileName);

        if (! file_exists($filePath)) {
            return back()->with('error', 'File backup không tồn tại.');
        }

        try {
            Artisan::call('db:restore', [
                'file' => $filePath,
                '--force' => true,
            ]);

            return back()->with('success', 'Đã restore database thành công.');
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi khi restore: '.$e->getMessage());
        }
    }
}
