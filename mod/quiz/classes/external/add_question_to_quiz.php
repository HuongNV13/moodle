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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/locallib.php');

/**
 * Quiz external API for adding the question to quiz.
 *
 * @package mod_quiz
 * @category external
 * @copyright 2022 Huong Nguyen <huongnv13@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 4.1
 */
class add_question_to_quiz extends external_api {

    /**
     * Describes the parameters for adding the question to the quiz.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'questionid' => new external_value(PARAM_INT, 'The question id'),
            'israndom' => new external_value(PARAM_BOOL, 'True if the question is a random question', VALUE_REQUIRED, false),
            'quizid' => new external_value(PARAM_INT, 'The context id'),
            'page' => new external_value(PARAM_INT, 'Which page in quiz to add the question on. If 0 (default), add at the end',
                VALUE_REQUIRED, 0),
        ]);
    }

    /**
     * External function to add the question to the quiz.
     *
     * @param int $questionid The question id that need to be added to the quiz.
     * @param bool $israndom If the question is a random question or not.
     * @param int $quizid The quiz id.
     * @param int $page The page id.
     * @return array
     */
    public static function execute(int $questionid, bool $israndom, int $quizid, int $page): array {
        global $DB;
        // Parameter validation.
        [
            'questionid' => $questionid,
            'israndom' => $israndom,
            'quizid' => $quizid,
            'page' => $page
        ] = self::validate_parameters(self::execute_parameters(), [
            'questionid' => $questionid,
            'israndom' => $israndom,
            'quizid' => $quizid,
            'page' => $page
        ]);

        $response = [
            'status' => false,
            'warnings' => []
        ];

        // Verify that the quiz is existed.
        if (!$DB->record_exists('quiz', ['id' => $quizid])) {
            $response['warnings'][] = [
                'item' => $quizid,
                'warningcode' => 'erroraddquestiontoquiz',
                'message' => get_string('errorinvalidquiz', 'quiz')
            ];
            return $response;
        }

        // Create the Quiz object.
        $quizobj = quiz::create($quizid);
        // Get the Quiz setting.
        $quiz = $quizobj->get_quiz();

        if ($israndom) {
            // Random question.
            $slot = $questionid;
            // Get the quiz structure.
            $structure = $quizobj->get_structure();
            // Get the slot information.
            $slot = $structure->get_slot_by_number($slot);

            // Add the random question to the Quiz.
            $slottags = [];
            if (isset($slot->randomtags)) {
                foreach ($slot->randomtags as $slottag) {
                    $slottag = explode(',', $slottag);
                    $slottags[] = $slottag[0];
                }
            }
            quiz_add_random_questions($quiz, $page, $slot->category, 1, $slot->randomrecurse, $slottags);
            $response['status'] = true;
            return $response;
        }

        // Verify that the question is existed.
        if (!$DB->record_exists('question', ['id' => $questionid])) {
            $response['warnings'][] = [
                'item' => $questionid,
                'warningcode' => 'erroraddquestiontoquiz',
                'message' => get_string('questiondoesnotexist', 'question')
            ];
            return $response;
        }

        // Add the question to the Quiz.
        quiz_add_quiz_question($questionid, $quiz, $page);
        $response['status'] = true;

        return $response;
    }

    /**
     * Describes the data returned from the external function.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'status: true if success'),
            'warnings' => new external_warnings()
        ]);
    }
}
