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
