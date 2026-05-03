<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\UserModel;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SetLocController extends Controller
{

    public function Insert(Request $request)
    {

        try {

            DB::beginTransaction();
            $set_date=$request->json()->get('set_date');
            $set_count=$request->json()->get('set_count');
            $set_locs_code=$request->json()->get('set_locs_code');
            $set_create_by=$request->json()->get('set_create_by');
            $details = $request->json()->get('details');
            
            $cekDate = DB::SELECT("show datestyle");

            $dateStyle = substr($cekDate[0]->DateStyle, -3);

            try {
                $dateObj = Carbon::createFromFormat('d-m-Y', $set_date);
            } catch (\Throwable $th) {
                $dateObj = Carbon::createFromFormat('d/m/Y', $set_date);
            }

            $set_code="";
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

            $ssql="SELECT COUNT (set_code) AS count_rec, cast(MAX(RIGHT(set_code,5)) as integer) + 1 AS id_rec FROM wms_set_location_mstr
            Where left(set_code,9)='SL-$year$month'";
            $kode = DB::select($ssql);

           
            $count=$kode[0]->count_rec;
            if ($count == 0) {
                $set_code="SL-$year$month" . "00001";
            } else {
                $id_new=$kode[0]->id_rec;
                $formattedNumber = str_pad($id_new, 5, '0', STR_PAD_LEFT);
                $set_code="SL-$year$month" . $formattedNumber;
            }


            $sql="INSERT INTO 
                    public.wms_set_location_mstr
                (
                    set_code,
                    set_date,
                    set_create_at,
                    set_create_by,
                    set_count,
                    set_locs_code
                )
                VALUES (
                    '$set_code',
                    '$formattedStartDate',
                    current_timestamp,
                    '$set_create_by',
                    '$set_count',
                    '$set_locs_code'
                )";
            $insert = DB::INSERT($sql);
           
    
            if ($insert) {
                if (count($details) > 0) {
                    foreach ($details as $det) {

                        $setd_id_stok = $det['setd_id_stok'];

                        $sql="INSERT INTO 
                            public.wms_set_location_dtl
                        (
                            setd_set_code,
                            setd_id_stok
                        )
                        VALUES (
                            '$set_code',
                            '$setd_id_stok'
                        )";

                        $insertDetail = DB::INSERT($sql);

                        $sql="UPDATE 
                            public.trn_gudang_jadi 
                        SET 
                            locs_code = '$set_locs_code'
                        WHERE 
                            id = '$setd_id_stok'";

                    $insertDetail = DB::UPDATE($sql);

                    }
                }
            }
            
            DB::commit();

            $dummy = DB::SELECT("SELECT * FROM wms_set_location_mstr WHERE set_code='$set_code'");

            if (count($dummy) > 0) {
                $data = [];
                foreach ($dummy as $dum) {
                    $dummyd_det = DB::SELECT("SELECT * FROM wms_set_location_dtl WHERE setd_set_code='$dum->set_code'");
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

            $set_code=$request->json()->get('set_code');
           
            $data_det = DB::SELECT("SELECT * FROM wms_set_location_dtl WHERE setd_set_code='$set_code'");
                      
            if (count($data_det) > 0) {
                foreach ($data_det as $det) {
                    $sql="UPDATE 
                            public.trn_gudang_jadi 
                        SET 
                            locs_code = null
                        WHERE 
                            id = '$det->setd_id_stok'";

                    $insertDetail = DB::UPDATE($sql);


                }
            }
            
            $sql="DELETE from
            public.wms_set_location_mstr
            where
                set_code='$set_code'";

            $exec = DB::DELETE($sql);
                
            DB::commit();

            $dummy = DB::SELECT("SELECT * FROM wms_set_location_mstr WHERE set_code='$set_code'");

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
                ], 200);
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
            a.set_code,
            a.set_date,
            a.set_count,
            a.set_locs_code,
            b.locs_loc_id,
            c.loc_name,
            a.set_create_at,
            a.set_create_by
          FROM
            public.wms_set_location_mstr a
            INNER JOIN public.wms_locs_sub b ON (a.set_locs_code = b.locs_code)
            INNER JOIN public.wms_loc_mstr c ON (b.locs_loc_id = c.loc_id)
          WHERE
            a.set_date BETWEEN '$formattedStartDate' AND '$formattedEndDate'
          ORDER BY
            a.set_code");
            
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
