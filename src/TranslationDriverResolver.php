<?php

namespace Volcy\Translator;

use Volcy\Translator\Drivers\GoogleTranslationDriver;
use Volcy\Translator\Drivers\GroqTranslationDriver;
use Volcy\Translator\Drivers\CerebrasTranslationDriver;

class TranslationDriverResolver
{
    /**
     * @param array{translation_driver?: string, drivers?: array<string, array>} $config
     */
    public function __construct(protected array $config = [])
    {
    }

    public function driver(): object
    {
        $name = $this->config['translation_driver'] ?? 'groq';

        return match ($name) {
            'google' => new GoogleTranslationDriver($this->config['drivers']['google'] ?? []),
            'cerebras' => new CerebrasTranslationDriver($this->config['drivers']['cerebras'] ?? []),
            default => new GroqTranslationDriver($this->config['drivers']['groq'] ?? []),
        };
    }
}
