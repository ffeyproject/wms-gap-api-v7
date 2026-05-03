<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\UserModel;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class MoveLocController extends Controller
{

    public function Insert(Request $request)
    {

        try {

            DB::beginTransaction();
            $move_date=$request->json()->get('move_date');
            $move_count=$request->json()->get('move_count');
            $move_locs_code_from=$request->json()->get('move_locs_code_from');
            $move_locs_code_to=$request->json()->get('move_locs_code_to');
            $move_create_by=$request->json()->get('move_create_by');

            $details = $request->json()->get('details');

            $cekDate = DB::SELECT("show datestyle");

            $dateStyle = substr($cekDate[0]->DateStyle, -3);

            try {
                $dateObj = Carbon::createFromFormat('d-m-Y', $move_date);
            } catch (\Throwable $th) {
                $dateObj = Carbon::createFromFormat('d/m/Y', $move_date);
            }

            $move_code="";
            $year = ""; 
            $month = ""; 

            if ($dateStyle === "MDY") {
                $formattedStartDate = $dateObj->format('m-d-Y');
                $year = substr($formattedStartDate,6,4); 
                $month = substr($formattedStartDate,0,2);   
            } elseif ($dateStyle === "DMY") {
                $formattedStartDate = $dateObj->format('d-m-Y');
                $year = substr($formattedStartDate,6,4); 
                $month = substr($formattedStartDate,3,2); 
            } elseif ($dateStyle === "YMD") {
                $formattedStartDate = $dateObj->format('Y-m-d');
                $year = substr($formattedStartDate,0,4); 
                $month = substr($formattedStartDate,3,2); 
            } else {
                $formattedStartDate = $dateObj->format('Y-m-d'); // Default jika tidak sesuai format yang diharapkan
                $year = substr($formattedStartDate,0,4); 
                $month = substr($formattedStartDate,3,2); 
            }

            $ssql="SELECT COUNT (move_code) AS count_rec, cast(MAX(RIGHT(move_code,5)) as integer) + 1 AS id_rec FROM wms_move_location_mstr
            Where left(move_code,9)='ML-$year$month'";
            $kode = DB::select($ssql);

           
            $count=$kode[0]->count_rec;
            if ($count == 0) {
                $move_code="ML-$year$month" . "00001";
            } else {
                $id_new=$kode[0]->id_rec;
                $formattedNumber = str_pad($id_new, 5, '0', STR_PAD_LEFT);
                $move_code="ML-$year$month" . $formattedNumber;
            }


            $ssql="INSERT INTO 
                public.wms_move_location_mstr
                (
                    move_code,
                    move_date,
                    move_create_at,
                    move_create_by,
                    move_count,
                    move_locs_code_from,
                    move_locs_code_to
                )
                VALUES (
                    '$move_code',
                    '$formattedStartDate',
                    current_timestamp,
                    '$move_create_by',
                    '$move_count',
                    '$move_locs_code_from',
                    '$move_locs_code_to'
                )";
         
            $insert = DB::INSERT($ssql);
           
    
            if ($insert) {
                if (count($details) > 0) {
                    foreach ($details as $det) {

                        $moved_id_stok = $det['moved_id_stok'];

                        $ssql="INSERT INTO 
                                public.wms_move_location_dtl
                            (
                                moved_move_code,
                                moved_id_stok
                            )
                            VALUES (
                                '$move_code',
                                '$moved_id_stok'
                            )";


                        $insertDetail = DB::INSERT($ssql);

                        $sql="UPDATE 
                            public.trn_gudang_jadi 
                        SET 
                            locs_code = '$move_locs_code_to'
                        WHERE 
                            id = '$moved_id_stok'";

                    $insertDetail = DB::UPDATE($sql);

                    }
                }
            }
            
            DB::commit();

            $dummy = DB::SELECT("SELECT * FROM wms_move_location_mstr WHERE move_code='$move_code'");

            if (count($dummy) > 0) {
                $data = [];
                foreach ($dummy as $dum) {
                    $dummyd_det = DB::SELECT("SELECT * FROM wms_move_location_dtl WHERE moved_move_code='$dum->move_code'");
                    $dum->details = $dummyd_det;
                    array_push($data, $dum);
                }
                return response()->json([
                    'success' => true,
                    'message' => 'Berhasil insert data!',
                    'data' => $data,
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal insert!',
                    'data' => [],
                ], 200);
            }
        } catch (\Throwable$th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal insert! '.$th->getMessage(),
                'data' => [],
            ], 200);
        }
    }


    public function Delete(Request $request)
    {

        try {

            DB::beginTransaction();

            $move_code=$request->json()->get('move_code');

            $dummy = DB::SELECT("SELECT move_locs_code_from FROM wms_move_location_mstr WHERE move_code='$move_code'");

            $move_locs_code_from="";
            if (count($dummy) > 0) {
                $move_locs_code_from=$dummy[0]->move_locs_code_from;
                
            }
           
            $data_det = DB::SELECT("SELECT * FROM wms_move_location_dtl WHERE moved_move_code='$move_code'");
                      

            if (count($data_det) > 0) {
                foreach ($data_det as $det) {
                    $sql="UPDATE 
                            public.trn_gudang_jadi 
                        SET 
                            locs_code = '$move_locs_code_from'
                        WHERE 
                            id = '$det->moved_id_stok'";

                    $insertDetail = DB::UPDATE($sql);


                }
            }
            
            $sql="DELETE from
                public.wms_move_location_mstr
            where
                move_code='$move_code'";

            $exec = DB::DELETE($sql);
                
            DB::commit();

           

            if ($exec) {
                
                return response()->json([
                    'success' => true,
                    'message' => 'Berhasil hapus data!',
                    'data' => [],
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal hapus data!',
                    'data' => [],
                ], 404);
            }
        } catch (\Throwable$th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal hapus data! '.$th->getMessage(),
                'data' => [],
            ], 200);
        }
    }

    public function GetList(Request $request)
    {
        try {
           
            
            $tgl_awal=$request->json()->get('tgl_awal');
            $tgl_akhir=$request->json()->get('tgl_akhir');

            $cekDate = DB::SELECT("show datestyle");

            $dateStyle = substr($cekDate[0]->DateStyle, -3);

            try {
                $dateObj = Carbon::createFromFormat('d-m-Y', $tgl_awal);
            } catch (\Throwable $th) {
                $dateObj = Carbon::createFromFormat('d/m/Y', $tgl_awal);
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
                $dateObj = Carbon::createFromFormat('d-m-Y', $tgl_akhir);
            } catch (\Throwable $th) {
                $dateObj = Carbon::createFromFormat('d/m/Y', $tgl_akhir);
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

            $data = DB::SELECT("SELECT 
            *
          FROM
            public.wms_move_location_mstr a
          WHERE
            a.move_date BETWEEN '$formattedStartDate' AND '$formattedEndDate'
          ORDER BY
            a.move_code");
            
            if (count($data) > 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'Berhasil menampilkan data',
                    'data' => $data,
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Data kosong!',
                    'data' => [],
                ], 200);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Get data gagal! '.$th->getMessage(),
                'data' => [],
            ], 200);
        }
    }
}
