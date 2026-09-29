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

            // Cari semua file .log di storage/logs/
            $files = glob($logPath . '/*.log');
            if (empty($files)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tidak ada file log di folder storage/logs',
                    'data' => []
                ]);
            }

            // Urutkan file berdasarkan waktu perubahan terbaru
            usort($files, function ($a, $b) {
                return filemtime($b) - filemtime($a);
            });

            $latestFile = $files[0];
            $fileName = basename($latestFile);

            // Baca baris file log
            $lines = file($latestFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (empty($lines)) {
                return response()->json([
                    'success' => true,
                    'message' => 'File ' . $fileName . ' masih kosong',
                    'data' => []
                ]);
            }

            // Ambil maksimal 300 baris terakhir
            $lines = array_slice($lines, -300);

            $parsedLogs = [];
            $currentLog = null;

            foreach ($lines as $line) {
                // Pola fleksibel: mencocokkan [YYYY-MM-DD ...] LEVEL: pesan
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
                    // Fallback jika tidak ada level ERROR/INFO
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
                    // Jika lanjutan dari stack trace error sebelumnya
                    if ($currentLog !== null && strlen($currentLog['message']) < 1500) {
                        $currentLog['message'] .= "\n" . trim($line);
                    }
                }
            }

            if ($currentLog !== null) {
                $parsedLogs[] = $currentLog;
            }

            // Urutkan dari log paling baru di atas
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