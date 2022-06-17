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
 * Question bank column for the quick duplicate action icon.
 *
 * @package qbank_duplicatequestion
 * @copyright 2022 Huong Nguyen <huongnv13@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace qbank_duplicatequestion;

use action_menu_link;
use action_menu_link_secondary;
use core_question\local\bank\action_column_base;
use core_question\local\bank\menuable_action;
use html_writer;
use moodle_url;
use pix_icon;
use question_bank;
use stdClass;

/**
 * Question bank column for the quick duplicate action icon.
 *
 * @package qbank_duplicatequestion
 * @copyright 2022 Huong Nguyen <huongnv13@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class duplicate_action_column extends action_column_base implements menuable_action {

    /** @var string avoids repeated calls to get_string('duplicate'). */
    protected $strcopy;

    /**
     * Init.
     *
     * @return void
     */
    protected function init(): void {
        global $PAGE;
        parent::init();
        $this->strcopy = get_string('duplicate');
        $PAGE->requires->js_call_amd('qbank_duplicatequestion/duplicate', 'init', ['#questionscontainer']);
    }

    /**
     * Get the internal name for this column. Used for CSS class.
     *
     * @return string
     */
    public function get_name(): string {
        return 'duplicateaction';
    }

    /**
     * Output the contents of this column.
     *
     * @param object $question the row from the $question table, augmented with extra information.
     * @param string $rowclasses CSS class names that should be applied to this row of output.
     */
    protected function display_content($question, $rowclasses): void {
        global $OUTPUT;
        if (question_has_capability_on($question, 'add') &&
            (question_has_capability_on($question, 'edit') || question_has_capability_on($question, 'view')) &&
            question_bank::is_qtype_installed($question->qtype)) {
            [$url, $attributes] = $this->get_link_url_and_attributes($question);
            echo html_writer::link($url, $OUTPUT->pix_icon('t/copy', $this->strcopy), $attributes);
        }
    }

    /**
     * Generate the link and attributes for given question.
     *
     * @param object $question the row from the $question table, augmented with extra information.
     * @return array Array contains the url and attributes
     */
    protected function get_link_url_and_attributes(object $question): array {
        $url = new moodle_url($this->qbank->returnurl);

        $attributes = [
            'data-action' => 'duplicatequestion',
            'data-contextid' => $this->qbank->get_most_specific_context()->id,
            'data-questionid' => $question->id,
            // If the user has disabled the qbank_viewquestioname plugin, the question object will not contain the name.
            // We need to check that before using it.
            'data-questionname' => $question->name ?? '',
            'data-url' => $url->out(),
        ];

        return [$url, $attributes];
    }

    /**
     * Return the appropriate action menu link, or null if it does not apply to this question.
     *
     * @param stdClass $question the row from the $question table, augmented with extra information.
     * @return action_menu_link|null the action, if applicable to this question.
     */
    public function get_action_menu_link(stdClass $question): ?action_menu_link {
        if (question_has_capability_on($question, 'add') &&
            (question_has_capability_on($question, 'edit') || question_has_capability_on($question, 'view')) &&
            question_bank::is_qtype_installed($question->qtype)) {
            [$url, $attributes] = $this->get_link_url_and_attributes($question);
            return new action_menu_link_secondary($url, new pix_icon('t/copy', ''), $this->strcopy, $attributes);
        }
        return null;
    }
}
