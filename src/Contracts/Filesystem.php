<?php

namespace Volcy\Translator\Contracts;

interface Filesystem
{
    public function exists(string $path): bool;

    public function get(string $path): string;

    public function put(string $path, string $contents): void;

    public function ensureDirectoryExists(string $path): void;

    /**
     * Return a list of paths, relative to $directory, matching $pattern
     * (e.g. "*.blade.php" or "*.json"), recursing into subdirectories.
     *
     * @return string[]
     */
    public function files(string $directory, string $pattern = '*'): array;
}
