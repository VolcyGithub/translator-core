<?php

namespace Volcy\Translator\Drivers;

use DOMDocument;
use DOMElement;
use DOMNode;
use Volcy\Translator\Contracts\DocumentDriver;
use Volcy\Translator\Contracts\IdStrategy;

class BladeDriver implements DocumentDriver
{
    public function __construct(protected IdStrategy $idStrategy)
    {
    }

    public function name(): string
    {
        return 'blade';
    }

    public function extensions(): array
    {
        return ['blade.php'];
    }

    public function supports(string $path): bool
    {
        return str_ends_with(strtolower($path), '.blade.php');
    }

    public function index(string $path, string $content): array
    {
        $items = array_merge(
            $this->extractBladeHelpers($path, $content),
            $this->extractPhpArrays($path, $content),
        );

        $document = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="utf-8" ?>' . $this->prepareDomContent($content),
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $this->walkDom($document, $path, [], $items);

        return [
            'driver' => $this->name(),
            'items' => array_values($this->deduplicate($items)),
        ];
    }

    protected function prepareDomContent(string $content): string
    {
        $patterns = [
            '/{{--.*?--}}/s',
            '/@php\b.*?@endphp/s',
            '/\{\{.*?\}\}/s',
            '/\{!!.*?!!\}/s',
            '/@(?:if|elseif|else|endif|foreach|endforeach|for|endfor|forelse|endforelse|while|endwhile|switch|case|break|default|endswitch|isset|endisset|empty|endempty|auth|endauth|guest|endguest|production|endproduction|unless|endunless|class|style|once|endonce|push|endpush|prepend|endprepend|section|endsection|show|yield|extends|include|component|endcomponent|slot|endslot|error|enderror|fragment|endfragment|verbatim|endverbatim)(?:\s*\([^)]*\))?/i',
        ];

        return preg_replace($patterns, ' ', $content) ?? $content;
    }

    protected function extractBladeHelpers(string $path, string $content): array
    {
        $items = [];

        $patterns = [
            '/(?:__|trans|trans_choice)\(\s*(?:\'([^\']+)\'|"([^"]+)")/s',
            '/@lang\(\s*(?:\'([^\']+)\'|"([^"]+)")/s',
        ];

        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);

            foreach ($matches[0] as $index => [$match, $offset]) {
                $text = $matches[1][$index][0] !== '' ? $matches[1][$index][0] : $matches[2][$index][0];

                if (! isset($text) || trim($text) === '') {
                    continue;
                }

                $item = [
                    'type' => 'php',
                    'text' => $text,
                    'path' => $path,
                    'tag_path' => 'blade > php',
                    'attribute' => null,
                    'line' => $this->positionFromOffset($offset, $content)['line'],
                    'column' => $this->positionFromOffset($offset, $content)['column'],
                ];
                
                $item['id'] = $this->idStrategy->generateId($item);
                $items[] = $item;
            }
        }

        return $items;
    }

    protected function walkDom(DOMNode $node, string $path, array $stack, array &$items): void
    {
        if ($node instanceof DOMElement) {
            if (in_array(strtolower($node->tagName), ['script', 'style'], true)) {
                return;
            }

            $stack[] = $node->tagName;

            foreach (['title', 'alt', 'placeholder', 'aria-label', 'aria-description', 'label'] as $attribute) {
                if (! $node->hasAttribute($attribute)) {
                    continue;
                }

                $value = trim($node->getAttribute($attribute));

                if ($value === '') {
                    continue;
                }

                $item = [
                    'type' => 'attribute',
                    'text' => $value,
                    'path' => $path,
                    'tag_path' => implode(' > ', $stack),
                    'attribute' => $attribute,
                    'line' => null,
                    'column' => null,
                ];
                
                // Check for explicit ID attribute (data-i18n-attribute)
                $explicitId = $this->explicitIdAttribute($node, "data-i18n-{$attribute}");
                if ($explicitId !== null) {
                    $item['translation_id'] = $explicitId;
                }
                
                $item['id'] = $this->idStrategy->generateId($item);
                $items[] = $item;
            }
        }

        if ($node->nodeType === XML_TEXT_NODE) {
            $text = trim(preg_replace('/\s+/u', ' ', $node->nodeValue ?? '') ?? '');

            if ($text !== '' && preg_match('/[\pL\pN]/u', $text) === 1) {
                $item = [
                    'type' => 'text',
                    'text' => $text,
                    'path' => $path,
                    'tag_path' => implode(' > ', $stack),
                    'attribute' => null,
                    'line' => null,
                    'column' => null,
                ];
                
                // Check for explicit ID attribute on parent element (data-i18n)
                $explicitId = $node->parentNode instanceof DOMElement
                    ? $this->explicitIdAttribute($node->parentNode, 'data-i18n')
                    : null;
                if ($explicitId !== null) {
                    $item['translation_id'] = $explicitId;
                }
                
                $item['id'] = $this->idStrategy->generateId($item);
                $items[] = $item;
            }
        }

        foreach ($node->childNodes as $childNode) {
            $this->walkDom($childNode, $path, $stack, $items);
        }
    }

    protected function deduplicate(array $items): array
    {
        $unique = [];

        foreach ($items as $item) {
            $unique[$item['id']] = $item;
        }

        return $unique;
    }

    protected function explicitIdAttribute(DOMElement $node, string $attribute): ?string
    {
        if (! $node->hasAttribute($attribute)) {
            return null;
        }

        $value = trim($node->getAttribute($attribute));

        return $value === '' ? null : $value;
    }

    protected function positionFromOffset(int $offset, string $content): array
    {
        $before = substr($content, 0, $offset);
        $line = substr_count($before, "\n") + 1;
        $lastBreak = strrpos($before, "\n");
        $column = $lastBreak === false ? $offset + 1 : $offset - $lastBreak;

        return [
            'line' => $line,
            'column' => $column,
        ];
    }

    protected function extractPhpArrays(string $path, string $content): array
    {
        $items = [];

        // Only scan actual PHP code regions (@php ... @endphp blocks, and
        // raw PHP open/close tags if present). This is deliberate: without
        // this boundary, matching any quoted string in the raw file would
        // also pick up HTML attribute values like class="mt-6 ...", since
        // those are just ="..." syntax with quotes around them.
        $phpBlockPatterns = [
            '/@php\b(.*?)@endphp/s',
            '/<\?php(.*?)(?:\?>|$)/s',
        ];

        foreach ($phpBlockPatterns as $blockPattern) {
            preg_match_all($blockPattern, $content, $blockMatches, PREG_OFFSET_CAPTURE);

            foreach ($blockMatches[1] as [$blockContent, $blockOffset]) {
                $items = array_merge(
                    $items,
                    $this->extractQuotedStrings($path, $content, $blockContent, $blockOffset)
                );
            }
        }

        return $items;
    }

    protected function extractQuotedStrings(string $path, string $fullContent, string $blockContent, int $blockOffset): array
    {
        $items = [];

        // Matches any single- or double-quoted string literal, respecting
        // backslash-escaped characters (so escaped quotes like \' or \"
        // don't prematurely terminate the match). This intentionally does
        // NOT require a preceding "=>" so that plain list-style array
        // items (e.g. 'bio' => ['text one', 'text two']) are captured too,
        // not just associative key => value pairs. It's safe to be this
        // permissive here because we only ever call this on isolated PHP
        // code blocks, never on raw Blade/HTML content.
        preg_match_all(
            '/(["\'])((?:\\\\.|(?!\1).)*)\1/s',
            $blockContent,
            $matches,
            PREG_OFFSET_CAPTURE
        );

        foreach ($matches[2] as $i => [$text]) {

            $text = html_entity_decode(trim($text), ENT_QUOTES | ENT_HTML5);

            if ($text === '') {
                continue;
            }

            // Unescape backslash-escaped quote characters now that we're
            // done using them for match boundaries.
            $text = str_replace(['\\\'', '\\"'], ['\'', '"'], $text);

            // Ignore translation keys
            if (preg_match('/^[a-z0-9_.-]+$/i', $text)) {
                continue;
            }

            // Ignore SVG paths
            if (preg_match('/^[MLHVCSQTAZ0-9 .,-]+$/i', $text)) {
                continue;
            }

            // Ignore URLs
            if (filter_var($text, FILTER_VALIDATE_URL)) {
                continue;
            }

            // Offset relative to the block, translated back to an offset
            // in the full file so line/column numbers stay accurate.
            $offset = $blockOffset + $matches[0][$i][1];

            $position = $this->positionFromOffset($offset, $fullContent);

            $item = [
                'type' => 'php_array',
                'text' => $text,
                'path' => $path,
                'tag_path' => 'blade > php-array',
                'attribute' => null,
                'line' => $position['line'],
                'column' => $position['column'],
            ];

            // If this string is the VALUE of an associative array entry
            // ('some-key' => 'the text'), use that key as the explicit
            // translation id. Only applies to associative arrays - plain
            // indexed list items ('text one', 'text two') have nothing
            // preceding them to key off, so they fall back to the
            // strategy-based id.
            $matchOffsetInBlock = $matches[0][$i][1];
            $before = rtrim(substr($blockContent, max(0, $matchOffsetInBlock - 120), min($matchOffsetInBlock, 120)));

            if (preg_match('/([\'"])([a-zA-Z0-9_.\-]+)\1\s*=>\s*$/', $before, $keyMatch)) {
                $item['translation_id'] = $keyMatch[2];
            }

            $item['id'] = $this->idStrategy->generateId($item);
            $items[] = $item;
        }

        return $items;
    }
}
