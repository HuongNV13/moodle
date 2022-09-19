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
 * MoodleNet external API for getting the activities information.
 *
 * @package   tool_moodlenet
 * @copyright 2022 Huong Nguyen <huongnv13@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_moodlenet\external\share;

use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use external_warnings;

defined('MOODLE_INTERNAL') || die();

/**
 * MoodleNet external API for getting the activities information.
 *
 * @package   tool_moodlenet
 * @copyright 2022 Huong Nguyen <huongnv13@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activities_info extends external_api {

    /**
     * Describes the parameters for the activity information.
     *
     * @return external_function_parameters
     * @since Moodle 4.1
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'The course id', VALUE_REQUIRED)
        ]);
    }

    /**
     * External function to get the activities information.
     *
     * @param int $courseid Course id.
     * @return array
     * @since Moodle 4.1
     */
    public static function execute(int $courseid): array {
        global $CFG;

        [
            'courseid' => $courseid
        ] = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid
        ]);

        $status = false;
        $warnings = [];
        $supporturl = '';

        $modinfo = get_fast_modinfo($courseid);
        $activities = [];
        foreach ($modinfo->get_cms() as $cm) {
            $activities[] = [
                'cmid' => $cm->id,
                'name' => $cm->name,
                'type' => get_string('modulename', $cm->modname),
            ];
        }

        if (empty($activities)) {
            $warnings = [
                'item' => $courseid,
                'warningcode' => 'errorgettingactivitiesinformation',
                'message' => get_string('invalidcoursemodule', 'error')
            ];
        } else {
            $status = true;
        }

        if (!empty($CFG->supportpage)) {
            $supporturl = $CFG->supportpage;
        } else {
            $supporturl = $CFG->wwwroot . '/user/contactsitesupport.php';
        }

        return [
            'status' => $status,
            'name' => get_course($courseid)->fullname,
            'activities' => $activities,
            'server' => get_config('tool_moodlenet', 'defaultmoodlenetname'),
            'supportpageurl' => $supporturl,
            'warnings' => $warnings
        ];
    }

    /**
     * Describes the data returned from the external function.
     *
     * @return external_single_structure
     * @since Moodle 4.1
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'name' => new external_value(PARAM_TEXT, 'Course name'),
            'activities' => new external_multiple_structure(
                new external_single_structure([
                    'cmid' => new external_value(PARAM_INT, 'The cmid of the activity'),
                    'name' => new external_value(PARAM_TEXT, 'Activity name'),
                    'type' => new external_value(PARAM_TEXT, 'Activity type'),
                ]),
                VALUE_DEFAULT,
                []
            ),
            'server' => new external_value(PARAM_TEXT, 'MoodleNet server'),
            'supportpageurl' => new external_value(PARAM_URL, 'Support page URL'),
            'status' => new external_value(PARAM_BOOL, 'status: true if success'),
            'warnings' => new external_warnings()
        ]);
    }
}
