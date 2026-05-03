<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\UserModel;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

require 'helpers.php';

class PengirimanController extends Controller
{
    function GetHeaderPengiriman()
    {
        try {

            $data = DB::SELECT("SELECT b.id, b.customer_id, b.nama_buyer, c.no_bal FROM public.trn_kirim_buyer_header b JOIN public.trn_kirim_buyer_bal c ON(b.id=c.header_id)
            WHERE (c.no_bal IS NOT NULL OR c.no_bal<>'') AND b.status=1
            GROUP BY b.id, b.customer_id, b.nama_buyer, c.no_bal
            ");

            if (count($data) < 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data Header kosong!',
                    'data' => [],
                ], 200);
            } else {
                if ($data) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Berhasil menampilkan data Header!',
                        'data' => $data,
                    ], 200);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Gagal mengambil data Header! ',
                        'data' => [],
                    ], 200);
                }
            }
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data WO! ' . $th->getMessage(),
                'data' => [],
            ], 200);
        }
    }

   function GetDetailHeaderPengiriman(Request $request)
{
    try {
        $resultData = [];
        $no_bal = $request->json()->get('no_bal');

        $getData = DB::SELECT("
            SELECT b.*, c.qr_code
            FROM public.trn_kirim_buyer_item b
            JOIN public.trn_kirim_buyer_bal d ON (b.bal_id = d.id)
            LEFT JOIN public.trn_gudang_jadi c ON (b.stock_id = c.id)
            WHERE b.no_bal = '$no_bal'
        ");

        if (count($getData) > 0) {

            foreach ($getData as $item) {

                // FIX: gunakan explode, bukan substr
                $parts = explode('-', $item->qr_code);
                $type  = $parts[0] ?? '';   // INS, MKL, INS2, STK

                if ($type == 'INS') {

                    $sql = "SELECT
                                a.wo_id,
                                d.no as no_wo,
                                b.grade,
                                b.join_piece,
                                b.qty,
                                b.qr_code,
                                c.id,
                                '$no_bal' as no_bal
                            FROM public.inspecting_item b
                            INNER JOIN public.trn_inspecting a ON (b.inspecting_id = a.id)
                            JOIN public.trn_wo d ON (d.id = a.wo_id)
                            LEFT JOIN public.trn_gudang_jadi c ON (b.qr_code = c.qr_code)
                            WHERE c.qr_code = '$item->qr_code'
                            LIMIT 100";

                    $data = DB::SELECT($sql);

                }
                elseif ($type == 'INS2' || $type == 'MKL') {

                    // INS2 sama dengan MKL ? gunakan tabel MKL
                    $sql = "SELECT
                                a.wo_id,
                                d.no as no_wo,
                                b.grade,
                                b.join_piece,
                                b.qty,
                                b.qr_code,
                                c.id,
                                '$no_bal' as no_bal
                            FROM public.inspecting_mkl_bj_items b
                            INNER JOIN public.inspecting_mkl_bj a ON (b.inspecting_id = a.id)
                            JOIN public.trn_wo d ON (d.id = a.wo_id)
                            LEFT JOIN public.trn_gudang_jadi c ON (b.qr_code = c.qr_code)
                            WHERE c.qr_code = '$item->qr_code'
                            LIMIT 100";

                    $data = DB::SELECT($sql);

                }
                elseif ($type == 'STK') {

                    $sql = "SELECT a.*, d.no as no_wo
                            FROM public.trn_gudang_jadi a
                            JOIN public.trn_wo d ON (d.id = a.wo_id)
                            WHERE a.qr_code = '$item->qr_code'
                            LIMIT 100";

                    $data = DB::SELECT($sql);
                }

                // masukkan data jika ada
                if (isset($data) && count($data) > 0) {
                    $resultData[] = $data[0];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Berhasil menampilkan data detail Header!',
                'data' => $resultData,
            ]);

        } else {

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail Header!',
                'data' => [],
            ]);
        }

    } catch (\Throwable $th) {

        return response()->json([
            'success' => false,
            'message' => 'Gagal mengambil data detail header! ' . $th->getMessage(),
            'data' => [],
        ]);
    }
}


    function GetCustomerPengiriman()
    {
        try {

            $data = DB::SELECT("SELECT id, customer_id, nama_buyer, alamat_buyer FROM public.trn_kirim_buyer_header WHERE status=1
            ");

            if (count($data) < 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data customer kosong!',
                    'data' => [],
                ], 200);
            } else {
                if ($data) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Berhasil menampilkan data Customer!',
                        'data' => $data,
                    ], 200);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Gagal mengambil data Customer! ',
                        'data' => [],
                    ], 200);
                }
            }
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data Customer! ' . $th->getMessage(),
                'data' => [],
            ], 200);
        }
    }

    function GetWOPengiriman(Request $request)
    {
        try {
            $header_id = $request->json()->get('header_id');

            $data = DB::SELECT("SELECT a.id, a.wo_id, a.nama_kain_alias, c.no_bal, d.no as no_wo FROM public.trn_kirim_buyer a
                                LEFT JOIN public.trn_kirim_buyer_bal c ON(a.id=c.trn_kirim_buyer_id)
                                JOIN public.trn_wo d ON(a.wo_id=d.id)
                                WHERE a.header_id=$header_id AND (c.no_bal IS NULL OR c.no_bal='')
                                GROUP BY a.id, a.wo_id, a.nama_kain_alias, c.no_bal
            ");

            if (count($data) < 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data WO kosong!',
                    'data' => [],
                ], 200);
            } else {
                if ($data) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Berhasil menampilkan data WO!',
                        'data' => $data,
                    ], 200);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Gagal mengambil data WO! ',
                        'data' => [],
                    ], 200);
                }
            }
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data WO! ' . $th->getMessage(),
                'data' => [],
            ], 200);
        }
    }

    function InsertPengiriman(Request $request)
    {
        try {
            DB::beginTransaction();

            $header_id = $request->json()->get('header_id');
            $no_bal_header = $request->json()->get('no_bal');
            $details = $request->json()->get('details');

            $ssql="INSERT INTO
                public.trn_kirim_buyer_bal
            (
                header_id,
                no_bal
            )
            VALUES (
                $header_id,
                '$no_bal_header'
            ) RETURNING id";

            $insertDetail = DB::SELECT($ssql);

            $balId = $insertDetail[0]->id;

            if ($insertDetail) {
                if (Count($details) > 0) {
                    foreach ($details as $det) {

                        $no_bal = $det['no_bal'];
                        $stock_id = $det['stock_id'];

                        $sql="UPDATE
                                public.trn_kirim_buyer_item
                            SET
                                no_bal = '$no_bal',
                                bal_id = $balId
                            WHERE
                                stock_id = $stock_id";

                        $updateDetail = DB::UPDATE($sql);

                        if (!$updateDetail) {
                            DB::rollBack();
                            return response()->json([
                                'success' => false,
                                'message' => 'Update Bal Gagal!',
                                'data' => [],
                            ], 200);
                        }

                    }

                    DB::commit();

                    $dummy = DB::SELECT("SELECT a.* FROM public.trn_kirim_buyer_item a JOIN public.trn_kirim_buyer b ON(a.kirim_buyer_id=b.id) WHERE b.header_id=$header_id
                    AND (a.no_bal IS NOT NULL OR a.no_bal<>'')");

                    if (count($dummy) > 0) {
                        return response()->json([
                            'success' => true,
                            'message' => 'Berhasil insert data!',
                            'data' => $dummy,
                        ], 200);
                    } else {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Gagal insert!',
                            'data' => [],
                        ], 200);
                    }
                } else {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Insert Detail Gagal!',
                        'data' => [],
                    ], 200);
                }
            } else {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Insert Detail Gagal!',
                    'data' => [],
                ], 200);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal insert Data! ' . $th->getMessage(),
                'data' => [],
            ], 200);
        }
    }


    public function getQrScanItemWO(Request $request)
{
    try {

        $qr_code = $request->json()->get('qr_code');
        $header_id = $request->json()->get('header_id');

        // FIX: gunakan explode
        $parts = explode('-', $qr_code);
        $type  = $parts[0] ?? '';    // INS, MKL, INS2, STK
        $id_new = $parts[2] ?? null; // ID ITEM
        $header_from_qr = $parts[1] ?? null;

        if (!$type) {
            return response()->json([
                'success' => false,
                'message' => 'Format QR code tidak valid!',
                'data' => []
            ]);
        }

        $data = [];

        /** ------------------------------------------
         *               TYPE = INS
         * -------------------------------------------*/
        if ($type == "INS") {

            $sql = "SELECT
                    a.id AS trn_inspecting_id,
                    a.sc_id,
                    a.sc_greige_id,
                    a.mo_id,
                    a.wo_id,
                    d.no as no_wo,
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
                FROM public.inspecting_item b
                INNER JOIN public.trn_inspecting a ON b.inspecting_id = a.id
                JOIN public.trn_wo d ON d.id = a.wo_id
                LEFT JOIN public.trn_gudang_jadi c ON b.qr_code = c.qr_code
                WHERE b.id = $id_new";

            $data = DB::SELECT($sql);
        }

        /** ------------------------------------------
         *     TYPE = MKL atau INS2 (LOGIKA SAMA)
         * -------------------------------------------*/
        if ($type == "MKL" || $type == "INS2") {

            $sql = "SELECT
                    a.id AS trn_inspecting_id,
                    a.wo_id,
                    d.no as no_wo,
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
                FROM public.inspecting_mkl_bj_items b
                INNER JOIN public.inspecting_mkl_bj a ON b.inspecting_id = a.id
                JOIN public.trn_wo d ON d.id = a.wo_id
                LEFT JOIN public.trn_gudang_jadi c ON b.qr_code = c.qr_code
                WHERE b.id = $id_new";

            $data = DB::SELECT($sql);
        }

        /** ------------------------------------------
         *                 TYPE = STK
         * -------------------------------------------*/
        if ($type == "STK") {

            $data = DB::SELECT("
                SELECT a.*, d.no as no_wo
                FROM trn_gudang_jadi a
                JOIN public.trn_wo d ON d.id = a.wo_id
                WHERE a.qr_code = '$qr_code' OR a.id = $header_from_qr
            ");
        }

        /** ------------------------------------------
         *          VALIDASI & RETURN DATA
         * -------------------------------------------*/
        if (count($data) < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Data item kosong!',
                'data' => [],
            ]);
        }

        // VALIDASI STOCK SUDAH ADA DI HEADER?
        $stock_id = $data[0]->id_trn_gudang_jadi ?? null;

        $cekData = DB::SELECT("
            SELECT a.*
            FROM public.trn_kirim_buyer_item a
            JOIN public.trn_kirim_buyer b ON a.kirim_buyer_id = b.id
            WHERE b.header_id = $header_id
            AND a.stock_id = $stock_id
        ");

        if (count($cekData) > 0) {

            // CEK apakah no_bal kosong
            $cekBal = DB::SELECT("
                SELECT * FROM public.trn_kirim_buyer_item
                WHERE stock_id = $stock_id
                AND (no_bal IS NULL OR no_bal = '')
            ");

            if (count($cekBal) > 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'Berhasil menampilkan data item!',
                    'data' => $data,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Item sudah masuk ke BAL lain! ' . $qr_code,
                'data' => [],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Item tidak sesuai dengan header! ' . $qr_code,
            'data' => [],
        ]);

    } catch (\Throwable $th) {

        return response()->json([
            'success' => false,
            'message' => 'Gagal! ' . $th->getMessage(),
            'data' => [],
        ]);
    }
}

}