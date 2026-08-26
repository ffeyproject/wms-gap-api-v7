<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\UserModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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

    /**
     * FITUR BARU: Get List Customer untuk dropdown Create Header
     */
    public function GetCustomerList(Request $request)
    {
        try {
            $data = DB::SELECT("SELECT id, cust_no, name, address FROM public.mst_customer WHERE aktif IS NOT FALSE ORDER BY name ASC");

            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengambil daftar customer',
                'data' => $data
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil daftar customer: ' . $th->getMessage(),
                'data' => []
            ], 200);
        }
    }

    /**
     * FITUR BARU: Get Daftar Header Pengiriman ke Buyer
     */
    public function GetHeaders(Request $request)
    {
        try {
            $status = $request->input('status');
            $search = $request->input('search');

            $whereClauses = [];
            if ($status !== null && $status !== '') {
                $statusInt = (int)$status;
                $whereClauses[] = "h.status = $statusInt";
            }

            if (!empty($search)) {
                $searchSafe = addslashes($search);
                $whereClauses[] = "(h.nama_buyer ILIKE '%$searchSafe%' OR h.no ILIKE '%$searchSafe%' OR c.name ILIKE '%$searchSafe%')";
            }

            $whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

            $sql = "
                SELECT 
                    h.id,
                    h.customer_id,
                    h.date,
                    h.no_urut,
                    h.no,
                    h.status,
                    h.created_at,
                    h.created_by,
                    h.updated_at,
                    h.updated_by,
                    h.pengirim,
                    h.penerima,
                    h.kepala_gudang,
                    h.note,
                    h.nama_buyer,
                    h.alamat_buyer,
                    h.plat_nomor,
                    h.is_export,
                    h.is_resmi,
                    c.name as customer_name,
                    c.cust_no,
                    coalesce(count(distinct i.id), 0) as total_pcs,
                    coalesce(sum(i.qty), 0) as total_qty
                FROM public.trn_kirim_buyer_header h
                LEFT JOIN public.mst_customer c ON h.customer_id = c.id
                LEFT JOIN public.trn_kirim_buyer kb ON h.id = kb.header_id
                LEFT JOIN public.trn_kirim_buyer_item i ON kb.id = i.kirim_buyer_id
                $whereSql
                GROUP BY h.id, c.name, c.cust_no
                ORDER BY h.id DESC
                LIMIT 200
            ";

            $headers = DB::SELECT($sql);

            return response()->json([
                'success' => true,
                'message' => 'Berhasil menampilkan daftar header pengiriman!',
                'data' => $headers
            ], 200);

        } catch (\Throwable $th) {
            Log::error('GetHeaders Error: ' . $th->getMessage(), [
                'exception' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil daftar header pengiriman: ' . $th->getMessage(),
                'data' => []
            ], 200);
        }
    }

    /**
     * FITUR BARU: Create Header Pengiriman ke Buyer
     */
    public function CreateHeader(Request $request)
    {
        try {
            DB::beginTransaction();

            $customer_id   = $request->input('customer_id');
            $nama_buyer    = $request->input('nama_buyer');
            $alamat_buyer  = $request->input('alamat_buyer');
            $pengirim      = $request->input('pengirim');
            $penerima      = $request->input('penerima');
            $kepala_gudang = $request->input('kepala_gudang');
            $plat_nomor    = $request->input('plat_nomor');
            $date          = $request->input('date') ?: date('Y-m-d');
            $note          = $request->input('note');

            $is_export_raw = $request->input('is_export');
            $is_resmi_raw  = $request->input('is_resmi');

            $is_export = filter_var($is_export_raw, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
            $is_resmi  = ($is_resmi_raw !== null && !filter_var($is_resmi_raw, FILTER_VALIDATE_BOOLEAN)) ? 'false' : 'true';

            if (!$customer_id || !$nama_buyer || !$alamat_buyer || !$pengirim || !$penerima || !$kepala_gudang) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Mohon lengkapi field mandatory (customer_id, nama_buyer, alamat_buyer, pengirim, penerima, kepala_gudang)!',
                    'data' => []
                ], 200);
            }

            $userId = $request->get('user') ?? (Auth::user() ? Auth::user()->id : 1);
            $now = time();

            $platNomorVal    = !empty($plat_nomor) ? "'" . addslashes($plat_nomor) . "'" : "NULL";
            $noteVal         = !empty($note) ? "'" . addslashes($note) . "'" : "NULL";
            $namaBuyerVal    = addslashes($nama_buyer);
            $alamatBuyerVal  = addslashes($alamat_buyer);
            $pengirimVal     = addslashes($pengirim);
            $penerimaVal     = addslashes($penerima);
            $kepalaGudangVal = addslashes($kepala_gudang);

            $sql = "INSERT INTO public.trn_kirim_buyer_header (
                customer_id, date, status, created_at, created_by,
                pengirim, penerima, kepala_gudang, note,
                nama_buyer, alamat_buyer, plat_nomor, is_export, is_resmi
            ) VALUES (
                $customer_id, '$date', 1, $now, $userId,
                '$pengirimVal', '$penerimaVal', '$kepalaGudangVal', $noteVal,
                '$namaBuyerVal', '$alamatBuyerVal', $platNomorVal, $is_export, $is_resmi
            ) RETURNING id";

            $inserted = DB::SELECT($sql);
            $headerId = $inserted[0]->id;

            DB::commit();

            $headerData = DB::SELECT("SELECT * FROM public.trn_kirim_buyer_header WHERE id = $headerId")[0];

            return response()->json([
                'success' => true,
                'message' => 'Berhasil membuat Header Pengiriman ke Buyer!',
                'data' => $headerData
            ], 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat Header Pengiriman: ' . $th->getMessage(),
                'data' => []
            ], 200);
        }
    }

     /**
     * Scan Item barang yang mau dikirim untuk Header (Hanya yang berstatus Stock = 1)
     */
    public function ScanItem(Request $request)
    {
        try {
            DB::beginTransaction();
            $header_id = $request->input('header_id');
            $qr_code   = $request->input('qr_code');
            if (!$header_id || !$qr_code) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'header_id dan qr_code wajib diisi!',
                    'data' => []
                ], 200);
            }
            // Validasi header
            $header = DB::SELECT("SELECT * FROM public.trn_kirim_buyer_header WHERE id = $header_id");
            if (empty($header)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Header Pengiriman tidak ditemukan!',
                    'data' => []
                ], 200);
            }
            if ($header[0]->status == 2) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Header pengiriman ini sudah di-submit / diproses (Post)!',
                    'data' => []
                ], 200);
            }
            $qrCodeSafe = addslashes($qr_code);
            // Cari item gudang jadi
            $gudangJadi = DB::SELECT("SELECT * FROM public.trn_gudang_jadi WHERE qr_code = '$qrCodeSafe' LIMIT 1");
            
            if (empty($gudangJadi)) {
                $parts = explode('-', $qr_code);
                $type = $parts[0] ?? '';
                $idItem = $parts[2] ?? null;
                if (($type == 'INS' || $type == 'MKL' || $type == 'INS2') && $idItem && is_numeric($idItem)) {
                    if ($type == 'INS') {
                        $sqlItem = "SELECT b.qr_code FROM public.inspecting_item b WHERE b.id = $idItem";
                    } else {
                        $sqlItem = "SELECT b.qr_code FROM public.inspecting_mkl_bj_items b WHERE b.id = $idItem";
                    }
                    $resItem = DB::SELECT($sqlItem);
                    if (!empty($resItem) && isset($resItem[0]->qr_code)) {
                        $realQr = addslashes($resItem[0]->qr_code);
                        $gudangJadi = DB::SELECT("SELECT * FROM public.trn_gudang_jadi WHERE qr_code = '$realQr' LIMIT 1");
                    }
                }
            }
            if (empty($gudangJadi)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Item barang dengan QR Code ' . $qr_code . ' tidak ditemukan di Gudang Jadi!',
                    'data' => []
                ], 200);
            }
            $stock = $gudangJadi[0];
            $stockId = $stock->id;
            $woId = $stock->wo_id;
            // =========================================================================
            // VALIDASI UTAMA: HANYA BARANG BERSTATUS 1 (STOCK) YANG BISA DI-SCAN
            // =========================================================================
            if ($stock->status != 1) {
                DB::rollBack();
                $statusLabel = 'Bukan Stock';
                if ($stock->status == 3) {
                    // Cari Header ID & Nama Buyer di mana barang ini sudah di-scan
                    $headerInfo = DB::SELECT("
                        SELECT b.header_id, h.nama_buyer
                        FROM public.trn_kirim_buyer_item i
                        JOIN public.trn_kirim_buyer b ON i.kirim_buyer_id = b.id
                        JOIN public.trn_kirim_buyer_header h ON b.header_id = h.id
                        WHERE i.stock_id = $stockId
                        LIMIT 1
                    ");
                    if (!empty($headerInfo)) {
                        $existingHeaderId = $headerInfo[0]->header_id;
                        $buyerName = $headerInfo[0]->nama_buyer ?? '';
                        $statusLabel = "Siap Kirim (Sudah di-scan pada Header ID #$existingHeaderId - $buyerName)";
                    } else {
                        $statusLabel = "Siap Kirim (Sudah di-scan pada pengiriman lain)";
                    }
                } else if ($stock->status == 4) {
                    $statusLabel = 'Shipped / Sudah Terkirim';
                }
                return response()->json([
                    'success' => false,
                    'message' => 'Barang ' . $qr_code . ' tidak dapat di-scan! Status barang saat ini: ' . $statusLabel,
                    'data' => []
                ], 200);
            }
            // Cek apakah item sudah pernah di-scan pada header ini
            $cekItemEx = DB::SELECT("
                SELECT i.* FROM public.trn_kirim_buyer_item i
                JOIN public.trn_kirim_buyer b ON i.kirim_buyer_id = b.id
                WHERE b.header_id = $header_id AND i.stock_id = $stockId
            ");
            if (!empty($cekItemEx)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Item barang ' . $qr_code . ' sudah di-scan untuk pengiriman ini!',
                    'data' => []
                ], 200);
            }
            // Cari atau buat row trn_kirim_buyer untuk header_id & wo_id
            $kirimBuyer = DB::SELECT("SELECT * FROM public.trn_kirim_buyer WHERE header_id = $header_id AND wo_id = $woId LIMIT 1");
            
            if (empty($kirimBuyer)) {
                $unit = $stock->unit ?? 1;
                $sqlInsertKB = "INSERT INTO public.trn_kirim_buyer (header_id, wo_id, unit) VALUES ($header_id, $woId, $unit) RETURNING id";
                $insertedKB = DB::SELECT($sqlInsertKB);
                $kirimBuyerId = $insertedKB[0]->id;
            } else {
                $kirimBuyerId = $kirimBuyer[0]->id;
            }
            // Insert trn_kirim_buyer_item
            $qty = $stock->qty ?? 0;
            $sqlInsertItem = "INSERT INTO public.trn_kirim_buyer_item (kirim_buyer_id, stock_id, qty) VALUES ($kirimBuyerId, $stockId, $qty) RETURNING id";
            $insertedItem = DB::SELECT($sqlInsertItem);
            // UPDATE STATUS STOCK BARANG DI GUDANG JADI KE STATUS 3 (SIAP KIRIM)
            $now = time();
            $userId = 1;
            if ($request->get('user') && isset($request->get('user')->id)) {
                $userId = $request->get('user')->id;
            }
            DB::UPDATE("UPDATE public.trn_gudang_jadi SET status = 3, updated_at = $now, updated_by = $userId WHERE id = $stockId");
            DB::commit();
            // Total count scanned items
            $totalCount = DB::SELECT("
                SELECT count(i.id) as total_pcs, coalesce(sum(i.qty), 0) as total_qty
                FROM public.trn_kirim_buyer_item i
                JOIN public.trn_kirim_buyer b ON i.kirim_buyer_id = b.id
                WHERE b.header_id = $header_id
            ")[0];
            return response()->json([
                'success' => true,
                'message' => 'Item ' . $stock->qr_code . ' berhasil di-scan (Status: Siap Kirim)!',
                'data' => [
                    'scanned_item' => $stock,
                    'total_pcs' => $totalCount->total_pcs,
                    'total_qty' => $totalCount->total_qty,
                ]
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal scan item: ' . $th->getMessage(),
                'data' => []
            ], 200);
        }
    }

    /**
     * FITUR BARU: Get daftar item yang sudah di-scan untuk Header
     */
    public function GetItemsHeader(Request $request)
    {
        try {
            $header_id = $request->input('header_id');

            if (!$header_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'header_id wajib diisi!',
                    'data' => []
                ], 200);
            }

            $sql = "
                SELECT 
                    i.id as item_id,
                    i.stock_id,
                    i.qty,
                    g.qr_code,
                    g.grade,
                    g.locs_code,
                    g.wo_id,
                    w.no as no_wo,
                    b.header_id,
                    b.id as kirim_buyer_id
                FROM public.trn_kirim_buyer_item i
                JOIN public.trn_kirim_buyer b ON i.kirim_buyer_id = b.id
                JOIN public.trn_gudang_jadi g ON i.stock_id = g.id
                LEFT JOIN public.trn_wo w ON g.wo_id = w.id
                WHERE b.header_id = $header_id
                ORDER BY i.id DESC
            ";

            $items = DB::SELECT($sql);

            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengambil daftar item scan',
                'data' => $items
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil item: ' . $th->getMessage(),
                'data' => []
            ], 200);
        }
    }

     /**
     * Submit Header Pengiriman (Ubah status ke Status 2 / Proses Surat Jalan)
     */
    public function SubmitHeader(Request $request)
    {
        try {
            DB::beginTransaction();
            $header_id = $request->input('header_id');
            if (!$header_id) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'header_id wajib diisi!',
                    'data' => []
                ], 200);
            }
            $headerRes = DB::SELECT("SELECT * FROM public.trn_kirim_buyer_header WHERE id = $header_id");
            if (empty($headerRes)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Header Pengiriman tidak ditemukan!',
                    'data' => []
                ], 200);
            }
            $header = $headerRes[0];
            if ($header->status == 2) {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Header pengiriman sudah berstatus Proses Surat Jalan!',
                    'data' => [
                        'header' => $header,
                        'no_surat_jalan' => $header->no,
                        'status_label' => 'Proses Surat Jalan'
                    ]
                ], 200);
            }
            // Validasi minimal ada 1 item yang di-scan
            $cekItems = DB::SELECT("
                SELECT count(i.id) as total
                FROM public.trn_kirim_buyer_item i
                JOIN public.trn_kirim_buyer b ON i.kirim_buyer_id = b.id
                WHERE b.header_id = $header_id
            ");
            if (empty($cekItems) || $cekItems[0]->total == 0) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Belum ada barang/item yang di-scan untuk pengiriman ini! Harap scan barang terlebih dahulu.',
                    'data' => []
                ], 200);
            }
            // Generate nomor surat jalan (no & no_urut) jika belum ada
            $noSuratJalan = $header->no;
            $noUrut = $header->no_urut;
            if (empty($noSuratJalan)) {
                $ts = strtotime($header->date);
                $year = date("Y", $ts);
                $month = date("n", $ts);
                $yearTwoDigit = date("y", $ts);
                $monthTwoDigit = date("m", $ts);
                $ym = $year . '-' . $month;
                $lastData = DB::SELECT("
                    SELECT no_urut FROM public.trn_kirim_buyer_header
                    WHERE no_urut IS NOT NULL
                    AND (EXTRACT(YEAR FROM date) || '-' || EXTRACT(MONTH FROM date)) = '$ym'
                    ORDER BY no_urut DESC LIMIT 1
                ");
                $noUrut = (!empty($lastData) && isset($lastData[0]->no_urut)) ? ($lastData[0]->no_urut + 1) : 1;
                $noUrutFormatted = sprintf("%04d", $noUrut);
                $noSuratJalan = "{$yearTwoDigit}/{$monthTwoDigit}/{$noUrutFormatted}";
            }
            $now = time();
            $userId = 1;
            if ($request->get('user') && isset($request->get('user')->id)) {
                $userId = $request->get('user')->id;
            }
            // UPDATE STATUS KE 2 (PROSES SURAT JALAN) & SIMPAN NOMOR SURAT JALAN
            $sqlUpdateHeader = "
                UPDATE public.trn_kirim_buyer_header
                SET status = 2,
                    no_urut = $noUrut,
                    no = '$noSuratJalan',
                    updated_at = $now,
                    updated_by = $userId
                WHERE id = $header_id
            ";
            DB::UPDATE($sqlUpdateHeader);

            // UPDATE STATUS STOCK BARANG DI GUDANG JADI KE 4 (PROSES SURAT JALAN / SHIPPED)
            $sqlUpdateStock = "
                UPDATE public.trn_gudang_jadi
                SET status = 4, updated_at = $now, updated_by = $userId
                WHERE id IN (
                    SELECT i.stock_id
                    FROM public.trn_kirim_buyer_item i
                    JOIN public.trn_kirim_buyer b ON i.kirim_buyer_id = b.id
                    WHERE b.header_id = $header_id
                )
            ";
            DB::UPDATE($sqlUpdateStock);

            DB::commit();
            $updatedHeader = DB::SELECT("SELECT * FROM public.trn_kirim_buyer_header WHERE id = $header_id")[0];
            return response()->json([
                'success' => true,
                'message' => 'Berhasil submit! Status pengiriman berubah menjadi Proses Surat Jalan.',
                'data' => [
                    'header' => $updatedHeader,
                    'no_surat_jalan' => $noSuratJalan,
                    'status_label' => 'Proses Surat Jalan'
                ]
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal submit pengiriman: ' . $th->getMessage(),
                'data' => []
            ], 200);
        }
    }



     /**
     * FITUR BARU: Hapus Item Barang dari Header Pengiriman (Status Kembali Ke 1 / Stock)
     */
    public function DeleteItem(Request $request)
    {
        try {
            DB::beginTransaction();
            $header_id = $request->input('header_id');
            $item_id   = $request->input('item_id');
            $qr_code   = $request->input('qr_code');
            if (!$header_id || (!$item_id && !$qr_code)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'header_id dan item_id/qr_code wajib diisi!',
                    'data' => []
                ], 200);
            }
            // Cari stock_id dari item yang akan dihapus
            $stockId = null;
            if ($item_id) {
                $itemRes = DB::SELECT("SELECT stock_id FROM public.trn_kirim_buyer_item WHERE id = $item_id");
                if (!empty($itemRes)) {
                    $stockId = $itemRes[0]->stock_id;
                }
            } else {
                $qrCodeSafe = addslashes($qr_code);
                $stockRes = DB::SELECT("SELECT id FROM public.trn_gudang_jadi WHERE qr_code = '$qrCodeSafe'");
                if (!empty($stockRes)) {
                    $stockId = $stockRes[0]->id;
                }
            }
            if ($item_id) {
                $sqlDelete = "
                    DELETE FROM public.trn_kirim_buyer_item
                    WHERE id = $item_id
                    AND kirim_buyer_id IN (
                        SELECT id FROM public.trn_kirim_buyer WHERE header_id = $header_id
                    )
                ";
            } else {
                $qrCodeSafe = addslashes($qr_code);
                $sqlDelete = "
                    DELETE FROM public.trn_kirim_buyer_item
                    WHERE stock_id IN (
                        SELECT id FROM public.trn_gudang_jadi WHERE qr_code = '$qrCodeSafe'
                    )
                    AND kirim_buyer_id IN (
                        SELECT id FROM public.trn_kirim_buyer WHERE header_id = $header_id
                    )
                ";
            }
            $deleted = DB::DELETE($sqlDelete);
            if ($deleted > 0) {
                // FITUR BARU: KEMBALIKAN STATUS BARANG DI GUDANG JADI KE STATUS 1 (STOCK)
                if ($stockId) {
                    $now = time();
                    $userId = 1;
                    if ($request->get('user') && isset($request->get('user')->id)) {
                        $userId = $request->get('user')->id;
                    }
                    DB::UPDATE("UPDATE public.trn_gudang_jadi SET status = 1, updated_at = $now, updated_by = $userId WHERE id = $stockId");
                }
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Item barang berhasil dihapus dan status dikembalikan menjadi Stock!',
                    'data' => []
                ], 200);
            } else {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Item barang tidak ditemukan atau sudah terhapus!',
                    'data' => []
                ], 200);
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus item: ' . $th->getMessage(),
                'data' => []
            ], 200);
        }
    }


}