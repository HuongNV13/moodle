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
 * Quiz external API for adding the question to quiz.
 *
 * @package mod_quiz
 * @category external
 * @copyright 2022 Huong Nguyen <huongnv13@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_quiz\external;

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use external_warnings;
use quiz;

require_once($CFG->dirroot . '/mod/quiz/locallib.php');

defined('MOODLE_INTERNAL') || die();

/**
 * Quiz external API for adding the question to quiz.
 *
 * @package mod_quiz
 * @category external
 * @copyright 2022 Huong Nguyen <huongnv13@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_question extends external_api {
    /**
     * Describes the parameters for deleting the subscription.
     *
     * @return external_function_parameters
     * @since Moodle 4.1
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'questionid' => new external_value(PARAM_INT, 'The question id'),
            'quizid' => new external_value(PARAM_INT, 'The context id'),
            'page' => new external_value(PARAM_INT, 'Which page in quiz to add the question on. If 0 (default), add at the end', VALUE_REQUIRED, 0),
        ]);
    }

    /**
     * External function to delete the calendar subscription.
     *
     * @param int $questionid The question id that need to be added to the quiz.
     * @param int $quizid The quiz id.
     * @param int $page The page id.
     * @return array
     * @since Moodle 4.1
     */
    public static function execute(int $questionid, int $quizid, int $page): array {
        // Parameter validation.
        $params = self::validate_parameters(self::execute_parameters(), [
            'questionid' => $questionid,
            'quizid' => $quizid,
            'page' => $page,
        ]);

        $warnings = [];
        $quizobj = quiz::create($params['quizid']);
        $quiz = $quizobj->get_quiz();
        $status = quiz_add_quiz_question($params['questionid'], $quiz, $params['page']);

        return [
            'status' => $status,
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
            'status' => new external_value(PARAM_BOOL, 'status: true if success'),
            'warnings' => new external_warnings()
        ]);
    }
}
