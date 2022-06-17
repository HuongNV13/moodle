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
 * Question duplicated event.
 *
 * @package core
 * @copyright 2022 Huong Nguyen <huongnv13@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 4.1
 */

namespace core\event;

use moodle_url;

/**
 * Question duplicated event class.
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - int categoryid: The ID of the category where the question resides
 *
 * @package core
 * @copyright 2022 Huong Nguyen <huongnv13@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 4.1
 */
class question_duplicated extends question_base {

    /**
     * Init method.
     */
    protected function init() {
        parent::init();
        $this->data['crud'] = 'c';
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('eventquestionduplicated', 'question');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description(): string {
        return "The user with id '$this->userid' duplicated a question with the id of '$this->objectid'" .
            " in the category with the id '" . $this->other['categoryid'] . "'.";
    }

    /**
     * Returns relevant URL.
     *
     * @return moodle_url
     */
    public function get_url(): moodle_url {
        if ($this->courseid) {
            if ($this->contextlevel == CONTEXT_MODULE) {
                return new moodle_url('/question/bank/previewquestion/preview.php',
                    ['cmid' => $this->contextinstanceid, 'id' => $this->objectid]);
            }
            return new moodle_url('/question/bank/previewquestion/preview.php',
                ['courseid' => $this->courseid, 'id' => $this->objectid]);
        }
        // Let's try editing from the frontpage for contexts above course.
        return new moodle_url('/question/bank/previewquestion/preview.php', ['courseid' => SITEID, 'id' => $this->objectid]);
    }
}
