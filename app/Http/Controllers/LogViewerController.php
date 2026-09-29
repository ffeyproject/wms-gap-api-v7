<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class LogViewerController extends Controller
{
    /**
     * Endpoint untuk melihat log error backend
     * Web UI: http://apiwms.produksionline.xyz/logs
     * JSON API: http://apiwms.produksionline.xyz/v1/system/logs
     */
    public function index(Request $request)
    {
        $logPath = storage_path('logs');
        $files = glob($logPath . '/*.log');
        
        if (empty($files)) {
            $files = [$logPath . '/laravel.log'];
        }
        
        // Urutkan file log terbaru
        rsort($files);
        $fileNames = array_map('basename', $files);

        $selectedFile = $request->get('file', !empty($fileNames) ? $fileNames[0] : 'laravel.log');
        $filePath = $logPath . '/' . basename($selectedFile);

        // Aksi Download
        if ($request->has('download') && file_exists($filePath)) {
            return response()->download($filePath);
        }

        // Aksi Clear / Kosongkan Log
        if ($request->has('clear') && file_exists($filePath)) {
            file_put_contents($filePath, '');
            return redirect('/logs?file=' . urlencode($selectedFile));
        }

        $logContent = '';
        $fileSize = 0;
        if (file_exists($filePath)) {
            $fileSize = round(filesize($filePath) / 1024, 2); // KB
            // Baca 500 baris terakhir agar cepat & ringan
            $lines = file($filePath);
            if (!empty($lines)) {
                $lines = array_slice($lines, -500);
                $logContent = implode('', $lines);
            }
        }

        // Parsing log per entri [YYYY-MM-DD HH:mm:ss]
        $pattern = '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s+([a-zA-Z0-9_-]+)\.([a-zA-Z]+):\s+(.*?)(?=\n\[\d{4}-\d{2}-\d{2}|\Z)/s';
        preg_match_all($pattern, $logContent, $matches, PREG_SET_ORDER);

        $logs = [];
        foreach (array_reverse($matches) as $m) {
            $logs[] = [
                'timestamp' => $m[1],
                'env'       => $m[2],
                'level'     => strtoupper($m[3]),
                'message'   => trim($m[4]),
            ];
        }

        // Response format JSON jika diakses dari mobile / API
        if ($request->wantsJson() || $request->has('json') || strpos($request->path(), 'v1/system/logs') !== false) {
            return response()->json([
                'success'   => true,
                'file'      => $selectedFile,
                'file_size' => $fileSize . ' KB',
                'total'     => count($logs),
                'data'      => $logs
            ], 200);
        }

        // Render Web UI (Self-contained HTML Responsive)
        $html = $this->renderHtml($fileNames, $selectedFile, $fileSize, $logs);
        return response($html, 200)->header('Content-Type', 'text/html');
    }

    private function renderHtml($fileNames, $selectedFile, $fileSize, $logs)
    {
        $optionsHtml = '';
        foreach ($fileNames as $fn) {
            $sel = ($fn === $selectedFile) ? 'selected' : '';
            $optionsHtml .= "<option value=\"$fn\" $sel>$fn</option>";
        }

        $logRowsHtml = '';
        if (empty($logs)) {
            $logRowsHtml = '<div class="alert alert-success text-center my-4 py-4">🎉 File log ini bersih! Tidak ada error yang tercatat.</div>';
        } else {
            foreach ($logs as $i => $log) {
                $level = $log['level'];
                $badgeClass = 'bg-danger';
                $cardBorder = 'border-danger';
                if ($level === 'WARNING') { $badgeClass = 'bg-warning text-dark'; $cardBorder = 'border-warning'; }
                elseif ($level === 'INFO') { $badgeClass = 'bg-info text-dark'; $cardBorder = 'border-info'; }
                elseif ($level === 'DEBUG') { $badgeClass = 'bg-secondary'; $cardBorder = 'border-secondary'; }

                $msg = htmlspecialchars($log['message']);
                $ts = $log['timestamp'];
                $env = $log['env'];

                $logRowsHtml .= "
                <div class=\"card mb-3 bg-dark text-light border-start border-4 $cardBorder log-card\">
                    <div class=\"card-header d-flex justify-content-between align-items-center py-2 bg-secondary bg-opacity-10\">
                        <div>
                            <span class=\"badge $badgeClass me-2\">$level</span>
                            <span class=\"badge bg-dark border border-secondary\">$env</span>
                        </div>
                        <small class=\"text-info font-monospace\">🕒 $ts</small>
                    </div>
                    <div class=\"card-body py-2 px-3\">
                        <pre class=\"mb-0 log-message\"><code>$msg</code></pre>
                    </div>
                </div>";
            }
        }

        $countTotal = count($logs);

        return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WMS GAP - API Log Viewer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #0d1117; color: #c9d1d9; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, monospace; }
        .log-message { white-space: pre-wrap; word-break: break-word; color: #ff7b72; font-size: 13px; max-height: 400px; overflow-y: auto; }
        .card { box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
        .navbar-brand { font-weight: 700; letter-spacing: 1px; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-thumb { background: #30363d; border-radius: 4px; }
    </style>
</head>
<body class="p-3 p-md-4">
    <div class="container-fluid max-w-7xl">
        <!-- Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center pb-3 mb-4 border-bottom border-secondary">
            <div>
                <h3 class="navbar-brand text-primary mb-1">📋 WMS GAP API - System Error Logs</h3>
                <p class="text-secondary small mb-0">Server: <strong class="text-light">http://apiwms.produksionline.xyz/</strong> | File: <span class="badge bg-secondary">$selectedFile ($fileSize KB)</span> | Total: <span class="badge bg-primary">$countTotal Entri</span></p>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">
                <form method="GET" class="d-flex gap-2">
                    <select name="file" class="form-select form-select-sm bg-dark text-light border-secondary" onchange="this.form.submit()">
                        $optionsHtml
                    </select>
                </form>
                <a href="/logs?file=$selectedFile&download=1" class="btn btn-sm btn-outline-info">⬇ Download</a>
                <a href="/logs?file=$selectedFile&clear=1" class="btn btn-sm btn-outline-danger" onclick="return confirm('Kosongkan file log ini?')">🗑 Bersihkan Log</a>
                <button class="btn btn-sm btn-primary" onclick="location.reload()">🔄 Refresh</button>
            </div>
        </div>

        <!-- Filter Search Bar -->
        <div class="row mb-3">
            <div class="col-12 col-md-8 mb-2 mb-md-0">
                <input type="text" id="searchInput" class="form-control bg-dark text-light border-secondary" placeholder="🔍 Cari error (misal: OpnamePcs, SQLSTATE, 42703, Undefined, dll)..." onkeyup="filterLogs()">
            </div>
            <div class="col-12 col-md-4 d-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary w-100" onclick="filterLevel('ALL')">Semua</button>
                <button class="btn btn-sm btn-outline-danger w-100" onclick="filterLevel('ERROR')">Error</button>
                <button class="btn btn-sm btn-outline-warning w-100" onclick="filterLevel('WARNING')">Warning</button>
                <button class="btn btn-sm btn-outline-info w-100" onclick="filterLevel('INFO')">Info</button>
            </div>
        </div>

        <!-- Logs Container -->
        <div id="logsContainer">
            $logRowsHtml
        </div>
    </div>

    <script>
        function filterLogs() {
            let query = document.getElementById('searchInput').value.toLowerCase();
            let cards = document.querySelectorAll('.log-card');
            cards.forEach(card => {
                let text = card.innerText.toLowerCase();
                card.style.display = text.includes(query) ? '' : 'none';
            });
        }

        function filterLevel(level) {
            let cards = document.querySelectorAll('.log-card');
            cards.forEach(card => {
                if (level === 'ALL') {
                    card.style.display = '';
                } else {
                    let badge = card.querySelector('.badge');
                    if (badge && badge.innerText.trim() === level) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                }
            });
        }
    </script>
</body>
</html>
HTML;
    }
}