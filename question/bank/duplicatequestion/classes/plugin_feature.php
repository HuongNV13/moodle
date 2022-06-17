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
 * Class columns is the entrypoint for the columns.
 *
 * @package qbank_duplicatequestion
 * @copyright 2022 Huong Nguyen <huongnv13@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace qbank_duplicatequestion;

use core_question\local\bank\plugin_features_base;
use core_question\local\bank\view;

/**
 * Class columns is the entrypoint for the columns.
 *
 * @package qbank_duplicatequestion
 * @copyright 2022 Huong Nguyen <huongnv13@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plugin_feature extends plugin_features_base {

    /**
     * This method will return the array of objects to be rendered as a part of question bank columns/actions.
     *
     * @param view $qbank Question bank view
     * @return array
     */
    public function get_question_columns(view $qbank): array {
        return [
            new duplicate_action_column($qbank)
        ];
    }
}
