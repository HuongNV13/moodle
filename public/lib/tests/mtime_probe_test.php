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

namespace core;

/**
 * DO-NOT-INTEGRATE: diagnostic for MDL-80443 (core_setuplib_testcase::test_localcachedir).
 *
 * Repeats the first block of setuplib_test::test_localcachedir in a loop, and checks whether
 * time() and the filesystem's file timestamps agree on the second.
 *
 * Run: vendor/bin/phpunit --filter mtime_probe_test public/lib/tests/mtime_probe_test.php
 * Override the loop sizes with MTIME_PROBE_ITERATIONS and MTIME_PROBE_CLOCK_SECONDS.
 *
 * @package   core
 * @category  test
 * @copyright 2026 Moodle Pty Ltd
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class mtime_probe_test extends \advanced_testcase {
    /**
     * Describe the host so a failing build is self-explanatory.
     *
     * @return string
     */
    private static function environment(): string {
        global $CFG;
        $fs = trim((string) @shell_exec('stat -f -c %T ' . escapeshellarg($CFG->dataroot) . ' 2>/dev/null'));
        $tck = trim((string) @shell_exec('getconf CLK_TCK 2>/dev/null'));
        return 'uname=' . php_uname() . '; php=' . PHP_VERSION . '; dataroot fs=' . ($fs ?: 'unknown') .
            '; CLK_TCK=' . ($tck ?: 'unknown');
    }

    /**
     * The real code path: delete localcachedir, make_localcache_directory(''), compare .lastpurged mtime.
     *
     * This is exactly what setuplib_test.php:114 asserts.
     */
    public function test_make_localcache_directory_mtime(): void {
        global $CFG;
        $this->resetAfterTest(true);

        $iterations = (int) (getenv('MTIME_PROBE_ITERATIONS') ?: 20000);
        $timestampfile = "$CFG->localcachedir/.lastpurged";
        $lag = [];
        for ($i = 0; $i < $iterations; $i++) {
            $this->assertTrue(remove_dir($CFG->localcachedir));
            $start = time();
            make_localcache_directory('');
            clearstatcache();
            $mtime = filemtime($timestampfile);
            if ($mtime < $start) {
                $lag[] = "start=$start mtime=$mtime";
            }
        }
        fwrite(STDERR, "\n[mtime_probe] make_localcache_directory: " . count($lag) . "/$iterations lagged. " .
            self::environment() . "\n");
        $this->assertSame([], array_slice($lag, 0, 5),
            count($lag) . "/$iterations new .lastpurged files had mtime < time() read just before. " .
            self::environment());
    }

    /**
     * Bare touch() of a new file in the dataroot, without any Moodle code around it.
     *
     * If this lags too, the cause is the kernel/filesystem and not make_localcache_directory().
     */
    public function test_bare_touch_mtime(): void {
        global $CFG;
        $this->resetAfterTest(true);

        $iterations = (int) (getenv('MTIME_PROBE_ITERATIONS') ?: 20000) * 10;
        $dir = make_temp_directory('mtimeprobe');
        $file = "$dir/probe";
        $lag = [];
        for ($i = 0; $i < $iterations; $i++) {
            $start = time();
            touch($file);
            clearstatcache();
            $mtime = filemtime($file);
            if ($mtime < $start) {
                $lag[] = "start=$start mtime=$mtime";
            }
            unlink($file);
        }
        fwrite(STDERR, "\n[mtime_probe] bare touch: " . count($lag) . "/$iterations lagged. " . self::environment() . "\n");
        $this->assertSame([], array_slice($lag, 0, 5),
            count($lag) . "/$iterations bare touch() files had mtime < time() read just before. " . self::environment());
    }

    /**
     * Does time() ever sit behind the fine-grained clock (bracketed so a rollover between reads is ignored)?
     */
    public function test_time_against_fine_clock(): void {
        $seconds = (int) (getenv('MTIME_PROBE_CLOCK_SECONDS') ?: 10);
        $behind = $ahead = $samples = 0;
        $end = microtime(true) + $seconds;
        while (microtime(true) < $end) {
            $before = (int) floor(microtime(true));
            $t = time();
            $after = (int) floor(microtime(true));
            $samples++;
            if ($t < $before) {
                $behind++;
            } else if ($t > $after) {
                $ahead++;
            }
        }
        fwrite(STDERR, "\n[mtime_probe] clock: $samples samples, time() behind=$behind ahead=$ahead. " .
            self::environment() . "\n");
        $this->assertSame(0, $behind, "time() was behind the fine clock $behind/$samples times. " . self::environment());
        $this->assertSame(0, $ahead, "time() was ahead of the fine clock $ahead/$samples times. " . self::environment());
    }
}
