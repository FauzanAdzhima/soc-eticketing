<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Support\Str;

class ReportHtmlSanitizer
{
    /**
     * @var array<string, array<int, string>>
     */
    private const ALLOWED_TAGS = [
        'p' => [],
        'br' => [],
        'h1' => [],
        'h2' => [],
        'h3' => [],
        'h4' => [],
        'blockquote' => [],
        'code' => [],
        'pre' => [],
        'strong' => [],
        'em' => [],
        'u' => [],
        's' => [],
        'ul' => [],
        'ol' => [],
        'li' => [],
        'a' => ['href', 'target', 'rel'],
        'img' => ['src', 'alt', 'title', 'width', 'height'],
        'span' => ['style'],
        'div' => ['style'],
    ];

    /**
     * @var array<int, string>
     */
    private const ALLOWED_STYLES = [
        'font-size',
        'color',
        'text-align',
    ];

    public static function sanitize(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $html = preg_replace('/<\s*(script|style)\b[^>]*>.*?<\s*\/\s*\1>/is', '', $html) ?? '';

        libxml_use_internal_errors(true);
        $doc = new DOMDocument('1.0', 'UTF-8');
        $wrappedHtml = '<!DOCTYPE html><html><body><div id="__report_root__">'.$html.'</div></body></html>';
        $doc->loadHTML($wrappedHtml);
        libxml_clear_errors();

        $xpath = new DOMXPath($doc);
        /** @var \DOMNodeList<DOMNode> $nodes */
        $nodes = $xpath->query('//div[@id="__report_root__"]//*');
        if ($nodes !== false) {
            $elements = [];
            foreach ($nodes as $node) {
                if ($node instanceof DOMElement) {
                    $elements[] = $node;
                }
            }

            foreach ($elements as $element) {
                self::sanitizeElement($element);
            }
        }

        $root = $xpath->query('//div[@id="__report_root__"]')->item(0);
        if (! $root instanceof DOMElement) {
            return '';
        }

        $sanitized = '';
        foreach ($root->childNodes as $child) {
            $sanitized .= $doc->saveHTML($child);
        }

        return trim($sanitized);
    }

    private static function sanitizeElement(DOMElement $element): void
    {
        $tag = strtolower($element->tagName);
        if (! array_key_exists($tag, self::ALLOWED_TAGS)) {
            self::unwrapElement($element);

            return;
        }

        $allowedAttrs = self::ALLOWED_TAGS[$tag];
        $toRemove = [];
        foreach ($element->attributes as $attribute) {
            $name = strtolower($attribute->name);
            if (Str::startsWith($name, 'on') || ! in_array($name, $allowedAttrs, true)) {
                $toRemove[] = $name;
            }
        }
        foreach ($toRemove as $name) {
            $element->removeAttribute($name);
        }

        if ($tag === 'a') {
            $href = (string) $element->getAttribute('href');
            if (! self::isSafeUrl($href, true)) {
                $element->removeAttribute('href');
            } else {
                $element->setAttribute('rel', 'noopener noreferrer');
                if ($element->getAttribute('target') === '_blank') {
                    $element->setAttribute('target', '_blank');
                } else {
                    $element->removeAttribute('target');
                }
            }
        }

        if ($tag === 'img') {
            $src = (string) $element->getAttribute('src');
            if (! self::isSafeUrl($src, false)) {
                $element->parentNode?->removeChild($element);

                return;
            }
        }

        if (in_array($tag, ['span', 'div'], true) && $element->hasAttribute('style')) {
            $style = self::sanitizeStyle((string) $element->getAttribute('style'));
            if ($style === '') {
                $element->removeAttribute('style');
            } else {
                $element->setAttribute('style', $style);
            }
        }
    }

    private static function unwrapElement(DOMElement $element): void
    {
        $parent = $element->parentNode;
        if ($parent === null) {
            return;
        }

        while ($element->firstChild !== null) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }

    private static function isSafeUrl(string $url, bool $allowRelative): bool
    {
        if ($url === '') {
            return false;
        }

        $normalized = strtolower(trim($url));
        if (Str::startsWith($normalized, ['javascript:', 'data:', 'vbscript:', 'file:'])) {
            return false;
        }

        if ($allowRelative && (Str::startsWith($normalized, '/') || Str::startsWith($normalized, '#'))) {
            return true;
        }

        return Str::startsWith($normalized, ['http://', 'https://']);
    }

    private static function sanitizeStyle(string $style): string
    {
        $rules = [];
        $parts = explode(';', $style);
        foreach ($parts as $part) {
            $pair = explode(':', $part, 2);
            if (count($pair) !== 2) {
                continue;
            }

            $prop = strtolower(trim($pair[0]));
            $value = trim($pair[1]);
            if (! in_array($prop, self::ALLOWED_STYLES, true)) {
                continue;
            }

            if ($value === '' || preg_match('/[<>"\']/', $value) === 1) {
                continue;
            }

            $rules[$prop] = $value;
        }

        $output = [];
        foreach ($rules as $prop => $value) {
            $output[] = $prop.': '.$value;
        }

        return implode('; ', $output);
    }
}
