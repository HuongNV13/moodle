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

namespace core\external;

use context_course;
use core\http_client;
use core\oauth2\api;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\external_warnings;
use moodle_url;

/**
 * The external API to send activity to MoodleNet.
 *
 * @package    core
 * @copyright  2023 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class moodlenet_send_activity extends external_api {

    /**
     * Describes the parameters for sending the activity.
     *
     * @return external_function_parameters
     * @since Moodle 4.2
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'issuerid' => new external_value(PARAM_INT, 'Client id', VALUE_REQUIRED),
            'courseid' => new external_value(PARAM_INT, 'Course id', VALUE_REQUIRED),
            'cmid' => new external_value(PARAM_INT, 'Course module id', VALUE_REQUIRED),
            'shareformat' => new external_value(PARAM_INT, 'Share format', VALUE_REQUIRED),
        ]);
    }

    /**
     * External function to send the activity to MoodleNet.
     *
     * @param int $issuerid Issuer ID
     * @param int $courseid Course ID
     * @param int $cmid Course module ID
     * @param int $shareformat Share format
     * @return array
     */
    public static function execute(int $issuerid, int $courseid, int $cmid, int $shareformat): array {
        global $USER, $CFG;

        [
            'issuerid' => $issuerid,
            'courseid' => $courseid,
            'cmid' => $cmid,
            'shareformat' => $shareformat,
        ] = self::validate_parameters(self::execute_parameters(), [
            'issuerid' => $issuerid,
            'courseid' => $courseid,
            'cmid' => $cmid,
            'shareformat' => $shareformat,
        ]);

        $status = false;
        $resourceurl = '';
        $warnings = [];

        // TODO: Check the experimental flag and the issuerid is equal to to the MN instance in the admin setting in MDL-75319.

        if (!in_array($shareformat, [\core\moodlenet\activity_sender::SHARE_FORMAT_BACKUP])) {
            return self::return_errors($shareformat, 'errorinvalidformat', get_string('nopermissions', 'error'));
        }

        if (!has_capability('moodle/moodlenet:sendactivity', context_course::instance($courseid))) {
            return self::return_errors($cmid, 'errorsendingactivity', get_string('nopermissions', 'error'));
        }

        // Get the issuer.
        $issuer = api::get_issuer($issuerid);
        // Validate the issuer and check if it is enabled or not.
        if (!$issuer || !$issuer->get('enabled')) {
            return self::return_errors($issuerid, 'errorissuernotenabled', get_string('nopermissions', 'error'));
        }

        // Get the OAuth Client.
        if (!$oauthclient = api::get_user_oauth_client($issuer, new moodle_url($CFG->wwwroot))) {
            return self::return_errors($issuerid, 'erroroauthclient', get_string('nopermissions', 'error'));
        }

        // Get the HTTP Client.
        $client = new http_client();
        $result = \core\moodlenet\activity_sender::share_activity($courseid, $cmid, $USER->id, $client, $oauthclient, $shareformat);
        if ($result['responsecode'] == 201) {
            $status = true;
            $resourceurl = $result['drafturl'];
        } else {
            return self::return_errors($issuerid, 'errorsendapi', get_string('nopermissions', 'error'));
        }

        return [
            'status' => $status,
            'resourceurl' => $resourceurl,
            'warnings' => $warnings
        ];
    }

    /**
     * Describes the data returned from the external function.
     *
     * @return external_single_structure
     * @since Moodle 4.2
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'status: true if success'),
            'resourceurl' => new external_value(PARAM_RAW, 'Resource URL from MoodleNet'),
            'warnings' => new external_warnings()
        ]);
    }

    protected static function return_errors(int $itemid, string $warningcode, string $message): array {
        $warnings = [
            'item' => $itemid,
            'warningcode' => $warningcode,
            'message' => $message
        ];
        var_dump($warningcode);
        exit();

        return [
            'status' => false,
            'resourceurl' => '',
            'warnings' => $warnings
        ];
    }
}
