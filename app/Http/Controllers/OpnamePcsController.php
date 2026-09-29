<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class OpnamePcsController extends Controller
{
    /**
     * Ekstrak nama warna dari string No. Lot jika warna diisi di lot (contoh: D2606/01738L -> D2606, atau ET 04 / COKLAT TUA -> COKLAT TUA)
     */
    public static function extractColorFromLot($lot)
    {
        if (empty($lot) || trim($lot) === '-') {
            return null;
        }
        return trim($lot);
    }

    /**
     * Get list data opname pcs dengan filter lokasi rak / kode opname
     */
    public function GetList(Request $request)
    {
        try {
            $locs_code   = $request->json('locs_code') ?? $request->input('locs_code');
            $opname_code = $request->json('opname_code') ?? $request->input('opname_code');
            $status      = $request->json('status') ?? $request->input('status');

            if (empty($locs_code) && empty($opname_code)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Silakan pilih rak terlebih dahulu untuk melihat data.',
                    'data'    => [],
                ], 200);
            }

            $query = DB::table('trn_gudang_jadi_opname_pcs as a')
                ->select(
                    'a.id',
                    DB::raw('COALESCE(a.id_trn_gudang_jadi, b.id) as id_trn_gudang_jadi'),
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

            // 1. Filter Lokasi / Rak Spesifik (PostgreSQL ILIKE pada tabel opname pcs)
            if (!empty($locs_code) && strtoupper(trim($locs_code)) != 'SEMUA') {
                $cleanLoc = trim($locs_code);
                $query->where(function($q) use ($cleanLoc) {
                    $q->where('a.locs_code', '=', $cleanLoc)
                      ->orWhere('a.locs_code', 'ILIKE', '%' . $cleanLoc . '%');
                });
            }

            // 2. Filter Kode Opname (Opsional)
            if (!empty($opname_code)) {
                $cleanOpnameCode = trim($opname_code);
                $query->where(function($q) use ($cleanOpnameCode) {
                    $q->where('a.opname_code', '=', $cleanOpnameCode)
                      ->orWhere('a.opname_code', 'ILIKE', '%' . $cleanOpnameCode . '%');
                });
            }

            // 3. Filter Status (Opsional)
            if ($status !== null && $status !== '') {
                $query->where('a.status', (int)$status);
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
            Log::error('OpnamePcsController GetList Error: ' . $th->getMessage() . "\n" . $th->getTraceAsString());
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
                    DB::raw('COALESCE(a.id_trn_gudang_jadi, b.id) as id_trn_gudang_jadi'),
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
            Log::error('OpnamePcsController GetDetail Error: ' . $th->getMessage() . "\n" . $th->getTraceAsString());
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
                $created_by = 1;
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
            $ins_type = '';

            if (preg_match('/STK-(\d+)/i', $qr_code, $stkMatches)) {
                $stock_id = (int)$stkMatches[1];
            }

            if (preg_match('/(INS2|INS|MKL)-\d+-(\d+)/i', $qr_code, $insMatches)) {
                $ins_type = strtoupper($insMatches[1]);
                $ins_item_id = (int)$insMatches[2];
            }

            $id_trn_gudang_jadi = null;
            $gudangJadi = null;

            // 1. Cari jika scan format STK-xxx
            if ($stock_id > 0) {
                $gudangJadi = DB::table('trn_gudang_jadi')->where('id', $stock_id)->first();
                if ($gudangJadi) {
                    $id_trn_gudang_jadi = $gudangJadi->id;
                }
            }

            // 1b. Cari berdasarkan qr_code jika belum ketemu
            if (!$id_trn_gudang_jadi) {
                $gudangJadi = DB::table('trn_gudang_jadi')
                    ->where('qr_code', $db_qr_code)
                    ->orWhere('qr_code', $qr_code)
                    ->first();
                if ($gudangJadi) {
                    $id_trn_gudang_jadi = $gudangJadi->id;
                }
            }

            // Ekstrak info dari barcode jika format teks pemisah '!'
            $wo_no = null;
            $color = null;
            $sourceStr = !empty($qr_code_desc) ? $qr_code_desc : $qr_code;
            if (strpos($sourceStr, '!') !== false) {
                $parts = explode('!', $sourceStr);
                if (isset($parts[1]) && !empty(trim($parts[1]))) {
                    $wo_no = trim($parts[1]);
                }
                if (isset($parts[4]) && !empty(trim($parts[4])) && trim($parts[4]) !== '-') {
                    $color = trim($parts[4]);
                }
                // Fallback jika color kosong atau '-', ambil dari parts[2] (No Lot / Design seperti D2606/01738L)
                if ((empty($color) || $color === '-') && isset($parts[2]) && !empty(trim($parts[2])) && trim($parts[2]) !== '-') {
                    $color = self::extractColorFromLot($parts[2]);
                }
                if (empty($join_piece) && isset($parts[5]) && !empty(trim($parts[5]))) {
                    $join_piece = trim($parts[5]);
                }
            }

            // Mapping Grade
            $letterToGrade = [
                'A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5,
                '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5,
            ];
            $upperGrade = strtoupper(trim((string)$grade));
            $db_grade = isset($letterToGrade[$upperGrade]) ? $letterToGrade[$upperGrade] : (is_numeric($grade) ? (int)$grade : 1);

            // Parsing QTY & Unit dari teks jika kosong
            if (empty($qty) || $qty == 0) {
                if (preg_match('/(\d+(?:\.\d+)?)\s*(YDS|YARD|YARDS|METER|M)/i', $sourceStr, $qtyMatches)) {
                    $qty = (float)$qtyMatches[1];
                    $parsedUnit = strtoupper($qtyMatches[2]);
                    if (in_array($parsedUnit, ['YARD', 'YDS', 'YARDS'])) {
                        $unit = 'YARDS';
                    } else if (in_array($parsedUnit, ['M', 'METER'])) {
                        $unit = 'METER';
                    }
                }
            }

            if (empty($unit)) {
                $unit = ($ins_type === 'INS2' || $ins_type === 'MKL') ? 'METER' : 'YARDS';
            }

            if (empty($qr_code_desc)) {
                $qr_code_desc = $qr_code;
            }

            if (empty($qty)) {
                $qty = 0;
            }

            $now = time();

            // Jika opname_code kosong, generate otomatis
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

            // AUTO-CREATE TRN_GUDANG_JADI jika belum ada id_trn_gudang_jadi
            if (!$id_trn_gudang_jadi) {
                $wo_id = null;

                // Cari WO ID dari nomor WO
                if (!empty($wo_no)) {
                    $wo = DB::table('trn_wo')->where('no', $wo_no)->first();
                    if ($wo) {
                        $wo_id = $wo->id;
                    }
                }

                // Fallback cari WO & Warna dari Inspecting
                if ($ins_item_id > 0) {
                    if ($ins_type === 'INS2' || $ins_type === 'MKL') {
                        $insData = DB::table('inspecting_mkl_bj_items as a')
                            ->join('inspecting_mkl_bj as b', 'a.inspecting_id', '=', 'b.id')
                            ->leftJoin('trn_wo_color as c', 'b.wo_color_id', '=', 'c.id')
                            ->leftJoin('trn_mo_color as d', 'c.mo_color_id', '=', 'd.id')
                            ->where('a.id', $ins_item_id)
                            ->select('b.wo_id', 'b.no_lot', 'd.color as color_name')
                            ->first();

                        if ($insData) {
                            if (!$wo_id) $wo_id = $insData->wo_id ?? null;
                            if ((empty($color) || $color === '-') && !empty($insData->color_name)) {
                                $color = $insData->color_name;
                            }
                            if ((empty($color) || $color === '-') && !empty($insData->no_lot)) {
                                $color = self::extractColorFromLot($insData->no_lot);
                            }
                        }
                    } else {
                        $insData = DB::table('inspecting_item as a')
                            ->join('trn_inspecting as b', 'a.inspecting_id', '=', 'b.id')
                            ->where('a.id', $ins_item_id)
                            ->select('b.wo_id', 'b.no_lot', 'b.kombinasi as color_name')
                            ->first();

                        if ($insData) {
                            if (!$wo_id) $wo_id = $insData->wo_id ?? null;
                            if ((empty($color) || $color === '-') && !empty($insData->color_name)) {
                                $color = $insData->color_name;
                            }
                            if ((empty($color) || $color === '-') && !empty($insData->no_lot)) {
                                $color = self::extractColorFromLot($insData->no_lot);
                            }
                        }
                    }
                }

                // Fallback cari warna dari WO jika masih kosong
                if ((empty($color) || $color === '-') && $wo_id) {
                    $woColor = DB::table('trn_wo_color as a')
                        ->join('trn_mo_color as b', 'a.mo_color_id', '=', 'b.id')
                        ->where('a.wo_id', $wo_id)
                        ->value('b.color');
                    if (!empty($woColor)) {
                        $color = $woColor;
                    }
                }

                if (empty($color)) {
                    $color = '-';
                }

                // Jika WO ditemukan, buat master stok di trn_gudang_jadi
                if ($wo_id) {
                    $source = ($ins_type === 'INS2' || $ins_type === 'MKL') ? 3 : 1;
                    $unitInt = (strtoupper(trim((string)$unit)) === 'METER' || strtoupper(trim((string)$unit)) === 'MTR' || $unit == 2) ? 2 : 1;

                    $id_trn_gudang_jadi = DB::table('trn_gudang_jadi')->insertGetId([
                        'jenis_gudang' => ($db_grade == 2) ? 2 : 1,
                        'wo_id'        => $wo_id,
                        'source'       => $source,
                        'source_ref'   => 'Opname ' . $opname_code,
                        'unit'         => $unitInt,
                        'qty'          => (float)$qty,
                        'date'         => date('Y-m-d'),
                        'status'       => 1, // STATUS_STOCK = 1
                        'note'         => 'Dibuat otomatis dari Stok Opname ' . $opname_code,
                        'color'        => mb_substr($color, 0, 255),
                        'grade'        => $db_grade,
                        'locs_code'    => mb_substr($locs_code, 0, 25),
                        'created_at'   => $now,
                        'created_by'   => $created_by ?: 1,
                        'updated_at'   => $now,
                        'updated_by'   => $created_by ?: 1,
                    ]);
                }
            } else {
                // Jika id_trn_gudang_jadi sudah ada, pastikan warna tidak strip/kosong
                if (empty($color) || $color === '-') {
                    $gj = DB::table('trn_gudang_jadi')->where('id', $id_trn_gudang_jadi)->first();
                    if ($gj) {
                        if (!empty($gj->color) && $gj->color !== '-') {
                            $color = $gj->color;
                        } elseif (!empty($gj->source_ref)) {
                            $lotFromIns = DB::table('trn_inspecting')->where('no', $gj->source_ref)->value('no_lot');
                            if (!$lotFromIns) {
                                $lotFromIns = DB::table('inspecting_mkl_bj')->where('no', $gj->source_ref)->value('no_lot');
                            }
                            if (!empty($lotFromIns) && trim($lotFromIns) !== '-') {
                                $color = self::extractColorFromLot($lotFromIns);
                            }
                        }
                    }
                }
            }

            // VALIDASI DUPLIKAT: Cek qr_code pada tabel opname pcs
            $existing = DB::table('trn_gudang_jadi_opname_pcs')
                ->where('qr_code', $db_qr_code)
                ->first();

            if ($existing) {
                $opnamedDate = '';
                if (!empty($existing->created_at)) {
                    if (is_numeric($existing->created_at)) {
                        $opnamedDate = date('d-m-Y H:i', (int)$existing->created_at);
                    } else {
                        try {
                            $opnamedDate = Carbon::parse($existing->created_at)->format('d-m-Y H:i');
                        } catch (\Throwable $t) {
                            $opnamedDate = (string)$existing->created_at;
                        }
                    }
                }

                $dateMsg = !empty($opnamedDate) ? " pada tanggal " . $opnamedDate : "";
                DB::rollBack();

                return response()->json([
                    'success'      => false,
                    'message'      => 'Stock (' . $db_qr_code . ') ini sudah pernah di-opname' . $dateMsg . '!',
                    'is_duplicate' => true,
                    'data'         => $existing,
                ], 200);
            }

            // Insert ke tabel trn_gudang_jadi_opname_pcs
            $id = DB::table('trn_gudang_jadi_opname_pcs')->insertGetId([
                'id_trn_gudang_jadi' => $id_trn_gudang_jadi,
                'opname_code'        => $opname_code,
                'qr_code'            => $db_qr_code,
                'qr_code_desc'       => $qr_code_desc,
                'qty'                => (float)$qty,
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

            // Update lokasi, status (OUT -> STOCK), & warna pada master gudang jadi jika ada relasinya
            if ($id_trn_gudang_jadi) {
                $updateGj = [
                    'locs_code'  => $locs_code,
                    'status'     => 1, // KEMBALI KE STATUS_STOCK = 1 (READY / STOCK)
                    'updated_at' => $now,
                    'updated_by' => $created_by
                ];
                if (!empty($color) && $color !== '-') {
                    $updateGj['color'] = mb_substr($color, 0, 255);
                }
                DB::table('trn_gudang_jadi')
                    ->where('id', $id_trn_gudang_jadi)
                    ->update($updateGj);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Berhasil menambahkan data opname pcs!',
                'data'    => [
                    'id'                  => $id,
                    'opname_code'         => $opname_code,
                    'qr_code'             => $qr_code,
                    'id_trn_gudang_jadi'  => $id_trn_gudang_jadi,
                    'locs_code'           => $locs_code,
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
     * Update data opname pcs & sinkronkan lokasi ke trn_gudang_jadi
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
                $updated_by = 1;
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

            $now = time();
            $updateData = [
                'updated_at' => $now,
                'updated_by' => $updated_by,
            ];

            if ($qty !== null) $updateData['qty'] = (float)$qty;
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

            // Jika lokasi diubah, update juga ke master trn_gudang_jadi
            if (!empty($opnamePcs->id_trn_gudang_jadi)) {
                $updateGj = [
                    'status'     => 1, // KEMBALI KE STATUS_STOCK = 1
                    'updated_at' => $now,
                    'updated_by' => $updated_by
                ];
                if (!empty($locs_code)) {
                    $updateGj['locs_code'] = $locs_code;
                }
                if ($qty !== null) {
                    $updateGj['qty'] = (float)$qty;
                }
                DB::table('trn_gudang_jadi')
                    ->where('id', $opnamePcs->id_trn_gudang_jadi)
                    ->update($updateGj);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Berhasil memperbarui data opname pcs!',
                'data'    => null,
            ], 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('OpnamePcsController Update Error: ' . $th->getMessage() . "\n" . $th->getTraceAsString());
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

            $opnamePcs = DB::table('trn_gudang_jadi_opname_pcs')->where('id', $id)->first();
            if (!$opnamePcs) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Data opname pcs tidak ditemukan!',
                    'data'    => null,
                ], 200);
            }

            DB::table('trn_gudang_jadi_opname_pcs')->where('id', $id)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Berhasil menghapus data opname pcs!',
                'data'    => null,
            ], 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('OpnamePcsController Delete Error: ' . $th->getMessage() . "\n" . $th->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data opname pcs: ' . $th->getMessage(),
                'data'    => null,
            ], 200);
        }
    }
}