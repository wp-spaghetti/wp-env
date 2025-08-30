# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 1.0.0 (2025-08-30)

### Features

* initial release with comprehensive WordPress environment management ([6744b4d](https://github.com/wp-spaghetti/wp-env/commit/6744b4d5ef18adc1bf1e01dfd7a57aecbd327089))

## [Unreleased]

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
