# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Configurable ID strategy system with three strategies:
  - Hash-based ID generation (default)
  - Tag path-based ID generation for context awareness
  - Explicit ID generation with data-i18n attribute support
- IdStrategy interface for extensible ID generation
- IdStrategyResolver for configuration-based strategy selection
- Support for explicit translation IDs via HTML data-i18n attributes
- Support for PHP array key-based translation IDs
- Collision detection and warnings during scanning
- Cerebras translation driver support
- Comprehensive test suite with PHPUnit
- Extensive documentation (README.md, ID_STRATEGIES.md)
- License file (MIT)
- Contributing guidelines

### Changed
- Improved ScanRunner to support ID strategies and collision detection
- Updated BuildRunner to work with new ID system
- Enhanced TranslationCatalog to support explicit and hash-based IDs
- Updated BladeDriver to accept IdStrategy in constructor
- Changed minimum stability from dev to stable
- Improved error handling and validation

### Fixed
- Fixed hash collision issues with new ID system
- Improved handling of empty translation results
- Better error messages for configuration issues

## Framework Bridges

### [volcy/translator-laravel](https://github.com/VolcyGithub/translator-laravel)
- Laravel service provider integration
- Artisan commands for scanning and building
- Translation middleware for Laravel
- Configuration file publishing

### [volcy/translator-flight](https://github.com/VolcyGithub/translator-flight)
- FlightPHP bootstrap integration
- BladeOne wrapper with view tracking
- Translation middleware for Flight
- Runway CLI commands support

---

## Versioning

For the versions available, see the [tags on this repository](https://github.com/VolcyGithub/translator-core/tags).