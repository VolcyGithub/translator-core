<?php

namespace Volcy\Translator\Contracts;

interface DocumentDriver
{
    public function name(): string;

    public function extensions(): array;

    public function supports(string $path): bool;

    public function index(string $path, string $content): array;
}
