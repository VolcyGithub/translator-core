<?php

namespace Volcy\Translator\Filesystem;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Volcy\Translator\Contracts\Filesystem;

class NativeFilesystem implements Filesystem
{
    public function exists(string $path): bool
    {
        return file_exists($path);
    }

    public function get(string $path): string
    {
        $contents = file_get_contents($path);

        return $contents === false ? '' : $contents;
    }

    public function put(string $path, string $contents): void
    {
        file_put_contents($path, $contents);
    }

    public function ensureDirectoryExists(string $path): void
    {
        if (! is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    public function files(string $directory, string $pattern = '*'): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $results = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
        );

        $regex = '/' . str_replace(['*', '.'], ['.*', '\.'], $pattern) . '$/i';

        foreach ($iterator as $file) {
            if (! $file->isFile() || ! preg_match($regex, $file->getFilename())) {
                continue;
            }

            $relative = ltrim(str_replace($directory, '', $file->getPathname()), '/\\');
            $results[] = str_replace('\\', '/', $relative);
        }

        sort($results);

        return $results;
    }
}
