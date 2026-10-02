<?php

namespace App\Services;

/**
 * Minimal HTML -> Markdown converter for article bodies.
 *
 * No external dependency: walks the DOM and converts the block/inline
 * elements actually used by editorial content (headings, paragraphs,
 * lists, links, images, quotes, code). Anything unrecognised degrades
 * to its inner text.
 */
class HtmlToMarkdown
{
    public static function convert(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML(
            '<?xml encoding="utf-8"?>' . $html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $root = $doc->documentElement ?: $doc;

        // With LIBXML_HTML_NOIMPLIED a fragment has multiple top-level nodes,
        // so iterate the document's own children — not just documentElement's.
        $markdown = '';
        foreach ($doc->childNodes as $child) {
            $markdown .= self::node($child, true);
        }

        // Normalise: collapse 3+ newlines, trim trailing spaces per line.
        $markdown = preg_replace("/[ \t]+\n/", "\n", $markdown);
        $markdown = preg_replace("/\n{3,}/", "\n\n", $markdown);

        return trim($markdown);
    }

    public static function toText(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);

        return trim((string) $text);
    }

    protected static function block(\DOMNode $node): string
    {
        $out = '';
        foreach ($node->childNodes as $child) {
            $out .= self::node($child, true);
        }

        return $out;
    }

    protected static function inline(\DOMNode $node): string
    {
        $out = '';
        foreach ($node->childNodes as $child) {
            $out .= self::node($child, false);
        }

        // Collapse whitespace inside inline runs (but keep single spaces).
        return preg_replace('/[ \t\x{00A0}]+/u', ' ', $out);
    }

    protected static function node(\DOMNode $node, bool $blockContext): string
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            $text = $node->nodeValue ?? '';
            if ($blockContext) {
                // Text directly inside a block container: treat as its own paragraph.
                $text = trim(preg_replace('/\s+/u', ' ', $text));
                return $text === '' ? '' : "\n\n" . $text . "\n\n";
            }

            return preg_replace('/\s+/u', ' ', $text);
        }

        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return '';
        }

        /** @var \DOMElement $node */
        $tag = strtolower($node->tagName);

        switch ($tag) {
            case 'script':
            case 'style':
            case 'noscript':
            case 'iframe':
                return '';

            case 'h1':
            case 'h2':
            case 'h3':
            case 'h4':
            case 'h5':
            case 'h6':
                $level = (int) substr($tag, 1);
                $text = trim(self::inline($node));
                return $text === '' ? '' : "\n\n" . str_repeat('#', $level) . ' ' . $text . "\n\n";

            case 'p':
                $text = trim(self::inline($node));
                return $text === '' ? '' : "\n\n" . $text . "\n\n";

            case 'br':
                return "\n";

            case 'hr':
                return "\n\n---\n\n";

            case 'strong':
            case 'b':
                $text = trim(self::inline($node));
                return $text === '' ? '' : '**' . $text . '**';

            case 'em':
            case 'i':
                $text = trim(self::inline($node));
                return $text === '' ? '' : '*' . $text . '*';

            case 'a':
                $text = trim(self::inline($node));
                $href = trim($node->getAttribute('href'));
                if ($text === '') {
                    return '';
                }
                if ($href === '' || $href === $text) {
                    return $text;
                }

                return '[' . $text . '](' . $href . ')';

            case 'img':
                $alt = trim($node->getAttribute('alt'));
                $src = trim($node->getAttribute('src'));
                if ($src === '') {
                    return '';
                }

                return "\n\n![" . $alt . '](' . $src . ")\n\n";

            case 'ul':
                $items = self::listItems($node, '-');
                return $items === '' ? '' : "\n\n" . $items . "\n";

            case 'ol':
                $items = self::listItems($node, '1.');
                return $items === '' ? '' : "\n\n" . $items . "\n";

            case 'blockquote':
                $text = trim(self::inline($node));
                if ($text === '') {
                    return '';
                }
                $quoted = preg_replace('/^/m', '> ', $text);

                return "\n\n" . $quoted . "\n\n";

            case 'pre':
                $code = $node->textContent ?? '';
                $code = trim(str_replace("\r\n", "\n", $code));
                return $code === '' ? '' : "\n\n```\n" . $code . "\n```\n\n";

            case 'code':
                $code = trim($node->textContent ?? '');
                return $code === '' ? '' : '`' . str_replace('`', "'", $code) . '`';

            case 'figure':
            case 'div':
            case 'section':
            case 'article':
            case 'header':
            case 'footer':
            case 'main':
            case 'aside':
            case 'figcaption':
            case 'table':
            case 'tbody':
            case 'thead':
            case 'tr':
            case 'td':
            case 'th':
                return self::block($node);

            default:
                return $blockContext ? self::block($node) : self::inline($node);
        }
    }

    protected static function listItems(\DOMElement $list, string $bullet): string
    {
        $out = '';
        $index = 0;
        foreach ($list->childNodes as $child) {
            if ($child->nodeType !== XML_ELEMENT_NODE || strtolower($child->tagName) !== 'li') {
                continue;
            }
            $index++;
            $marker = $bullet === '1.' ? $index . '.' : $bullet;
            $text = trim(self::inline($child));
            if ($text !== '') {
                $out .= $marker . ' ' . $text . "\n";
            }
        }

        return $out;
    }
}
