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

use core\oauth2\api;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\external_warnings;
use moodle_url;

/**
 * The external API authorize with MoodleNet.
 *
 * @package    core
 * @copyright  2023 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class moodlenet_authorize extends external_api {

    /**
     * Returns description of parameters.
     *
     * @return external_function_parameters
     * @since Moodle 4.2
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'issuerid' => new external_value(PARAM_INT, 'Issuer id', VALUE_REQUIRED),
        ]);
    }

    /**
     * External function to authorize with MoodleNet.
     *
     * @param int $issuerid Issuer Id.
     * @return array
     * @since Moodle 4.2
     */
    public static function execute(int $issuerid): array {
        [
            'issuerid' => $issuerid
        ] = self::validate_parameters(self::execute_parameters(), [
            'issuerid' => $issuerid
        ]);

        // Get the issuer.
        $issuer = api::get_issuer($issuerid);
        // Validate the issuer and check if it is enabled or not.
        if (!$issuer || !$issuer->get('enabled')) {
            return self::return_errors($issuerid, 'errorissuernotenabled', get_string('nopermissions', 'error'));
        }

        $returnurl = new moodle_url('/lib/classes/moodlenet/callback.php');
        $returnurl->param('issuerid', $issuerid);
        $returnurl->param('callback', 'yes');
        $returnurl->param('sesskey', sesskey());

        // Get the OAuth Client.
        if (!$oauthclient = api::get_user_oauth_client($issuer, $returnurl, 'scope1 scope2 scope3', true)) {
            return self::return_errors($issuerid, 'erroroauthclient', get_string('nopermissions', 'error'));
        }

        $status = false;
        $warnings = [];
        $token = '';
        $refreshtoken = '';
        $loginurl = '';

        if (!$oauthclient->is_logged_in()) {
            $loginurl = $oauthclient->get_login_url()->out(false);
        } else {
            $status = true;
        }


        return [
            'status' => $status,
            'token' => $token,
            'refreshtoken' => $refreshtoken,
            'loginurl' => $loginurl,
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
            'token' => new external_value(PARAM_RAW, 'Token'),
            'refreshtoken' => new external_value(PARAM_RAW, 'Refresh Token'),
            'loginurl' => new external_value(PARAM_RAW, 'Login url'),
            'status' => new external_value(PARAM_BOOL, 'status: true if success'),
            'warnings' => new external_warnings()
        ]);
    }

    protected static function return_errors(int $itemid, string $warningcode, string $message): array {
        $warnings = [
            'item' => $itemid,
            'warningcode' => $warningcode,
            'message' => $message
        ];

        return [
            'status' => false,
            'token' => '',
            'refreshtoken' => '',
            'loginurl' => '',
            'warnings' => $warnings
        ];
    }
}
