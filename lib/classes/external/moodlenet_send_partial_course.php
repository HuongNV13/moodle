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
use core\moodlenet\course_partial_sender;
use core\moodlenet\moodlenet_client;
use core\moodlenet\resource_sender;
use core\moodlenet\utilities;
use core\oauth2\api;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\external_warnings;
use moodle_url;

/**
 * The external API to send selected activities in a course to MoodleNet.
 *
 * @package    core
 * @copyright  2023 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class moodlenet_send_partial_course extends external_api {

    /**
     * Describes the parameters for sending the activities in a course.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'issuerid' => new external_value(PARAM_INT, 'OAuth 2 issuer ID', VALUE_REQUIRED),
            'courseid' => new external_value(PARAM_INT, 'Course ID', VALUE_REQUIRED),
            'shareformat' => new external_value(PARAM_INT, 'Share format', VALUE_REQUIRED),
            'cmids' => new external_value(PARAM_SEQUENCE, 'Course module IDs', VALUE_REQUIRED),
        ]);
    }

    /**
     * External function to send the selected activities in a course to MoodleNet.
     *
     * @param int $issuerid The MoodleNet OAuth 2 issuer ID
     * @param int $courseid The course ID
     * @param int $shareformat The share format being used, as defined by \core\moodlenet\course_sender
     * @param string $cmids List of course module IDs of the activities that being shared, seperated by comma
     * @return array
     */
    public static function execute(
        int $issuerid,
        int $courseid,
        int $shareformat,
        string $cmids,
    ): array {
        global $CFG, $USER;

        [
            'issuerid' => $issuerid,
            'courseid' => $courseid,
            'shareformat' => $shareformat,
            'cmids' => $cmids,
        ] = self::validate_parameters(self::execute_parameters(), [
            'issuerid' => $issuerid,
            'courseid' => $courseid,
            'shareformat' => $shareformat,
            'cmids' => $cmids,
        ]);

        // Check capability.
        $course = get_course($courseid);
        $coursecontext = context_course::instance($course->id);
        $usercanshare = utilities::can_user_share($coursecontext, $USER->id, 'course');
        if (!$usercanshare) {
            return self::return_errors(
                $course->id,
                'errorpermission',
                get_string('nopermissions', 'error', get_string('moodlenet:sharetomoodlenet', 'moodle'))
            );
        }

        // Check format.
        if ($shareformat !== resource_sender::SHARE_FORMAT_BACKUP) {
            return self::return_errors(
                $shareformat,
                'errorinvalidformat',
                get_string('invalidparameter', 'debug')
            );
        }

        // Check Cmids.
        $activities = explode(',', $cmids);
        if (!$activities) {
            return self::return_errors(
                $cmids,
                'errorinvalidcmids',
                get_string('invalidparameter', 'debug')
            );
        }

        // Check course modules in the course.
        foreach ($activities as $activity) {
            if (!get_coursemodule_from_id('', $activity, $course->id)) {
                return self::return_errors(
                    $activity,
                    'errorinvalidcmids',
                    get_string('invalidparameter', 'debug')
                );
            }
        }

        // Get the issuer.
        $issuer = api::get_issuer($issuerid);
        // Validate the issuer and check if it is enabled or not.
        if (!utilities::is_valid_instance($issuer)) {
            return self::return_errors(
                $issuerid,
                'errorissuernotenabled',
                get_string('invalidparameter', 'debug')
            );
        }

        // Get the OAuth Client.
        if (!$oauthclient = api::get_user_oauth_client(
            $issuer,
            new moodle_url($CFG->wwwroot),
            moodlenet_client::API_SCOPE_CREATE_RESOURCE
        )) {
            return self::return_errors(
                $issuerid,
                'erroroauthclient',
                get_string('invalidparameter', 'debug')
            );
        }

        // Check login state.
        if (!$oauthclient->is_logged_in()) {
            return self::return_errors(
                $issuerid,
                'erroroauthclient',
                get_string('moodlenet:issuerisnotauthorized', 'moodle')
            );
        }

        // Get the HTTP Client.
        $client = new http_client();

        // Share course.
        try {
            $moodlenetclient = new moodlenet_client($client, $oauthclient);
            $coursesender = new course_partial_sender($course->id, $USER->id, $moodlenetclient, $oauthclient, $activities, $shareformat);
            $result = $coursesender->share_resource();
            if (empty($result['drafturl'])) {
                return self::return_errors(
                    $result['responsecode'],
                    'errorsendingcourse',
                    get_string('moodlenet:cannotconnecttoserver', 'moodle')
                );
            }
        } catch (\moodle_exception $e) {
            return self::return_errors(
                0,
                'errorsendingcourse',
                $e->getMessage()
            );
        }

        return [
            'status' => true,
            'resourceurl' => $result['drafturl'],
            'warnings' => [],
        ];
    }

    /**
     * Describes the data returned from the external function.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'Status: true if success'),
            'resourceurl' => new external_value(PARAM_URL, 'Resource URL from MoodleNet'),
            'warnings' => new external_warnings(),
        ]);
    }

    /**
     * Handle return error.
     *
     * @param int $itemid Item id
     * @param string $warningcode Warning code
     * @param string $message Message
     * @return array
     */
    protected static function return_errors(int $itemid, string $warningcode, string $message): array {
        $warnings[] = [
            'item' => $itemid,
            'warningcode' => $warningcode,
            'message' => $message,
        ];

        return [
            'status' => false,
            'resourceurl' => '',
            'warnings' => $warnings,
        ];
    }
}
