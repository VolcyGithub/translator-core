<?php

namespace Volcy\Translator;

class ViewIndexPathResolver
{
    /**
     * Convert a Blade source path like "global/pages/about.blade.php"
     * into a mirrored index path segment "global/pages/about.json".
     */
    public function fromSourcePath(string $bladePath): string
    {
        $trimmed = preg_replace('/\.blade\.php$/', '', $bladePath);

        return trim($trimmed, '/\\') . '.json';
    }

    /**
     * Convert a dot-notation view name (e.g. "global.pages.about", as
     * used by both Laravel views and BladeOne) into the same mirrored
     * path used by fromSourcePath().
     */
    public function fromViewName(string $viewName): string
    {
        return str_replace('.', '/', $viewName) . '.json';
    }

    public function indexFilePath(string $indexBasePath, string $locale, string $mirroredPath): string
    {
        return rtrim($indexBasePath, '/\\')
            . DIRECTORY_SEPARATOR . $locale
            . DIRECTORY_SEPARATOR . $mirroredPath;
    }
}
