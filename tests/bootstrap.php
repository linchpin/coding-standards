<?php
/**
 * Bootstrap file for PHPUnit tests.
 *
 * @category  Testing
 * @package   Linchpin\CodingStandards
 * @author    Linchpin <info@linchpin.com>
 * @copyright 2026 Linchpin
 * @license   GPL-2.0-or-later https://www.gnu.org/licenses/gpl-2.0.html
 * @version   1.0.0
 * @link      https://linchpin.com
 * @since     1.0.0
 */

// Setup some constants that PHPCS uses for testing.
define('PHP_CODESNIFFER_IN_TESTS', true);
define('PHP_CODESNIFFER_CBF', false);

// Check phpcs is installed.
$phpcs_dir = dirname(__DIR__) . '/vendor/squizlabs/php_codesniffer';
if (! file_exists($phpcs_dir) ) {
    throw new Exception('Could not find PHP_CodeSniffer. Run `composer install --prefer-source`');
}

// Check phpcs' test framework is available.
$test_file = $phpcs_dir . '/tests/Standards/AllSniffs.php';
if (! file_exists($test_file) ) {
    throw new Exception("Could not find PHP_CodeSniffer's tests. Make sure you run composer with `--prefer-source`");
}

// Require autoloader and bootstrap.
require dirname(__DIR__) . '/vendor/autoload.php';
require $phpcs_dir . '/tests/bootstrap.php';
