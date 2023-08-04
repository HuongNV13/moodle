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

namespace tiny_premium\external;

use core\context;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
/**
 * Web Service functions for steps.
 *
 * @copyright  2016 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class apikey extends external_api {

    /**
     * Describes the parameters for premium API key.
     *
     * @return external_function_parameters
     * @since Moodle 4.3
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT, 'The context ID', VALUE_REQUIRED),
        ]);
    }

    /**
     * External function to get the premium API key.
     *
     * @param int $contextid Context ID.
     * @return array
     * @since Moodle 4.3
     */
    public static function execute(int $contextid): array {
        [
            'contextid' => $contextid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'contextid' => $contextid,
        ]);

        $context = context::instance_by_id($contextid);
        self::validate_context($context);

        return [
            'apikey' => 'Putyourapihere',
        ];
    }

    /**
     * Describes the data returned from the external function.
     *
     * @return external_single_structure
     * @since Moodle 4.3
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'apikey' => new external_value(PARAM_ALPHANUM, 'The api key for Tiny Premium'),
        ]);
    }
}
