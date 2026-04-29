<?php

namespace App\Support;

use DOMDocument;
use DOMXPath;

/**
 * Strip script-like content from admin-authored HTML before storage.
 *
 * Used by the Pages module (Quill-edited bodies) and any other path
 * that stores HTML supplied by an admin user. Quill itself sanitises
 * what users *paste*, but the hidden form input accepts whatever is
 * POSTed — so a malicious admin (or anyone with stolen admin creds)
 * could hand-craft a payload like `<img src=x onerror=fetch(...)>`
 * and have it stored verbatim, then rendered to every visitor.
 *
 * This is a deliberately narrow allow-list approach: we let through
 * the formatting tags Quill produces, drop everything else, and strip
 * any attribute that browsers will execute as code. We avoid the
 * `mews/purifier` dependency on purpose — the policy here is small
 * enough to fit in one file and deploy without `composer install`.
 *
 * For a richer allow-list (e.g. to support tables, code blocks with
 * syntax classes, etc.) we'd switch to HTMLPurifier; until then this
 * covers the threat without enlarging the dep tree.
 */
class HtmlSanitizer
{
    /**
     * Tags allowed through. Anything not in this list gets unwrapped
     * (its text content is preserved, the tag is removed).
     */
    private const ALLOWED_TAGS = [
        'p', 'br', 'span', 'div', 'strong', 'b', 'em', 'i', 'u', 's', 'sub', 'sup',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'ul', 'ol', 'li',
        'blockquote', 'pre', 'code',
        'a', 'img',
        'hr',
        'table', 'thead', 'tbody', 'tr', 'th', 'td',
    ];

    /**
     * Per-tag allowed attributes. Anything not listed here is stripped.
     * No tag may keep `on*` handlers or `style`.
     */
    private const ALLOWED_ATTRS = [
        'a' => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'title', 'width', 'height'],
        'th' => ['colspan', 'rowspan', 'scope'],
        'td' => ['colspan', 'rowspan'],
        '*' => ['class'],  // allow Quill's `ql-align-*`, `ql-indent-*`, etc.
    ];

    /**
     * URI schemes allowed in href / src. Everything else (including
     * `javascript:`, `data:text/html`, `vbscript:`) is dropped — and
     * relative URLs and fragments still pass.
     */
    private const ALLOWED_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    public static function sanitize(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }
        $trimmed = trim($html);
        if ($trimmed === '') {
            return $trimmed;
        }

        $dom = new DOMDocument('1.0', 'UTF-8');

        // Suppress warnings from non-well-formed input — Quill emits
        // valid HTML but defensive parsing is cheap. The wrapper tags
        // are stripped after parsing.
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="UTF-8"><div id="jambo-sanitize-root">' . $trimmed . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $dom->getElementById('jambo-sanitize-root');
        if (!$root) {
            return '';
        }

        self::cleanNode($dom, $root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }
        return $out;
    }

    private static function cleanNode(DOMDocument $dom, \DOMNode $node): void
    {
        // Walk a stable copy of the children — modifications during
        // iteration would skip elements.
        $children = iterator_to_array($node->childNodes);

        foreach ($children as $child) {
            if ($child instanceof \DOMComment) {
                $node->removeChild($child);
                continue;
            }

            if (!($child instanceof \DOMElement)) {
                // Text nodes, etc. — leave alone.
                continue;
            }

            $tag = strtolower($child->nodeName);

            // Hostile tags: drop the entire subtree (including text).
            // <script>alert(1)</script> => gone, no text reflow.
            if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'link', 'meta', 'svg', 'math', 'form', 'input', 'button', 'textarea', 'select', 'frame', 'frameset', 'noscript'], true)) {
                $node->removeChild($child);
                continue;
            }

            // Disallowed tag (but not actively hostile): unwrap — keep
            // its children, drop the tag itself.
            if (!in_array($tag, self::ALLOWED_TAGS, true)) {
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }

            // Allowed tag — clean attributes, recurse.
            self::cleanAttributes($child, $tag);
            self::cleanNode($dom, $child);
        }
    }

    private static function cleanAttributes(\DOMElement $el, string $tag): void
    {
        $allowed = array_merge(
            self::ALLOWED_ATTRS['*'] ?? [],
            self::ALLOWED_ATTRS[$tag] ?? [],
        );

        // Walk a copy so removal doesn't desync the live attribute map.
        $attrs = iterator_to_array($el->attributes);
        foreach ($attrs as $attr) {
            $name = strtolower($attr->nodeName);

            if (!in_array($name, $allowed, true)) {
                $el->removeAttributeNode($attr);
                continue;
            }

            // URI attributes — gate the scheme.
            if (in_array($name, ['href', 'src'], true)) {
                if (!self::isSafeUri((string) $attr->nodeValue)) {
                    $el->removeAttributeNode($attr);
                    continue;
                }
            }
        }

        // Force `target=_blank` links to ship `rel="noopener"` so the
        // opened page can't access window.opener.
        if ($tag === 'a' && strtolower((string) $el->getAttribute('target')) === '_blank') {
            $rel = trim((string) $el->getAttribute('rel'));
            if (!str_contains(strtolower($rel), 'noopener')) {
                $el->setAttribute('rel', trim($rel . ' noopener noreferrer'));
            }
        }
    }

    private static function isSafeUri(string $value): bool
    {
        $trimmed = ltrim($value);
        if ($trimmed === '') {
            return true;
        }
        // Relative URL or fragment — fine.
        if (str_starts_with($trimmed, '/') || str_starts_with($trimmed, '#') || str_starts_with($trimmed, '?')) {
            return true;
        }
        // `data:image/...` is allowed only for inline images via <img src>.
        // `data:text/html` and friends are blocked. Keep this conservative —
        // the whole content pipeline doesn't actually need data URIs.
        if (preg_match('/^[a-z][a-z0-9+.\-]*:/i', $trimmed, $m)) {
            $scheme = strtolower(rtrim($m[0], ':'));
            return in_array($scheme, self::ALLOWED_SCHEMES, true);
        }
        // No scheme, no leading slash — assume relative.
        return true;
    }
}
