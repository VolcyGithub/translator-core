<?php

namespace Volcy\Translator;

use DOMDocument;
use DOMElement;
use DOMNode;
use Volcy\Translator\Contracts\Filesystem;

class TranslationCatalog
{
    /** @var array<string, array<string, string>> */
    protected array $cache = [];

    public function __construct(
        protected Filesystem $filesystem,
        protected ViewIndexPathResolver $resolver,
        protected string $indexBasePath,
    ) {
    }

    /**
     * Merge the locale dictionaries for every view name given (a page's
     * layout + template + partials/components all contribute strings).
     *
     * @param string[] $viewNames
     * @return array<string, string> map of id => translated text
     */
    public function forViewsAndLocale(array $viewNames, string $locale): array
    {
        $dictionary = [];

        foreach ($viewNames as $viewName) {
            $mirroredPath = $this->resolver->fromViewName($viewName);
            $dictionary += $this->loadFile($locale, $mirroredPath);
        }

        return $dictionary;
    }

    /** @return array<string, string> */
    protected function loadFile(string $locale, string $mirroredPath): array
    {
        $key = "$locale:$mirroredPath";

        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $path = $this->resolver->indexFilePath($this->indexBasePath, $locale, $mirroredPath);

        if (! $this->filesystem->exists($path)) {
            return $this->cache[$key] = [];
        }

        $items = json_decode($this->filesystem->get($path), true);

        if (! is_array($items)) {
            return $this->cache[$key] = [];
        }

        $map = [];

        foreach ($items as $item) {
            if (isset($item['id'], $item['text'])) {
                $map[$item['id']] = $item['text'];
            }
        }

        return $this->cache[$key] = $map;
    }

    /**
     * Apply an id => translated text dictionary to rendered HTML.
     * Pure function: no I/O, no side effects. Framework bridges call
     * this from their middleware/hook after getting the dictionary above.
     */
    public function applyToHtml(string $html, array $dictionary): string
    {
        if (empty($dictionary)) {
            return $html;
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);

        // DOMDocument::loadHTML() assumes ISO-8859-1 unless the encoding
        // is unambiguous, which corrupts multibyte UTF-8 (accented
        // characters, non-Latin scripts, etc). Prepending an XML encoding
        // declaration is the standard way to hint the real encoding to
        // libxml during parsing. It becomes a processing-instruction node
        // at the start of the document, which we explicitly remove below
        // before serializing - otherwise saveHTML() re-emits it as
        // literal, visible text in the output.
        $doc->loadHTML(
            '<?xml encoding="utf-8" ?>' . $html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        foreach (iterator_to_array($doc->childNodes) as $child) {
            if ($child->nodeType === XML_PI_NODE) {
                $doc->removeChild($child);
            }
        }

        $this->walk($doc, $dictionary);

        // DOMDocument::saveHTML() entity-encodes non-ASCII characters
        // (e.g. "é" becomes "&eacute;") as part of its normal HTML
        // serialization - this is valid HTML and renders correctly in
        // browsers, but decoding it back to raw UTF-8 gives cleaner,
        // more readable output. We protect the handful of entities that
        // are structurally required for valid HTML (&amp; &lt; &gt;
        // &quot; &#039;) so decoding never un-escapes something that
        // needs to stay escaped.
        return $this->decodeNonStructuralEntities($doc->saveHTML());
    }

    protected function decodeNonStructuralEntities(string $html): string
    {
        $structural = [
            '&amp;' => "\0ENT_AMP\0",
            '&lt;' => "\0ENT_LT\0",
            '&gt;' => "\0ENT_GT\0",
            '&quot;' => "\0ENT_QUOT\0",
            '&#039;' => "\0ENT_APOS\0",
            '&#39;' => "\0ENT_APOS\0",
        ];

        $protected = strtr($html, $structural);
        $decoded = html_entity_decode($protected, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return strtr($decoded, array_flip($structural));
    }

    protected function walk(DOMNode $node, array $dictionary): void
    {
        if ($node instanceof DOMElement) {
            if (in_array(strtolower($node->tagName), ['script', 'style'], true)) {
                return;
            }

            foreach (['title', 'alt', 'placeholder', 'aria-label', 'aria-description'] as $attr) {
                if (! $node->hasAttribute($attr)) {
                    continue;
                }

                $value = trim($node->getAttribute($attr));

                // Explicit id takes priority (data-i18n-title, data-i18n-alt,
                // etc.) - falls back to hashing the rendered value, same as
                // scan time, for anything left untagged.
                $explicitId = $this->explicitIdAttribute($node, "data-i18n-{$attr}");
                $key = $explicitId ?? sha1($value);

                if (isset($dictionary[$key])) {
                    $node->setAttribute($attr, $dictionary[$key]);
                }
            }
        }

        if ($node->nodeType === XML_TEXT_NODE) {
            $trimmed = trim($node->nodeValue ?? '');

            // A text node's explicit id comes from data-i18n on its
            // *parent element* (e.g. <p data-i18n="bio.founder-intro">).
            $explicitId = $node->parentNode instanceof DOMElement
                ? $this->explicitIdAttribute($node->parentNode, 'data-i18n')
                : null;
            $key = $explicitId ?? sha1($trimmed);

            if ($trimmed !== '' && isset($dictionary[$key])) {
                $node->nodeValue = preg_replace(
                    '/' . preg_quote($trimmed, '/') . '/',
                    $dictionary[$key],
                    $node->nodeValue,
                    1
                );
            }
        }

        foreach (iterator_to_array($node->childNodes ?? []) as $child) {
            $this->walk($child, $dictionary);
        }
    }

    protected function explicitIdAttribute(DOMElement $node, string $attribute): ?string
    {
        if (! $node->hasAttribute($attribute)) {
            return null;
        }

        $value = trim($node->getAttribute($attribute));

        return $value === '' ? null : $value;
    }
}
