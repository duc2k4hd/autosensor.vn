<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Services\PostImportExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PostImportController extends Controller
{
    public function index()
    {
        return view('admins.posts.import');
    }

    public function exportTemplate(PostImportExportService $service)
    {
        $fileName = 'posts_template_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $path = storage_path('app/' . $fileName);
        $service->exportTemplate($path);

        return response()->download($path)->deleteFileAfterSend(true);
    }

    public function exportAll(PostImportExportService $service)
    {
        $fileName = 'posts_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $path = storage_path('app/' . $fileName);
        $service->exportAll($path);

        return response()->download($path)->deleteFileAfterSend(true);
    }

    public function import(Request $request, PostImportExportService $service)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:51200', // max 50MB
        ]);

        $file = $request->file('excel_file');

        try {
            $report = $service->importFromFile($file->getRealPath());

            // Write errors to log file if any
            $logFile = null;
            if (! empty($report['errors'])) {
                $logDir = storage_path('logs/imports');
                if (! is_dir($logDir)) {
                    @mkdir($logDir, 0755, true);
                }
                $logFile = 'posts_import_' . time() . '.log';
                $logPath = $logDir . '/' . $logFile;
                file_put_contents($logPath, implode("\n", $report['errors']));
            }

            $message = "Import hoàn thành. Đã xử lý: {$report['processed']}. Tạo mới: {$report['created']}. Cập nhật: {$report['updated']}. Bỏ qua: {$report['skipped']}";
            if ($logFile) {
                return redirect()->back()->with('success', $message)->with('log_file', $logFile);
            }

            return redirect()->back()->with('success', $message);

        } catch (\Throwable $e) {
            Log::error('Post import error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Lỗi khi import: ' . $e->getMessage());
        }
    }
}
