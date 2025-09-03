# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.2.0](https://github.com/wp-spaghetti/wp-env/compare/v2.1.0...v2.2.0) (2025-09-03)

### Features

* improve testing env ([#23](https://github.com/wp-spaghetti/wp-env/issues/23)) ([#24](https://github.com/wp-spaghetti/wp-env/issues/24)) ([ac9cfaa](https://github.com/wp-spaghetti/wp-env/commit/ac9cfaa6e3c93c7c9e36fc64ee2109e2349950ae))

## [2.1.0](https://github.com/wp-spaghetti/wp-env/compare/v2.0.0...v2.1.0) (2025-09-02)

### Features

* improve mock ([#21](https://github.com/wp-spaghetti/wp-env/issues/21)) ([#22](https://github.com/wp-spaghetti/wp-env/issues/22)) ([aeef3fb](https://github.com/wp-spaghetti/wp-env/commit/aeef3fb279842a201dbca4cadf91438d638b9ab3))

## [2.0.0](https://github.com/wp-spaghetti/wp-env/compare/v1.0.0...v2.0.0) (2025-09-02)

### ⚠ BREAKING CHANGES

* major release (#15) (#16)

### Features

* add global mock ([#13](https://github.com/wp-spaghetti/wp-env/issues/13)) ([72bb2d9](https://github.com/wp-spaghetti/wp-env/commit/72bb2d9e6d2355ebc8a3fc58c67b05b16a5a4512)), closes [#8](https://github.com/wp-spaghetti/wp-env/issues/8) [#10](https://github.com/wp-spaghetti/wp-env/issues/10) [#12](https://github.com/wp-spaghetti/wp-env/issues/12)
* major release ([#15](https://github.com/wp-spaghetti/wp-env/issues/15)) ([#16](https://github.com/wp-spaghetti/wp-env/issues/16)) ([47814f3](https://github.com/wp-spaghetti/wp-env/commit/47814f313b3655b4fa1f0987249ac89a5e3545ec))
* minor release ([#17](https://github.com/wp-spaghetti/wp-env/issues/17)) ([#18](https://github.com/wp-spaghetti/wp-env/issues/18)) ([7778847](https://github.com/wp-spaghetti/wp-env/commit/77788477894c78376787d34d5c6cc2d3aabb49c3))
* minor release ([#19](https://github.com/wp-spaghetti/wp-env/issues/19)) ([#20](https://github.com/wp-spaghetti/wp-env/issues/20)) ([0a60a10](https://github.com/wp-spaghetti/wp-env/commit/0a60a10d842b777e84336bf1775d63f6db237c8c))

## 1.0.0 (2025-08-30)

### Features

* initial release with comprehensive WordPress environment management ([6744b4d](https://github.com/wp-spaghetti/wp-env/commit/6744b4d5ef18adc1bf1e01dfd7a57aecbd327089))

### Added
- Initial release of WP Env package
- Multi-source configuration support (WordPress constants, .env files, getenv())
- Typed getters for bool, int, float, and array values
- Environment detection (development, staging, production)
- Container detection (Docker, Kubernetes, Podman)
- Performance caching with sensitive data protection
- WordPress integration with hooks and filters
- Bedrock compatibility
- Zero dependencies core functionality
- Comprehensive test suite
- Complete documentation and examples

### Security
- Built-in protection for sensitive configuration values
- Secure caching system that excludes sensitive keys
