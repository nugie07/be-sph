<?php

namespace App\Helpers;

use Illuminate\Http\Request;

class ClientHelper
{
    public static function validateClientSecret(Request $request): bool
    {
        $incoming = (string) $request->header('X-Client-Secret');
        $expected = (string) config('app.client_secret');

        // Jika expected kosong, berarti config belum ter-setup
        if ($expected === '') {
            \Log::error('API_CLIENT_SECRET not set in config/app.php or .env');
            return false;
        }

        return hash_equals($expected, $incoming);
    }
}