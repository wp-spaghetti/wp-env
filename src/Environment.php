<?php

declare(strict_types=1);

/*
 * This file is part of the WP Env package.
 *
 * (ɔ) Frugan <dev@frugan.it>
 *
 * This source file is subject to the GNU GPLv3 or later license that is bundled
 * with this source code in the file LICENSE.
 */

namespace WpSpaghetti\WpEnv;

use Env\Env;

if (!\defined('ABSPATH')) {
    exit;
}

/**
 * Comprehensive WordPress environment management utility.
 *
 * Features:
 * - WordPress constants and .env file support
 * - Typed environment getters (bool, int, float, array)
 * - Docker and containerization detection
 * - Environment detection (development, staging, production)
 * - Value caching for performance
 * - Security-focused handling of sensitive data
 * - WordPress hooks for customization
 */
class Environment
{
    /**
     * Environment types.
     */
    public const ENV_DEVELOPMENT = 'development';

    public const ENV_STAGING = 'staging';

    public const ENV_PRODUCTION = 'production';

    /**
     * Cache for environment values to improve performance.
     *
     * @var array<string, mixed>
     */
    private static array $cache = [];

    /**
     * Cache for computed values.
     *
     * @var array<string, mixed>
     */
    private static array $computedCache = [];

    /**
     * List of sensitive keys that should not be cached or logged.
     *
     * @var array<string>
     */
    private static array $sensitiveKeys = [
        'DB_PASSWORD',
        'AUTH_KEY',
        'SECURE_AUTH_KEY',
        'LOGGED_IN_KEY',
        'NONCE_KEY',
        'AUTH_SALT',
        'SECURE_AUTH_SALT',
        'LOGGED_IN_SALT',
        'NONCE_SALT',
        'API_KEY',
        'SECRET_KEY',
        'PRIVATE_KEY',
        'PASSWORD',
        'TOKEN',
        'ACCESS_TOKEN',
        'REFRESH_TOKEN',
    ];

    /**
     * Get environment variable with WordPress constants fallback and caching.
     *
     * Priority: WordPress constants > .env files > getenv() > default
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        // In testing mode with mock variables, always bypass cache
        if (self::hasMockVariables()) {
            $value = self::getRaw($key, $default);

            // Apply WordPress filter for customization
            if (\function_exists('apply_filters')) {
                return apply_filters('wp_env_get_value', $value, $key, $default);
            }

            return $value;
        }

        // Check cache first (skip for sensitive keys)
        if (!self::isSensitiveKey($key) && isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $value = self::getRaw($key, $default);

        // Apply WordPress filter for customization
        if (\function_exists('apply_filters')) {
            $value = apply_filters('wp_env_get_value', $value, $key, $default);
        }

        // Cache non-sensitive values (but not in testing mode)
        if (!self::isSensitiveKey($key)) {
            self::$cache[$key] = $value;
        }

        return $value;
    }

    /**
     * Get environment variable as boolean.
     */
    public static function getBool(string $key, bool $default = false): bool
    {
        $value = self::get($key, $default);

        if (\is_bool($value)) {
            return $value;
        }

        if (\is_string($value)) {
            $value = strtolower(trim($value));

            return \in_array($value, ['1', 'true', 'on', 'yes', 'enabled'], true);
        }

        if (is_numeric($value)) {
            return (bool) $value;
        }

        return $default;
    }

    /**
     * Get environment variable as integer.
     */
    public static function getInt(string $key, int $default = 0): int
    {
        $value = self::get($key, $default);

        if (\is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return $default;
    }

    /**
     * Get environment variable as float.
     */
    public static function getFloat(string $key, float $default = 0.0): float
    {
        $value = self::get($key, $default);

        if (\is_float($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return $default;
    }

    /**
     * Get environment variable as array (comma-separated values).
     *
     * @param array<mixed> $default
     *
     * @return array<mixed>
     */
    public static function getArray(string $key, array $default = []): array
    {
        $value = self::get($key, $default);

        if (\is_array($value)) {
            return $value;
        }

        if (\is_string($value) && !empty($value)) {
            // Split by comma and trim whitespace
            $array = array_map('trim', explode(',', $value));

            return array_filter($array); // Remove empty values
        }

        return $default;
    }

    /**
     * Get required environment variable (throws exception if not found).
     *
     * @throws \InvalidArgumentException
     */
    public static function getRequired(string $key): mixed
    {
        $value = self::get($key);

        if (null === $value || '' === $value) {
            throw new \InvalidArgumentException(\sprintf("Required environment variable '%s' is not set", $key));
        }

        return $value;
    }

    /**
     * Validate that all required environment variables are set.
     *
     * @param array<string> $keys Array of required environment variable names
     *
     * @throws \InvalidArgumentException
     */
    public static function validateRequired(array $keys): void
    {
        $missing = [];

        foreach ($keys as $key) {
            $value = self::get($key);
            if (null === $value || '' === $value) {
                $missing[] = $key;
            }
        }

        if (!empty($missing)) {
            throw new \InvalidArgumentException(
                'Missing required environment variables: '.implode(', ', $missing)
            );
        }
    }

    /**
     * Load multiple environment variables at once.
     *
     * @param array<int|string, mixed> $keys Array of keys to load, or associative array with defaults
     *
     * @return array<string, mixed> Associative array of key => value pairs
     */
    public static function load(array $keys): array
    {
        $result = [];

        foreach ($keys as $key => $default) {
            if (\is_int($key)) {
                // Simple array ['KEY1', 'KEY2']
                $result[$default] = self::get($default);
            } else {
                // Associative array ['KEY1' => 'default1', 'KEY2' => 'default2']
                $result[$key] = self::get($key, $default);
            }
        }

        return $result;
    }

    /**
     * Check if we're running inside a Docker container.
     */
    public static function isDocker(): bool
    {
        // In testing mode, don't use computed cache
        if (!self::hasMockVariables() && isset(self::$computedCache['is_docker'])) {
            return self::$computedCache['is_docker'];
        }

        // For testing, check if we have mock functions available
        $fileExists = \function_exists('mock_file_exists') ? 'mock_file_exists' : 'file_exists';
        $fileGetContents = \function_exists('mock_file_get_contents') ? 'mock_file_get_contents' : 'file_get_contents';

        // Check for wp-logger mock environment variables first
        $dockerContainer = self::getMockAwareEnvVar('DOCKER_CONTAINER');
        $kubernetesHost = self::getMockAwareEnvVar('KUBERNETES_SERVICE_HOST');

        $isDocker = $fileExists('/.dockerenv')
            || ($fileExists('/proc/1/cgroup') && str_contains((string) $fileGetContents('/proc/1/cgroup'), 'docker'))
            || !empty($dockerContainer)
            || !empty($kubernetesHost) // Kubernetes detection
            || ($fileExists('/proc/self/mountinfo') && str_contains((string) $fileGetContents('/proc/self/mountinfo'), 'docker'));

        // Allow override via WordPress filter
        if (\function_exists('apply_filters')) {
            $isDocker = apply_filters('wp_env_is_docker', $isDocker);
        }

        // Only cache if not in testing mode
        if (!self::hasMockVariables()) {
            self::$computedCache['is_docker'] = $isDocker;
        }

        return $isDocker;
    }

    /**
     * Check if we're running in a containerized environment (Docker, Podman, etc.).
     */
    public static function isContainer(): bool
    {
        // In testing mode, don't use computed cache
        if (!self::hasMockVariables() && isset(self::$computedCache['is_container'])) {
            return self::$computedCache['is_container'];
        }

        $podmanContainer = self::getMockAwareEnvVar('PODMAN_CONTAINER');
        $singularityContainer = self::getMockAwareEnvVar('SINGULARITY_CONTAINER');
        $apptainerContainer = self::getMockAwareEnvVar('APPTAINER_CONTAINER');

        $isContainer = self::isDocker()
            || !empty($podmanContainer)
            || !empty($singularityContainer)
            || !empty($apptainerContainer);

        if (\function_exists('apply_filters')) {
            $isContainer = apply_filters('wp_env_is_container', $isContainer);
        }

        // Only cache if not in testing mode
        if (!self::hasMockVariables()) {
            self::$computedCache['is_container'] = $isContainer;
        }

        return $isContainer;
    }

    /**
     * Get current environment type (development, staging, production).
     */
    public static function getEnvironment(): string
    {
        // In testing mode, don't use computed cache
        if (!self::hasMockVariables() && isset(self::$computedCache['environment'])) {
            return self::$computedCache['environment'];
        }

        // Check various environment indicators
        $env = self::get('WP_ENV', '');

        if (empty($env)) {
            $env = self::get('WP_ENVIRONMENT_TYPE', '');
        }

        if (empty($env)) {
            $env = self::get('ENVIRONMENT', '');
        }

        if (empty($env)) {
            $env = self::get('NODE_ENV', '');
        }

        // Normalize environment name
        $env = strtolower(trim($env));

        // Map common variations
        $environment = match ($env) {
            'dev', 'develop', 'development', 'local' => self::ENV_DEVELOPMENT,
            'stage', 'staging', 'test', 'testing' => self::ENV_STAGING,
            'prod', 'production', 'live' => self::ENV_PRODUCTION,
            default => self::detectEnvironmentByIndicators()
        };

        if (\function_exists('apply_filters')) {
            $environment = apply_filters('wp_env_get_environment', $environment, $env);
        }

        // Only cache if not in testing mode
        if (!self::hasMockVariables()) {
            self::$computedCache['environment'] = $environment;
        }

        return $environment;
    }

    /**
     * Check if we're in development environment.
     */
    public static function isDevelopment(): bool
    {
        return self::ENV_DEVELOPMENT === self::getEnvironment();
    }

    /**
     * Check if we're in staging environment.
     */
    public static function isStaging(): bool
    {
        return self::ENV_STAGING === self::getEnvironment();
    }

    /**
     * Check if we're in production environment.
     */
    public static function isProduction(): bool
    {
        return self::ENV_PRODUCTION === self::getEnvironment();
    }

    /**
     * Check if WordPress debug mode is enabled.
     */
    public static function isDebug(): bool
    {
        return self::getBool('WP_DEBUG', false);
    }

    /**
     * Check if we're in WordPress multisite.
     */
    public static function isMultisite(): bool
    {
        if (self::getBool('MULTISITE', false)) {
            return true;
        }

        return \defined('MULTISITE') && MULTISITE;
    }

    /**
     * Get server software information.
     */
    public static function getServerSoftware(): string
    {
        $software = $_SERVER['SERVER_SOFTWARE'] ?? 'unknown';

        // Common server software detection
        if (str_contains(strtolower($software), 'nginx')) {
            return 'nginx';
        }

        if (str_contains(strtolower($software), 'apache')) {
            return 'apache';
        }

        if (str_contains(strtolower($software), 'litespeed')) {
            return 'litespeed';
        }

        if (str_contains(strtolower($software), 'iis')) {
            return 'iis';
        }

        return $software;
    }

    /**
     * Get PHP SAPI (Server API) information.
     */
    public static function getPhpSapi(): string
    {
        return \PHP_SAPI;
    }

    /**
     * Check if we're running via CLI.
     */
    public static function isCli(): bool
    {
        return \defined('WP_CLI') && WP_CLI || 'cli' === \PHP_SAPI;
    }

    /**
     * Check if we're running via web request.
     */
    public static function isWeb(): bool
    {
        return !self::isCli();
    }

    /**
     * Get all environment information for debugging.
     *
     * @return array<string, mixed>
     */
    public static function getDebugInfo(): array
    {
        return [
            'environment' => self::getEnvironment(),
            'is_development' => self::isDevelopment(),
            'is_staging' => self::isStaging(),
            'is_production' => self::isProduction(),
            'is_debug' => self::isDebug(),
            'is_multisite' => self::isMultisite(),
            'is_docker' => self::isDocker(),
            'is_container' => self::isContainer(),
            'is_cli' => self::isCli(),
            'server_software' => self::getServerSoftware(),
            'php_sapi' => self::getPhpSapi(),
            'php_version' => PHP_VERSION,
            'wp_version' => \function_exists('get_bloginfo') ? get_bloginfo('version') : 'unknown',
            'cache_count' => \count(self::$cache),
            'computed_cache_count' => \count(self::$computedCache),
            'has_oscarotero_env' => \function_exists('Env\env'),
        ];
    }

    /**
     * Clear all caches (useful for testing or when environment changes).
     */
    public static function clearCache(): void
    {
        self::$cache = [];
        self::$computedCache = [];

        if (\function_exists('do_action')) {
            do_action('wp_env_cache_cleared');
        }
    }

    /**
     * Add sensitive key to the list (to prevent caching/logging).
     */
    public static function addSensitiveKey(string $key): void
    {
        if (!\in_array($key, self::$sensitiveKeys, true)) {
            self::$sensitiveKeys[] = $key;
        }
    }

    /**
     * Add multiple sensitive keys.
     *
     * @param array<string> $keys
     */
    public static function addSensitiveKeys(array $keys): void
    {
        foreach ($keys as $key) {
            self::addSensitiveKey($key);
        }
    }

    /**
     * Check if we're in a testing environment with mock variables.
     */
    private static function hasMockVariables(): bool
    {
        global $mock_environment_vars, $mock_env_vars, $mock_constants;

        return (isset($mock_environment_vars) && !empty($mock_environment_vars))
            || (isset($mock_env_vars) && !empty($mock_env_vars))
            || (isset($mock_constants) && !empty($mock_constants));
    }

    /**
     * Get raw environment variable without caching or filtering.
     */
    private static function getRaw(string $key, mixed $default = null): mixed
    {
        // First check wp-logger AND wp-env mock environment variables (ABSOLUTE PRIORITY in testing)
        $mockValue = self::getMockAwareEnvVar($key);
        if (false !== $mockValue) {
            return $mockValue;
        }

        // Check wp-env mock constants (for testing compatibility) - also absolute priority
        global $mock_constants;
        if (isset($mock_constants) && \is_array($mock_constants) && isset($mock_constants[$key])) {
            return $mock_constants[$key];
        }

        // Only proceed with normal priority if we're NOT in testing mode with mock variables
        if (self::hasMockVariables()) {
            // In testing mode, if no mock found, return default immediately
            // This prevents WordPress constants from overriding during testing
            return $default;
        }

        // Normal priority outside of testing:
        // Then try WordPress constants (more WordPress-native)
        if (\defined($key)) {
            return \constant($key);
        }

        // Then try oscarotero/env (for Bedrock and modern setups)
        if (\function_exists('Env\env')) {
            /** @phpstan-ignore-next-line */
            $originalDefault = Env::$default ?? null;
            // @phpstan-ignore-next-line
            Env::$default = $default;
            $value = \Env\env($key);
            // @phpstan-ignore-next-line
            Env::$default = $originalDefault; // Restore original default

            return $value;
        }

        // Finally try getenv() fallback (with test mock support)
        $getEnv = \function_exists('mock_getenv') ? 'mock_getenv' : 'getenv';
        $value = $getEnv($key);

        return false !== $value ? $value : $default;
    }

    /**
     * Get environment variable with mock support for wp-logger testing.
     */
    private static function getMockAwareEnvVar(string $key): false|string
    {
        // Check wp-logger mock environment variables first (absolute priority)
        global $mock_environment_vars;
        if (isset($mock_environment_vars) && \is_array($mock_environment_vars)) {
            if (isset($mock_environment_vars[$key])) {
                return $mock_environment_vars[$key];
            }
        }

        // Check wp-env mock environment variables
        global $mock_env_vars;
        if (isset($mock_env_vars) && \is_array($mock_env_vars)) {
            if (isset($mock_env_vars[$key])) {
                return $mock_env_vars[$key];
            }
        }

        // In testing mode, don't fall back to regular getenv to maintain mock isolation
        if (self::hasMockVariables()) {
            return false;
        }

        // Fall back to regular getenv only in non-testing mode
        $getEnv = \function_exists('mock_getenv') ? 'mock_getenv' : 'getenv';

        return $getEnv($key);
    }

    /**
     * Check if a key is considered sensitive.
     */
    private static function isSensitiveKey(string $key): bool
    {
        // Check exact matches
        if (\in_array($key, self::$sensitiveKeys, true)) {
            return true;
        }

        // Check patterns (case-insensitive)
        $keyLower = strtolower($key);
        foreach (self::$sensitiveKeys as $sensitiveKey) {
            if (str_contains($keyLower, strtolower($sensitiveKey))) {
                return true;
            }
        }

        // Allow customization via WordPress filter
        if (\function_exists('apply_filters')) {
            return apply_filters('wp_env_is_sensitive_key', false, $key);
        }

        return false;
    }

    /**
     * Detect environment by various indicators when not explicitly set.
     */
    private static function detectEnvironmentByIndicators(): string
    {
        // Development indicators - use mock-aware methods
        $wpDebug = self::getBool('WP_DEBUG', false);
        $serverName = self::getMockAwareEnvVar('SERVER_NAME');
        $httpHost = self::getMockAwareEnvVar('HTTP_HOST');

        if ($wpDebug
            && ('localhost' === $serverName
             || str_contains((string) $httpHost, 'localhost')
             || str_contains((string) $httpHost, '.local')
             || str_contains((string) $httpHost, '.test')
             || str_contains((string) $httpHost, '.dev'))) {
            return self::ENV_DEVELOPMENT;
        }

        // Staging indicators
        if (str_contains((string) $httpHost, 'staging')
            || str_contains((string) $httpHost, 'stage')
            || str_contains((string) $httpHost, 'test')) {
            return self::ENV_STAGING;
        }

        // Production is the safe default
        return self::ENV_PRODUCTION;
    }
}
