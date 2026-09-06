<?php

namespace App\Concerns;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * Restricts a note's rich-text HTML - produced client-side by the browser's
 * contentEditable + execCommand - to a small, safe formatting allowlist
 * (bold, bullet lists, inline text/background colors) before it is ever
 * persisted or re-rendered, since contentEditable output isn't trustworthy
 * as-is.
 */
trait SanitizesNoteHtml
{
    /**
     * @var array<int, string>
     */
    private const ALLOWED_TAGS = ['b', 'strong', 'i', 'em', 'ul', 'li', 'br', 'div', 'span'];

    /**
     * Tags removed entirely, together with their content.
     *
     * @var array<int, string>
     */
    private const REMOVED_TAGS = ['script', 'style', 'iframe', 'object', 'embed', 'template'];

    /**
     * @var array<int, string>
     */
    private const ALLOWED_STYLE_PROPERTIES = ['color', 'background-color'];

    protected function sanitizeNoteHtml(?string $html): ?string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return null;
        }

        $document = new DOMDocument;

        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $body = $document->getElementsByTagName('body')->item(0);

        if (! $body instanceof DOMElement) {
            return null;
        }

        $this->cleanChildren($body);

        $clean = '';

        foreach (iterator_to_array($body->childNodes) as $child) {
            $clean .= $document->saveHTML($child);
        }

        $clean = trim($clean);

        if ($clean === '' || trim(strip_tags($clean)) === '') {
            return null;
        }

        return $clean;
    }

    private function cleanChildren(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMText) {
                continue;
            }

            if (! $child instanceof DOMElement) {
                $node->removeChild($child);

                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::REMOVED_TAGS, true)) {
                $node->removeChild($child);

                continue;
            }

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                $this->cleanChildren($child);
                $this->unwrap($child);

                continue;
            }

            $this->cleanAttributes($child);
            $this->cleanChildren($child);
        }
    }

    private function unwrap(DOMElement $element): void
    {
        $parent = $element->parentNode;

        if ($parent === null) {
            return;
        }

        foreach (iterator_to_array($element->childNodes) as $child) {
            $parent->insertBefore($child, $element);
        }

        $parent->removeChild($element);
    }

    private function cleanAttributes(DOMElement $element): void
    {
        foreach (iterator_to_array($element->attributes) as $attribute) {
            if ($attribute->name !== 'style') {
                $element->removeAttribute($attribute->name);

                continue;
            }

            $safeStyle = $this->sanitizeStyle($attribute->value);

            if ($safeStyle === '') {
                $element->removeAttribute('style');
            } else {
                $element->setAttribute('style', $safeStyle);
            }
        }
    }

    private function sanitizeStyle(string $style): string
    {
        $declarations = [];

        foreach (explode(';', $style) as $declaration) {
            if (! str_contains($declaration, ':')) {
                continue;
            }

            [$property, $value] = array_map('trim', explode(':', $declaration, 2));
            $property = strtolower($property);

            if (! in_array($property, self::ALLOWED_STYLE_PROPERTIES, true)) {
                continue;
            }

            if (! preg_match('/^(#[0-9a-f]{3,8}|rgba?\([0-9.,%\s]+\)|[a-z]+)$/i', $value)) {
                continue;
            }

            $declarations[] = "{$property}: {$value}";
        }

        return implode('; ', $declarations);
    }
}
