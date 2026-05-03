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

class PackingController extends Controller
{
    function GetCustomerPacking(Request $request) {
        try {
            $search = $request->json()->get('search');
            
            $data = DB::SELECT("
                    SELECT distinct
                    public.mst_customer.id,
                    public.mst_customer.cust_no,
                    public.mst_customer.name,
                    public.mst_customer.address
                FROM
                    public.trn_gudang_jadi
                    INNER JOIN public.trn_wo ON (public.trn_gudang_jadi.wo_id = public.trn_wo.id)
                    INNER JOIN public.trn_sc ON (public.trn_wo.sc_id = public.trn_sc.id)
                    INNER JOIN public.mst_customer ON (public.trn_sc.cust_id = public.mst_customer.id)
                WHERE trn_gudang_jadi.status=3 and status_packing is null 
                AND (public.mst_customer.cust_no ~~* '%$search%' OR public.mst_customer.name ~~* '%$search%' OR public.mst_customer.address ~~* '%$search%')
            ");

            // $header=array("Content-Type:application/json","Authorization: key=AAAA7rHxAxw:APA91bEX6eI2Oj-oWsOGCRqmshQip-FT2TOr9n2L0X0U-ExYRalg_ZgtVcw1sEotmZkapjJ4dkeumVNGMGCiCfElhIPrntoAMTdt_ypn2HOXQPaciaP10nPNqVDlzqL-HbfsUVUvOJFA");

            // $fcm=json_encode(
            //     array(
            //     //   "to"              => "ceblGaxMQE-gPjveJrnZsa:APA91bF69crnaO2AFIyhuamAGNfJKxxxbtGIGFp1YdiyohH52j9kCFNo9a4qfCY_jVgCU7vrpiU9Bz9yTbwkrnaEEoNarslk2t1T7xl9Qx4m7OHnJuncfNXMqrhzORyiH21ddxBUJ03D",
            //       "to"              =>"/topics/penerimaan",//allDevice
            //       "notification"    => array(
            //         "title"         => "Pemberitahuan",
            //         "message"       => "Penerimaan Inspecting",
            //         "body"          => "Penerimaan Inspecting",
            //         "id"            => "43",
            //         "no_kartu"  => "JP JF 03/0003/20",
            //         'vibrate'       => 1,
            //         'sound'         => 1,
            //         "click_action"  => "OPEN_ACTIVITY"
            //         ),
            //       "data"  => array(
            //         "title"     => "Pemberitahuan",
            //         "message"   => "Penerimaan Inspecting",
            //         "body"          => "Penerimaan Inspecting",
            //         "id"        => "43",
            //         "no_kartu"  => "JP JF 03/0003/20",
            //         'vibrate'   => 1,
            //         'sound'     => 1
            //       )
            //     )
            //   );
    
    
            //   $curl = curl_init();

            //   curl_setopt_array($curl, array(
            //       CURLOPT_URL => "https://fcm.googleapis.com/fcm/send",
            //       CURLOPT_RETURNTRANSFER => true,
            //       CURLOPT_SSL_VERIFYPEER => false,
            //       CURLOPT_ENCODING => "",
            //       CURLOPT_MAXREDIRS => 10,
            //       CURLOPT_TIMEOUT => 30,
            //       CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            //       CURLOPT_CUSTOMREQUEST => "POST",
            //       CURLOPT_POSTFIELDS => $fcm,
            //       CURLOPT_HTTPHEADER =>$header,
            //   ));

            //   $response = curl_exec($curl);
            //   $err = curl_error($curl);
            //   $data_curl = json_decode($response,true);

            // if (!$err) {
                
            // } else {
            // }

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

    function GetItemPackingList(Request $request) {
        try {
            $cust_id = $request->json()->get('cust_id');

            $cekDate = ConvertDateStyle('20-08-2023');

            $data = DB::SELECT("
            SELECT 
                public.trn_gudang_jadi.id,
                public.trn_gudang_jadi.jenis_gudang,
                public.trn_gudang_jadi.wo_id,
                public.trn_gudang_jadi.source,
                public.trn_gudang_jadi.source_ref,
                public.trn_gudang_jadi.unit,
                public.trn_gudang_jadi.qty,
                public.trn_gudang_jadi.no_urut,
                public.trn_gudang_jadi.no,
                public.trn_gudang_jadi.date,
                public.trn_gudang_jadi.status,
                public.trn_gudang_jadi.note,
                public.trn_gudang_jadi.created_at,
                public.trn_gudang_jadi.created_by,
                public.trn_gudang_jadi.updated_at,
                public.trn_gudang_jadi.updated_by,
                public.trn_gudang_jadi.color,
                public.trn_gudang_jadi.no_memo_repair,
                public.trn_gudang_jadi.no_memo_ganti_greige,
                public.trn_gudang_jadi.grade,
                public.trn_gudang_jadi.hasil_pemotongan,
                public.trn_gudang_jadi.dipotong,
                public.trn_gudang_jadi.trans_from,
                public.trn_gudang_jadi.id_from,
                public.trn_gudang_jadi.qr_code,
                public.trn_gudang_jadi.qr_code_desc,
                public.trn_gudang_jadi.qr_print_at,
                public.trn_gudang_jadi.locs_code,
                public.trn_gudang_jadi.status_packing,
                public.mst_greige.nama_kain
                FROM
                public.trn_gudang_jadi
                INNER JOIN public.trn_wo ON (public.trn_gudang_jadi.wo_id = public.trn_wo.id)
                INNER JOIN public.trn_sc ON (public.trn_wo.sc_id = public.trn_sc.id)
                INNER JOIN public.mst_customer ON (public.trn_sc.cust_id = public.mst_customer.id)
                INNER JOIN public.mst_greige ON (public.trn_wo.greige_id = public.mst_greige.id)
            WHERE
                trn_gudang_jadi.status = 3 AND 
                status_packing IS NULL AND 
                public.mst_customer.id = $cust_id
            ");

            if (count($data) < 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data item kosong!',
                    'data' => [],
                ], 200);
            } else {
                if ($data) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Berhasil menampilkan data item!',
                        'data' => $data,
                    ], 200);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Gagal mengambil data item! ',
                        'data' => [],
                    ], 200);
                }
            }
    
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data item! '.$th->getMessage(),
                'data' => [],
            ], 200);
        }
    }

    public function Insert(Request $request)
    {
        $ssql_master="a";
        try {

            DB::beginTransaction();
            $pck_date=$request->json()->get('pck_date');
            $pck_count=$request->json()->get('pck_count');
            $pck_locs_code_to=$request->json()->get('pck_locs_code_to');
            $pck_create_by=$request->json()->get('pck_create_by');

            $details = $request->json()->get('details');

            $cekDate = DB::SELECT("show datestyle");

            $dateStyle = substr($cekDate[0]->DateStyle, -3);

            try {
                $dateObj = Carbon::createFromFormat('d-m-Y', $pck_date);
            } catch (\Throwable $th) {
                $dateObj = Carbon::createFromFormat('d/m/Y', $pck_date);
            }

            $pck_code="";
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

            $ssql="SELECT COUNT (packing_code) AS count_rec, cast(MAX(RIGHT(packing_code,5)) as integer) + 1 AS id_rec FROM wms_packing_mstr
            Where left(packing_code,9)='PK-$year$month'";
            $kode = DB::select($ssql);

           
            $count=$kode[0]->count_rec;
            if ($count == 0) {
                $pck_code="PK-$year$month" . "00001";
            } else {
                $id_new=$kode[0]->id_rec;
                $formattedNumber = str_pad($id_new, 5, '0', STR_PAD_LEFT);
                $pck_code="PK-$year$month" . $formattedNumber;
            }


            $ssql="INSERT INTO 
                public.wms_packing_mstr
                (
                    packing_code,
                    packing_date,
                    packing_create_at,
                    packing_create_by,
                    packing_count,
                    packing_locs_code_to
                )
                VALUES (
                    '$pck_code',
                    '$formattedStartDate',
                    current_timestamp,
                    '$pck_create_by',
                    '$pck_count',
                    '$pck_locs_code_to'
                )";
            $ssql_master=$ssql;
            $insert = DB::INSERT($ssql);
           
    
            if ($insert) {
                if (count($details) > 0) {
                    foreach ($details as $det) {

                        $pckd_id_stok = $det['pckd_id_stok'];

                        $ssql="INSERT INTO 
                                public.wms_packing_dtl
                            (
                                packingd_packing_code,
                                packingd_id_stok
                            )
                            VALUES (
                                '$pck_code',
                                $pckd_id_stok
                            )";


                        $insertDetail = DB::INSERT($ssql);

                        $sql="UPDATE 
                            public.trn_gudang_jadi 
                        SET 
                            locs_code = '$pck_locs_code_to',
                            status_packing = 'Y' 
                        WHERE 
                            id = '$pckd_id_stok'";

                        $insertDetail = DB::UPDATE($sql);

                        if (!$insertDetail) {
                            DB::rollBack();
                            return response()->json([
                                'success' => false,
                                'message' => 'Gagal insert!',
                                'data' => [],
                            ], 200);
                        }

                    }
                }
            } else {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal insert!',
                    'data' => [],
                ], 200);
            }
            
            DB::commit();

            $dummy = DB::SELECT("SELECT * FROM wms_packing_mstr WHERE packing_code='$pck_code'");

            if (count($dummy) > 0) {
                $data = [];
                foreach ($dummy as $dum) {
                    $dummyd_det = DB::SELECT("SELECT * FROM wms_packing_dtl WHERE packingd_packing_code='$dum->packing_code'");
                    $dum->details = $dummyd_det;
                    array_push($data, $dum);
                }
                return response()->json([
                    'success' => true,
                    'message' => 'Berhasil insert data!',
                    'data' => $data,
                ], 200);
            } else {
                DB::rollBack();
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
                'message' => 'Gagal insert! '.$th->getMessage() . " " . $ssql_master,
                'data' => [],
            ], 200);
        }
    }
}