<?php

namespace Volcy\Translator\IdStrategies;

use Volcy\Translator\Contracts\IdStrategy;

/**
 * Tag path-based ID generation strategy.
 *
 * This strategy generates context-aware IDs based on the HTML structure
 * (tag path) where the translatable text appears. Same text in different
 * HTML contexts will get different translation IDs.
 *
 * Advantages:
 * - Context-aware: Same text in different locations gets different translations
 * - Automatic differentiation: No manual configuration needed for context
 * - Structure-based: IDs reflect the HTML hierarchy
 * - More precise translations: Can translate "Submit" differently on buttons vs forms
 *
 * Disadvantages:
 * - Less stable: HTML structure changes affect IDs
 * - Fragile: Moving elements changes their IDs
 * - No manual override: Cannot customize IDs for specific strings
 * - Structural dependency: Requires consistent HTML structure
 *
 * Use cases:
 * - When context matters for translations (e.g., "Save" on buttons vs forms)
 * - Stable HTML structures that don't change frequently
 * - When automatic context differentiation is preferred over manual control
 *
 * Example:
 * <div class="header">
 *   <h1>Welcome</h1>  // ID: hash("div > h1|Welcome")
 * </div>
 * <div class="footer">
 *   <h1>Welcome</h1>  // ID: hash("div > footer > h1|Welcome") - Different!
 * </div>
 *
 * @package Volcy\Translator\IdStrategies
 */
class TagPathIdStrategy implements IdStrategy
{
    /**
     * Generate a tag path-based ID for the translatable item.
     *
     * Creates SHA1 hashes based on the HTML tag path, item type, and content.
     * This ensures that identical text in different structural contexts gets
     * different translation IDs.
     *
     * @param array $item The translatable item metadata
     * @return string 40-character SHA1 hash incorporating tag path
     */
    public function generateId(array $item): string
    {
        $text = trim($item['text']);
        $tagPath = $item['tag_path'] ?? '';
        
        // Use tag path + type + attribute (if applicable) to create unique IDs
        // This gives more context-aware IDs than pure hashes
        $base = $tagPath;
        
        if ($item['type'] === 'attribute' && $item['attribute']) {
            $base .= '@' . $item['attribute'];
        } elseif ($item['type'] === 'php') {
            $base .= '|php|' . ($item['line'] ?? 0);
        } elseif ($item['type'] === 'php_array') {
            $base .= '|php-array|' . ($item['line'] ?? 0);
        }
        
        // Still hash the combined string to ensure valid IDs
        return sha1($base . '|' . $text);
    }

    /**
     * Get the strategy name.
     *
     * @return string 'tag_path'
     */
    public function getName(): string
    {
        return 'tag_path';
    }
}