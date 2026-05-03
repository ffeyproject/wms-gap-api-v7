<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\UserModel;
use Illuminate\Support\Facades\Auth;

class LocsController extends Controller
{

    public function getLocs(Request $request)
    {

        try {

            $locs_code=$request->json()->get('locs_code');

           
                $sql="SELECT 
                a.locs_code,
                a.locs_description,
                a.locs_active,
                a.locs_loc_id,
                b.loc_name
              FROM
                public.wms_locs_sub a
                INNER JOIN public.wms_loc_mstr b ON (a.locs_loc_id = b.loc_id)
              WHERE
                a.locs_active = 'Y' and a.locs_code ~~* '%$locs_code%'
              ORDER BY
                a.locs_code limit 100";
                $data = DB::SELECT($sql);
    
            if ($data) {
                return response()->json([
                    'success' => true,
                    'message' => 'Berhasil menampilkan data!',
                    'data' => $data,
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menampilkan data! ' . $sql,
                    'data' => [],
                ], 200);
            }
        } catch (\Throwable$th) {
            return response()->json([
                'success' => false,
                'message' => 'Login Failed! '.$th->getMessage(),
                'data' => [],
            ], 200);
        }
    }

}
