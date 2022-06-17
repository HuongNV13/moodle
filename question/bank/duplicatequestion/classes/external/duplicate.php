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
 * Question bank external API for duplicating the question.
 *
 * @package qbank_duplicatequestion
 * @category external
 * @copyright 2022 Huong Nguyen <huongnv13@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace qbank_duplicatequestion\external;

use context;
use core\event\question_duplicated;
use core_question\local\bank\question_edit_contexts;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use external_warnings;
use moodle_url;
use question_bank;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/engine/bank.php');
require_once($CFG->dirroot . '/question/format/xml/format.php');

/**
 * Question bank external API for duplicating the question.
 *
 * @package qbank_duplicatequestion
 * @category external
 * @copyright 2022 Huong Nguyen <huongnv13@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 4.1
 */
class duplicate extends external_api {

    /** @var int Current processing question id. */
    protected static $currentprocessingquestionid = 0;

    /**
     * Describes the parameters for duplicating the question.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'questionid' => new external_value(PARAM_INT, 'The question id'),
            'contextid' => new external_value(PARAM_INT, 'The context id'),
            'returnurl' => new external_value(PARAM_URL, 'The redirect url')
        ]);
    }

    /**
     * External function to duplicate question in the question bank.
     *
     * @param int $questionid The question id that need to be duplicated.
     * @param int $contextid The editing context id.
     * @param string $returnurl The return url.
     * @return array
     */
    public static function execute(int $questionid, int $contextid, string $returnurl): array {
        global $COURSE, $DB;

        // Parameter validation.
        [
            'questionid' => $questionid,
            'contextid' => $contextid,
            'returnurl' => $returnurl
        ] = self::validate_parameters(self::execute_parameters(), [
            'questionid' => $questionid,
            'contextid' => $contextid,
            'returnurl' => $returnurl
        ]);

        // Set the current question id.
        self::$currentprocessingquestionid = $questionid;

        // Context validation.
        $editingcontext = context::instance_by_id($contextid);
        self::validate_context($editingcontext);

        $response = [
            'status' => false,
            'warnings' => []
        ];

        // Verify that the question is existed.
        if (!$DB->record_exists('question', ['id' => $questionid])) {
            $response['warnings'][] = self::get_error_response('questiondoesnotexist', 'question');
            return $response;
        }

        // Load the necessary data.
        $contexts = new question_edit_contexts($editingcontext);
        $questiondata = question_bank::load_question_data($questionid);

        // Check permissions.
        if (!question_has_capability_on($questiondata, 'add') ||
            (!question_has_capability_on($questiondata, 'edit') && !question_has_capability_on($questiondata, 'view'))) {
            $response['warnings'][] = self::get_error_response('nopermissions', 'error', get_string('duplicate'));
            return $response;
        }

        // Get the suitable name for the question.
        $questiondata->name = get_string('questionnamecopy', 'question', $questiondata->name);

        // Export the question to temporary file.
        $eformat = new \qformat_xml();
        $eformat->setContexts($contexts->having_one_edit_tab_cap('export'));
        $eformat->setCourse($COURSE);
        $eformat->setQuestions([$questiondata]);
        $eformat->setCattofile(true);
        $eformat->setContexttofile(true);
        if (!$eformat->exportpreprocess()) {
            $response['warnings'][] = self::get_error_response('filenotfound', 'error');
            return $response;
        }
        if (!$content = $eformat->exportprocess(true)) {
            $response['warnings'][] = self::get_error_response('filenotfound', 'error');
            return $response;
        }

        // Create temporary directory.
        $tempfile = tempnam(make_temp_directory('qbank_duplicatequestion'), 'tmp');
        file_put_contents($tempfile, $content);

        // Import the question again.
        $iformat = new \qformat_xml();
        $iformat->set_display_progress(false);
        $iformat->setContexts($contexts->having_one_edit_tab_cap('import'));
        $iformat->setFilename($tempfile);
        $iformat->setCatfromfile(true);
        $iformat->setContextfromfile(true);
        $iformat->setStoponerror(true);
        if (!$iformat->importpreprocess()) {
            $response['warnings'][] = self::get_error_response('filenotfound', 'error');
            return $response;
        }
        if (!$iformat->importprocess()) {
            $response['warnings'][] = self::get_error_response('filenotfound', 'error');
            return $response;
        }
        if (!$iformat->importpostprocess()) {
            $response['warnings'][] = self::get_error_response('filenotfound', 'error');
            return $response;
        }

        // Delete the temporary file.
        fulldelete($tempfile);

        // Get the duplicated question.
        $duplicatedquestiondata = question_bank::load_question_data($iformat->questionids[0]);

        // If the original question has the idnumber, find the next unused idnumber and set it for the duplicated one.
        $newidnumber = isset($questiondata->idnumber) ?
            core_question_find_next_unused_idnumber($questiondata->idnumber, $questiondata->category) : '';
        if ($newidnumber != '') {
            $questionbankentry = get_question_bank_entry($duplicatedquestiondata->id);
            $questionbankentry->idnumber = $newidnumber;
            $DB->update_record('question_bank_entries', $questionbankentry);
        }

        // Log the duplication of this question.
        $context = context::instance_by_id($iformat->category->contextid);
        $event = question_duplicated::create_from_question_instance($duplicatedquestiondata, $context);
        $event->trigger();

        // Add the lastchanged param with the duplicated question id to highlight it in the question bank.
        $newreturnurl = new moodle_url($returnurl);
        $newreturnurl->param('lastchanged', $duplicatedquestiondata->id);

        return [
            'status' => true,
            'createdquestionid' => $duplicatedquestiondata->id,
            'returnurl' => $newreturnurl->out(false),
            'warnings' => []
        ];
    }

    /**
     * Describes the data returned from the external function.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'status: true if success'),
            'createdquestionid' => new external_value(PARAM_INT, 'The duplicated question id', VALUE_OPTIONAL),
            'returnurl' => new external_value(PARAM_URL, 'The duplicated question id', VALUE_OPTIONAL),
            'warnings' => new external_warnings()
        ]);
    }

    /**
     * Get the suitable error response
     *
     * @param string $errorcode Error code
     * @param string $component Component
     * @param string $a Extra string
     * @return array
     */
    protected static function get_error_response(string $errorcode, string $component = '', string $a = ''): array {
        return [
            'item' => self::$currentprocessingquestionid,
            'warningcode' => 'errorduplicatequestion',
            'message' => get_string($errorcode, $component, $a)
        ];
    }
}
