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

namespace WpSpaghetti\WpEnv\Tests;

use PHPUnit\Framework\TestCase;
use WpSpaghetti\WpEnv\Environment;

/**
 * @internal
 *
 * @coversNothing
 */
final class EnvironmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Reset global test state
        reset_wp_env_test_globals();

        // Clear Environment cache
        Environment::clearCache();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Reset test globals
        reset_wp_env_test_globals();

        // Clear Environment cache
        Environment::clearCache();
    }

    public function testBasicGet(): void
    {
        // Test WordPress constant priority
        set_mock_constant('TEST_CONSTANT', 'constant_value');
        self::assertSame('constant_value', Environment::get('TEST_CONSTANT'));

        // Test environment variable when no constant exists
        set_mock_env_var('TEST_ENV_ONLY', 'env_value');
        self::assertSame('env_value', Environment::get('TEST_ENV_ONLY'));

        // Test default value when neither exists
        self::assertSame('default', Environment::get('NONEXISTENT', 'default'));

        // Test null default - this should return null when no value exists and no default provided
        self::assertNull(Environment::get('NONEXISTENT_NULL'));
    }

    public function testGetBool(): void
    {
        // Test various truthy values
        set_mock_constant('BOOL_TRUE_TEST', true);
        set_mock_constant('STRING_TRUE_TEST', 'true');
        set_mock_constant('STRING_1_TEST', '1');
        set_mock_constant('STRING_ON_TEST', 'on');
        set_mock_constant('STRING_YES_TEST', 'yes');
        set_mock_constant('STRING_ENABLED_TEST', 'enabled');

        self::assertTrue(Environment::getBool('BOOL_TRUE_TEST'));
        self::assertTrue(Environment::getBool('STRING_TRUE_TEST'));
        self::assertTrue(Environment::getBool('STRING_1_TEST'));
        self::assertTrue(Environment::getBool('STRING_ON_TEST'));
        self::assertTrue(Environment::getBool('STRING_YES_TEST'));
        self::assertTrue(Environment::getBool('STRING_ENABLED_TEST'));

        // Test falsy values
        set_mock_constant('BOOL_FALSE_TEST', false);
        set_mock_constant('STRING_FALSE_TEST', 'false');
        set_mock_constant('STRING_0_TEST', '0');
        set_mock_constant('STRING_OFF_TEST', 'off');

        self::assertFalse(Environment::getBool('BOOL_FALSE_TEST'));
        self::assertFalse(Environment::getBool('STRING_FALSE_TEST'));
        self::assertFalse(Environment::getBool('STRING_0_TEST'));
        self::assertFalse(Environment::getBool('STRING_OFF_TEST'));

        // Test default
        self::assertTrue(Environment::getBool('NONEXISTENT_BOOL', true));
        self::assertFalse(Environment::getBool('NONEXISTENT_BOOL2', false));
    }

    public function testGetInt(): void
    {
        set_mock_constant('INT_VALUE', 42);
        set_mock_constant('STRING_INT', '123');
        set_mock_constant('FLOAT_VALUE', 3.14);

        self::assertSame(42, Environment::getInt('INT_VALUE'));
        self::assertSame(123, Environment::getInt('STRING_INT'));
        self::assertSame(3, Environment::getInt('FLOAT_VALUE')); // Float truncated to int

        // Test default
        self::assertSame(10, Environment::getInt('NONEXISTENT', 10));

        // Test non-numeric string
        set_mock_constant('NON_NUMERIC', 'not_a_number');
        self::assertSame(5, Environment::getInt('NON_NUMERIC', 5));
    }

    public function testGetFloat(): void
    {
        set_mock_constant('FLOAT_VALUE', 3.14);
        set_mock_constant('STRING_FLOAT', '2.718');
        set_mock_constant('INT_VALUE', 42);

        self::assertSame(3.14, Environment::getFloat('FLOAT_VALUE'));
        self::assertSame(2.718, Environment::getFloat('STRING_FLOAT'));
        self::assertSame(42.0, Environment::getFloat('INT_VALUE'));

        // Test default
        self::assertSame(1.5, Environment::getFloat('NONEXISTENT', 1.5));
    }

    public function testGetArray(): void
    {
        set_mock_constant('ARRAY_VALUE', ['a', 'b', 'c']);
        set_mock_constant('STRING_ARRAY', 'one,two,three');
        set_mock_constant('STRING_ARRAY_SPACES', 'one, two , three ');
        set_mock_constant('EMPTY_STRING', '');

        self::assertSame(['a', 'b', 'c'], Environment::getArray('ARRAY_VALUE'));
        self::assertSame(['one', 'two', 'three'], Environment::getArray('STRING_ARRAY'));
        self::assertSame(['one', 'two', 'three'], Environment::getArray('STRING_ARRAY_SPACES'));
        self::assertSame([], Environment::getArray('EMPTY_STRING'));

        // Test default
        self::assertSame(['default'], Environment::getArray('NONEXISTENT', ['default']));
    }

    public function testGetRequired(): void
    {
        set_mock_constant('REQUIRED_VALUE', 'exists');

        self::assertSame('exists', Environment::getRequired('REQUIRED_VALUE'));

        // Test exception for missing value
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Required environment variable 'MISSING' is not set");
        Environment::getRequired('MISSING');
    }

    public function testValidateRequired(): void
    {
        set_mock_constant('VAR1', 'value1');
        set_mock_constant('VAR2', 'value2');

        // Should not throw when all variables exist
        Environment::validateRequired(['VAR1', 'VAR2']);

        // Should throw when some variables are missing
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required environment variables: MISSING1, MISSING2');
        Environment::validateRequired(['VAR1', 'MISSING1', 'MISSING2']);
    }

    public function testLoad(): void
    {
        set_mock_constant('VAR1', 'value1');
        set_mock_constant('VAR2', 'value2');

        // Simple array
        $result = Environment::load(['VAR1', 'VAR2', 'MISSING']);
        $expected = [
            'VAR1' => 'value1',
            'VAR2' => 'value2',
            'MISSING' => null,
        ];
        self::assertSame($expected, $result);

        // Array with defaults
        $result = Environment::load([
            'VAR1' => 'default1',
            'MISSING' => 'default_value',
        ]);
        $expected = [
            'VAR1' => 'value1',
            'MISSING' => 'default_value',
        ];
        self::assertSame($expected, $result);
    }

    public function testIsDocker(): void
    {
        // Test dockerenv file detection
        reset_wp_env_test_globals();
        Environment::clearCache();
        set_mock_file('/.dockerenv', true);
        self::assertTrue(Environment::isDocker());

        // Reset and test negative case - this is critical
        reset_wp_env_test_globals();
        Environment::clearCache();
        set_mock_file('/.dockerenv', false);
        set_mock_file('/proc/1/cgroup', false);
        set_mock_file('/proc/self/mountinfo', false);
        // Ensure no Docker environment variables are set
        self::assertFalse(Environment::isDocker());

        // Reset and test cgroup detection
        reset_wp_env_test_globals();
        Environment::clearCache();
        set_mock_file('/proc/1/cgroup', true);
        self::assertTrue(Environment::isDocker());

        // Reset and test environment variable detection
        reset_wp_env_test_globals();
        Environment::clearCache();
        set_mock_env_var('DOCKER_CONTAINER', '1');
        self::assertTrue(Environment::isDocker());

        // Reset and test Kubernetes detection
        reset_wp_env_test_globals();
        Environment::clearCache();
        set_mock_env_var('KUBERNETES_SERVICE_HOST', 'kubernetes');
        self::assertTrue(Environment::isDocker());

        // Reset and test mountinfo detection
        reset_wp_env_test_globals();
        Environment::clearCache();
        set_mock_file('/proc/self/mountinfo', true);
        self::assertTrue(Environment::isDocker());

        // Final test - no Docker indicators should return false
        reset_wp_env_test_globals();
        Environment::clearCache();
        // Explicitly set all indicators to false/empty
        set_mock_file('/.dockerenv', false);
        set_mock_file('/proc/1/cgroup', false);
        set_mock_file('/proc/self/mountinfo', false);
        self::assertFalse(Environment::isDocker());
    }

    public function testIsContainer(): void
    {
        // Docker should be detected as container
        reset_wp_env_test_globals();
        Environment::clearCache();
        set_mock_file('/.dockerenv', true);
        self::assertTrue(Environment::isContainer());

        // Reset and test Podman
        reset_wp_env_test_globals();
        Environment::clearCache();
        set_mock_env_var('PODMAN_CONTAINER', '1');
        self::assertTrue(Environment::isContainer());

        // Reset and test Singularity
        reset_wp_env_test_globals();
        Environment::clearCache();
        set_mock_env_var('SINGULARITY_CONTAINER', '1');
        self::assertTrue(Environment::isContainer());

        // Reset and test no container indicators - this should return false
        reset_wp_env_test_globals();
        Environment::clearCache();
        // Ensure all container indicators are false
        set_mock_file('/.dockerenv', false);
        set_mock_file('/proc/1/cgroup', false);
        set_mock_file('/proc/self/mountinfo', false);
        self::assertFalse(Environment::isContainer());
    }

    public function testEnvironmentDetection(): void
    {
        // Test explicit WP_ENV
        set_mock_constant('WP_ENV_TEST', 'development');
        Environment::clearCache();
        // We need to clear cache and use a different key since WP_ENV might be already defined
        self::assertSame('development', Environment::get('WP_ENV_TEST'));

        // Test environment mapping
        set_mock_constant('WP_ENV_TEST2', 'dev');
        self::assertSame('dev', Environment::get('WP_ENV_TEST2'));

        // Test production mapping
        set_mock_constant('WP_ENV_TEST3', 'prod');
        self::assertSame('prod', Environment::get('WP_ENV_TEST3'));

        // Test staging mapping
        set_mock_constant('WP_ENV_TEST4', 'stage');
        self::assertSame('stage', Environment::get('WP_ENV_TEST4'));

        // Since we can't easily test getEnvironment() due to constants being already defined,
        // let's test the individual methods
        self::assertTrue(Environment::isProduction() || Environment::isDevelopment() || Environment::isStaging());
    }

    public function testWordPressMethods(): void
    {
        // Test isDebug with explicit mock values
        set_mock_constant('WP_DEBUG_TEST_TRUE', true);
        self::assertTrue(Environment::getBool('WP_DEBUG_TEST_TRUE'));

        set_mock_constant('WP_DEBUG_TEST_FALSE', false);
        self::assertFalse(Environment::getBool('WP_DEBUG_TEST_FALSE'));

        // Test isMultisite
        set_mock_constant('MULTISITE_TEST', true);
        // We can't easily test the actual isMultisite() method since MULTISITE might be defined
        self::assertTrue(Environment::getBool('MULTISITE_TEST'));

        // Test isCli (based on PHP_SAPI)
        self::assertTrue(Environment::isCli()); // In PHPUnit, SAPI is usually 'cli'
        self::assertFalse(Environment::isWeb());

        // Test getPhpSapi
        self::assertSame(\PHP_SAPI, Environment::getPhpSapi());
    }

    public function testGetServerSoftware(): void
    {
        $_SERVER['SERVER_SOFTWARE'] = 'nginx/1.20.1';
        self::assertSame('nginx', Environment::getServerSoftware());

        $_SERVER['SERVER_SOFTWARE'] = 'Apache/2.4.41 (Ubuntu)';
        self::assertSame('apache', Environment::getServerSoftware());

        $_SERVER['SERVER_SOFTWARE'] = 'LiteSpeed';
        self::assertSame('litespeed', Environment::getServerSoftware());

        $_SERVER['SERVER_SOFTWARE'] = 'Microsoft-IIS/10.0';
        self::assertSame('iis', Environment::getServerSoftware());

        $_SERVER['SERVER_SOFTWARE'] = 'Custom Server';
        self::assertSame('Custom Server', Environment::getServerSoftware());

        unset($_SERVER['SERVER_SOFTWARE']);
        self::assertSame('unknown', Environment::getServerSoftware());
    }

    public function testGetPhpSapi(): void
    {
        self::assertSame(\PHP_SAPI, Environment::getPhpSapi());
    }

    public function testGetDebugInfo(): void
    {
        $debugInfo = Environment::getDebugInfo();

        // Remove redundant assertIsArray since getDebugInfo() return type is already array
        self::assertNotEmpty($debugInfo);
        self::assertArrayHasKey('environment', $debugInfo);
        self::assertArrayHasKey('is_development', $debugInfo);
        self::assertArrayHasKey('is_staging', $debugInfo);
        self::assertArrayHasKey('is_production', $debugInfo);
        self::assertArrayHasKey('is_debug', $debugInfo);
        self::assertArrayHasKey('is_multisite', $debugInfo);
        self::assertArrayHasKey('is_docker', $debugInfo);
        self::assertArrayHasKey('is_container', $debugInfo);
        self::assertArrayHasKey('is_cli', $debugInfo);
        self::assertArrayHasKey('server_software', $debugInfo);
        self::assertArrayHasKey('php_sapi', $debugInfo);
        self::assertArrayHasKey('php_version', $debugInfo);
        self::assertArrayHasKey('wp_version', $debugInfo);
        self::assertArrayHasKey('cache_count', $debugInfo);
        self::assertArrayHasKey('computed_cache_count', $debugInfo);
        self::assertArrayHasKey('has_oscarotero_env', $debugInfo);

        self::assertSame(PHP_VERSION, $debugInfo['php_version']);
        self::assertSame('6.4', $debugInfo['wp_version']);
        self::assertFalse($debugInfo['has_oscarotero_env']);
    }

    public function testCaching(): void
    {
        set_mock_constant('CACHED_VALUE', 'test');

        // First call should be cached
        $value1 = Environment::get('CACHED_VALUE');
        $value2 = Environment::get('CACHED_VALUE');

        self::assertSame($value1, $value2);

        // Clear cache and verify
        Environment::clearCache();

        $actions = get_triggered_actions();
        $cacheCleared = false;
        foreach ($actions as $action) {
            if ('wp_env_cache_cleared' === $action['hook']) {
                $cacheCleared = true;

                break;
            }
        }

        self::assertTrue($cacheCleared);
    }

    public function testSensitiveKeys(): void
    {
        // Add custom sensitive key
        Environment::addSensitiveKey('CUSTOM_SECRET');
        Environment::addSensitiveKeys(['API_SECRET', 'PRIVATE_TOKEN']);

        // Test that we can still get sensitive values (they just won't be cached)
        set_mock_constant('DB_PASSWORD', 'secret123');
        set_mock_constant('CUSTOM_SECRET', 'very_secret');

        self::assertSame('secret123', Environment::get('DB_PASSWORD'));
        self::assertSame('very_secret', Environment::get('CUSTOM_SECRET'));
    }

    public function testHooks(): void
    {
        // Test value filtering hook
        set_mock_constant('FILTERED_VALUE', 'original');
        Environment::get('FILTERED_VALUE');

        $appliedFilters = get_applied_filters();
        $filterFound = false;
        foreach ($appliedFilters as $appliedFilter) {
            if ('wp_env_get_value' === $appliedFilter['hook']) {
                $filterFound = true;
                self::assertSame('original', $appliedFilter['value']);
                self::assertSame('FILTERED_VALUE', $appliedFilter['args'][0]);

                break;
            }
        }

        self::assertTrue($filterFound, 'wp_env_get_value filter should be called');
    }
}
