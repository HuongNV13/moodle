<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * PHPUnit bootstrap function
 *
 * Note: these functions must be self contained and must not rely on any other library or include
 *
 * @package    core
 * @category   phpunit
 * @copyright  2012 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../testing/lib.php');

define('PHPUNIT_EXITCODE_PHPUNITMISSING', 129);
define('PHPUNIT_EXITCODE_PHPUNITWRONG', 130);
define('PHPUNIT_EXITCODE_PHPUNITEXTMISSING', 131);
define('PHPUNIT_EXITCODE_CONFIGERROR', 135);
define('PHPUNIT_EXITCODE_CONFIGWARNING', 136);
define('PHPUNIT_EXITCODE_INSTALL', 140);
define('PHPUNIT_EXITCODE_REINSTALL', 141);

/**
 * Print error and stop execution
 * @param int $errorcode The exit error code
 * @param string $text An error message to display
 * @return void stops code execution with error code
 */
function phpunit_bootstrap_error($errorcode, $text = '') {
    switch ($errorcode) {
        case 0:
            // this is not an error, just print information and exit
            break;
        case 1:
            $text = 'Error: '.$text;
            break;
        case PHPUNIT_EXITCODE_PHPUNITMISSING:
            $text = "Can not find PHPUnit library, to install use: php composer.phar install";
            break;
        case PHPUNIT_EXITCODE_PHPUNITWRONG:
            $text = 'Moodle requires PHPUnit 3.6.x, '.$text.' is not compatible';
            break;
        case PHPUNIT_EXITCODE_PHPUNITEXTMISSING:
            $text = 'Moodle can not find required PHPUnit extension '.$text;
            break;
        case PHPUNIT_EXITCODE_CONFIGERROR:
            $text = "Moodle PHPUnit environment configuration error:\n".$text;
            break;
        case PHPUNIT_EXITCODE_CONFIGWARNING:
            $text = "Moodle PHPUnit environment configuration warning:\n".$text;
            break;
        case PHPUNIT_EXITCODE_INSTALL:
            $initpath = testing_cli_argument_path('/public/admin/tool/phpunit/cli/init.php');
            $text = "Moodle PHPUnit environment is not initialised, please use:\n php $initpath";
            break;
        case PHPUNIT_EXITCODE_REINSTALL:
            $initpath = testing_cli_argument_path('/public/admin/tool/phpunit/cli/init.php');
            $utilpath = testing_cli_argument_path('/public/admin/tool/phpunit/cli/util.php');
            $text = "Moodle PHPUnit environment was initialised for different version, please use:\n"
                . " php $initpath\n or php $utilpath --upgrade";
            break;
        default:
            $text = empty($text) ? '' : ': '.$text;
            $text = 'Unknown error '.$errorcode.$text;
            break;
    }

    testing_error($errorcode, $text);
}

/**
 * Get the number of the parallel run process this script is running in.
 *
 * Parallel runs are driven by ParaTest, which sets PARATEST=1 and TEST_TOKEN=n (1..N) in the environment of each worker.
 * Both must be present so that an unrelated TEST_TOKEN in the environment does not switch on parallel mode.
 *
 * @return int The process number starting from 1, or 0 when this is not a parallel run.
 */
function phpunit_get_run_process(): int {
    if (!getenv('PARATEST')) {
        return 0;
    }

    $token = getenv('TEST_TOKEN');
    if ($token === false || $token === '') {
        return 0;
    }

    if (!ctype_digit($token) || (int) $token < 1) {
        phpunit_bootstrap_error(
            PHPUNIT_EXITCODE_CONFIGERROR,
            "Invalid TEST_TOKEN '{$token}', it must be a positive integer, can not run tests!",
        );
    }

    return (int) $token;
}

/**
 * Fix the phpunit_* variables for a parallel run process.
 *
 * Every process gets its own data directory and database prefix, so that the processes do not interfere with each other:
 * - phpunit_dataroot = phpunit_dataroot/phpunit/phpunitrun{process}
 * - phpunit_prefix = phpunit_prefix with the process number before the trailing underscore (phpu_ becomes phpu1_)
 *
 * The values can be overridden per process via $CFG->phpunit_parallel_run, indexed from 0, for example:
 * $CFG->phpunit_parallel_run = [
 *     ['phpunit_dataroot' => '/path/one', 'phpunit_prefix' => 'one_', 'phpunit_dbname' => 'phpunit1'],
 *     ['phpunit_dataroot' => '/path/two', 'phpunit_prefix' => 'two_', 'phpunit_dbname' => 'phpunit2'],
 * ];
 *
 * Nothing is changed when this is not a parallel run.
 */
function phpunit_update_vars_for_process(): void {
    global $CFG;

    $process = phpunit_get_run_process();
    if (!$process) {
        return;
    }
    $CFG->phpunit_parallel_process = $process;

    $overrides = $CFG->phpunit_parallel_run[$process - 1] ?? [];
    if (!is_array($overrides)) {
        $overrides = [];
    }

    // The dataroot is inside the phpunit directory of the main one, because everything else in the main dataroot is
    // wiped when it is reset. This way the processes only need write access to the main dataroot.
    if (isset($CFG->phpunit_dataroot) && !isset($overrides['phpunit_dataroot'])) {
        $CFG->phpunit_dataroot = rtrim($CFG->phpunit_dataroot, '/\\') . '/phpunit/phpunitrun' . $process;
    }

    // The process number goes before the trailing underscore (phpu_ becomes phpu1_), because the main site drops all
    // the tables starting with its prefix, so the prefix of a process must not start with the main prefix.
    // Only the number is added to keep the prefix short, as table names have a maximum length.
    if (isset($CFG->phpunit_prefix) && !isset($overrides['phpunit_prefix'])) {
        $CFG->phpunit_prefix = rtrim($CFG->phpunit_prefix, '_') . "{$process}_";
    }

    $allowedoverrides = [
        'phpunit_dataroot', 'phpunit_prefix', 'phpunit_dbtype', 'phpunit_dblibrary', 'phpunit_dbhost',
        'phpunit_dbname', 'phpunit_dbuser', 'phpunit_dbpass', 'phpunit_dboptions',
    ];
    foreach ($allowedoverrides as $name) {
        if (isset($overrides[$name])) {
            $CFG->{$name} = $overrides[$name];
        }
    }
}

/**
 * Get the path of the file recording the number of parallel processes the test sites were initialised for.
 *
 * The file is in the phpunit directory of the main phpunit_dataroot, as everything else in the dataroot is wiped when
 * it is reset. It is removed when the main test site is dropped, like all the other data of the test site.
 * It needs $CFG->phpunit_dataroot, so config.php must have been loaded.
 *
 * @return string
 */
function phpunit_get_parallel_config_path(): string {
    global $CFG;

    if (!isset($CFG->phpunit_dataroot)) {
        phpunit_bootstrap_error(PHPUNIT_EXITCODE_CONFIGERROR, 'Missing $CFG->phpunit_dataroot in config.php, can not run tests!');
    }

    return rtrim($CFG->phpunit_dataroot, '/\\') . '/phpunit/paratest.json';
}

/**
 * Get the number of parallel processes the test sites were initialised for.
 *
 * @return int The number of processes, or 0 when the test site was not initialised for parallel runs.
 */
function phpunit_get_parallel_config(): int {
    $path = phpunit_get_parallel_config_path();
    if (!is_readable($path)) {
        return 0;
    }

    $config = json_decode(file_get_contents($path), true);
    return max(0, (int) ($config['parallel'] ?? 0));
}

/**
 * Record the number of parallel processes the test sites were initialised for.
 *
 * @param int $processes The number of processes, 0 to remove the record.
 */
function phpunit_set_parallel_config(int $processes): void {
    $path = phpunit_get_parallel_config_path();
    if ($processes > 0) {
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 02777, true);
        }
        file_put_contents($path, json_encode(['parallel' => $processes]));
    } else if (file_exists($path)) {
        unlink($path);
    }
}
