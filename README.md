# Translator Core

[![Latest Stable Version](https://img.shields.io/packagist/v/volcy/translator-core)](https://packagist.org/packages/volcy/translator-core)
[![Total Downloads](https://img.shields.io/packagist/dt/volcy/translator-core)](https://packagist.org/packages/volcy/translator-core)
[![License](https://img.shields.io/packagist/l/volcy/translator-core)](https://packagist.org/packages/volcy/translator-core)
[![PHP Version](https://img.shields.io/packagist/php-v/volcy/translator-core)](https://packagist.org/packages/volcy/translator-core)

Framework-agnostic Blade string extraction and JSON-based translation catalog system. This library provides the core functionality for scanning Blade templates, building translation indexes, and applying translations to rendered HTML.

## Features

- **Framework Agnostic**: Works with any PHP framework or plain PHP applications
- **Blade Template Support**: Extracts translatable strings from Blade templates
- **Multiple ID Strategies**: Configurable ID generation (hash, tag path, explicit)
- **Translation Drivers**: Support for Google Translate, Groq, and Cerebras APIs
- **HTML Translation**: Applies translations to rendered HTML via DOM manipulation
- **PHP Array Support**: Extracts strings from PHP arrays with optional key-based IDs
- **Collision Detection**: Warns about ID conflicts during scanning
- **Framework Bridges**: Laravel and FlightPHP integration packages available
- **Custom Data Attributes**: Support for `data-i18n-*` attributes for dynamic content translation
- **Balanced Element Extraction**: Accurate HTML parsing for nested tag structures

## Requirements

- PHP 8.0 or higher
- ext-dom
- ext-json  
- ext-curl
- ext-mbstring

## Installation

Install via Composer:

```bash
composer require volcy/translator-core
```

## Requirements

- PHP 8.0 or higher
- ext-dom
- ext-json  
- ext-curl
- ext-mbstring

## Quick Start

### Basic Usage

```php
use Volcy\Translator\Drivers\BladeDriver;
use Volcy\Translator\IdStrategies\HashIdStrategy;
use Volcy\Translator\ScanRunner;
use Volcy\Translator\Filesystem\NativeFilesystem;
use Volcy\Translator\ViewIndexPathResolver;

// Create components
$filesystem = new NativeFilesystem();
$resolver = new ViewIndexPathResolver();
$driver = new BladeDriver(new HashIdStrategy());
$scanRunner = new ScanRunner($driver, $filesystem, $resolver, new HashIdStrategy());

// Scan Blade views
$result = $scanRunner->run(
    '/path/to/views',
    '/path/to/indexes',
    'en'
);

echo "Scanned {$result['written']} files\n";
foreach ($result['warnings'] as $warning) {
    echo "Warning: $warning\n";
}
```

### Building Translations

```php
use Volcy\Translator\BuildRunner;
use Volcy\Translator\TranslationDriverResolver;

$config = [
    'translation_driver' => 'groq',
    'drivers' => [
        'groq' => [
            'key' => 'your-groq-api-key',
            'model' => 'llama-3.1-8b-instant',
        ],
    ],
];

$buildRunner = new BuildRunner(
    $filesystem,
    $resolver,
    new TranslationDriverResolver($config)
);

$result = $buildRunner->run(
    '/path/to/indexes',
    'fr',
    'en'
);

echo "Translated {$result['translated']} strings, reused {$result['reused']}\n";
```

### Applying Translations

```php
use Volcy\Translator\TranslationCatalog;

$catalog = new TranslationCatalog(
    $filesystem,
    $resolver,
    '/path/to/indexes'
);

// Get translation dictionary for views
$dictionary = $catalog->forViewsAndLocale(
    ['home', 'layout', 'partials.header'],
    'fr'
);

// Apply to rendered HTML
$html = '<h1>Welcome</h1><p>This is the home page</p>';
$translated = $catalog->applyToHtml($html, $dictionary);
```

## ID Strategies

The library supports multiple strategies for generating translation IDs:

### Hash Strategy (Default)
Content-based SHA1 hashes for maximum stability.

```php
use Volcy\Translator\IdStrategies\HashIdStrategy;

$driver = new BladeDriver(new HashIdStrategy());
```

### Tag Path Strategy
Context-aware IDs based on HTML structure.

```php
use Volcy\Translator\IdStrategies\TagPathIdStrategy;

$driver = new BladeDriver(new TagPathIdStrategy());
```

### Explicit Strategy
Manual control via data-i18n attributes with hash fallback.

```php
use Volcy\Translator\IdStrategies\ExplicitIdStrategy;

$driver = new BladeDriver(new ExplicitIdStrategy());
```

For detailed information about ID strategies, see [ID_STRATEGIES.md](ID_STRATEGIES.md).

## Translation Drivers

### Google Translate

```php
$config = [
    'translation_driver' => 'google',
    'drivers' => [
        'google' => [
            'key' => 'your-google-translate-key',
        ],
    ],
];
```

### Groq

```php
$config = [
    'translation_driver' => 'groq',
    'drivers' => [
        'groq' => [
            'key' => 'your-groq-api-key',
            'model' => 'llama-3.1-8b-instant',
        ],
    ],
];
```

### Cerebras

```php
$config = [
    'translation_driver' => 'cerebras',
    'drivers' => [
        'cerebras' => [
            'key' => 'your-cerebras-api-key',
            'model' => 'llama-3.3-70b',
        ],
    ],
];
```

## HTML with Explicit IDs

When using the explicit ID strategy, you can specify translation IDs directly in your HTML:

```html
<h1 data-i18n="home.title">Welcome</h1>
<p data-i18n="home.description">This is the home page</p>
<input data-i18n-placeholder="form.email.placeholder" placeholder="Enter email">
<button data-i18n="buttons.submit">Submit</button>
```

## Custom Data Attributes for Dynamic Content

The scanner now supports custom `data-i18n-*` attributes for translating dynamic JavaScript content:

```html
<!-- For Alpine.js or other JavaScript frameworks -->
<button data-i18n-loading="Loading..." data-i18n-text="Submit"
        x-text="loading ? $el.dataset.i18nLoading : $el.dataset.i18nText">
    Submit
</button>
```

The scanner will extract the values from custom `data-i18n-*` attributes and make them available for translation. This enables compile-time translation for dynamic content that's controlled by JavaScript frameworks.

## PHP Arrays with Key-Based IDs

The scanner also extracts strings from PHP arrays and uses array keys as explicit IDs:

```php
return [
    'title' => 'Welcome to our site',
    'description' => 'This is the home page',
    'cta_button' => 'Get Started',
];
```

## Framework Integration

### Laravel

Use the [volcy/translator-laravel](https://github.com/VolcyGithub/translator-laravel) package for Laravel integration:

```bash
composer require volcy/translator-laravel
```

### FlightPHP

Use the [volcy/translator-flight](https://github.com/VolcyGithub/translator-flight) package for FlightPHP integration:

```bash
composer require volcy/translator-flight
```

## API Reference

### Core Classes

- **BladeDriver**: Extracts translatable strings from Blade templates
- **ScanRunner**: Scans views and generates source locale indexes
- **BuildRunner**: Builds target locale indexes from source
- **TranslationCatalog**: Manages translation lookup and HTML application
- **TranslationDriverResolver**: Resolves translation driver from config
- **IdStrategyResolver**: Resolves ID generation strategy from config

### Interfaces

- **DocumentDriver**: Interface for document parsing drivers
- **Filesystem**: Interface for filesystem operations
- **IdStrategy**: Interface for ID generation strategies

## CLI Workflow

### 1. Scan Views

Extract translatable strings from your Blade templates:

```bash
# Laravel
php artisan translator:scan

# Flight with Runway
vendor/bin/runway translator:scan --path=app/views
```

### 2. Build Translations

Generate translations for target locales:

```bash
# Laravel
php artisan translator:build fr

# Flight with Runway  
vendor/bin/runway translator:build fr --source=en
```

### 3. Apply Translations

Translations are automatically applied via middleware during web requests.

## Configuration

The library accepts configuration arrays for:

- **ID Strategy**: Choose between hash, tag_path, or explicit
- **Translation Driver**: Select Google, Groq, or Cerebras
- **API Keys**: Configure API credentials for translation services
- **Paths**: Specify views and indexes directory paths

## Testing

Run the test suite:

```bash
vendor/bin/phpunit
```

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

For issues and questions, please use the [GitHub issue tracker](https://github.com/VolcyGithub/translator-core/issues).

For security issues, please see [SECURITY.md](SECURITY.md).

## Related Packages

- [volcy/translator-laravel](https://github.com/VolcyGithub/translator-laravel) - Laravel integration
- [volcy/translator-flight](https://github.com/VolcyGithub/translator-flight) - FlightPHP integration