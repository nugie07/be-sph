<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Carbon\Carbon;
use App\Helpers\ClientHelper;

class AuthValidator
{
    public static function validateTokenAndClient(Request $request)
    {
            $tokenString = $request->bearerToken();
        if (!$tokenString) {
            return response()->json(['message' => 'Token not provided'], 401);
        }

        $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($tokenString);
        if (!$accessToken) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        if ($accessToken->expires_at && Carbon::now()->greaterThan($accessToken->expires_at)) {
            $accessToken->delete();
            return response()->json(['message' => 'Token expired'], 401);
        }

        if (!ClientHelper::validateClientSecret($request)) {
            return response()->json(['message' => 'Unauthorized Client secret not matched'], 401);
        }

        // ✅ Tambahkan fallback user ID di sini
        $id = $request->input('id');
        if (!$id) {
            $userFromToken = $accessToken->tokenable; // Polymorphic relation dari Sanctum
            if (!$userFromToken) {
                return response()->json([
                    'message' => 'Unauthorized: No user found from Bearer and no ID provided'
                ], 401);
            }
            $id = $userFromToken->id;
        }

        // ✅ Return detail dalam array biar controller tinggal pakai
        return [
            'status' => true,
            'token'  => $accessToken,
            'id'     => $id
        ];
    }
}
