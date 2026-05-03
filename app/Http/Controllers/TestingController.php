<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\UserModel;
use Illuminate\Support\Facades\Auth;

class TestingController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function getSesuatu()
    {
        try {

	    $sql = "SELECT * FROM mst_customer ORDER BY id DESC ";
            $data = DB::SELECT($sql);
            // $data = Auth::user();
            // echo $data;

            if ($data) {
                return response()->json([
                    'success' => true,
                    'message' => 'Berhasil menampilkan data!',
                    'data' => $data,
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menampilkan data!',
                    'data' => [],
                ], 200);
            }
        } catch (\Throwable$th) {
            return response()->json([
                'success' => false,
                'message' => 'Login Failed! '.$th->getMessage(),
                'data' => [],
            ], 400);
        }
    }
}
