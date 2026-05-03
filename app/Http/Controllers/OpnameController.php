<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\UserModel;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class OpnameController extends Controller
{
    function GetListOpname(Request $request) {
        try {
            $start_date = $request->json()->get('start_date');
            $end_date = $request->json()->get('end_date');

            $cekDate = DB::SELECT("show datestyle");

            $dateStyle = substr($cekDate[0]->DateStyle, -3);

            try {
                $dateObj = Carbon::createFromFormat('d-m-Y', $start_date);
            } catch (\Throwable $th) {
                $dateObj = Carbon::createFromFormat('d/m/Y', $start_date);
            }

            if ($dateStyle === "MDY") {
                $formattedStartDate = $dateObj->format('m-d-Y');
            } elseif ($dateStyle === "DMY") {
                $formattedStartDate = $dateObj->format('d-m-Y');
            } elseif ($dateStyle === "YMD") {
                $formattedStartDate = $dateObj->format('Y-m-d');
            } else {
                $formattedStartDate = $dateObj->format('Y-m-d'); // Default jika tidak sesuai format yang diharapkan
            }

            try {
                $dateObj = Carbon::createFromFormat('d-m-Y', $end_date);
            } catch (\Throwable $th) {
                $dateObj = Carbon::createFromFormat('d/m/Y', $end_date);
            }

            if ($dateStyle === "MDY") {
                $formattedEndDate = $dateObj->format('m-d-Y');
            } elseif ($dateStyle === "DMY") {
                $formattedEndDate = $dateObj->format('d-m-Y');
            } elseif ($dateStyle === "YMD") {
                $formattedEndDate = $dateObj->format('Y-m-d');
            } else {
                $formattedEndDate = $dateObj->format('Y-m-d'); // Default jika tidak sesuai format yang diharapkan
            }
            
            $data = DB::SELECT("
                        SELECT * FROM wms_opname_mstr 
                        WHERE opname_start_date BETWEEN '$formattedStartDate' AND '$formattedEndDate' 
                        OR opname_end_date BETWEEN '$formattedStartDate' AND '$formattedEndDate' 
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
                'message' => 'Gagal mengambil data Customer! '.$th->getMessage(),
                'data' => [],
            ], 200);
        }
    }

    function GetOpnamedDetail(Request $request) {
        try {
            $opname_code = $request->json()->get('opname_code');
            
            $sql="SELECT 
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
                d.opnamed_locs_code as locs_code
              FROM
                public.inspecting_item b
                INNER JOIN public.trn_inspecting a ON (b.inspecting_id = a.id)
                LEFT OUTER JOIN public.trn_gudang_jadi c ON (b.qr_code = c.qr_code)
                INNER JOIN public.wms_opnamed_detail d ON (c.id = d.opnamed_id_stok)
              WHERE
                d.opnamed_opname_code = '$opname_code'";
            $data = DB::SELECT($sql);

            if (count($data) < 1) {
                return response()->json([
                    'success' => true,
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
                'message' => 'Gagal mengambil data Customer! '.$th->getMessage(),
                'data' => [],
            ], 200);
        }
    }

    function GetOpnamedNotDetected(Request $request) {
        try {
            $opname_code = $request->json()->get('opname_code');
            $locs_code = $request->json()->get('locs_code');

            $clause = "";
            if ($locs_code != "") {
                $clause = " AND c.locs_code = '$locs_code'";
            }
            
            $sql="SELECT 
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
              FROM
                public.inspecting_item b
                INNER JOIN public.trn_inspecting a ON (b.inspecting_id = a.id)
                LEFT OUTER JOIN public.trn_gudang_jadi c ON (b.qr_code = c.qr_code)
              WHERE
                c.id NOT IN (SELECT opnamed_id_stok FROM public.wms_opnamed_detail WHERE opnamed_opname_code = '$opname_code')".$clause."";
            $data = DB::SELECT($sql);

            if (count($data) < 1) {
                return response()->json([
                    'success' => true,
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
                'message' => 'Gagal mengambil data Customer! '.$th->getMessage(),
                'data' => [],
            ], 200);
        }
    }

    function Insert(Request $request) {
        try {
            DB::beginTransaction();
            $opname_code=$request->json()->get('opname_code');
            $id_stok=$request->json()->get('id_stok');
            $locs_code=$request->json()->get('locs_code');

            $checkStatOpname = DB::SELECT("SELECT opname_status FROM public.wms_opname_mstr WHERE opname_code='$opname_code'");
            if ($checkStatOpname[0]->opname_status != 'R') {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Opname sudah dengan code '.$opname_code.' sudah diclose!',
                    'data' => [],
                ], 200);
            }

            $checkIdStok = DB::SELECT("SELECT * FROM public.wms_opnamed_detail WHERE opnamed_opname_code='$opname_code' AND opnamed_id_stok=$id_stok");

            if (count($checkIdStok) > 0) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Id Stok '.$id_stok.' sudah diopname!',
                    'data' => [],
                ], 200);
            } else {
                $uuid = $this->generateUuid();
                $ssql="INSERT INTO 
                        public.wms_opnamed_detail
                    (
                        opnamed_oid,
                        opnamed_opname_code,
                        opnamed_id_stok,
                        opnamed_locs_code,
                        opnamed_time
                    )
                    VALUES (
                        '$uuid',
                        '$opname_code',
                        $id_stok,
                        '$locs_code',
                        current_timestamp
                    )";

                $insertDetail = DB::INSERT($ssql);

                if ($insertDetail) {
                    DB::commit();
                    return response()->json([
                        'success' => true,
                        'message' => 'Opaname '.$id_stok.' berhasil!',
                        'data' => [],
                    ], 200);
                } else {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Gagal opname!',
                        'data' => [],
                    ], 200);
                }
            }

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal insert! '.$th->getMessage(),
                'data' => [],
            ], 200);
        }
    }
    public function generateUuid()
    {
        if (function_exists('com_create_guid')) {
            return com_create_guid();
        } else {
            mt_srand((double) microtime() * 10000);
            $charId = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45); // "-"
            $uuid = substr($charId, 0, 8) . $hyphen . substr($charId, 8, 4) . $hyphen . substr($charId, 12, 4) . $hyphen . substr($charId, 16, 4) . $hyphen . substr($charId, 20, 12);
    
            return $uuid;
        }
    }
}