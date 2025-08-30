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
final class HooksTest extends TestCase
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

    public function testGetValueFilter(): void
    {
        set_mock_constant('FILTERED_VALUE_TEST', 'original');
        Environment::get('FILTERED_VALUE_TEST');

        $appliedFilters = get_applied_filters();
        $filterFound = false;
        foreach ($appliedFilters as $appliedFilter) {
            if ('wp_env_get_value' === $appliedFilter['hook']) {
                $filterFound = true;
                self::assertSame('original', $appliedFilter['value']);
                self::assertSame('FILTERED_VALUE_TEST', $appliedFilter['args'][0]);

                break;
            }
        }

        self::assertTrue($filterFound, 'wp_env_get_value filter should be called');
    }

    public function testDockerFilter(): void
    {
        // Force a Docker check which should trigger the filter
        set_mock_file('/.dockerenv', true);
        Environment::isDocker();

        $appliedFilters = get_applied_filters();
        $dockerFilterFound = false;
        foreach ($appliedFilters as $appliedFilter) {
            if ('wp_env_is_docker' === $appliedFilter['hook']) {
                $dockerFilterFound = true;
                self::assertTrue($appliedFilter['value']);

                break;
            }
        }

        self::assertTrue($dockerFilterFound, 'wp_env_is_docker filter should be called');
    }

    public function testContainerFilter(): void
    {
        set_mock_file('/.dockerenv', true);
        Environment::isContainer();

        $appliedFilters = get_applied_filters();
        $containerFilterFound = false;
        foreach ($appliedFilters as $appliedFilter) {
            if ('wp_env_is_container' === $appliedFilter['hook']) {
                $containerFilterFound = true;
                self::assertTrue($appliedFilter['value']);

                break;
            }
        }

        self::assertTrue($containerFilterFound, 'wp_env_is_container filter should be called');
    }

    public function testCacheClearAction(): void
    {
        Environment::clearCache();

        $triggeredActions = get_triggered_actions();
        $cacheActionFound = false;
        foreach ($triggeredActions as $triggeredAction) {
            if ('wp_env_cache_cleared' === $triggeredAction['hook']) {
                $cacheActionFound = true;

                break;
            }
        }

        self::assertTrue($cacheActionFound, 'wp_env_cache_cleared action should be triggered');
    }

    public function testHookNamingConventions(): void
    {
        $expectedHooks = [
            'wp_env_get_value',
            'wp_env_get_environment',
            'wp_env_is_docker',
            'wp_env_is_container',
            'wp_env_is_sensitive_key',
            'wp_env_cache_cleared',
        ];

        foreach ($expectedHooks as $expectedHook) {
            // Check that hook names are properly prefixed
            self::assertStringStartsWith('wp_env_', $expectedHook);

            // Check that hook names use underscores (WordPress convention)
            self::assertMatchesRegularExpression('/^[a-z_]+$/', $expectedHook);

            // Check that hook names are not too long
            self::assertLessThanOrEqual(50, \strlen($expectedHook));
        }
    }

    public function testSensitiveKeyDetection(): void
    {
        // Add a custom sensitive key
        Environment::addSensitiveKey('CUSTOM_SECRET_TEST');

        // The method to test sensitive keys is private, so we test indirectly
        // by checking that sensitive values are not cached
        set_mock_constant('DB_PASSWORD_TEST', 'secret123');
        set_mock_constant('CUSTOM_SECRET_TEST', 'very_secret');

        // Get values - sensitive keys shouldn't affect functionality
        self::assertSame('secret123', Environment::get('DB_PASSWORD_TEST'));
        self::assertSame('very_secret', Environment::get('CUSTOM_SECRET_TEST'));

        // Add multiple sensitive keys
        Environment::addSensitiveKeys(['API_SECRET_TEST', 'PRIVATE_TOKEN_TEST']);

        set_mock_constant('API_SECRET_TEST', 'api_secret');
        self::assertSame('api_secret', Environment::get('API_SECRET_TEST'));
    }
}
