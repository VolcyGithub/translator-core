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
     * @return array<string, string> map of text_hash => translated text
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
            if (isset($item['text_hash'], $item['text'])) {
                $map[$item['text_hash']] = $item['text'];
            }
        }

        return $this->cache[$key] = $map;
    }

    /**
     * Apply a text_hash => translated text dictionary to rendered HTML.
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
        // characters, non-Latin scripts, etc). Converting to HTML
        // entities first sidesteps that entirely, and avoids needing an
        // injected xml-declaration prefix, which saveHTML() would otherwise
        // re-emit as literal, visible text in the output.
        $doc->loadHTML(
            mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'),
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $this->walk($doc, $dictionary);

        return $doc->saveHTML();
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
                $hash = sha1($value);

                if (isset($dictionary[$hash])) {
                    $node->setAttribute($attr, $dictionary[$hash]);
                }
            }
        }

        if ($node->nodeType === XML_TEXT_NODE) {
            $trimmed = trim($node->nodeValue ?? '');
            $hash = sha1($trimmed);

            if ($trimmed !== '' && isset($dictionary[$hash])) {
                $node->nodeValue = preg_replace(
                    '/' . preg_quote($trimmed, '/') . '/',
                    $dictionary[$hash],
                    $node->nodeValue,
                    1
                );
            }
        }

        foreach (iterator_to_array($node->childNodes ?? []) as $child) {
            $this->walk($child, $dictionary);
        }
    }
}
