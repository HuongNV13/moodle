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
 * A scheduled task for sending notification test.
 *
 * @package    mod_workshop
 * @copyright  2020 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * A scheduled task for sending notification test.
 *
 * @package    mod_workshop
 * @copyright  2020 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_workshop_send_notifications_task_testcase extends advanced_testcase {

    /**
     * Test the scheduled task.
     */
     public function test_send_notifications() {
         global $DB;
         $this->resetAfterTest();
         $this->setAdminUser();

         // Prepare course.
         $generator = $this->getDataGenerator();
         $course = $generator->create_course();

         // Prepare users.
         $student1 = $this->getDataGenerator()->create_user(['firstname' => 'Student', 'lastname' => '1',
                 'email' => 'student1@localhost.com']);
         $student2 = $this->getDataGenerator()->create_user(['firstname' => 'Student', 'lastname' => '2',
                 'email' => 'student2@localhost.com']);
         $teacher = self::getDataGenerator()->create_user(['email' => 'teacher@localhost.com']);

         // Users enrolments.
         $studentrole = $DB->get_record('role', ['shortname' => 'student']);
         $teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);
         $this->getDataGenerator()->enrol_user($student1->id, $course->id, $studentrole->id, 'manual');
         $this->getDataGenerator()->enrol_user($student2->id, $course->id, $studentrole->id, 'manual');
         $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $teacherrole->id, 'manual');

         // Set up a test workshop users and configurations for testing.
         $workshop = $generator->create_module('workshop', [
                 'course' => $course,
                 'name' => 'Test Workshop',
         ]);

         // Set up teacher role notification.
         $DB->insert_record('workshop_notifications', [
                 'phase' => workshop::PHASE_ASSESSMENT,
                 'roleid' => $teacherrole->id,
                 'workshopid' => $workshop->id,
                 'value' => 1
         ]);

         // Set up student role notification.
         $DB->insert_record('workshop_notifications', [
                 'phase' => workshop::PHASE_CLOSED,
                 'roleid' => $studentrole->id,
                 'workshopid' => $workshop->id,
                 'value' => 1
         ]);

         $cm = get_coursemodule_from_instance('workshop', $workshop->id);
         $workshop = new workshop($workshop, $cm, $course);
         $workshop->switch_phase(workshop::PHASE_SUBMISSION);

         // Switch to Assessment phase from Submission phase.
         $workshop->switch_phase(workshop::PHASE_ASSESSMENT);

         // Execute the cron.
         ob_start();
         cron_setup_user();
         $cron = new \mod_workshop\task\send_notification_task();
         $cron->set_custom_data([
                 'workshopid' => $workshop->id,
                 'courseid' => $course->id,
                 'cmid' => $cm->id,
                 'singlephase' => false,
                 'oldphase' => workshop::PHASE_SUBMISSION,
                 'newphase' => workshop::PHASE_ASSESSMENT
         ]);
         $cron->execute();
         $output = ob_get_contents();
         ob_end_clean();

         $this->assertContains('Sending notification for workshop phase changed from ' .
                 workshop::get_phase_name_by_value(workshop::PHASE_SUBMISSION) . ' to ' .
                 workshop::get_phase_name_by_value(workshop::PHASE_ASSESSMENT), $output);
         $this->assertContains("Notification to {$teacher->firstname} has been sent", $output);
         $this->assertContains('Sent 1 messages with 0 failures', $output);

         // Switch to Closed phase from Assessment phase.
         $workshop->switch_phase(workshop::PHASE_CLOSED);
         // Execute the cron.
         ob_start();
         cron_setup_user();
         $cron = new \mod_workshop\task\send_notification_task();
         $cron->set_custom_data([
                 'workshopid' => $workshop->id,
                 'courseid' => $course->id,
                 'cmid' => $cm->id,
                 'singlephase' => true,
                 'oldphase' => workshop::PHASE_ASSESSMENT,
                 'newphase' => workshop::PHASE_CLOSED
         ]);
         $cron->execute();
         $output = ob_get_contents();
         ob_end_clean();

         $this->assertContains('Sending notification for workshop phase changed from ' .
                 workshop::get_phase_name_by_value(workshop::PHASE_ASSESSMENT) . ' to ' .
                 workshop::get_phase_name_by_value(workshop::PHASE_CLOSED), $output);
         $this->assertContains("Notification to {$student1->firstname} has been sent", $output);
         $this->assertContains("Notification to {$student2->firstname} has been sent", $output);
         $this->assertContains('Sent 2 messages with 0 failures', $output);
     }
}
