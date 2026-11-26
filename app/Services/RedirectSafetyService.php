<?php

namespace App\Services;

class RedirectSafetyService
{
    public function sanitize(?string $redirectTo, string $fallback = '/'): string
    {
        if ($redirectTo === null) {
            return $fallback;
        }

        $trimmed = trim($redirectTo);
        if ($trimmed === '') {
            return $fallback;
        }

        // Only allow safe local relative paths starting with a single slash.
        if (! str_starts_with($trimmed, '/') || str_starts_with($trimmed, '//') || str_starts_with($trimmed, '/\\')) {
            return $fallback;
        }

        if (preg_match('/[\r\n\0]/', $trimmed) || str_contains($trimmed, '://')) {
            return $fallback;
        }

        $parsed = parse_url($trimmed);
        if ($parsed === false || isset($parsed['scheme']) || isset($parsed['host']) || isset($parsed['user'])) {
            return $fallback;
        }

        return $trimmed;
    }
}
