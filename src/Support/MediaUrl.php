<?php

namespace CarlJanzell\FilamentPageBuilder\Support;

use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Turn a stored upload path into something an `<img>` can load.
 *
 * FileUpload stores a disk path (`pages/x.jpg`). Livewire's temporary URL and an
 * already-absolute path are left alone. Resolving at render time — not persist
 * time — keeps the stored JSON portable across hosts.
 */
class MediaUrl
{
    public static function public(?string $path, string $disk = 'public'): ?string
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        if (self::isAbsolute($path)) {
            return $path;
        }

        try {
            return Storage::disk($disk)->url($path);
        } catch (Throwable) {
            return $path;
        }
    }

    protected static function isAbsolute(string $path): bool
    {
        return str_starts_with($path, 'http://')
            || str_starts_with($path, 'https://')
            || str_starts_with($path, '//')
            || str_starts_with($path, 'data:')
            || str_starts_with($path, 'blob:');
    }
}
