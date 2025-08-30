![GitHub Downloads (all assets, all releases)](https://img.shields.io/github/downloads/wp-spaghetti/wp-env/total)
![GitHub Actions Workflow Status](https://github.com/wp-spaghetti/wp-env/actions/workflows/main.yml/badge.svg)
![GitHub Issues](https://img.shields.io/github/issues/wp-spaghetti/wp-env)
![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen)
![GitHub Release](https://img.shields.io/github/v/release/wp-spaghetti/wp-env)
![License](https://img.shields.io/github/license/wp-spaghetti/wp-env)
<!--
![PHP Version](https://img.shields.io/packagist/php-v/wp-spaghetti/wp-env)
![Coverage Status](https://img.shields.io/codecov/c/github/wp-spaghetti/wp-env)
![Code Climate](https://img.shields.io/codeclimate/maintainability/wp-spaghetti/wp-env)
-->

# WP Env

A comprehensive WordPress environment management utility with typed getters, system detection and secure configuration handling.

## Features

- **Multi-Source Configuration**: WordPress constants, .env files, and getenv() with intelligent priority
- **Typed Getters**: Type-safe methods for bool, int, float, and array values
- **Environment Detection**: Automatic development, staging, and production environment detection
- **Container Detection**: Docker, Kubernetes, Podman, and other containerization detection
- **Performance Caching**: Smart caching system with sensitive data protection
- **Security-Focused**: Built-in protection for sensitive configuration values
- **WordPress Integration**: Native WordPress hooks and filters for customization
- **Zero Dependencies**: Works with or without external environment libraries
- **Bedrock Compatible**: Seamless integration with modern WordPress setups

## Installation

Install via Composer:

```bash
composer require wp-spaghetti/wp-env
```

## Quick Start

### 1. Basic Usage

```php
<?php
use WpSpaghetti\WpEnv\Environment;

// Get environment variables with fallbacks
$dbHost = Environment::get('DB_HOST', 'localhost');
$debug = Environment::getBool('WP_DEBUG', false);
$maxUploads = Environment::getInt('MAX_UPLOADS', 10);
$allowedTypes = Environment::getArray('ALLOWED_TYPES', ['jpg', 'png']);

// Check environment type
if (Environment::isDevelopment()) {
    // Development-specific code
    error_reporting(E_ALL);
}

// Check containerization
if (Environment::isDocker()) {
    // Docker-specific configuration
    $redisHost = 'redis'; // Use container name
}
```

### 2. Configuration Priority

WP Env uses the following priority order:

1. **WordPress Constants** (`define()` in wp-config.php)
2. **.env files** (via oscarotero/env if available)
3. **System environment** (`getenv()`)
4. **Default values**

```php
<?php
// wp-config.php
define('API_TIMEOUT', 30);

// .env file
API_TIMEOUT=60

// System environment
export API_TIMEOUT=90

// Result: 30 (WordPress constant wins)
$timeout = Environment::getInt('API_TIMEOUT', 10);
```

### 3. WordPress Integration

```php
<?php
// In your plugin or theme
use WpSpaghetti\WpEnv\Environment;

class MyPlugin 
{
    public function __construct() 
    {
        // Validate required configuration
        Environment::validateRequired([
            'MY_PLUGIN_API_KEY',
            'MY_PLUGIN_SECRET'
        ]);
        
        add_action('init', [$this, 'init']);
    }
    
    public function init(): void 
    {
        $config = Environment::load([
            'MY_PLUGIN_API_KEY',
            'MY_PLUGIN_TIMEOUT' => 30,
            'MY_PLUGIN_RETRIES' => 3,
            'MY_PLUGIN_ENABLED' => true
        ]);
        
        // Environment-specific behavior
        if (Environment::isProduction()) {
            $this->enableCaching();
        }
        
        if (Environment::isDebug()) {
            $this->enableDetailedLogging();
        }
    }
}
```

## API Reference

### Core Methods

#### `Environment::get(string $key, mixed $default = null): mixed`
Get environment variable with fallback to default value.

#### `Environment::getBool(string $key, bool $default = false): bool`
Get environment variable as boolean. Recognizes: `1`, `true`, `on`, `yes`, `enabled`.

#### `Environment::getInt(string $key, int $default = 0): int`
Get environment variable as integer with type conversion.

#### `Environment::getFloat(string $key, float $default = 0.0): float`
Get environment variable as float with type conversion.

#### `Environment::getArray(string $key, array $default = []): array`
Get environment variable as array (comma-separated values).

#### `Environment::getRequired(string $key): mixed`
Get required environment variable (throws exception if missing).

### Validation Methods

#### `Environment::validateRequired(array $keys): void`
Validate that all required environment variables are set.

```php
Environment::validateRequired([
    'DB_HOST',
    'DB_NAME', 
    'API_KEY'
]);
```

#### `Environment::load(array $keys): array`
Load multiple environment variables at once.

```php
// Simple array
$vars = Environment::load(['KEY1', 'KEY2', 'KEY3']);

// With defaults
$vars = Environment::load([
    'API_URL' => 'https://api.example.com',
    'TIMEOUT' => 30,
    'ENABLED' => true
]);
```

### Environment Detection

#### `Environment::getEnvironment(): string`
Get current environment type: `development`, `staging`, or `production`.

#### `Environment::isDevelopment(): bool`
Check if running in development environment.

#### `Environment::isStaging(): bool`
Check if running in staging environment.

#### `Environment::isProduction(): bool`
Check if running in production environment.

### Container Detection

#### `Environment::isDocker(): bool`
Check if running inside a Docker container.

#### `Environment::isContainer(): bool`
Check if running in any containerized environment (Docker, Podman, etc.).

### WordPress-Specific Methods

#### `Environment::isDebug(): bool`
Check if WordPress debug mode is enabled (`WP_DEBUG`).

#### `Environment::isMultisite(): bool`
Check if WordPress is running in multisite mode.

#### `Environment::isCli(): bool`
Check if running via CLI (WP-CLI or PHP CLI).

#### `Environment::isWeb(): bool`
Check if running via web request.

### System Information

#### `Environment::getServerSoftware(): string`
Get server software (nginx, apache, litespeed, iis).

#### `Environment::getPhpSapi(): string`
Get PHP SAPI information.

### Utility Methods

#### `Environment::getDebugInfo(): array`
Get comprehensive environment information for debugging.

#### `Environment::clearCache(): void`
Clear internal caches (useful for testing).

#### `Environment::addSensitiveKey(string $key): void`
Add key to sensitive list (prevents caching/logging).

## Configuration Examples

### WordPress Constants (wp-config.php)

```php
<?php
// Basic WordPress configuration
define('WP_DEBUG', true);
define('WP_ENVIRONMENT_TYPE', 'development');

// Custom application settings
define('API_BASE_URL', 'https://api.example.com');
define('CACHE_ENABLED', true);
define('MAX_UPLOAD_SIZE', 50);
define('ALLOWED_EXTENSIONS', 'jpg,png,gif,pdf');

// Container-specific settings
define('REDIS_HOST', 'redis');
define('ELASTICSEARCH_URL', 'http://elasticsearch:9200');
```

### Environment File (.env)

```env
# Environment identification
WP_ENVIRONMENT_TYPE=development
WP_DEBUG=true

# Database configuration
DB_HOST=db
DB_NAME=wordpress
DB_USER=wp_user
DB_PASSWORD=secure_password

# Application settings
API_BASE_URL=https://api.staging.example.com
CACHE_TTL=3600
MAX_RETRIES=5
FEATURE_FLAGS=feature1,feature2,feature3

# Container settings
REDIS_HOST=redis
REDIS_PORT=6379
ELASTICSEARCH_URL=http://elasticsearch:9200
```

### Docker Compose Integration

```yaml
services:
  wordpress:
    image: wordpress:latest
    environment:
      - WP_ENVIRONMENT_TYPE=development
      - WP_DEBUG=true
      - DB_HOST=db
      - REDIS_HOST=redis
      - API_TIMEOUT=60
      - DOCKER_CONTAINER=true
    volumes:
      - .:/var/www/html
    depends_on:
      - db
      - redis
      
  db:
    image: mysql:8.0
    environment:
      - MYSQL_DATABASE=wordpress
      - MYSQL_USER=wp_user
      - MYSQL_PASSWORD=secure_password
      
  redis:
    image: redis:alpine
```

## Hook System

WP Env provides several WordPress hooks for customization:

### Filter Environment Values

```php
// Modify any environment value
add_filter('wp_env_get_value', function($value, $key, $default) {
    // Force debug mode for specific users
    if ($key === 'WP_DEBUG' && current_user_can('administrator')) {
        return true;
    }
    
    return $value;
}, 10, 3);
```

### Custom Environment Detection

```php
// Override environment detection
add_filter('wp_env_get_environment', function($environment, $originalEnv) {
    // Custom logic for environment detection
    if (str_contains($_SERVER['HTTP_HOST'] ?? '', 'beta.')) {
        return 'staging';
    }
    
    return $environment;
}, 10, 2);
```

### Container Detection Override

```php
// Override Docker detection
add_filter('wp_env_is_docker', function($isDocker) {
    // Custom Docker detection logic
    return file_exists('/app/.dockerenv');
});
```

### Sensitive Key Protection

```php
// Add custom sensitive keys
add_filter('wp_env_is_sensitive_key', function($isSensitive, $key) {
    $customSensitive = [
        'STRIPE_SECRET_KEY',
        'MAILCHIMP_API_KEY',
        'GOOGLE_ANALYTICS_SECRET'
    ];
    
    return $isSensitive || in_array($key, $customSensitive);
}, 10, 2);
```

### Cache Events

```php
// React to cache clearing
add_action('wp_env_cache_cleared', function() {
    // Your custom cache clearing logic
    wp_cache_flush();
});
```

## Advanced Usage Examples

### Plugin Configuration Management

```php
<?php
use WpSpaghetti\WpEnv\Environment;

class PluginConfigManager 
{
    private array $config;
    
    public function __construct() 
    {
        $this->loadConfiguration();
    }
    
    private function loadConfiguration(): void 
    {
        // Load all plugin settings at once
        $this->config = Environment::load([
            'MYPLUGIN_API_URL' => 'https://api.example.com',
            'MYPLUGIN_TIMEOUT' => 30,
            'MYPLUGIN_RETRIES' => 3,
            'MYPLUGIN_CACHE_TTL' => 3600,
            'MYPLUGIN_FEATURES' => [],
            'MYPLUGIN_DEBUG' => false
        ]);
        
        // Environment-specific overrides
        if (Environment::isDevelopment()) {
            $this->config['MYPLUGIN_DEBUG'] = true;
            $this->config['MYPLUGIN_TIMEOUT'] = 5; // Shorter timeout for dev
        }
        
        if (Environment::isDocker()) {
            $this->config['MYPLUGIN_API_URL'] = 'http://api:8080'; // Container URL
        }
        
        // Validate critical configuration
        if (Environment::isProduction()) {
            Environment::validateRequired([
                'MYPLUGIN_API_KEY',
                'MYPLUGIN_SECRET_KEY'
            ]);
        }
    }
    
    public function get(string $key, $default = null) 
    {
        return $this->config[$key] ?? $default;
    }
}
```

### Environment-Specific Service Registration

```php
<?php
use WpSpaghetti\WpEnv\Environment;

class ServiceProvider 
{
    public function register(): void 
    {
        switch (Environment::getEnvironment()) {
            case Environment::ENV_DEVELOPMENT:
                $this->registerDevelopmentServices();
                break;
                
            case Environment::ENV_STAGING:
                $this->registerStagingServices();
                break;
                
            case Environment::ENV_PRODUCTION:
                $this->registerProductionServices();
                break;
        }
        
        // Container-specific services
        if (Environment::isContainer()) {
            $this->registerContainerServices();
        }
    }
    
    private function registerDevelopmentServices(): void 
    {
        // Development-only services
        add_action('wp_footer', [$this, 'addDebugInfo']);
        
        // Use different API endpoints
        $apiUrl = 'http://localhost:3000/api';
    }
    
    private function registerProductionServices(): void 
    {
        // Production optimizations
        add_action('init', [$this, 'enableCaching']);
        
        // Production API endpoints
        $apiUrl = Environment::get('PROD_API_URL', 'https://api.example.com');
    }
    
    private function registerContainerServices(): void 
    {
        // Container-specific networking
        $redisHost = Environment::get('REDIS_HOST', 'redis');
        $dbHost = Environment::get('DB_HOST', 'db');
    }
}
```

### Multi-Environment Configuration

```php
<?php
use WpSpaghetti\WpEnv\Environment;

class MultiEnvironmentConfig 
{
    private array $environments = [
        Environment::ENV_DEVELOPMENT => [
            'debug' => true,
            'cache_ttl' => 0,
            'api_url' => 'http://localhost:3000',
            'log_level' => 'debug'
        ],
        Environment::ENV_STAGING => [
            'debug' => true,
            'cache_ttl' => 300,
            'api_url' => 'https://staging-api.example.com',
            'log_level' => 'info'
        ],
        Environment::ENV_PRODUCTION => [
            'debug' => false,
            'cache_ttl' => 3600,
            'api_url' => 'https://api.example.com',
            'log_level' => 'error'
        ]
    ];
    
    public function get(string $key, $default = null) 
    {
        $currentEnv = Environment::getEnvironment();
        $envConfig = $this->environments[$currentEnv] ?? [];
        
        // Try environment-specific config first
        if (isset($envConfig[$key])) {
            return $envConfig[$key];
        }
        
        // Fall back to environment variable
        return Environment::get(strtoupper($key), $default);
    }
    
    public function getApiUrl(): string 
    {
        return $this->get('api_url');
    }
    
    public function getCacheTtl(): int 
    {
        return (int) $this->get('cache_ttl');
    }
    
    public function shouldEnableDebug(): bool 
    {
        return (bool) $this->get('debug');
    }
}
```

## Troubleshooting

### Debug Information

```php
// Get comprehensive environment info
$info = Environment::getDebugInfo();
print_r($info);

// Check specific values
echo "Environment: " . Environment::getEnvironment() . "\n";
echo "Is Docker: " . (Environment::isDocker() ? 'yes' : 'no') . "\n";
echo "Debug Mode: " . (Environment::isDebug() ? 'enabled' : 'disabled') . "\n";
```

### Common Issues

**Environment not detected correctly:**
- Set `WP_ENVIRONMENT_TYPE` constant in wp-config.php
- Use `.env` file with `WP_ENV=development`
- Check domain-based detection logic

**Values not loading:**
- Verify constant names (WordPress constants take priority)
- Check if oscarotero/env is installed for .env support
- Clear cache with `Environment::clearCache()`

**Container detection issues:**
- Ensure Docker environment variables are set
- Check if `.dockerenv` file exists
- Use custom detection with hooks

## Requirements

- PHP 8.0 or higher
- WordPress 5.0 or higher (for WordPress-specific features)
- Optional: [oscarotero/env](https://packagist.org/packages/oscarotero/env) for .env file support

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for a detailed list of changes for each release.

We follow [Semantic Versioning](https://semver.org/) and use [Conventional Commits](https://www.conventionalcommits.org/) to automatically generate our changelog.

### Release Process

- **Major versions** (1.0.0 → 2.0.0): Breaking changes
- **Minor versions** (1.0.0 → 1.1.0): New features, backward compatible
- **Patch versions** (1.0.0 → 1.0.1): Bug fixes, backward compatible

All releases are automatically created when changes are pushed to the `main` branch, based on commit message conventions.

## Contributing

For your contributions please use:

- [git-flow workflow](https://danielkummer.github.io/git-flow-cheatsheet/)
- [conventional commits](https://www.conventionalcommits.org)

## Sponsor

[<img src="https://cdn.buymeacoffee.com/buttons/v2/default-yellow.png" width="200" alt="Buy Me A Coffee">](https://buymeacoff.ee/frugan)

## License

(ɔ) Copyleft 2025 [Frugan](https://frugan.it).  
[GNU GPLv3](https://choosealicense.com/licenses/gpl-3.0/), see [LICENSE](LICENSE) file.

