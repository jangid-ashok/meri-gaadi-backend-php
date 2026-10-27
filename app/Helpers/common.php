<?php
if (!function_exists('sanitizeInputArray')) {
    function sanitizeInputArray(array $input): array
    {
        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $input[$key] = sanitizeInputArray($value); // 🔁 recursive
            } elseif (is_string($value)) {
                $input[$key] = preg_replace('/\s+/', ' ', trim(strip_tags($value)));
            }
        }
        return $input;
    }
}

if (!function_exists('media_image_url')) {
    function media_image_url(?string $path, string $legacyDisk): ?string
    {
        if (! $path) return null;
        if (str_starts_with($path, 'images/') || str_starts_with($path, 'media/images/')) {
            return \Illuminate\Support\Facades\Storage::disk('media')->url($path);
        }
        return \Illuminate\Support\Facades\Storage::disk($legacyDisk)->url($path);
    }
}
