<?php

use Illuminate\Support\Facades\Storage;

if (!function_exists('byteplus_url')) {

    /**
     * URL untuk file di disk BytePlus (TOS).
     * Jika bucket private & TOS_USE_PRESIGNED_URL=true: mengembalikan presigned URL (link sementara).
     * Jika bucket public atau TOS_USE_PRESIGNED_URL=false: mengembalikan URL langsung.
     *
     * @param string $path Path object di bucket (contoh: 'sph/xxx.pdf', 'logo/logo.png')
     * @param int|float|string|null $ttlMinutes Masa berlaku presigned URL dalam menit (default dari config). Diterima string dari env.
     * @return string URL untuk akses file (langsung atau presigned)
     */
    function byteplus_url(string $path, int|float|string|null $ttlMinutes = null): string
    {
        $disk = Storage::disk('byteplus');
        $usePresigned = filter_var(config('filesystems.disks.byteplus.use_presigned_url', true), FILTER_VALIDATE_BOOLEAN);
        $ttlRaw = $ttlMinutes ?? config('filesystems.disks.byteplus.presigned_ttl_minutes', 60);
        $ttl = (int) (is_numeric($ttlRaw) ? $ttlRaw : 60);

        if ($usePresigned && $ttl > 0) {
            return $disk->temporaryUrl($path, now()->addMinutes($ttl));
        }

        return $disk->url($path);
    }
}
