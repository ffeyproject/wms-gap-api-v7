<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;

class LogViewerController extends BaseController
{
    public function index(Request $request)
    {
        try {
            $logPath = storage_path('logs');
            if (!is_dir($logPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Folder log tidak ditemukan di: ' . $logPath,
                    'data' => []
                ]);
            }

            $files = glob($logPath . '/*.log');
            if (empty($files)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tidak ada file log di folder storage/logs',
                    'data' => []
                ]);
            }

            usort($files, function ($a, $b) {
                return filemtime($b) - filemtime($a);
            });

            $latestFile = $files[0];
            $fileName = basename($latestFile);

            $lines = file($latestFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (empty($lines)) {
                return response()->json([
                    'success' => true,
                    'message' => 'File ' . $fileName . ' masih kosong',
                    'data' => []
                ]);
            }

            $lines = array_slice($lines, -300);

            $parsedLogs = [];
            $currentLog = null;

            foreach ($lines as $line) {
                if (preg_match('/^\[(\d{4}-\d{2}-\d{2}[^\]]*)\]\s*(?:([a-zA-Z0-9_-]+)\.)?([A-Z]+):\s*(.*)$/', $line, $matches)) {
                    if ($currentLog !== null) {
                        $parsedLogs[] = $currentLog;
                    }
                    $currentLog = [
                        'timestamp' => trim($matches[1]),
                        'env'       => !empty($matches[2]) ? trim($matches[2]) : 'lumen',
                        'level'     => strtoupper(trim($matches[3])),
                        'message'   => trim($matches[4]),
                        'file'      => $fileName
                    ];
                } elseif (preg_match('/^\[(\d{4}-\d{2}-\d{2}[^\]]*)\]\s*(.*)$/', $line, $matches)) {
                    if ($currentLog !== null) {
                        $parsedLogs[] = $currentLog;
                    }
                    $currentLog = [
                        'timestamp' => trim($matches[1]),
                        'env'       => 'lumen',
                        'level'     => 'INFO',
                        'message'   => trim($matches[2]),
                        'file'      => $fileName
                    ];
                } else {
                    if ($currentLog !== null && strlen($currentLog['message']) < 1500) {
                        $currentLog['message'] .= "\n" . trim($line);
                    }
                }
            }

            if ($currentLog !== null) {
                $parsedLogs[] = $currentLog;
            }

            $parsedLogs = array_reverse($parsedLogs);

            return response()->json([
                'success'    => true,
                'file_read'  => $fileName,
                'total_logs' => count($parsedLogs),
                'data'       => $parsedLogs
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membaca log: ' . $e->getMessage(),
                'data'    => []
            ], 500);
        }
    }
}