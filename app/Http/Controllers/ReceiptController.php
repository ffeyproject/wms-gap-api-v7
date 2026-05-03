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

class ReceiptController extends Controller
{
    protected function logKartuDyeing(Request $request, $actionName, $kartuProsesId = null, $description = null)
    {
        $userId = $request->json()->get('update_by');

        $username = DB::table('user')
            ->where('id', $userId)
            ->value('full_name');

        $ip        = $request->ip();
        $agent     = $request->userAgent();
        $createdAt = date('Y-m-d H:i:s');

        DB::insert("
            INSERT INTO action_log_kartu_dyeing
            (user_id, username, kartu_proses_id, action_name, description, ip, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $userId,
            $username,
            $kartuProsesId,
            $actionName,
            $description,
            $ip,
            $agent,
            $createdAt
        ]);
    }


    function GetInspectingHeaderList(){
        try {
            $sql = "SELECT ins.*,
                        grg.nama_kain,
                        wo.no as no_wo,
                        CASE
                            WHEN ins.kartu_process_dyeing_id is not null THEN dyg.no
                            WHEN ins.kartu_process_printing_id is not null THEN prnt.no
                            WHEN ins.memo_repair_id is not null THEN memo.no
                        ELSE 'Null'
                        END AS no_kartu
                    FROM public.trn_inspecting ins
                    INNER JOIN public.trn_wo wo ON(ins.wo_id=wo.id)
                    INNER JOIN public.mst_greige grg ON(wo.greige_id=grg.id)
                    LEFT JOIN public.trn_kartu_proses_printing prnt ON(ins.kartu_process_printing_id=prnt.id)
                    LEFT JOIN public.trn_kartu_proses_dyeing dyg ON(ins.kartu_process_dyeing_id=dyg.id)
                    LEFT JOIN public.trn_memo_repair memo ON(ins.memo_repair_id=memo.id)
                    WHERE ins.status = 3
                    OR (ins.status = 4 AND ins.id IN (
                        SELECT ii.inspecting_id 
                        FROM public.inspecting_item ii
                        LEFT JOIN public.trn_gudang_jadi gj ON (gj.trans_from = 'INS' AND gj.id_from = ii.id)
                        WHERE ii.is_posted = true AND gj.id IS NULL
                    ))
                    ORDER BY ins.id ASC";

            $data = DB::SELECT($sql);

            if (count($data) < 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data header penerimaan inspecting kosong!',
                    'data' => [],
                ], 200);
            } else {
                if ($data) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Berhasil mengambil data header penerimaan inspecting!',
                        'data' => $data,
                    ], 200);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Gagal mengambil data header penerimaan inspecting!',
                        'data' => [],
                    ], 200);
                }
            }
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data header penerimaan inspecting! '.$th->getMessage(),
                'data' => [],
            ], 200);
        }
    }

    function GetMklbjHeaderList(){
        try {
            $sql = "SELECT mklbj.*, wo.no as no_wo, mo_color.color
                    FROM public.inspecting_mkl_bj mklbj
                    INNER JOIN public.trn_wo wo ON(mklbj.wo_id=wo.id)
                    INNER JOIN public.trn_wo_color color ON(mklbj.wo_color_id=color.id)
                    INNER JOIN public.trn_mo_color mo_color ON(color.mo_color_id=mo_color.id)
                    WHERE mklbj.status = 2
                    OR (mklbj.status = 3 AND mklbj.id IN (
                        SELECT mkl_item.inspecting_id 
                        FROM public.inspecting_mkl_bj_items mkl_item
                        LEFT JOIN public.trn_gudang_jadi gj ON (gj.trans_from = 'MKL' AND gj.id_from = mkl_item.id)
                        WHERE mkl_item.is_posted = true AND gj.id IS NULL
                    ))
                    ORDER BY mklbj.id ASC";

            $data = DB::SELECT($sql);

            if (count($data) < 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data header penerimaan mklbj kosong!',
                    'data' => [],
                ], 200);
            } else {
                if ($data) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Berhasil mengambil data header penerimaan mklbj!',
                        'data' => $data,
                    ], 200);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Gagal mengambil data header penerimaan mklbj!',
                        'data' => [],
                    ], 200);
                }
            }
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data master penerimaan mklbj! '.$th->getMessage(),
                'data' => [],
            ], 200);
        }
    }

    // function GetInspectingItemList(Request $request){
    //     try {
    //         $id = $request->json()->get('id');

    //         $sql = "SELECT *,
    //                     CASE
    //                         WHEN grade = 1 THEN 'Grade A'
    //                         WHEN grade = 2 THEN 'Grade B'
    //                         WHEN grade = 3 THEN 'Grade C'
    //                         WHEN grade = 4 THEN 'Piece Kecil'
    //                         WHEN grade = 5 THEN 'Sample'
    //                     ELSE 'Null'
    //                     END AS grade_name
    //                 FROM public.inspecting_item WHERE inspecting_id=$id
    //                 ORDER BY id ASC";
    //         $data = DB::SELECT($sql);

    //         if (count($data) < 1) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Data item penerimaan inspecting kosong!',
    //                 'data' => [],
    //             ], 200);
    //         } else {
    //             if ($data) {
    //                 return response()->json([
    //                     'success' => true,
    //                     'message' => 'Berhasil mengambil data item penerimaan inspecting!',
    //                     'data' => $data,
    //                 ], 200);
    //             } else {
    //                 return response()->json([
    //                     'success' => false,
    //                     'message' => 'Gagal mengambil data item penerimaan inspecting!',
    //                     'data' => [],
    //                 ], 200);
    //             }
    //         }
    //     } catch (\Throwable $th) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Gagal mengambil data item penerimaan inspecting! '.$th->getMessage(),
    //             'data' => [],
    //         ], 200);
    //     }
    // }

    //feeey
    function GetInspectingItemList(Request $request){
        try {
            $id = $request->json()->get('id');

            // Cek apakah kolom no_urut ada di tabel inspecting_item
            $hasNoUrut = false;
            $columns = DB::select("SELECT column_name
                                    FROM information_schema.columns
                                    WHERE table_name = 'inspecting_item'
                                    AND column_name = 'no_urut'");

            if (count($columns) > 0) {
                $hasNoUrut = true;
            }

            // Bangun query SQL berdasarkan ketersediaan kolom no_urut
            $orderBy = $hasNoUrut ? 'no_urut ASC' : 'id ASC';

            // $sql = "SELECT *,
            //             CASE
            //                 WHEN grade = 1 THEN 'Grade A'
            //                 WHEN grade = 2 THEN 'Grade B'
            //                 WHEN grade = 3 THEN 'Grade C'
            //                 WHEN grade = 4 THEN 'Piece Kecil'
            //                 WHEN grade = 5 THEN 'Sample'
            //             ELSE 'Null'
            //             END AS grade_name
            //         FROM public.inspecting_item
            //         WHERE inspecting_id = ?
            //         ORDER BY $orderBy";


            $sql = "SELECT *,
            CASE
                WHEN grade = 1 THEN 'Grade A'
                WHEN grade = 2 THEN 'Grade B'
                WHEN grade = 3 THEN 'Grade C'
                WHEN grade = 4 THEN 'Piece Kecil'
                WHEN grade = 5 THEN 'Sample'
            ELSE 'Null'
                END AS grade_name
            FROM public.inspecting_item
            WHERE inspecting_id = ? 
            AND is_posted = true
            AND NOT EXISTS (
                SELECT 1 FROM public.trn_gudang_jadi gj 
                WHERE gj.trans_from = 'INS' AND gj.id_from = public.inspecting_item.id
            )
            ORDER BY
            CASE WHEN no_urut IS NULL THEN 1 ELSE 0 END ASC,  -- utamakan yg ada no_urut dulu
            no_urut ASC,                                     -- urut berdasarkan no_urut
            id ASC";

            $data = DB::select($sql, [$id]);

            if (count($data) < 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data item penerimaan inspecting kosong!',
                    'data' => [],
                ], 200);
            } else {
                return response()->json([
                    'success' => true,
                    'message' => 'Berhasil mengambil data item penerimaan inspecting!',
                    'data' => $data,
                ], 200);
            }

            } catch (\Throwable $th) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengambil data item penerimaan inspecting! '.$th->getMessage(),
                    'data' => [],
                ], 200);
            }
    }

    // function GetMklbjItemList(Request $request){
    //     try {
    //         $id = $request->json()->get('id');

    //         $sql = "SELECT *,
    //                     CASE
    //                         WHEN grade = 1 THEN 'Grade A'
    //                         WHEN grade = 2 THEN 'Grade B'
    //                         WHEN grade = 3 THEN 'Grade C'
    //                         WHEN grade = 4 THEN 'Piece Kecil'
    //                         WHEN grade = 5 THEN 'Sample'
    //                     ELSE 'Null'
    //                     END AS grade_name
    //                 FROM public.inspecting_mkl_bj_items WHERE inspecting_id=$id
    //                 ORDER BY id ASC";
    //         $data = DB::SELECT($sql);

    //         if (count($data) < 1) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Data item penerimaan inspecting kosong!',
    //                 'data' => [],
    //             ], 200);
    //         } else {
    //             if ($data) {
    //                 return response()->json([
    //                     'success' => true,
    //                     'message' => 'Berhasil mengambil data item penerimaan inspecting!',
    //                     'data' => $data,
    //                 ], 200);
    //             } else {
    //                 return response()->json([
    //                     'success' => false,
    //                     'message' => 'Gagal mengambil data item penerimaan inspecting!',
    //                     'data' => [],
    //                 ], 200);
    //             }
    //         }
    //     } catch (\Throwable $th) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Gagal mengambil data master penerimaan inspecting! '.$th->getMessage(),
    //             'data' => [],
    //         ], 200);
    //     }
    // }

    //Feeey
    function GetMklbjItemList(Request $request){
        try {
                $id = $request->json()->get('id');

                // Cek apakah ADA nilai no_urut yang tidak NULL
                $result = DB::select("
                    SELECT COUNT(*) AS total
                    FROM inspecting_mkl_bj_items
                    WHERE inspecting_id = ? AND no_urut IS NOT NULL AND is_posted = true
                ", [$id]);

                // Jika ada nilai no_urut yang tidak null, pakai ORDER BY no_urut
                $orderBy = ($result[0]->total > 0) ? 'no_urut ASC' : 'id ASC';

                $sql = "
                    SELECT *,
                        CASE
                            WHEN grade = 1 THEN 'Grade A'
                            WHEN grade = 2 THEN 'Grade B'
                            WHEN grade = 3 THEN 'Grade C'
                            WHEN grade = 4 THEN 'Piece Kecil'
                            WHEN grade = 5 THEN 'Sample'
                            ELSE 'Null'
                        END AS grade_name
                    FROM public.inspecting_mkl_bj_items
                    WHERE inspecting_id = ? 
                    AND is_posted = true
                    AND NOT EXISTS (
                        SELECT 1 FROM public.trn_gudang_jadi gj 
                        WHERE gj.trans_from = 'MKL' AND gj.id_from = public.inspecting_mkl_bj_items.id
                    )
                    ORDER BY $orderBy
                ";

                $data = DB::select($sql, [$id]);

                if (count($data) < 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Data item penerimaan inspecting kosong!',
                        'data' => [],
                    ], 200);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Berhasil mengambil data item penerimaan inspecting!',
                    'data' => $data,
                ], 200);

            } catch (\Throwable $th) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengambil data master penerimaan inspecting! '.$th->getMessage(),
                    'data' => [],
                ], 200);
            }
    }


    function RejectItemInspecting(Request $request){
        try {
            DB::beginTransaction();

            $id = $request->json()->get('id');
            $update_by = $request->json()->get('update_by');
            $note = $request->json()->get('note');
            $details = $request->json()->get('details');

            $current_timestamp = date('Y-m-d H:i:s');
            $data = [
                [
                    "date_time" => $current_timestamp,
                    "note" => $note
                ]
            ];

            $json = json_encode($data);


            $sql = "UPDATE public.trn_inspecting
                    SET
                        status = 1,
                        note = '$json',
                        updated_by = $update_by,
                        updated_at = cast(extract(epoch from current_timestamp) as integer)
                    WHERE id=$id";

            $updateReject = DB::UPDATE($sql);

            if ($updateReject) {
                // Ambil kartu proses ID secara aman
                $kp4 = DB::selectOne("
                    SELECT kartu_process_dyeing_id
                    FROM public.trn_inspecting
                    WHERE id = $id
                    LIMIT 1
                ");

                $kartuProsesId = ($kp4 && isset($kp4->kartu_process_dyeing_id))
                                    ? $kp4->kartu_process_dyeing_id
                                    : 0;

                $this->logKartuDyeing(
                    $request,
                    'barang_inspect_reject',
                    $kartuProsesId,
                    "Barang Inspecting ID $id direject oleh user $update_by. Catatan: $note"
                );

                if (count($details) > 0) {
                    foreach ($details as $det) {
                        $id_item = $det['id_item'];
                        $note_item = $det['note_item'];

                        $sql="UPDATE public.inspecting_item
                        SET
                            note = '$note_item'
                        WHERE
                            id = $id_item";

                        $updateRejectItem = DB::UPDATE($sql);

                        if (!$updateRejectItem) {
                            DB::rollBack();
                            return response()->json([
                                'success' => false,
                                'message' => 'Gagal reject penerimaan inspecting! '.$id_item,
                                'data' => [],
                            ], 200);
                        }
                    }
                }
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Reject penerimaan inspecting berhasil!',
                    'data' => [],
                ], 200);
            }
            else {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal reject penerimaan inspecting!',
                    'data' => [],
                ], 200);
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal reject penerimaan inspecting! '.$th->getMessage(),
                'data' => [],
            ], 200);
        }
    }

    function RejectItemMklbj(Request $request){
        try {
            DB::beginTransaction();

            $id = $request->json()->get('id');
            $update_by = $request->json()->get('update_by');
            $note = $request->json()->get('note');
            $details = $request->json()->get('details');

            $current_timestamp = date('Y-m-d H:i:s');
            $data = [
                [
                    "date_time" => $current_timestamp,
                    "note" => $note
                ]
            ];

            $json = json_encode($data);

            $sql_master="";
            $sql = "UPDATE public.inspecting_mkl_bj
                    SET
                        status = 1,
                        delivery_reject_note = '$json',
                        updated_by = $update_by,
                        updated_at = cast(extract(epoch from current_timestamp) as integer)
                    WHERE id=$id";
            $sql_master=$sql;
            $updateReject = DB::UPDATE($sql);

            if ($updateReject) {
                if (count($details) > 0) {
                    foreach ($details as $det) {
                        $id_item = $det['id_item'];
                        $note_item = $det['note_item'];

                        $sql="UPDATE public.inspecting_mkl_bj_items
                        SET
                            note = '$note_item'
                        WHERE
                            id = $id_item";

                        $updateRejectItem = DB::UPDATE($sql);

                        if (!$updateRejectItem) {
                            DB::rollBack();
                            return response()->json([
                                'success' => false,
                                'message' => 'Gagal reject penerimaan mklbj! '.$id_item,
                                'data' => [],
                            ], 200);
                        }
                    }
                }
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Reject penerimaan MKLBJ berhasil! ' . $sql_master,
                    'data' => [],
                ], 200);
            }
            else {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal reject MKLBJ inspecting!',
                    'data' => [],
                ], 200);
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal reject MKLBJ inspecting! '.$th->getMessage(),
                'data' => [],
            ], 200);
        }
    }

    function ReceiptItemInspecting(Request $request){
        try {
            DB::beginTransaction();

            $id = $request->json()->get('id');
            $update_by = $request->json()->get('update_by');
            $jenis_gudang = $request->json()->get('jenis_gudang');
            $wo_id = $request->json()->get('wo_id');
            $no_wo = $request->json()->get('no_wo');
            $source_ref = $request->json()->get('no');
            $unit = $request->json()->get('unit');
            $color = $request->json()->get('color');
            $details = $request->json()->get('details');

            $sql = "UPDATE public.trn_inspecting
                    SET status = 4,
                        delivered_by = $update_by,
                        delivered_at = cast(extract(epoch from current_timestamp) as integer)
                    WHERE id=$id";

            $updateReceipt = DB::UPDATE($sql);

            if ($updateReceipt) {

                // Ambil kartu proses ID secara aman
                $kp4 = DB::selectOne("
                    SELECT kartu_process_dyeing_id
                    FROM public.trn_inspecting
                    WHERE id = $id
                    LIMIT 1
                ");

                $kartuProsesId = ($kp4 && isset($kp4->kartu_process_dyeing_id))
                                    ? $kp4->kartu_process_dyeing_id
                                    : 0;


                $this->logKartuDyeing(
                    $request,
                    'terima_gudang_jadi',
                    $kartuProsesId,
                    "Barang Inspecting ID $id diterima oleh user $update_by"
                );

                // =====================================================
                // === UPDATE STATUS KARTU PROSES DYEING ? STATUS 13 ===
                // =====================================================
                $sqlKP = "
                    SELECT kartu_process_dyeing_id
                    FROM public.trn_inspecting
                    WHERE id = $id
                    LIMIT 1
                ";

                $kp = DB::selectOne($sqlKP);

                if ($kp && $kp->kartu_process_dyeing_id != null) {

                    $kp_id = $kp->kartu_process_dyeing_id;

                    $sqlUpdateKP = "
                        UPDATE public.trn_kartu_proses_dyeing
                        SET status = 13,
                            updated_at = cast(extract(epoch from current_timestamp) as integer),
                            updated_by = $update_by
                        WHERE id = $kp_id
                    ";

                    DB::update($sqlUpdateKP);
                }
                // =====================================================

                if (count($details) > 0) {
                    foreach ($details as $det) {
                        $id_item = $det['id_item'];
                        $qty_item = $det['qty_item'];
                        $qty_sum_item = $det['qty_sum_item'];
                        $grade_item = $det['grade_item'];
                        $note_item = $det['note_item'];
                        $qr_code_item = $det['qr_code_item'];
                        $qr_code_desc_item = $det['qr_code_desc_item'];
                        $is_head_item = $det['is_head_item'];

                        if ($is_head_item == 1) {
                            $sql="INSERT INTO public.trn_gudang_jadi
                            (
                                jenis_gudang,
                                wo_id,
                                source,
                                source_ref,
                                unit,
                                qty,
                                date,
                                status,
                                note,
                                color,
                                grade,
                                trans_from,
                                id_from,
                                qr_code,
                                qr_code_desc,
                                created_at,
                                created_by
                            )
                            VALUES
                            (
                                $jenis_gudang,
                                $wo_id,
                                1,
                                '$source_ref',
                                $unit,
                                $qty_sum_item,
                                current_date,
                                1,
                                'Dari inspecting dengan nomor $source_ref',
                                '$color',
                                $grade_item,
                                'INS',
                                $id_item,
                                '$qr_code_item',
                                '$qr_code_desc_item',
                                cast(extract(epoch from current_timestamp) as integer),
                                '$update_by'
                            )";

                            $insertGudangJadi = DB::INSERT($sql);

                            if (!$insertGudangJadi) {
                                DB::rollBack();
                                return response()->json([
                                    'success' => false,
                                    'message' => 'Gagal penerimaan inspecting! '.$id_item,
                                    'data' => [],
                                ], 200);
                            }
                        }
                    }
                }
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Penerimaan inspecting berhasil!',
                    'data' => [],
                ], 200);
            } else {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal submit penerimaan inspecting!',
                    'data' => [],
                ], 200);
            }

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal submit penerimaan inspecting! '.$th->getMessage(),
                'data' => [],
            ], 200);
        }
    }

    function ReceiptItemMklbj(Request $request){
        try {
            DB::beginTransaction();

            $id = $request->json()->get('id');
            $update_by = $request->json()->get('update_by');
            $jenis_gudang = $request->json()->get('jenis_gudang');
            $wo_id = $request->json()->get('wo_id');
            $no_wo = $request->json()->get('no_wo');
            $source_ref = $request->json()->get('no');
            $unit = $request->json()->get('unit');
            $color = $request->json()->get('color');
            $details = $request->json()->get('details');

            $sql = "UPDATE public.inspecting_mkl_bj
                    SET status = 3,
                        delivered_by = $update_by,
                        delivered_at = cast(extract(epoch from current_timestamp) as integer)
                    WHERE id=$id";

            $updateReceipt = DB::UPDATE($sql);

            if ($updateReceipt) {
                if (count($details) > 0) {
                    foreach ($details as $det) {
                        $id_item = $det['id_item'];
                        $qty_item = $det['qty_item'];
                        $qty_sum_item = $det['qty_sum_item'];
                        $grade_item = $det['grade_item'];
                        $qr_code_item = $det['qr_code_item'];
                        $qr_code_desc_item = $det['qr_code_desc_item'];
                        $is_head_item = $det['is_head_item'];

                        if ($is_head_item == 1) {
                            $sql="INSERT INTO public.trn_gudang_jadi
                            (
                                jenis_gudang,
                                wo_id,
                                source,
                                source_ref,
                                unit,
                                qty,
                                date,
                                status,
                                note,
                                color,
                                grade,
                                trans_from,
                                id_from,
                                qr_code,
                                qr_code_desc,
                                created_at,
                                created_by
                            )
                            VALUES
                            (
                                $jenis_gudang,
                                $wo_id,
                                1,
                                '$source_ref',
                                $unit,
                                $qty_sum_item,
                                current_date,
                                1,
                                'Dari mklbj dengan nomor $source_ref',
                                '$color',
                                $grade_item,
                                'MKL',
                                $id_item,
                                '$qr_code_item',
                                '$qr_code_desc_item',
                                cast(extract(epoch from current_timestamp) as integer),
                                '$update_by'
                            )";

                            $insertGudangJadi = DB::INSERT($sql);

                            if (!$insertGudangJadi) {
                                DB::rollBack();
                                return response()->json([
                                    'success' => false,
                                    'message' => 'Gagal penerimaan mklbj! '.$id_item,
                                    'data' => [],
                                ], 200);
                            }
                        }
                    }
                }
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Penerimaan mklbj berhasil!',
                    'data' => [],
                ], 200);
            } else {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal submit penerimaan mklbj!',
                    'data' => [],
                ], 200);
            }

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal submit penerimaan mklbj! '.$th->getMessage(),
                'data' => [],
            ], 200);
        }
    }
}
