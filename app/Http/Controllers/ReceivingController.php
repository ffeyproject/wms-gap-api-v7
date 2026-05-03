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

class ReceivingController extends Controller
{
    function GetInspectingHeaderList(){
        try {
            $sql = "SELECT ins.*,
                        grg.nama_kain,
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
                    WHERE ins.status=3
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
                    WHERE mklbj.status=2
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

    function GetInspectingItemList(Request $request){
        try {
            $id = $request->json()->get('id');

            $sql = "SELECT *, 
                        CASE
                            WHEN grade = 1 THEN 'Grade A'
                            WHEN grade = 2 THEN 'Grade B'
                            WHEN grade = 3 THEN 'Grade C'
                            WHEN grade = 4 THEN 'Piece Kecil'
                            WHEN grade = 5 THEN 'Sample'
                        ELSE 'Null'
                        END AS grade_name
                    FROM public.inspecting_item WHERE inspecting_id=$id
                    ORDER BY id ASC";
            $data = DB::SELECT($sql);

            if (count($data) < 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data item penerimaan inspecting kosong!',
                    'data' => [],
                ], 200);
            } else {
                if ($data) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Berhasil mengambil data item penerimaan inspecting!',
                        'data' => $data,
                    ], 200);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Gagal mengambil data item penerimaan inspecting!',
                        'data' => [],
                    ], 200);
                }
            }
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data item penerimaan inspecting! '.$th->getMessage(),
                'data' => [],
            ], 200);
        }
    }

    function GetMklbjItemList(Request $request){
        try {
            $id = $request->json()->get('id');

            $sql = "SELECT *, 
                        CASE
                            WHEN grade = 1 THEN 'Grade A'
                            WHEN grade = 2 THEN 'Grade B'
                            WHEN grade = 3 THEN 'Grade C'
                            WHEN grade = 4 THEN 'Piece Kecil'
                            WHEN grade = 5 THEN 'Sample'
                        ELSE 'Null'
                        END AS grade_name
                    FROM public.inspecting_mkl_bj_items WHERE inspecting_id=$id
                    ORDER BY id ASC";
            $data = DB::SELECT($sql);

            if (count($data) < 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data item penerimaan inspecting kosong!',
                    'data' => [],
                ], 200);
            } else {
                if ($data) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Berhasil mengambil data item penerimaan inspecting!',
                        'data' => $data,
                    ], 200);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Gagal mengambil data item penerimaan inspecting!',
                        'data' => [],
                    ], 200);
                }
            }
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
            //$note = $request->json()->get('note');
            $details = $request->json()->get('details');

            $rejectNote = [
                'date_time' => date('Y-m-d H:i:s'),
                'note'=> $request->json()->get('note')
            ];

            $note=Json_encode($rejectNote);

            $sql = "UPDATE public.trn_inspecting
                    SET 
                        status = 1,
                        note = '$note',
                        updated_by = $update_by,
                        updated_date = current_timestamp
                    WHERE id=$id";
            
            $updateReject = DB::UPDATE($sql);

            if ($updateReject) {
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
            $status = $request->json()->get('status');
            //$note = $request->json()->get('note');
            $details = $request->json()->get('details');

            $rejectNote = [
                'date_time' => date('Y-m-d H:i:s'),
                'note'=> $request->json()->get('note')
            ];

            $note=Json_encode($rejectNote);

            
            $sql = "UPDATE public.inspecting_mkl_bj
                    SET 
                        status = $status,
                        delivery_reject_note = '$note',
                        updated_by = $update_by,
                        updated_date = current_timestamp
                    WHERE id=$id";
            
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
                    'message' => 'Reject penerimaan MKLBJ berhasil!',
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
            return response()->json([
                'success' => false,
                'message' => 'Gagal reject MKLBJ inspecting! '.$th->getMessage(),
                'data' => [],
            ], 200);
        }
    }

    function ReceiveItemInspecting(Request $request){
        try {
            
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data master penerimaan inspecting! '.$th->getMessage(),
                'data' => [],
            ], 200);
        }
    }

    function ReceiveItemMklbj(){
        try {
            $sql = "SELECT * FROM";
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data master penerimaan inspecting! '.$th->getMessage(),
                'data' => [],
            ], 200);
        }
    }
}