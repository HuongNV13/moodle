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
 * Run PHPUnit tests, in parallel with ParaTest when the test site was initialised for it.
 *
 * The tests are run in parallel when the test site was initialised with:
 *   php admin/tool/phpunit/cli/init.php --parallel=N
 * Otherwise they are run in a single process, like vendor/bin/phpunit.
 *
 * @package    tool_phpunit
 * @copyright  2026 Huong Nguyen <huongnv13@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (isset($_SERVER['REMOTE_ADDR'])) {
    die; // No access from web!
}

define('CLI_SCRIPT', true);
define('ABORT_AFTER_CONFIG', true);

putenv('BEHAT_CLI=0');

require_once(__DIR__ . '/../../../../config.php');
require_once(__DIR__ . '/../../../../lib/phpunit/bootstraplib.php');

$dirroot = realpath(__DIR__ . '/../../../../../');

$help = "
Run PHPUnit tests, in parallel when the test site was initialised for it.

Usage:
  php run.php [--processes=N] [--single-run] [PHPUNIT_OPTION]

Options:
--processes=N      Use N parallel processes, at most the number the test site was initialised for.
                   By default all of them are used.
-s, --single-run   Run in a single process, even if the test site was initialised for parallel runs.
-h, --help         Print out this help

Any other option is passed on to PHPUnit (or ParaTest, which accepts the same options).

Tests that can not run in parallel (for example because they share an external service, like an LDAP server)
are marked with the 'noparallel' group. In a parallel run they are excluded from the parallel processes and then
run all together in a single process, after them. If --log-junit is used, the second run writes to a file with
'-noparallel' added to the name (log.junit -> log-noparallel.junit).

Initialise the test site for parallel runs with: php init.php --parallel=N
The main test site (php init.php) must be initialised too, as ParaTest uses it to load the tests.

Example from Moodle root directory:
\$ php public/admin/tool/phpunit/cli/run.php --testsuite core_dml_testsuite
";

$processes = 0;
$singlerun = false;
$passthrough = [];
foreach (array_slice($_SERVER['argv'], 1) as $arg) {
    if ($arg === '-h' || $arg === '--help') {
        echo $help;
        exit(0);
    }
    if ($arg === '-s' || $arg === '--single-run') {
        $singlerun = true;
        continue;
    }
    if (preg_match('/^--processes=(.*)$/', $arg, $matches)) {
        if (!ctype_digit($matches[1]) || (int) $matches[1] < 1) {
            phpunit_bootstrap_error(1, '--processes must be a positive integer.');
        }
        $processes = (int) $matches[1];
        continue;
    }
    $passthrough[] = $arg;
}

$initialised = phpunit_get_parallel_config();
if (!$initialised && $processes > 1) {
    echo "The test site is not initialised for parallel runs, please use:\n";
    echo " php admin/tool/phpunit/cli/init.php --parallel={$processes}\n";
    exit(1);
}
if ($processes > $initialised && $initialised) {
    echo "Processes ({$processes}) is more than the test site was initialised for ({$initialised}).\n";
    exit(1);
}
$processes = $processes ?: $initialised;

chdir($dirroot);
$parallelrun = !$singlerun && $processes > 1;
$executable = $dirroot . '/vendor/bin/' . ($parallelrun ? 'paratest' : 'phpunit');
if (!file_exists($executable)) {
    phpunit_bootstrap_error(PHPUNIT_EXITCODE_PHPUNITMISSING);
}

/**
 * Build the command line to run an executable with some arguments.
 *
 * @param string $executable The PHP script to run.
 * @param string[] $args The arguments.
 * @return string
 */
function phpunit_build_command(string $executable, array $args): string {
    return 'php ' . implode(' ', array_map('escapeshellarg', array_merge([$executable], $args)));
}

/**
 * Change the file of the --log-junit option, adding '-noparallel' to its name (log.junit -> log-noparallel.junit).
 *
 * @param string[] $args The arguments.
 * @return string[] The arguments with the new file.
 */
function phpunit_noparallel_junit_args(array $args): array {
    $rename = function (string $path): string {
        $info = pathinfo($path);
        $dir = ($info['dirname'] ?? '.') === '.' ? '' : rtrim($info['dirname'], '/') . '/';
        $extension = isset($info['extension']) ? '.' . $info['extension'] : '';
        return $dir . $info['filename'] . '-noparallel' . $extension;
    };

    foreach ($args as $i => $arg) {
        if ($arg === '--log-junit' && isset($args[$i + 1])) {
            $args[$i + 1] = $rename($args[$i + 1]);
        } else if (str_starts_with($arg, '--log-junit=')) {
            $args[$i] = '--log-junit=' . $rename(substr($arg, strlen('--log-junit=')));
        }
    }
    return $args;
}

if (!$parallelrun) {
    passthru(phpunit_build_command($executable, $passthrough), $code);
    exit($code);
}

// Make sure the test site of every process is ready, so that the tests do not fail one by one with confusing errors.
// Note that ParaTest itself loads the tests using the main test site, which must be initialised too.
$utilpath = __DIR__ . '/util.php';
for ($process = 1; $process <= $processes; $process++) {
    putenv('PARATEST=1');
    putenv("TEST_TOKEN={$process}");
    $output = [];
    exec('php ' . escapeshellarg($utilpath) . ' --diag', $output, $code);
    if ($code != 0) {
        echo "The test site of parallel process {$process} is not ready:\n" . implode("\n", $output) . "\n";
        echo "Please initialise it with:\n php admin/tool/phpunit/cli/init.php --parallel={$initialised}\n";
        exit($code);
    }
}
putenv('PARATEST');
putenv('TEST_TOKEN');

// First, everything that can run in parallel.
passthru(
    phpunit_build_command($executable, array_merge(['--processes=' . $processes, '--exclude-group', 'noparallel'], $passthrough)),
    $parallelcode
);

// Then, the tests that can not, in a single process. It is not a failure if there are none to run.
echo "\nRunning the tests that can not run in parallel (group noparallel) in a single process...\n";
passthru(
    phpunit_build_command(
        $dirroot . '/vendor/bin/phpunit',
        array_merge(['--group', 'noparallel'], phpunit_noparallel_junit_args($passthrough)),
    ),
    $singlecode
);

exit(max($parallelcode, $singlecode));
