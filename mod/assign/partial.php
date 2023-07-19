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
 * POC for partial course sharing.
 *
 * @package    xxx_yyy
 * @copyright  2023 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

$courseid = required_param('courseid', PARAM_INT);
$cmids = required_param('cmids', PARAM_SEQUENCE);
$cmidarray = explode(',',$cmids);

$controller = new backup_controller(
    backup::TYPE_1COURSE,
    $courseid,
    backup::FORMAT_MOODLE,
    backup::INTERACTIVE_NO,
    backup::MODE_GENERAL,
    $USER->id,
);

foreach ($controller->get_plan()->get_tasks() as $task) {
    if ($task instanceof backup_activity_task) {
        foreach ($task->get_settings() as $setting) {
            if (in_array($task->get_moduleid(), $cmidarray) && strpos($setting->get_name(), '_included') !== false) {
                $setting->set_value(1);
            } else {
                $setting->set_value(0);
            }
        }
    }
}
$controller->execute_plan();
$result = $controller->get_results();
$backupfile = $result['backup_destination'];

send_stored_file($backupfile, null, 0, true);
