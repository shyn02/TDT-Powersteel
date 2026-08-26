<?php

namespace App\Support;

/**
 * Allow-list HTML sanitizer using DOMDocument (parser-based).
 *
 * Context: BlogPost::body is rendered raw in blog_detail.blade.php:37.
 * Previous implementation used strip_tags + regex which is bypassable via
 * control chars, entity encoding, and parser differentials (CWE-79, CWE-1333).
 * This version uses PHP's DOMDocument as a real HTML parser, matching the
 * audit's recommendation for a maintained parser-based sanitizer.
 * For production, prefer swapping to league/html-sanitizer when network
 * allows (`composer require league/html-sanitizer`) — this remains a
 * dependency-free but parser-based stopgap that is significantly stronger
 * than regex.
 */
class HtmlSanitizer
{
    private const ALLOWED_TAGS = [
        'p','br','b','strong','i','em','u','ul','ol','li',
        'a','img','h1','h2','h3','h4','h5','h6','blockquote','span','div',
        'table','thead','tbody','tr','td','th','hr',
    ];

    private const ALLOWED_ATTRIBUTES = ['href', 'src', 'alt', 'title', 'class', 'target', 'rel'];

    private const DANGEROUS_TAGS = ['script','style','iframe','object','embed','form','svg','math','link','meta','base','template'];

    public static function clean(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return $html;
        }

        // Remove dangerous tags with their content early (defense in depth)
        $html = preg_replace('/<(script|style|iframe|object|embed|form|svg|math)\b[^>]*>.*?<\/\1>/is', '', $html) ?? $html;

        libxml_use_internal_errors(true);
        $doc = new \DOMDocument('1.0', 'UTF-8');
        // Wrap in div to handle fragments
        $wrapped = '<div id="__tdt_wrap">'. $html .'</div>';
        // Use HTML_NOIMPLIED if available, but handle fallback
        $options = LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOCDATA | LIBXML_NONET;
        // Prevent XXE
        $doc->loadHTML('<?xml encoding="utf-8" ?>' . $wrapped, $options);
        libxml_clear_errors();

        $wrap = $doc->getElementById('__tdt_wrap');
        if (! $wrap) {
            return '';
        }

        // Collect all elements to process (reverse to safely remove)
        $all = [];
        $xpath = new \DOMXPath($doc);
        foreach ($xpath->query('//*') as $node) {
            $all[] = $node;
        }

        foreach (array_reverse($all) as $el) {
            if (! $el instanceof \DOMElement) continue;
            if ($el->getAttribute('id') === '__tdt_wrap') continue;

            $tag = strtolower($el->tagName);

            // Remove dangerous tags entirely
            if (in_array($tag, self::DANGEROUS_TAGS, true)) {
                $el->parentNode?->removeChild($el);
                continue;
            }

            // Strip disallowed tags but keep their text content (unwrap)
            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                // Unwrap: move children to parent
                $parent = $el->parentNode;
                if ($parent) {
                    while ($el->firstChild) {
                        $parent->insertBefore($el->firstChild, $el);
                    }
                    $parent->removeChild($el);
                }
                continue;
            }

            // Clean attributes for allowed tags
            // Collect to remove first
            $toRemove = [];
            foreach (iterator_to_array($el->attributes) as $attr) {
                $name = strtolower($attr->name);
                $value = $attr->value;

                if (str_starts_with($name, 'on')) {
                    $toRemove[] = $name;
                    continue;
                }
                if (! in_array($name, self::ALLOWED_ATTRIBUTES, true)) {
                    $toRemove[] = $name;
                    continue;
                }
                if (in_array($name, ['href', 'src'], true) && self::isDangerousUrl($value)) {
                    $toRemove[] = $name;
                    continue;
                }
                // Normalize and re-set to prevent entity tricks
                $el->setAttribute($name, htmlspecialchars_decode($value, ENT_QUOTES));
            }
            foreach ($toRemove as $n) {
                $el->removeAttribute($n);
            }

            // Force safe rel/target for external links
            if ($tag === 'a' && $el->hasAttribute('href')) {
                $href = $el->getAttribute('href');
                if (preg_match('#^https?://#i', $href)) {
                    $el->setAttribute('rel', 'noopener noreferrer');
                    if (! $el->hasAttribute('target')) {
                        $el->setAttribute('target', '_blank');
                    }
                }
            }

            // Ensure img has alt
            if ($tag === 'img' && ! $el->hasAttribute('alt')) {
                $el->setAttribute('alt', '');
            }
        }

        // Serialize innerHTML of wrapper
        $out = '';
        foreach ($wrap->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return $out;
    }

    private static function isDangerousUrl(string $value): bool
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/\s+/', '', $value) ?? $value;
        // Decode HTML entities that could hide javascript:
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = strtolower($value);

        if (str_starts_with($value, 'javascript:') || str_starts_with($value, 'vbscript:') || str_starts_with($value, 'data:')) {
            if (str_starts_with($value, 'data:')) {
                $safeData = preg_match('#^data:image/(jpeg|jpg|png|webp|gif|avif);base64,#', $value);
                if ($safeData) {
                    return false;
                }
                return true;
            }
            return true;
        }
        return false;
    }
}
