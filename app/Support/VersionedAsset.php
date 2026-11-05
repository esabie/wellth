<?php

namespace App\Support;

class VersionedAsset
{
    /**
     * Public asset URL with a filemtime query so browsers refetch after replace-in-place updates.
     */
    public static function url(string $path): string
    {
        $relative = ltrim($path, '/');
        $url = asset($relative);
        $fullPath = public_path($relative);

        if (! is_file($fullPath)) {
            return $url;
        }

        return $url.'?v='.filemtime($fullPath);
    }
}
