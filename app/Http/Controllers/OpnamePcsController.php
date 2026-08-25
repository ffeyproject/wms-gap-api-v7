<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class OpnamePcsController extends Controller
{
    /**
     * Get list data opname pcs (dengan filter tanggal, opname_code, & status opsional)
     */
    public function GetList(Request $request)
    {
        try {
            $opname_code = $request->json('opname_code') ?? $request->input('opname_code');
            $start_date  = $request->json('start_date') ?? $request->input('start_date');
            $end_date    = $request->json('end_date') ?? $request->input('end_date');
            $status      = $request->json('status') ?? $request->input('status');

            $query = DB::table('trn_gudang_jadi_opname_pcs as a')
                ->select(
                    'a.id',
                    'a.id_trn_gudang_jadi',
                    'a.opname_code',
                    'a.qr_code',
                    'a.qr_code_desc',
                    'a.qty',
                    'a.unit',
                    'a.grade',
                    'a.join_piece',
                    'a.locs_code',
                    'a.status',
                    'a.remark',
                    'a.created_at',
                    'a.created_by',
                    'a.updated_at',
                    'a.updated_by',
                    'b.locs_code as current_gudang_locs_code'
                )
                ->leftJoin('trn_gudang_jadi as b', 'a.id_trn_gudang_jadi', '=', 'b.id');

            if (!empty($opname_code)) {
                $cleanOpnameCode = trim($opname_code);
                $query->where(function($q) use ($cleanOpnameCode) {
                    $q->where('a.opname_code', '=', $cleanOpnameCode)
                      ->orWhere('a.opname_code', 'ILIKE', '%' . $cleanOpnameCode . '%');
                });
            }

            if (!empty($start_date) && !empty($end_date)) {
                $startDateObj = null;
                $endDateObj = null;
                $formats = ['d-m-Y', 'd/m/Y', 'Y-m-d', 'Y/m/d'];

                foreach ($formats as $fmt) {
                    try {
                        $startDateObj = Carbon::createFromFormat($fmt, $start_date)->startOfDay();
                        break;
                    } catch (\Throwable $th) {}
                }

                foreach ($formats as $fmt) {
                    try {
                        $endDateObj = Carbon::createFromFormat($fmt, $end_date)->endOfDay();
                        break;
                    } catch (\Throwable $th) {}
                }

                if ($startDateObj && $endDateObj) {
                    $query->whereBetween('a.created_at', [$startDateObj->timestamp, $endDateObj->timestamp]);
                }
            }

            $data = $query->orderBy('a.id', 'DESC')->get();

            $smallintToLetter = [
                1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', 5 => 'E',
                '1' => 'A', '2' => 'B', '3' => 'C', '4' => 'D', '5' => 'E',
            ];

            foreach ($data as $item) {
                if (isset($item->grade)) {
                    $g = (string)$item->grade;
                    $item->grade = isset($smallintToLetter[$g]) ? $smallintToLetter[$g] : $g;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengambil data opname pcs!',
                'data'    => $data,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data opname pcs: ' . $th->getMessage(),
                'data'    => [],
            ], 200);
        }
    }

    /**
     * Get detail data opname pcs berdasarkan ID
     */
    public function GetDetail(Request $request)
    {
        try {
            $id = $request->json()->get('id');

            if (!$id) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID wajib diisi!',
                    'data'    => null,
                ], 200);
            }

            $data = DB::table('trn_gudang_jadi_opname_pcs as a')
                ->select(
                    'a.*',
                    'b.locs_code as current_gudang_locs_code'
                )
                ->leftJoin('trn_gudang_jadi as b', 'a.id_trn_gudang_jadi', '=', 'b.id')
                ->where('a.id', $id)
                ->first();

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data opname pcs tidak ditemukan!',
                    'data'    => null,
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengambil detail opname pcs!',
                'data'    => $data,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail opname pcs: ' . $th->getMessage(),
                'data'    => null,
            ], 200);
        }
    }

    /**
     * Insert / Scan Opname Per Pcs
     */
    public function Insert(Request $request)
    {
        try {
            DB::beginTransaction();

            $opname_code  = $request->json('opname_code') ?? $request->input('opname_code');
            $qr_code      = $request->json('qr_code') ?? $request->input('qr_code');
            $qr_code_desc = $request->json('qr_code_desc') ?? $request->input('qr_code_desc');
            $qty          = $request->json('qty') ?? $request->input('qty');
            $unit         = $request->json('unit') ?? $request->input('unit');
            $grade        = $request->json('grade') ?? $request->input('grade');
            $join_piece   = $request->json('join_piece') ?? $request->input('join_piece');
            $locs_code    = $request->json('locs_code') ?? $request->input('locs_code') ?? 'TRANSIT';
            $status       = $request->json('status') ?? $request->input('status') ?? '1';
            $remark       = $request->json('remark') ?? $request->input('remark');
            $created_by   = $request->json('created_by') ?? $request->input('created_by') ?? $request->json('user_id') ?? $request->input('user_id') ?? 1;
            if (!is_numeric($created_by)) {
                $created_by = null;
            }

            if (empty($qr_code)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'qr_code wajib diisi!',
                    'data'    => null,
                ], 200);
            }

            // Sanitasi QR Code dari karakter newline/carriage return
            $qr_code = trim(str_replace(["\r", "\n", "\0"], '', $qr_code));
            $db_qr_code = mb_substr($qr_code, 0, 100);

            // Extract IDs & prefixes dari QR code string
            $stock_id = 0;
            $ins_item_id = 0;
            $cleanQrCode = null;

            if (preg_match('/STK-(\d+)/i', $qr_code, $stkMatches)) {
                $stock_id = (int)$stkMatches[1];
            }

            if (preg_match('/INS-\d+-(\d+)/i', $qr_code, $insMatches)) {
                $ins_item_id = (int)$insMatches[1];
            }

            if (preg_match('/\[?([A-Z0-9]+-\d+(?:-\d+)?)\]?/i', $qr_code, $cleanMatches)) {
                $cleanQrCode = $cleanMatches[1];
            }

            // Ultra-fast indexed lookup for trn_gudang_jadi
            $gudangJadi = null;

            if ($stock_id > 0) {
                $gudangJadi = DB::table('trn_gudang_jadi')->where('id', $stock_id)->first();
            }

            if (!$gudangJadi && $ins_item_id > 0) {
                $gudangJadi = DB::table('trn_gudang_jadi')->where('source_ref', (string)$ins_item_id)->first();
            }

            if (!$gudangJadi && !empty($cleanQrCode)) {
                $gudangJadi = DB::table('trn_gudang_jadi')->where('qr_code', $cleanQrCode)->first();
            }

            if (!$gudangJadi) {
                $gudangJadi = DB::table('trn_gudang_jadi')->where('qr_code', $db_qr_code)->first();
            }

            // Fallback for INS scanning if not present in trn_gudang_jadi
            if (!$gudangJadi && $ins_item_id > 0) {
                $insItem = DB::table('inspecting_item')->where('id', $ins_item_id)->first();
                if ($insItem) {
                    $gudangJadi = (object)[
                        'id'           => null,
                        'qr_code_desc' => $insItem->qr_code ?? $qr_code,
                        'qty'          => $insItem->qty ?? 0,
                        'unit'         => 'YARDS',
                        'grade'        => $insItem->grade ?? 1,
                    ];
                }
            }

            $id_trn_gudang_jadi = ($gudangJadi && !empty($gudangJadi->id)) ? $gudangJadi->id : null;

            // =========================================================================
            // 🛑 VALIDASI DUPLIKAT DI SINI: Cek qr_code atau id_trn_gudang_jadi di DB
            // =========================================================================
            $existingQuery = DB::table('trn_gudang_jadi_opname_pcs')
                ->where(function($q) use ($db_qr_code, $id_trn_gudang_jadi) {
                    $q->where('qr_code', $db_qr_code);
                    if ($id_trn_gudang_jadi) {
                        $q->orWhere('id_trn_gudang_jadi', $id_trn_gudang_jadi);
                    }
                });

            $existing = $existingQuery->first();

            if ($existing) {
                DB::rollBack();
                return response()->json([
                    'success'      => false,
                    'message'      => 'Stock ini sudah pernah di opname!',
                    'is_duplicate' => true,
                    'data'         => $existing,
                ], 200);
            }

            // Jika opname_code kosong, baru generate otomatis
            if (empty($opname_code)) {
                $latest = DB::table('trn_gudang_jadi_opname_pcs')
                    ->where('opname_code', 'ILIKE', 'OPN-PCS-%')
                    ->orderBy('id', 'desc')
                    ->value('opname_code');

                $nextNum = 1;
                if ($latest && preg_match('/OPN-PCS-(\d+)/i', $latest, $m)) {
                    $nextNum = ((int)$m[1]) + 1;
                }
                $opname_code = sprintf('OPN-PCS-%03d', $nextNum);
            }
            
            $letterToGrade = [
                'A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5,
                '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5,
            ];

            if ($gudangJadi) {
                if (empty($qr_code_desc) && isset($gudangJadi->qr_code_desc)) {
                    $qr_code_desc = $gudangJadi->qr_code_desc;
                }
                if ((empty($qty) || $qty == 0) && isset($gudangJadi->qty)) {
                    $qty = $gudangJadi->qty;
                }
                if (empty($unit) && isset($gudangJadi->unit)) {
                    $unit = $gudangJadi->unit;
                }
                if (isset($gudangJadi->grade)) {
                    $grade = $gudangJadi->grade;
                }
            }

            // Convert request grade to SmallInt for PostgreSQL
            $upperGrade = strtoupper(trim((string)$grade));
            $db_grade = isset($letterToGrade[$upperGrade]) ? $letterToGrade[$upperGrade] : (is_numeric($grade) ? (int)$grade : 1);

            // Fallback parsing QTY & Unit dari teks QR Code (misal: "10YDS/9.1M") jika QTY masih 0
            if (empty($qty) || $qty == 0) {
                if (preg_match('/(\d+(?:\.\d+)?)\s*(YDS|YARD|YARDS|METER|M)/i', $qr_code, $qtyMatches)) {
                    $qty = (float)$qtyMatches[1];
                    $parsedUnit = strtoupper($qtyMatches[2]);
                    if (in_array($parsedUnit, ['YARD', 'YDS', 'YARDS'])) {
                        $unit = 'YARDS';
                    } else if (in_array($parsedUnit, ['M', 'METER'])) {
                        $unit = 'METER';
                    }
                }
            }

            if (empty($qr_code_desc)) {
                $qr_code_desc = $qr_code;
            }

            if (empty($qty)) {
                $qty = 0;
            }

            $now = time();

            // Insert data baru
            $id = DB::table('trn_gudang_jadi_opname_pcs')->insertGetId([
                'id_trn_gudang_jadi' => $id_trn_gudang_jadi,
                'opname_code'        => $opname_code,
                'qr_code'            => $db_qr_code,
                'qr_code_desc'       => $qr_code_desc,
                'qty'                => $qty,
                'unit'               => $unit,
                'grade'              => $db_grade,
                'join_piece'         => $join_piece,
                'locs_code'          => $locs_code,
                'status'             => 1, // 1 = Draft / Submitted
                'remark'             => $remark,
                'created_at'         => $now,
                'created_by'         => $created_by,
                'updated_at'         => $now,
                'updated_by'         => $created_by,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Berhasil menambahkan data opname pcs!',
                'data'    => [
                    'id'                  => $id,
                    'opname_code'         => $opname_code,
                    'qr_code'             => $qr_code,
                    'id_trn_gudang_jadi'  => $id_trn_gudang_jadi,
                ],
            ], 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('OpnamePcsController Insert Error: ' . $th->getMessage() . "\n" . $th->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan opname pcs: ' . $th->getMessage(),
                'data'    => null,
            ], 200);
        }
    }

    /**
     * Update data opname pcs
     */
    public function Update(Request $request)
    {
        try {
            DB::beginTransaction();

            $id           = $request->json()->get('id');
            $qty          = $request->json()->get('qty');
            $unit         = $request->json()->get('unit');
            $grade        = $request->json()->get('grade');
            $join_piece   = $request->json()->get('join_piece');
            $locs_code    = $request->json()->get('locs_code');
            $status       = $request->json()->get('status');
            $remark       = $request->json()->get('remark');
            $updated_by   = $request->json()->get('updated_by');
            if (!is_numeric($updated_by)) {
                $updated_by = null;
            }

            if (!$id) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'ID wajib diisi!',
                    'data'    => null,
                ], 200);
            }

            $opnamePcs = DB::table('trn_gudang_jadi_opname_pcs')->where('id', $id)->first();
            if (!$opnamePcs) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Data opname pcs tidak ditemukan!',
                    'data'    => null,
                ], 200);
            }

            $updateData = [
                'updated_at' => time(),
                'updated_by' => $updated_by,
            ];

            if ($qty !== null) $updateData['qty'] = $qty;
            if ($unit !== null) $updateData['unit'] = $unit;
            if ($grade !== null) {
                $letterToGrade = [
                    'A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5,
                    '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5,
                ];
                $upperGrade = strtoupper(trim((string)$grade));
                $updateData['grade'] = isset($letterToGrade[$upperGrade]) ? $letterToGrade[$upperGrade] : (is_numeric($grade) ? (int)$grade : 1);
            }
            if ($join_piece !== null) $updateData['join_piece'] = $join_piece;
            if ($locs_code !== null) $updateData['locs_code'] = $locs_code;
            if ($status !== null) $updateData['status'] = $status;
            if ($remark !== null) $updateData['remark'] = $remark;

            DB::table('trn_gudang_jadi_opname_pcs')
                ->where('id', $id)
                ->update($updateData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Berhasil memperbarui data opname pcs!',
                'data'    => null,
            ], 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui opname pcs: ' . $th->getMessage(),
                'data'    => null,
            ], 200);
        }
    }

    /**
     * Delete data opname pcs
     */
    public function Delete(Request $request)
    {
        try {
            DB::beginTransaction();

            $id = $request->json()->get('id');

            if (!$id) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'ID wajib diisi!',
                    'data'    => null,
                ], 200);
            }

            $deleted = DB::table('trn_gudang_jadi_opname_pcs')->where('id', $id)->delete();

            if ($deleted) {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Berhasil menghapus data opname pcs!',
                    'data'    => null,
                ], 200);
            } else {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Data opname pcs tidak ditemukan!',
                    'data'    => null,
                ], 200);
            }

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus opname pcs: ' . $th->getMessage(),
                'data'    => null,
            ], 200);
        }
    }

    /**
     * Get next auto-increment opname_code (e.g. OPN-PCS-001, OPN-PCS-002, ...)
     */
    public function GetNextOpnameCode(Request $request)
    {
        try {
            $codes = DB::table('trn_gudang_jadi_opname_pcs')
                ->whereNotNull('opname_code')
                ->pluck('opname_code');

            $maxNumber = 0;

            foreach ($codes as $code) {
                if (preg_match('/OPN-PCS-(\d+)/i', $code, $m)) {
                    $num = (int)$m[1];
                    if ($num > $maxNumber) {
                        $maxNumber = $num;
                    }
                }
            }

            $nextNumber = $maxNumber + 1;
            $nextCode = sprintf("OPN-PCS-%03d", $nextNumber);

            Log::info("GetNextOpnameCode: maxNumber=$maxNumber, nextCode=$nextCode");

            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengambil kode opname berikutnya!',
                'data'    => $nextCode,
            ], 200);

        } catch (\Throwable $th) {
            Log::error("GetNextOpnameCode Error: " . $th->getMessage());
            return response()->json([
                'success' => true,
                'message' => 'Default kode opname',
                'data'    => 'OPN-PCS-001',
            ], 200);
        }
    }
}
