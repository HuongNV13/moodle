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
 * [Description here].
 *
 * @package    xxx_yyy
 * @copyright  2023 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\moodlenet;

use core\oauth2\client;
use stored_file;

class course_partial_sender extends course_sender {

    /**
     * Constructor for course sender.
     *
     * @param int $courseid The course ID of the course being shared
     * @param int $userid The user ID who is sharing the activity
     * @param moodlenet_client $moodlenetclient The moodlenet_client object used to perform the share
     * @param client $oauthclient The OAuth 2 client for the MoodleNet instance
     * @param int $shareformat The data format to share in. Defaults to a Moodle backup (SHARE_FORMAT_BACKUP)
     */
    public function __construct(
        int $courseid,
        protected int $userid,
        protected moodlenet_client $moodlenetclient,
        protected client $oauthclient,
        protected array $cmids,
        protected int $shareformat = self::SHARE_FORMAT_BACKUP,
    ) {
        parent::__construct($courseid, $userid, $moodlenetclient, $oauthclient, $shareformat);
        $this->course = get_course($courseid);
        $this->coursecontext = \core\context\course::instance($courseid);
    }

    /**
     * Prepare the data for sharing, in the format specified.
     *
     * @return stored_file
     */
    protected function prepare_share_contents(): stored_file {
        $packager = new course_partial_packager($this->course, $this->cmids, $this->userid);
        return match ($this->shareformat) {
            self::SHARE_FORMAT_BACKUP => $packager->get_package(),
            default => throw new \coding_exception("Unknown share format: {$this->shareformat}'"),
        };
    }

}
