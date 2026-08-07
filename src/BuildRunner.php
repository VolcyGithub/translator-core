<?php

namespace Volcy\Translator;

use Volcy\Translator\Contracts\Filesystem;

class BuildRunner
{
    public function __construct(
        protected Filesystem $filesystem,
        protected ViewIndexPathResolver $resolver,
        protected TranslationDriverResolver $drivers,
    ) {
    }

    /**
     * Fill in a target-locale index from the source-locale index. Only
     * translates strings whose text_hash isn't already present in the
     * target file, so re-running this is cheap and idempotent. Never
     * called during a web request - this is an offline/CLI operation.
     *
     * @return array{translated: int, reused: int}
     */
    public function run(string $indexBasePath, string $locale, string $sourceLocale = 'en', bool $dryRun = false): array
    {
        $sourceRoot = rtrim($indexBasePath, '/\\') . DIRECTORY_SEPARATOR . $sourceLocale;

        $relativePaths = $this->filesystem->files($sourceRoot, '*.json');
        $driver = $this->drivers->driver();

        $translated = 0;
        $reused = 0;

        foreach ($relativePaths as $relativePath) {
            $sourcePath = rtrim($sourceRoot, '/\\') . DIRECTORY_SEPARATOR . $relativePath;
            $sourceItems = json_decode($this->filesystem->get($sourcePath), true) ?: [];

            $targetPath = $this->resolver->indexFilePath($indexBasePath, $locale, $relativePath);

            $existing = [];

            if ($this->filesystem->exists($targetPath)) {
                $decoded = json_decode($this->filesystem->get($targetPath), true) ?: [];

                foreach ($decoded as $item) {
                    if (isset($item['text_hash'])) {
                        $existing[$item['text_hash']] = $item;
                    }
                }
            }

            $result = [];

            foreach ($sourceItems as $item) {
                if (isset($existing[$item['text_hash']])) {
                    $result[] = $existing[$item['text_hash']];
                    $reused++;
                    continue;
                }

                $translatedText = $dryRun
                    ? $item['text']
                    : $driver->translate($item['text'], $locale, $sourceLocale);

                $result[] = [
                    'text_hash' => $item['text_hash'],
                    'text' => $translatedText,
                    'type' => $item['type'],
                    'tag_path' => $item['tag_path'],
                ];

                $translated++;
            }

            if (! $dryRun) {
                $this->filesystem->ensureDirectoryExists(dirname($targetPath));
                $this->filesystem->put(
                    $targetPath,
                    json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                );
            }
        }

        return ['translated' => $translated, 'reused' => $reused];
    }
}
