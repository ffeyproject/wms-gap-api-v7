<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\UserModel;
use Illuminate\Support\Facades\Auth;

class QrController extends Controller
{

    public function getQrScanBulk(Request $request){
    try {

        $qr_code = $request->json()->get('qr_code');

        // FIX UTAMA: gunakan explode, bukan substr()
        $parts = explode('-', $qr_code);
        $type  = $parts[0] ?? '';      // INS, MKL, INS2, STK
        $id_new = $parts[1] ?? null;   // ID HEADER
        $item_id = $parts[2] ?? null;  // ID ITEM (tidak dipakai di bulk, tapi tetap aman)

        if (!$type || !$id_new) {
            return response()->json([
                'success' => false,
                'message' => 'Format QR code tidak valid!',
                'data' => [],
            ]);
        }

        $data = [];

        if ($type == "INS") {

            $sql = "SELECT 
                a.id AS trn_inspecting_id,
                a.sc_id,
                a.sc_greige_id,
                a.mo_id,
                a.wo_id,
                a.kartu_process_dyeing_id,
                a.jenis_process,
                a.no_urut,
                a.no,
                a.date,
                a.tanggal_inspeksi,
                a.no_lot,
                a.kombinasi,
                a.note,
                a.status,
                a.unit,
                a.created_at,
                a.created_by,
                a.updated_at,
                a.updated_by,
                a.approved_at,
                a.approved_by,
                a.approval_reject_note,
                a.delivered_at,
                a.delivered_by,
                a.delivery_reject_note,
                a.kartu_process_printing_id,
                a.memo_repair_id,
                a.k3l_code,
                b.id,
                b.inspecting_id,
                b.grade,
                b.join_piece,
                b.qty,
                b.note,
                b.is_head,
                b.qty_sum,
                b.qty_count,
                b.qr_code,
                b.qr_code_desc,
                b.qr_print_at,
                c.id as id_trn_gudang_jadi,
                c.locs_code
            FROM public.inspecting_item b
            INNER JOIN public.trn_inspecting a ON b.inspecting_id = a.id
            LEFT JOIN public.trn_gudang_jadi c ON b.qr_code = c.qr_code
            WHERE b.inspecting_id = $id_new";

            $data = DB::select($sql);
        }

        if ($type == "MKL" || $type == "INS2") {

            $sql = "SELECT 
                a.id AS trn_inspecting_id,
                a.wo_id,
                a.wo_color_id,
                a.jenis as jenis_process,
                a.no_urut,
                a.no,
                a.tgl_kirim as date,
                a.tgl_inspeksi as tanggal_inspeksi,
                a.no_lot,
                a.status,
                a.satuan as unit,
                a.created_at,
                a.created_by,
                a.updated_at,
                a.updated_by,
                a.delivered_at,
                a.delivered_by,
                a.delivery_reject_note,
                a.k3l_code,
                b.id,
                b.inspecting_id,
                b.grade,
                b.join_piece,
                b.qty,
                b.note,
                b.is_head,
                b.qty_sum,
                b.qty_count,
                b.qr_code,
                b.qr_code_desc,
                b.qr_print_at,
                c.id as id_trn_gudang_jadi,
                c.locs_code
            FROM public.inspecting_mkl_bj_items b
            INNER JOIN public.inspecting_mkl_bj a ON b.inspecting_id = a.id
            LEFT JOIN public.trn_gudang_jadi c ON b.qr_code = c.qr_code
            WHERE b.inspecting_id = $id_new";

            $data = DB::select($sql);
        }

        if ($type == "STK") {
            $data = DB::select("SELECT * FROM trn_gudang_jadi WHERE qr_code='$qr_code' OR id=$id_new");
        }

        if (count($data) < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Data item kosong!',
                'data' => [],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Berhasil menampilkan data item!",
            'data' => $data,
        ]);

    } catch (\Throwable $th) {
        return response()->json([
            'success' => false,
            'message' => $th->getMessage(),
            'data' => [],
        ]);
    }
}



    public function getQrScan(Request $request)
{
    try {
        $qr_code = $request->json()->get('qr_code');

        if (!$qr_code) {
            return response()->json([
                'success' => false,
                'message' => 'qr_code tidak dikirim!',
                'data' => [],
            ], 200);
        }

        $parts  = explode('-', $qr_code);
        $prefix = $parts[0] ?? '';
        $inspect_id = $parts[1] ?? null;
        $item_id    = $parts[2] ?? null;

        $data = DB::select("SELECT CURRENT_DATE");

        if ($prefix == "INS") {

            $sql = "SELECT 
                a.id AS trn_inspecting_id,
                a.sc_id,
                a.sc_greige_id,
                a.mo_id,
                a.wo_id,
                a.kartu_process_dyeing_id,
                a.jenis_process,
                a.no_urut,
                a.no,
                a.date,
                a.tanggal_inspeksi,
                a.no_lot,
                a.kombinasi,
                a.note,
                a.status,
                a.unit,
                a.created_at,
                a.created_by,
                a.updated_at,
                a.updated_by,
                a.approved_at,
                a.approved_by,
                a.approval_reject_note,
                a.delivered_at,
                a.delivered_by,
                a.delivery_reject_note,
                a.kartu_process_printing_id,
                a.memo_repair_id,
                a.k3l_code,
                b.id,
                b.inspecting_id,
                b.grade,
                b.join_piece,
                c.qty,
                b.note,
                b.is_head,
                b.qty_sum,
                b.qty_count,
                b.qr_code,
                b.qr_code_desc,
                b.qr_print_at,
                c.id as id_trn_gudang_jadi,
                c.locs_code
              FROM
                public.inspecting_item b
                INNER JOIN public.trn_inspecting a ON (b.inspecting_id = a.id)
                LEFT OUTER JOIN public.trn_gudang_jadi c ON (b.qr_code = c.qr_code)
              WHERE
                b.id = $item_id";
            $data = DB::select($sql);
        }

        if ($prefix == "MKL" || $prefix == "INS2") {

            $sql = "SELECT 
                a.id AS trn_inspecting_id,
                a.wo_id,
                a.wo_color_id,
                a.jenis as jenis_process,
                a.no_urut,
                a.no,
                a.tgl_kirim as date,
                a.tgl_inspeksi as tanggal_inspeksi,
                a.no_lot,
                a.status,
                a.satuan as unit,
                a.created_at,
                a.created_by,
                a.updated_at,
                a.updated_by,
                a.delivered_at,
                a.delivered_by,
                a.delivery_reject_note,
                a.k3l_code,
                b.id,
                b.inspecting_id,
                b.grade,
                b.join_piece,
                c.qty,
                b.note,
                b.is_head,
                b.qty_sum,
                b.qty_count,
                b.qr_code,
                b.qr_code_desc,
                b.qr_print_at,
                c.id as id_trn_gudang_jadi,
                c.locs_code
              FROM
                public.inspecting_mkl_bj_items b
                INNER JOIN public.inspecting_mkl_bj a ON (b.inspecting_id = a.id)
                LEFT OUTER JOIN public.trn_gudang_jadi c ON (b.qr_code = c.qr_code)
              WHERE
                b.id = $item_id";
            $data = DB::select($sql);
        }

        if ($prefix == "STK") {
            $id = substr($qr_code, 4);
            $data = DB::select("SELECT * FROM trn_gudang_jadi WHERE qr_code = '$qr_code' OR id = $id");
        }

        if (count($data) < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Data item kosong!',
                'data' => [],
            ], 200);
        }

        return response()->json([
            'success' => true,
            'message' => 'Berhasil menampilkan data item!',
            'data' => $data,
        ], 200);

    } catch (\Throwable $th) {
        return response()->json([
            'success' => false,
            'message' => 'Login Failed! '.$th->getMessage(),
            'data' => [],
        ], 200);
    }
}

    public function getQrScanInspecting(Request $request)
{
    try {

        // Ambil QR
        $qr_code = $request->json()->get('qr_code');

        if (!$qr_code) {
            return response()->json([
                'success' => false,
                'message' => 'qr_code tidak dikirim!',
                'data' => [],
            ], 200);
        }

        // Pecah QR: PREFIX-IDHEADER-IDITEM
        $parts  = explode('-', $qr_code);
        $prefix = $parts[0] ?? '';
        $inspect_id = $parts[1] ?? null;
        $item_id    = $parts[2] ?? null;

        // Jika prefix = INS2
        if ($prefix === "INS2") {
            $sql = "
                SELECT 
                    a.id AS trn_inspecting_id,
                    a.wo_id,
                    a.wo_color_id,
                    a.jenis as jenis_process,
                    a.no_urut,
                    a.no,
                    a.tgl_kirim as date,
                    a.tgl_inspeksi as tanggal_inspeksi,
                    a.no_lot,
                    a.status,
                    a.satuan as unit,
                    a.created_at,
                    a.created_by,
                    a.updated_at,
                    a.updated_by,
                    a.delivered_at,
                    a.delivered_by,
                    a.delivery_reject_note,
                    a.k3l_code,
                    b.id,
                    b.inspecting_id,
                    b.grade,
                    b.join_piece,
                    b.qty,
                    b.note,
                    b.is_head,
                    b.qty_sum,
                    b.qty_count,
                    b.qr_code,
                    b.qr_code_desc,
                    b.qr_print_at,
                    c.id as id_trn_gudang_jadi,
                    c.locs_code
                FROM public.inspecting_mkl_bj_items b
                INNER JOIN public.inspecting_mkl_bj a 
                    ON b.inspecting_id = a.id
                LEFT JOIN public.trn_gudang_jadi c 
                    ON b.qr_code = c.qr_code
                WHERE b.id = $item_id
            ";
            
            $data = DB::select($sql);
        }

        // Prefix = MKL (lama, tapi structure sama seperti INS2)
        else if ($prefix === "MKL") {
            $sql = "
                SELECT 
                    a.id AS trn_inspecting_id,
                    a.wo_id,
                    a.wo_color_id,
                    a.jenis as jenis_process,
                    a.no_urut,
                    a.no,
                    a.tgl_kirim as date,
                    a.tgl_inspeksi as tanggal_inspeksi,
                    a.no_lot,
                    a.status,
                    a.satuan as unit,
                    a.created_at,
                    a.created_by,
                    a.updated_at,
                    a.updated_by,
                    a.delivered_at,
                    a.delivered_by,
                    a.delivery_reject_note,
                    a.k3l_code,
                    b.id,
                    b.inspecting_id,
                    b.grade,
                    b.join_piece,
                    b.qty,
                    b.note,
                    b.is_head,
                    b.qty_sum,
                    b.qty_count,
                    b.qr_code,
                    b.qr_code_desc,
                    b.qr_print_at,
                    c.id as id_trn_gudang_jadi,
                    c.locs_code
                FROM public.inspecting_mkl_bj_items b
                INNER JOIN public.inspecting_mkl_bj a 
                    ON b.inspecting_id = a.id
                LEFT JOIN public.trn_gudang_jadi c 
                    ON b.qr_code = c.qr_code
                WHERE b.id = $item_id
            ";
            
            $data = DB::select($sql);
        }

        // Prefix = INS
        else if ($prefix === "INS") {

            $sql = "
                SELECT 
                    a.id AS trn_inspecting_id,
                    a.sc_id,
                    a.sc_greige_id,
                    a.mo_id,
                    a.wo_id,
                    a.kartu_process_dyeing_id,
                    a.jenis_process,
                    a.no_urut,
                    a.no,
                    a.date,
                    a.tanggal_inspeksi,
                    a.no_lot,
                    a.kombinasi,
                    a.note,
                    a.status,
                    a.unit,
                    a.created_at,
                    a.created_by,
                    a.updated_at,
                    a.updated_by,
                    a.approved_at,
                    a.approved_by,
                    a.approval_reject_note,
                    a.delivered_at,
                    a.delivered_by,
                    a.delivery_reject_note,
                    a.kartu_process_printing_id,
                    a.memo_repair_id,
                    a.k3l_code,
                    b.id,
                    b.inspecting_id,
                    b.grade,
                    b.join_piece,
                    b.qty,
                    b.note,
                    b.is_head,
                    b.qty_sum,
                    b.qty_count,
                    b.qr_code,
                    b.qr_code_desc,
                    b.qr_print_at,
                    c.id as id_trn_gudang_jadi,
                    c.locs_code
                FROM public.inspecting_item b
                INNER JOIN public.trn_inspecting a 
                    ON b.inspecting_id = a.id
                LEFT JOIN public.trn_gudang_jadi c 
                    ON b.qr_code = c.qr_code
                WHERE b.id = $item_id
            ";

            $data = DB::select($sql);
        }

        // Prefix = STK
        else if ($prefix === "STK") {
            $stock_id = $parts[1] ?? 0;
            $data = DB::select("SELECT * FROM trn_gudang_jadi WHERE qr_code = '$qr_code' OR id = $stock_id");
        }

        // Tidak ada prefix yg valid
        else {
            return response()->json([
                'success' => false,
                'message' => "Prefix QR tidak dikenal ($prefix)",
                'data' => [],
            ]);
        }

        // Jika tidak ada data
        if (count($data) < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Data item kosong!',
                'data' => [],
            ], 200);
        }

        return response()->json([
            'success' => true,
            'message' => 'Berhasil menampilkan data item!',
            'data' => $data,
        ], 200);

    } catch (\Throwable $th) {
        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan: '.$th->getMessage(),
            'data' => [],
        ], 200);
    }
}


}
