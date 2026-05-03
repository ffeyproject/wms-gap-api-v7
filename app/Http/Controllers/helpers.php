<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\UserModel;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

function ConvertDateStyle($date) {
    $cekDate = DB::SELECT("show datestyle");

    $dateStyle = substr($cekDate[0]->DateStyle, -3);

    try {
        $dateObj = Carbon::createFromFormat('d-m-Y', $date);
    } catch (\Throwable $th) {
        $dateObj = Carbon::createFromFormat('d/m/Y', $date);
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

    return $formattedStartDate;
}