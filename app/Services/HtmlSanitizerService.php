<?php

namespace App\Services;

class HtmlSanitizerService
{
    private const ALLOWED_TAGS = '<p><br><b><strong><i><em><u><ul><ol><li><a>';

    public function sanitize(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $clean = preg_replace('/<(script|style|iframe|object|embed|form)[^>]*>.*?<\/\1>/is', '', $html) ?? '';
        $clean = strip_tags($clean, self::ALLOWED_TAGS);
        $clean = preg_replace('/\s+on[a-z]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean) ?? $clean;
        $clean = preg_replace('/href\s*=\s*(["\'])\s*(?:javascript|data|vbscript):[^"\']*\1/i', 'href="#"', $clean) ?? $clean;

        return trim($clean);
    }
}
