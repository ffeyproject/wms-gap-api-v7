<?php
include 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Sample Inspecting Items:\n";
$items = DB::select("SELECT id, inspecting_id, grade, qty, no_urut FROM inspecting_item LIMIT 5");
print_r($items);

echo "\nSample Mklbj Items:\n";
$mklbj_items = DB::select("SELECT id, inspecting_id, grade, qty, no_urut FROM inspecting_mkl_bj_items LIMIT 5");
print_r($mklbj_items);

echo "\nCheck if any items are in trn_gudang_jadi:\n";
$gj = DB::select("SELECT id, trans_from, id_from, qty FROM trn_gudang_jadi WHERE trans_from IN ('INS', 'MKL') LIMIT 5");
print_r($gj);
