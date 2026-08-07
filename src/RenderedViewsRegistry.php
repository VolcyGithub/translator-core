<?php

namespace Volcy\Translator;

class RenderedViewsRegistry
{
    /** @var array<string, true> */
    protected array $viewNames = [];

    public function add(string $viewName): void
    {
        $this->viewNames[$viewName] = true;
    }

    /** @return string[] */
    public function all(): array
    {
        return array_keys($this->viewNames);
    }

    public function reset(): void
    {
        $this->viewNames = [];
    }
}
