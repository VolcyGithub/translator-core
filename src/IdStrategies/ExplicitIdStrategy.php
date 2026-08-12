<?php

namespace Volcy\Translator\IdStrategies;

use Volcy\Translator\Contracts\IdStrategy;

/**
 * Explicit ID generation strategy with hash fallback.
 *
 * This strategy prioritizes manually specified translation IDs from HTML
 * data-i18n attributes or PHP array keys, falling back to hash-based IDs
 * when no explicit ID is provided. This gives developers maximum control
 * over translation IDs while maintaining automatic fallback.
 *
 * Advantages:
 * - Full manual control: Specify exact IDs for critical strings
 * - Human-readable IDs: Use semantic names like 'home.title' instead of hashes
 * - Context awareness: Different contexts can have different IDs for same text
 * - Fallback safety: Automatic hash generation for unspecified strings
 * - Collaboration-friendly: Translation teams can work with meaningful keys
 *
 * Disadvantages:
 * - Manual effort: Requires adding data-i18n attributes to HTML
 * - Maintenance: Must keep explicit IDs in sync with code changes
 * - Collision risk: Same explicit ID used for different texts causes warnings
 * - Partial coverage: Only strings with explicit IDs get human-readable keys
 *
 * Use cases:
 * - Production applications requiring stable, meaningful translation keys
 * - When working with professional translation teams
 * - Complex UIs where context matters for translations
 * - Applications with long-term maintenance requirements
 *
 * HTML Examples:
 * <h1 data-i18n="home.title">Welcome</h1>
 * <p data-i18n="home.description">This is the home page</p>
 * <input data-i18n-placeholder="form.email.placeholder" placeholder="Enter email">
 *
 * PHP Array Examples:
 * return [
 *     'title' => 'Welcome to our site',  // ID: 'title'
 *     'description' => 'This is the home page',  // ID: 'description'
 * ];
 *
 * @package Volcy\Translator\IdStrategies
 */
class ExplicitIdStrategy implements IdStrategy
{
    /**
     * Generate an explicit ID or fall back to hash-based ID.
     *
     * Uses the translation_id from data-i18n attributes or PHP array keys
     * when available. Falls back to hash-based generation for items without
     * explicit IDs, ensuring all translatable strings get valid IDs.
     *
     * @param array $item The translatable item metadata
     * @return string The explicit ID if available, otherwise a hash-based ID
     */
    public function generateId(array $item): string
    {
        $text = trim($item['text']);
        
        // If there's an explicit translation_id, use it
        if (isset($item['translation_id']) && $item['translation_id'] !== '' && $item['translation_id'] !== null) {
            return $item['translation_id'];
        }
        
        // Fall back to hash-based ID generation
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
     * @return string 'explicit'
     */
    public function getName(): string
    {
        return 'explicit';
    }
}