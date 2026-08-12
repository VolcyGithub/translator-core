<?php

namespace Volcy\Translator\IdStrategies;

use Volcy\Translator\Contracts\IdStrategy;

/**
 * Hash-based ID generation strategy.
 *
 * This strategy generates stable, content-based SHA1 hashes for translation IDs.
 * It's the default strategy and provides the most stable IDs since identical
 * content will always generate the same hash, regardless of location or context.
 *
 * Advantages:
 * - Maximum stability: Same content always produces same ID
 * - No manual configuration required
 * - Works well for simple translation scenarios
 * - Content deduplication: Same text in different locations shares translation
 *
 * Disadvantages:
 * - Context-agnostic: Same text in different contexts gets same translation
 * - No manual control: Cannot override ID generation for specific strings
 * - Hash collisions: Extremely rare but theoretically possible
 *
 * Use cases:
 * - Simple websites where context doesn't matter
 * - Legacy systems upgrading from hash-based systems
 * - When content deduplication is desired
 *
 * @package Volcy\Translator\IdStrategies
 */
class HashIdStrategy implements IdStrategy
{
    /**
     * Generate a hash-based ID for the translatable item.
     *
     * Creates SHA1 hashes based on the item's type, path, and content.
     * Different item types use different hash patterns to ensure uniqueness.
     *
     * @param array $item The translatable item metadata
     * @return string 40-character SHA1 hash
     */
    public function generateId(array $item): string
    {
        $text = trim($item['text']);
        
        return match ($item['type']) {
            'php' => sha1($item['path'] . '|php|' . $text . '|' . ($item['line'] ?? 0)),
            'php_array' => sha1($item['path'] . '|php-array|' . $text . '|' . ($item['line'] ?? 0)),
            'attribute' => sha1($item['path'] . '|attribute|' . $item['attribute'] . '|' . $text),
            default => sha1($item['path'] . '|text|' . $text),
        };
    }

    /**
     * Get the strategy name.
     *
     * @return string 'hash'
     */
    public function getName(): string
    {
        return 'hash';
    }
}