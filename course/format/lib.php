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
 * Base class for course format plugins
 *
 * @package    core_course
 * @copyright  2012 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

use core_course\course_format;

/**
 * Returns an instance of format class (extending course_format) for given course
 *
 * @param int|stdClass $courseorid either course id or
 *     an object that has the property 'format' and may contain property 'id'
 * @return course_format
 */
function course_get_format($courseorid) {
    return course_format::instance($courseorid);
}

/**
 * Pseudo course format used for the site main page
 *
 * @package    core_course
 * @copyright  2012 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_site extends course_format {

    /**
     * Returns the display name of the given section that the course prefers.
     *
     * @param int|stdClass $section Section object from database or just field section.section
     * @return Display name that the course format prefers, e.g. "Topic 2"
     */
    function get_section_name($section) {
        return get_string('site');
    }

    /**
     * For this fake course referring to the whole site, the site homepage is always returned
     * regardless of arguments
     *
     * @param int|stdClass $section
     * @param array $options
     * @return null|moodle_url
     */
    public function get_view_url($section, $options = array()) {
        return new moodle_url('/', array('redirect' => 0));
    }

    /**
     * Returns the list of blocks to be automatically added on the site frontpage when moodle is installed
     *
     * @return array of default blocks, must contain two keys BLOCK_POS_LEFT and BLOCK_POS_RIGHT
     *     each of values is an array of block names (for left and right side columns)
     */
    public function get_default_blocks() {
        return blocks_get_default_site_course_blocks();
    }

    /**
     * Definitions of the additional options that site uses
     *
     * @param bool $foreditform
     * @return array of options
     */
    public function course_format_options($foreditform = false) {
        static $courseformatoptions = false;
        if ($courseformatoptions === false) {
            $courseformatoptions = array(
                'numsections' => array(
                    'default' => 1,
                    'type' => PARAM_INT,
                ),
            );
        }
        return $courseformatoptions;
    }

    /**
     * Returns whether this course format allows the activity to
     * have "triple visibility state" - visible always, hidden on course page but available, hidden.
     *
     * @param stdClass|cm_info $cm course module (may be null if we are displaying a form for adding a module)
     * @param stdClass|section_info $section section where this module is located or will be added to
     * @return bool
     */
    public function allow_stealth_module_visibility($cm, $section) {
        return true;
    }

    /**
     * Returns instance of page renderer used by the site page
     *
     * @param moodle_page $page the current page
     * @return renderer_base
     */
    public function get_renderer(moodle_page $page) {
        global $CFG;
        if (!class_exists('format_site_renderer')) {
            require_once($CFG->dirroot.'/course/format/renderer.php');
        }
        return new format_site_renderer($page, null);
    }

    /**
     * Site format uses only section 1.
     *
     * @return int
     */
    public function get_section_number(): int {
        return 1;
    }
}
