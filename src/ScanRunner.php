<?php

namespace Volcy\Translator;

use Volcy\Translator\Contracts\Filesystem;
use Volcy\Translator\Drivers\BladeDriver;

class ScanRunner
{
    public function __construct(
        protected BladeDriver $driver,
        protected Filesystem $filesystem,
        protected ViewIndexPathResolver $resolver,
        protected array $excludedFolders = [],
    ) {
        $this->excludedFolders = $excludedFolders;
    }

    /**
     * Scan every .blade.php file under $viewsRoot and write a source-locale
     * index file for each, mirroring the view's directory structure under
     * $indexBasePath/$sourceLocale/.
     *
     * @param string[] $excludedFolders Relative folder prefixes to skip.
     *
     * @return array{written: int, files: string[]}
     */
    public function run(string $viewsRoot, string $indexBasePath, string $sourceLocale = 'en', array $excludedFolders = []): array
    {
        $relativePaths = $this->filesystem->files($viewsRoot, '*.blade.php');
        $excludedFolders = array_values(array_filter(array_map(
            static fn (string $folder) => trim(str_replace('\\', '/', $folder), '/'),
            $excludedFolders
        )));

        if ($excludedFolders !== []) {
            $relativePaths = array_values(array_filter($relativePaths, static function (string $relativePath) use ($excludedFolders): bool {
                $normalizedPath = ltrim(str_replace('\\', '/', $relativePath), '/');

                foreach ($excludedFolders as $excludedFolder) {
                    if ($normalizedPath === $excludedFolder || str_starts_with($normalizedPath, $excludedFolder . '/')) {
                        return false;
                    }
                }

                return true;
            }));
        }

        $written = 0;

        foreach ($relativePaths as $relativePath) {
            $fullPath = rtrim($viewsRoot, '/\\') . DIRECTORY_SEPARATOR . $relativePath;

            if (! $this->filesystem->exists($fullPath)) {
                continue;
            }

            $content = $this->filesystem->get($fullPath);
            $result = $this->driver->index($relativePath, $content);

            $indexed = [];

            foreach ($result['items'] as $item) {
                $hash = sha1(trim($item['text']));

                // Keyed by hash to dedupe within this single file's index
                $indexed[$hash] = [
                    'text_hash' => $hash,
                    'text' => $item['text'],
                    'type' => $item['type'],
                    'tag_path' => $item['tag_path'],
                ];
            }

            $mirroredPath = $this->resolver->fromSourcePath($relativePath);
            $outputPath = $this->resolver->indexFilePath($indexBasePath, $sourceLocale, $mirroredPath);

            $this->filesystem->ensureDirectoryExists(dirname($outputPath));
            $this->filesystem->put(
                $outputPath,
                json_encode(array_values($indexed), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );

            $written++;
        }

        return ['written' => $written, 'files' => $relativePaths];
    }
}
