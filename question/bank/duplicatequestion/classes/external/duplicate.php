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

use context_course;
use core_question\local\bank\question_edit_contexts;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use external_warnings;
use moodle_url;

require_once($CFG->dirroot . '/question/engine/bank.php');
require_once($CFG->dirroot . '/question/format/xml/format.php');

defined('MOODLE_INTERNAL') || die();

/**
 * Question bank external API for duplicating the question.
 *
 * @package qbank_duplicatequestion
 * @category external
 * @copyright 2022 Huong Nguyen <huongnv13@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class duplicate extends external_api {

    /**
     * Describes the parameters for deleting the subscription.
     *
     * @return external_function_parameters
     * @since Moodle 4.1
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'questionid' => new external_value(PARAM_INT, 'The question id'),
            'contextid' => new external_value(PARAM_INT, 'The context id'),
            'returnurl' => new external_value(PARAM_URL, 'The redirect url')
        ]);
    }

    /**
     * External function to delete the calendar subscription.
     *
     * @param int $questionid The question id that need to be duplicated.
     * @param int $contextid The editing context id.
     * @return array
     * @since Moodle 4.1
     */
    public static function execute(int $questionid, int $contextid, $returnurl): array {
        global $COURSE;
        // Parameter validation.
        $params = self::validate_parameters(self::execute_parameters(), [
            'questionid' => $questionid,
            'contextid' => $contextid,
            'returnurl' => $returnurl,
        ]);

        $editingcontext = \context::instance_by_id($params['contextid']);
        self::validate_context($editingcontext);

        $status = false;
        $warnings = [];

        // Create tempdir.
        $uniquecode = time();
        $tempdir = make_temp_directory('qbank_duplicatequestion/' . $uniquecode);

        // Load the necessary data.
        $contexts = new question_edit_contexts($editingcontext);
        $questiondata = \question_bank::load_question_data($questionid);
        $questiondata->name .= ' (copy)';
        // Check permissions.
        question_require_capability_on($questiondata, 'view');

        // Set up the export format.
        $qformat = new \qformat_xml();
        $qformat->setContexts($contexts->having_one_edit_tab_cap('export'));
        $qformat->setCourse($COURSE);
        $qformat->setQuestions([$questiondata]);
        $qformat->setCattofile(true);
        $qformat->setContexttofile(true);
        $qformat->exportpreprocess();
        $content = $qformat->exportprocess(true);
        $tempfile = fopen($tempdir . '/testtemp.xml', "w");
        fwrite($tempfile, $content);
        fclose($tempfile);

        // Import again.
        $iformat = new \qformat_xml();
        $iformat->set_display_progress(false);
        $iformat->setContexts($contexts->having_one_edit_tab_cap('import'));
        $iformat->setFilename($tempdir . '/testtemp.xml');
        $iformat->setRealfilename('testtemp.xml');
        $iformat->setCatfromfile(true);
        $iformat->setContextfromfile(true);
        $iformat->setStoponerror(true);
        $iformat->importpreprocess();
        $iformat->importprocess();
        $iformat->importpostprocess();
        fulldelete($tempdir);
        $status = true;
        $newreturnurl = new moodle_url($params['returnurl']);
        $newreturnurl->param('lastchanged', $iformat->questionids[0]);

        return [
            'status' => $status,
            'createdquestionid' => $iformat->questionids[0],
            'returnurl' => $newreturnurl->out(false),
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
            'createdquestionid' => new external_value(PARAM_INT, 'The duplicated question id'),
            'returnurl' => new external_value(PARAM_URL, 'The duplicated question id'),
            'warnings' => new external_warnings()
        ]);
    }
}
