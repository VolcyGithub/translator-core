<?php

namespace Volcy\Translator\Contracts;

/**
 * Interface for translation ID generation strategies.
 *
 * Translation IDs are used to uniquely identify translatable strings across
 * different locales. Different strategies offer various trade-offs between
 * stability, context-awareness, and manual control.
 *
 * Available strategies:
 * - HashIdStrategy: Content-based SHA1 hashes (default, most stable)
 * - TagPathIdStrategy: Context-aware IDs based on HTML structure
 * - ExplicitIdStrategy: Manual control via data-i18n attributes with hash fallback
 *
 * @package Volcy\Translator\Contracts
 */
interface IdStrategy
{
    /**
     * Generate an ID for a text item based on the strategy.
     *
     * The item array contains metadata about the translatable text including
     * its type, content, location, and optional explicit identifiers.
     *
     * @param array{type: string, text: string, path: string, tag_path: string, attribute: string|null, translation_id?: string|null} $item
     *              The translatable item metadata with keys:
     *              - type: Item type ('text', 'attribute', 'php', 'php_array')
     *              - text: The actual translatable text content
     *              - path: File path where the text was found
     *              - tag_path: HTML tag path (e.g., 'div > h1 > p')
     *              - attribute: Attribute name if type is 'attribute'
     *              - translation_id: Optional explicit ID from data-i18n attributes
     *
     * @return string The generated translation ID
     */
    public function generateId(array $item): string;

    /**
     * Get the strategy name for identification.
     *
     * This name is used in configuration files to select which strategy
     * should be used for ID generation.
     *
     * @return string The strategy name (e.g., 'hash', 'tag_path', 'explicit')
     */
    public function getName(): string;
}