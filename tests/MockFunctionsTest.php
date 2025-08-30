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

/**
 * @internal
 *
 * @coversNothing
 */
final class MockFunctionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Reset global test state
        reset_wp_env_test_globals();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Reset test globals
        reset_wp_env_test_globals();
    }

    public function testMockFileExists(): void
    {
        // Test that mock functions exist
        self::assertTrue(\function_exists('mock_file_exists'));

        // Test mock file behavior
        set_mock_file('/.dockerenv', true);
        self::assertTrue(mock_file_exists('/.dockerenv'));

        set_mock_file('/.dockerenv', false);
        self::assertFalse(mock_file_exists('/.dockerenv'));

        // Test fallback to real file_exists
        self::assertTrue(mock_file_exists(__FILE__)); // This file should exist
    }

    public function testMockFileGetContents(): void
    {
        self::assertTrue(\function_exists('mock_file_get_contents'));

        // Test mock file content
        set_mock_file('/.dockerenv', true);
        $content = mock_file_get_contents('/.dockerenv');
        self::assertSame('', $content);

        set_mock_file('/proc/1/cgroup', true);
        $content = mock_file_get_contents('/proc/1/cgroup');
        self::assertSame('docker', $content);

        // Test non-existent mock file
        set_mock_file('/nonexistent', false);
        $content = mock_file_get_contents('/nonexistent');
        self::assertFalse($content);
    }

    public function testMockGetEnv(): void
    {
        self::assertTrue(\function_exists('mock_getenv'));

        // Test mock environment variable
        set_mock_env_var('TEST_VAR', 'test_value');
        self::assertSame('test_value', mock_getenv('TEST_VAR'));

        // Test non-existent variable
        self::assertFalse(mock_getenv('NONEXISTENT_VAR'));
    }

    public function testGlobalStateHelpers(): void
    {
        // Test setting constants (use unique names to avoid conflicts)
        $constantName = 'TEST_CONSTANT_'.uniqid();
        set_mock_constant($constantName, 'test_value');
        self::assertTrue(\defined($constantName));
        self::assertSame('test_value', \constant($constantName));

        // Test environment variables
        set_mock_env_var('TEST_ENV_UNIQUE', 'env_value');
        global $mock_env_vars;
        self::assertSame('env_value', $mock_env_vars['TEST_ENV_UNIQUE']);

        // Test file mocking
        set_mock_file('/test/file', true);
        global $mock_files;
        self::assertTrue($mock_files['/test/file']);
    }

    public function testResetGlobals(): void
    {
        // Set some test data
        set_mock_constant('TEMP_CONSTANT', 'temp');
        set_mock_env_var('TEMP_VAR', 'temp');
        set_mock_file('/temp/file', true);

        // Reset globals
        reset_wp_env_test_globals();

        // Check that globals are cleared
        global $applied_filters, $triggered_actions, $mock_env_vars, $mock_files;

        self::assertEmpty($applied_filters);
        self::assertEmpty($triggered_actions);
        self::assertEmpty($mock_env_vars);
        self::assertEmpty($mock_files);
    }
}
