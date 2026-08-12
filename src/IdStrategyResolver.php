<?php

namespace Volcy\Translator;

use Volcy\Translator\Contracts\IdStrategy;
use Volcy\Translator\IdStrategies\ExplicitIdStrategy;
use Volcy\Translator\IdStrategies\HashIdStrategy;
use Volcy\Translator\IdStrategies\TagPathIdStrategy;

/**
 * Resolver for translation ID strategies based on configuration.
 *
 * This class acts as a factory for ID strategy instances, selecting the
 * appropriate strategy based on configuration settings. It enables runtime
 * configuration of ID generation without code changes.
 *
 * Supported strategies:
 * - 'hash': HashIdStrategy (default, content-based SHA1 hashes)
 * - 'tag_path': TagPathIdStrategy (context-aware HTML structure-based IDs)
 * - 'explicit': ExplicitIdStrategy (manual data-i18n attributes with hash fallback)
 *
 * Configuration:
 * The resolver expects a configuration array with an optional 'id_strategy' key.
 * If not specified, defaults to 'hash' strategy.
 *
 * Example configuration:
 * ```php
 * $resolver = new IdStrategyResolver(['id_strategy' => 'explicit']);
 * $strategy = $resolver->strategy(); // Returns ExplicitIdStrategy instance
 * ```
 *
 * @package Volcy\Translator
 */
class IdStrategyResolver
{
    /**
     * Create a new ID strategy resolver.
     *
     * @param array{id_strategy?: string} $config Configuration array with optional 'id_strategy' key
     *                                              - 'id_strategy': Strategy name ('hash', 'tag_path', 'explicit')
     *                                              - Defaults to 'hash' if not specified
     */
    public function __construct(protected array $config = [])
    {
    }

    /**
     * Get the configured ID strategy instance.
     *
     * Returns a strategy instance based on the configuration. Invalid strategy
     * names fall back to the default HashIdStrategy.
     *
     * @return IdStrategy The configured strategy instance
     */
    public function strategy(): IdStrategy
    {
        $name = $this->config['id_strategy'] ?? 'hash';

        return match ($name) {
            'tag_path' => new TagPathIdStrategy(),
            'explicit' => new ExplicitIdStrategy(),
            default => new HashIdStrategy(),
        };
    }
}