<?php

namespace App\Support;

/**
 * Minimal allow-list HTML sanitizer.
 *
 * Context: BlogPost::body (and similar rich-text fields) is rendered on
 * the public site with Blade's raw {!! !!} output, so admins can format
 * posts with real HTML instead of being limited to escaped plain text.
 * That means anyone who can write to that field — including a
 * lower-privileged staff account, or an attacker who compromises one —
 * can otherwise inject arbitrary <script>, event handlers, or
 * javascript: URLs that execute in every visitor's browser (stored XSS).
 *
 * This is a pragmatic allow-list filter, not a full HTML parser like
 * league/html-sanitizer or mews/purifier. Prefer swapping to one of
 * those (`composer require league/html-sanitizer`) when the environment
 * has Packagist access — this class is a dependency-free stopgap.
 */
class HtmlSanitizer
{
    /** Tags allowed to remain in the output (everything else is stripped). */
    private const ALLOWED_TAGS = '<p><br><b><strong><i><em><u><ul><ol><li>'
        . '<a><img><h1><h2><h3><h4><h5><h6><blockquote><span><div>'
        . '<table><thead><tbody><tr><td><th><hr>';

    /** Attributes allowed on any surviving tag. */
    private const ALLOWED_ATTRIBUTES = ['href', 'src', 'alt', 'title', 'class', 'target', 'rel'];

    public static function clean(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return $html;
        }

        // 1. Drop tags that aren't in the allow-list entirely (script,
        // style, iframe, form, object, svg, etc.), including their content
        // for the genuinely dangerous ones.
        $html = preg_replace('/<(script|style|iframe|object|embed|form|svg)\b[^>]*>.*?<\/\1>/is', '', $html) ?? $html;
        $html = strip_tags($html, self::ALLOWED_TAGS);

        // 2. Strip any attribute not on the allow-list, including all
        // on* event handlers (onclick, onerror, onload, ...).
        $html = preg_replace_callback('/<([a-z0-9]+)([^>]*)>/i', function (array $m) {
            [, $tag, $attrString] = $m;

            $cleanAttrs = '';
            if (preg_match_all('/([a-zA-Z-]+)\s*=\s*"([^"]*)"|([a-zA-Z-]+)\s*=\s*\'([^\']*)\'/', $attrString, $attrs, PREG_SET_ORDER)) {
                foreach ($attrs as $attr) {
                    $name = strtolower($attr[1] !== '' ? $attr[1] : $attr[3]);
                    $value = $attr[1] !== '' ? $attr[2] : $attr[4];

                    if (str_starts_with($name, 'on')) {
                        continue; // event handlers
                    }
                    if (! in_array($name, self::ALLOWED_ATTRIBUTES, true)) {
                        continue;
                    }
                    if (in_array($name, ['href', 'src'], true) && self::isDangerousUrl($value)) {
                        continue;
                    }

                    $cleanAttrs .= ' '.$name.'="'.htmlspecialchars($value, ENT_QUOTES).'"';
                }
            }

            return '<'.$tag.$cleanAttrs.'>';
        }, $html) ?? $html;

        return $html;
    }

    private static function isDangerousUrl(string $value): bool
    {
        $value = strtolower(trim($value));
        // Remove whitespace/control chars that can be used to obfuscate (e.g. " java\tscript:")
        $value = preg_replace('/\s+/', '', $value) ?? $value;

        if (str_starts_with($value, 'javascript:') || str_starts_with($value, 'vbscript:') || str_starts_with($value, 'data:')) {
            // Allow only safe data:image/* for raster images (jpeg/png/webp/gif). Block svg/xml/html which can carry script.
            if (str_starts_with($value, 'data:')) {
                // Allow data:image/jpeg, png, webp, gif, avif with base64
                $safeData = preg_match('#^data:image/(jpeg|jpg|png|webp|gif|avif);base64,#', $value);
                if ($safeData) {
                    return false;
                }
                return true; // all other data: URLs are dangerous (svg+xml, text/html, application/xhtml, etc.)
            }
            return true;
        }

        // Also block if url contains javascript: anywhere after encoding
        return false;
    }
}
