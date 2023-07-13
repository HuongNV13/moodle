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
 * Unit tests for mod_lti backup restore
 *
 * @package    mod_lti
 * @copyright  2023 Jackson D'Souza <jackson.dsouza@catalyst-eu.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 4.2
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/course_categories_trait.php');

/**
 * Unit tests for mod_lti backup restore
 *
 * @package    mod_lti
 * @copyright  2023 Jackson D'Souza <jackson.dsouza@catalyst-eu.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 4.2
 */
class restore_lti_activity_structure_step_test extends \advanced_testcase {
    // There are shared helpers for these tests in the helper course_categories_trait.
    use \mod_lti_course_categories_trait;

    /**
     * Tests the LTI tool restricted course categories backup and restore in a course on the same site.
     * On Course restore, it should create LTI tool and assign the restricted course categories to LTI module.
     *
     * @covers \restore_lti_activity_structure_step::process_ltitype
     */
    public function test_backup_restore_restricted_categories() {
        global $CFG, $DB;

        // Include the necessary files to perform backup and restore.
        require_once($CFG->dirroot . '/mod/lti/locallib.php');
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $admin = get_admin();
        $time = time();

        // Setup fixture.
        $coursecategories = $this->setup_course_categories();

        // Create a course to backup.
        $course1 = $this->getDataGenerator()->create_course(['category' => $coursecategories['subcata']->id]);

        // Create a course to restore above course backup in a different sub category.
        $course2 = $this->getDataGenerator()->create_course(['category' => $coursecategories['subcatca']->id]);

        // Restrict LTI to course categories.
        $restrictcoursecategories = $coursecategories['subcata']->id . ','
                                        . $coursecategories['subcatca']->id . ','
                                        . $coursecategories['subcatcb']->id;

        // Create LTI tool.
        $course1toolrecord = (object) [
            'name' => 'Course created tool which is available in the activity chooser',
            'baseurl' => 'http://example3.com',
            'createdby' => $admin->id,
            'course' => $course1->id,
            'coursecategories' => $restrictcoursecategories,
            'ltiversion' => 'LTI-1p0',
            'timecreated' => $time,
            'timemodified' => $time,
            'state' => LTI_TOOL_STATE_CONFIGURED,
            'coursevisible' => LTI_COURSEVISIBLE_ACTIVITYCHOOSER
        ];
        $tool1id = $DB->insert_record('lti_types', $course1toolrecord);

        // Add LTI tool to course.
        $lti = $this->getDataGenerator()->create_module(
            'lti',
            ['course' => $course1->id, 'typeid' => $tool1id]
        );

        // Backup the course.
        $bc = new \backup_controller(\backup::TYPE_1COURSE, $course1->id, \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO, \backup::MODE_GENERAL, 2);
        $bc->execute_plan();
        $results = $bc->get_results();
        $file = $results['backup_destination'];
        $fp = get_file_packer('application/vnd.moodle.backup');
        $filepath = $CFG->dataroot . '/temp/backup/test-restore-course';
        $file->extract_to_pathname($fp, $filepath);
        $bc->destroy();

        // Now restore the course.
        $rc = new \restore_controller('test-restore-course', $course2->id, \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL, 2, \backup::TARGET_NEW_COURSE);
        $rc->execute_precheck();
        $rc->execute_plan();

        $ltirecords = $DB->get_records('lti_types');

        // There should only be two LTI tool records.
        $this->assertCount(2, $ltirecords);

        $originallti = array_shift($ltirecords);
        $restoredlti = array_shift($ltirecords);

        // Restored LTI should belong to Course 2.
        $this->assertEquals($course2->id, $restoredlti->course);

        // Course category restriction should match.
        $this->assertEquals($originallti->coursecategories, $restoredlti->coursecategories);
    }
}
