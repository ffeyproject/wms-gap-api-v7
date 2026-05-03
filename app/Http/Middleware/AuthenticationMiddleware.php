<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use App\Models\UserModel;
Use \Carbon\Carbon;

class AuthenticationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            $token = $request->header('Authorization');

            if (!$token || $token == "") {
                return response()->json([
                    'success'   => false,
                    'message'   => 'Unauthorized',
                ], 401);
            }
    
            $token = str_replace('Bearer ', '', $token);
    
            // Split token menjadi header, payload, dan signature
            list($header, $payload, $signature) = explode('.', $token);
    
            // Decode payload dari base64 ke JSON
            $payload = json_decode(base64_decode($payload));
            // Ambil waktu kadaluwarsa dari payload
            $expiration = $payload->expired;
            $id = $payload->sub;
            $name = $payload->name;
    
            // $tokenData = UserModel::where('verification_token', $token)
            //     ->where('username', $name)->where('token_expired', '>', Carbon::now())
            //     ->first();
    
            // if (!$tokenData) {
            //     return response()->json([
            //         'success'   => false,
            //         'message'   => 'Invalid Token 1',
            //         'data' => $tokenData
            //     ], 401);
            // }
    
            // Verifikasi signature
            $jwtSecret = env('JWT_SECRET');
            $expectedSignature = base64_encode(hash_hmac('sha256', 'header.payload', $jwtSecret, true));
    
            if ($signature !== $expectedSignature) {
                return response()->json([
                    'success'   => false,
                    'message'   => 'Invalid Token 2',
                ], 401);
            }
    
            // Verifikasi waktu kadaluwarsa
            if (Carbon::now() >= $expiration) {
                return response()->json([
                    'success'   => false,
                    'message'   => 'Token Expired!',
                    'now' => Carbon::now(),
                    'exp' => $expiration
                ], 401);
            }
    
            // Setel pengguna saat ini berdasarkan ID pengguna yang terkait dengan token
            // $request->user = $tokenData->id;
            // Tambahkan data pengguna ke dalam request untuk digunakan pada handler berikutnya
            $request->merge(['user' => $id]);
    
            return $next($request);
        } catch (\Throwable $th) {
            return response()->json([
                'success'   => false,
                'message'   => 'Unauthorized',
            ], 401);
        }
    }
}
