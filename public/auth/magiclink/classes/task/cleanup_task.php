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
 * Scheduled task to clean up expired magic link tokens
 *
 * @package   auth_magiclink
 * @copyright 2026 Moodle
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_magiclink\task;

/**
 * Scheduled task to clean up expired and consumed magic link tokens
 */
class cleanup_task extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('taskcleanup', 'auth_magiclink');
    }

    /**
     * Execute the task.
     *
     * @return void
     */
    public function execute(): void {
        mtrace('Starting to clean up expired magic link tokens');
        global $DB;
        $DB->delete_records_select('auth_magiclink_token', 'consumed = 1 OR expires < ?', [time()]);
        mtrace('Finished cleaning up expired magic link tokens');
    }
}
