<?php
include 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$inspecting_with_unreceived = DB::select("
    SELECT ins.id, ins.no, ins.status,
    (SELECT count(*) FROM inspecting_item WHERE inspecting_id = ins.id) as total_items,
    (SELECT count(*) FROM inspecting_item item 
     WHERE item.inspecting_id = ins.id 
     AND EXISTS (SELECT 1 FROM trn_gudang_jadi gj WHERE gj.trans_from = 'INS' AND gj.id_from = item.id)) as received_items
    FROM trn_inspecting ins
    WHERE ins.status IN (3, 4)
    ORDER BY ins.id DESC
    LIMIT 10
");

print_r($inspecting_with_unreceived);

$mklbj_with_unreceived = DB::select("
    SELECT mklbj.id, mklbj.no, mklbj.status,
    (SELECT count(*) FROM inspecting_mkl_bj_items WHERE inspecting_id = mklbj.id) as total_items,
    (SELECT count(*) FROM inspecting_mkl_bj_items item 
     WHERE item.inspecting_id = mklbj.id 
     AND EXISTS (SELECT 1 FROM trn_gudang_jadi gj WHERE gj.trans_from = 'MKL' AND gj.id_from = item.id)) as received_items
    FROM inspecting_mkl_bj mklbj
    WHERE mklbj.status IN (2, 3)
    ORDER BY mklbj.id DESC
    LIMIT 10
");

print_r($mklbj_with_unreceived);
