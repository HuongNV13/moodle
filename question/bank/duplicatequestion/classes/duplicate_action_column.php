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

use action_menu_link_secondary;
use core_question\local\bank\action_column_base;
use core_question\local\bank\menuable_action;
use html_writer;
use moodle_url;
use pix_icon;

/**
 * Question bank column for the quick duplicate action icon.
 *
 * @package qbank_duplicatequestion
 * @copyright 2022 Huong Nguyen <huongnv13@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class duplicate_action_column extends action_column_base implements menuable_action {
    protected function init(): void {
        global $PAGE;
        parent::init();
        $PAGE->requires->js_call_amd('qbank_duplicatequestion/duplicate', 'init', ['#questionscontainer']);
    }

    public function get_name() {
        return 'duplicateaction';
    }

    protected function display_content($question, $rowclasses): void {
        global $OUTPUT;
        if (question_has_capability_on($question, 'add') &&
            (question_has_capability_on($question, 'edit') || question_has_capability_on($question, 'view'))) {
            [$url, $attributes] = $this->get_link_url_and_attributes($question);
            echo html_writer::link($url, $OUTPUT->pix_icon('t/copy', 'Quick duplicate'), $attributes);
        }
    }

    protected function get_link_url_and_attributes($question): array {
        $url = new moodle_url($this->qbank->returnurl);

        $attributes = [
            'data-action' => 'duplicatequestion',
            'data-contextid' => $this->qbank->get_most_specific_context()->id,
            'data-questionid' => $question->id,
            'data-url' => $url->out(),
        ];

        return [$url, $attributes];
    }

    public function get_action_menu_link(\stdClass $question): ?action_menu_link_secondary {
        if (question_has_capability_on($question, 'add') &&
            (question_has_capability_on($question, 'edit') || question_has_capability_on($question, 'view'))) {
            [$url, $attributes] = $this->get_link_url_and_attributes($question);
            return new action_menu_link_secondary($url, new pix_icon('t/copy', ''), 'Quick duplicate', $attributes);
        }
        return null;
    }
}
