# Translation ID Strategies

The translator-core library supports multiple strategies for generating translation IDs, giving you flexibility in how you manage and organize your translations.

## Overview

Translation IDs are unique identifiers used to track translatable strings across different locales. Different ID generation strategies offer various trade-offs between stability, context-awareness, and manual control.

## Available Strategies

### 1. Hash Strategy (Default)

**Strategy Name:** `hash`

**Description:** Generates content-based SHA1 hashes for translation IDs. Identical content always produces the same hash, regardless of location or context.

**Advantages:**
- Maximum stability - same content always produces same ID
- No manual configuration required
- Content deduplication - same text in different locations shares translation
- Works well for simple translation scenarios

**Disadvantages:**
- Context-agnostic - same text in different contexts gets same translation
- No manual control - cannot override ID generation for specific strings
- Hash collisions - extremely rare but theoretically possible

**Use Cases:**
- Simple websites where context doesn't matter
- Legacy systems upgrading from hash-based systems
- When content deduplication is desired

**Configuration:**
```php
// Laravel config/translator.php
'id_strategy' => 'hash',

// Flight bootstrap
$translator = TranslatorBootstrap::register(Flight::app(), [
    'id_strategy' => 'hash',
]);
```

### 2. Tag Path Strategy

**Strategy Name:** `tag_path`

**Description:** Generates context-aware IDs based on HTML structure (tag path). Same text in different HTML contexts gets different translation IDs.

**Advantages:**
- Context-aware - same text in different locations gets different translations
- Automatic differentiation - no manual configuration needed for context
- Structure-based - IDs reflect the HTML hierarchy
- More precise translations - can translate "Submit" differently on buttons vs forms

**Disadvantages:**
- Less stable - HTML structure changes affect IDs
- Fragile - moving elements changes their IDs
- No manual override - cannot customize IDs for specific strings
- Structural dependency - requires consistent HTML structure

**Use Cases:**
- When context matters for translations (e.g., "Save" on buttons vs forms)
- Stable HTML structures that don't change frequently
- When automatic context differentiation is preferred over manual control

**Example:**
```html
<div class="header">
  <h1>Welcome</h1>  <!-- ID: hash("div > h1|Welcome") -->
</div>
<div class="footer">
  <h1>Welcome</h1>  <!-- ID: hash("div > footer > h1|Welcome") - Different! -->
</div>
```

**Configuration:**
```php
// Laravel config/translator.php
'id_strategy' => 'tag_path',

// Flight bootstrap
$translator = TranslatorBootstrap::register(Flight::app(), [
    'id_strategy' => 'tag_path',
]);
```

### 3. Explicit Strategy

**Strategy Name:** `explicit`

**Description:** Prioritizes manually specified translation IDs from HTML data-i18n attributes or PHP array keys, falling back to hash-based IDs when no explicit ID is provided.

**Advantages:**
- Full manual control - specify exact IDs for critical strings
- Human-readable IDs - use semantic names like 'home.title' instead of hashes
- Context awareness - different contexts can have different IDs for same text
- Fallback safety - automatic hash generation for unspecified strings
- Collaboration-friendly - translation teams can work with meaningful keys

**Disadvantages:**
- Manual effort - requires adding data-i18n attributes to HTML
- Maintenance - must keep explicit IDs in sync with code changes
- Collision risk - same explicit ID used for different texts causes warnings
- Partial coverage - only strings with explicit IDs get human-readable keys

**Use Cases:**
- Production applications requiring stable, meaningful translation keys
- When working with professional translation teams
- Complex UIs where context matters for translations
- Applications with long-term maintenance requirements

**HTML Examples:**
```html
<h1 data-i18n="home.title">Welcome</h1>
<p data-i18n="home.description">This is the home page</p>
<input data-i18n-placeholder="form.email.placeholder" placeholder="Enter email">
<button data-i18n="buttons.submit">Submit</button>
```

**PHP Array Examples:**
```php
return [
    'title' => 'Welcome to our site',        // ID: 'title'
    'description' => 'This is the home page', // ID: 'description'
    'cta_button' => 'Get Started',           // ID: 'cta_button'
];
```

**Configuration:**
```php
// Laravel config/translator.php
'id_strategy' => 'explicit',

// Flight bootstrap
$translator = TranslatorBootstrap::register(Flight::app(), [
    'id_strategy' => 'explicit',
]);
```

## Strategy Comparison

| Feature | Hash | Tag Path | Explicit |
|---------|------|----------|----------|
| **Stability** | ⭐⭐⭐⭐⭐ | ⭐⭐ | ⭐⭐⭐⭐ |
| **Context Awareness** | ⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| **Manual Control** | ⭐ | ⭐ | ⭐⭐⭐⭐⭐ |
| **Ease of Use** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐ |
| **Collaboration** | ⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| **Human-Readable IDs** | ❌ | ❌ | ✅ |
| **Content Deduplication** | ✅ | ❌ | ❌ |

## Migration Guide

### From Hash to Explicit Strategy

1. **Start with hash strategy** to generate initial translation files
2. **Add data-i18n attributes** to critical strings gradually
3. **Switch to explicit strategy** in configuration
4. **Re-scan views** to generate new IDs with explicit keys
5. **Re-build translations** for target locales

### From Hash to Tag Path Strategy

1. **Backup existing translation files**
2. **Switch to tag_path strategy** in configuration
3. **Re-scan views** to generate new context-aware IDs
4. **Re-build translations** for target locales
5. **Test thoroughly** as same text may now have different translations

## Best Practices

### When to Use Hash Strategy
- Simple websites with consistent terminology
- Quick prototyping and development
- When translation teams prefer working with content directly
- Legacy systems with existing hash-based translations

### When to Use Tag Path Strategy
- Applications with stable HTML structure
- When context matters for translations
- Automatic differentiation preferred over manual control
- Medium-complexity applications

### When to Use Explicit Strategy
- Production applications with long-term maintenance
- Working with professional translation agencies
- Complex UIs with contextual variations
- When human-readable keys are important for collaboration

## Advanced Usage

### Custom ID Strategies

You can create custom ID strategies by implementing the `IdStrategy` interface:

```php
use Volcy\Translator\Contracts\IdStrategy;

class CustomIdStrategy implements IdStrategy
{
    public function generateId(array $item): string
    {
        // Your custom ID generation logic
        return 'custom_' . md5($item['text']);
    }

    public function getName(): string
    {
        return 'custom';
    }
}
```

Then register it in your framework's service provider or bootstrap:

```php
// Laravel
$this->app->singleton(IdStrategy::class, CustomIdStrategy::class);

// Flight
$strategy = new CustomIdStrategy();
$bladeDriver = new BladeDriver($strategy);
```

## Troubleshooting

### ID Collisions

When using the explicit strategy, you may encounter warnings about ID collisions:

```
views/home.blade.php: id 'title' is used by two different strings - only the last one was kept.
```

**Solution:** Ensure each explicit ID is unique within the same file or scope.

### Broken Translations After Strategy Change

If translations break after changing strategies:

1. **Re-scan views** to generate new ID structure
2. **Re-build translations** for all target locales
3. **Clear translation cache** if applicable
4. **Test each locale** thoroughly

### Missing Explicit IDs

When using explicit strategy, some strings may still use hash IDs:

**Solution:** This is expected behavior. Strings without explicit IDs automatically fall back to hash generation.

## Performance Considerations

- **Hash Strategy:** Fastest, minimal computation
- **Tag Path Strategy:** Moderate, requires DOM traversal
- **Explicit Strategy:** Fast for explicit IDs, falls back to hash for others

All strategies have negligible performance impact during normal web requests. The performance difference is primarily visible during the scanning process (CLI operation).

## Security Considerations

- IDs are not security-sensitive - they're only used for translation lookup
- Hash IDs use SHA1, which is sufficient for this use case
- Explicit IDs should follow naming conventions (e.g., lowercase with dots)
- Avoid exposing sensitive information in explicit ID names

## Conclusion

Choose the ID strategy that best fits your application's requirements:

- **Hash** for simplicity and stability
- **Tag Path** for automatic context awareness
- **Explicit** for maximum control and collaboration

You can always start with one strategy and migrate to another as your application grows.