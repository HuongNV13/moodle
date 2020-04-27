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
 * A scheduled task for sending notification.
 *
 * @package    mod_workshop
 * @copyright  2020 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_workshop\task;

use context_course;
use core\message\message;
use core_user;
use stdClass;
use workshop;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__.'/../../locallib.php');

/**
 * Adhoc task to send user forum notifications.
 *
 * @package    mod_workshop
 * @copyright 2020 Tien.NguyenPhuc <nguyenphuctien@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_notification_task extends \core\task\adhoc_task {

    // Use the logging trait to get some nice, juicy, logging.
    use \core\task\logging_trait;

    /**
     * @var array emails list which will receive the notification.
     */
    protected $recipients = [];

    /**
     * @var stdClass The course of workshop.
     */
    protected $course;

    /**
     * @var stdClass The cm.
     */
    protected $cm;

    /**
     * @var stdClass The workshop.
     */
    protected $workshop;

    /**
     * @var stdClass The workshop's old phase.
     */
    protected $oldphase;

    /**
     * @var stdClass The workshop's new phase.
     */
    protected $newphase;

    /**
     * @var boolean Single phase or not.
     */
    protected $singlephase;

    /**
     * @var stdClass The renderer.
     */
    protected $renderer;

    /** @var int 0 or timestamp */
    protected $opendate;

    /** @var int 0 or timestamp */
    protected $enddate;

    /**
     * Send out messages.
     */
    public function execute() {
        $this->prepare_data();
        $this->log_start('Sending notification for workshop phase changed from ' .
                workshop::get_phase_name_by_value($this->oldphase) . ' to ' . workshop::get_phase_name_by_value($this->newphase));
        $sentcount = 0;
        $errorcount = 0;

        foreach ($this->recipients as $recipient) {
            if ($this->send_notifications($recipient)) {
                $this->log("Notification to {$recipient->firstname} has been sent", 1);
                $sentcount++;
            } else {
                $this->log("Failed to send notification to {$recipient->firstname}", 1);
                $errorcount++;
            }
        }

        $this->log_finish("Sent {$sentcount} messages with {$errorcount} failures");
    }
    /**
     * Prepare all data for this run.
     *
     * Take workshop information, fetch the courses, recipients, old phase, new phase, start date and end date.
     *
     */
    protected function prepare_data(): void {
        global $DB;

        $data = $this->get_custom_data();
        $this->course = get_course($data->courseid);
        $this->cm = get_coursemodule_from_id('workshop', $data->cmid, $data->courseid, false, MUST_EXIST);
        $this->workshop = $DB->get_record('workshop', ['id' => $this->cm->instance], '*', MUST_EXIST);
        $coursecontext = context_course::instance($this->course->id);
        $this->oldphase = $data->oldphase;
        $this->newphase = $data->newphase;
        $this->singlephase = $data->singlephase;

        if ($this->newphase == workshop::PHASE_SUBMISSION) {
            $this->opendate = $this->workshop->submissionstart;
            $this->enddate = $this->workshop->submissionend;
        }

        if ($this->newphase == workshop::PHASE_ASSESSMENT) {
            $this->opendate = $this->workshop->assessmentstart;
            $this->enddate = $this->workshop->assessmentend;
        }

        // Find all $recipients.
        $conditions = ['workshopid' => $this->workshop->id, 'phase' => $this->newphase];
        $notificationoptions = $DB->get_records('workshop_notifications', $conditions);

        foreach ($notificationoptions as $option) {
            // Ignore disabled options.
            if ($option->value == 0) {
                continue;
            }

            // Custom email addresses option.
            if ($option->roleid == 0) {
                foreach (explode(',', $this->workshop->customnotificationemail) as $email) {
                    $user = new stdClass();
                    $user->firstname = 'Anonymous';
                    $user->lastname = 'User';
                    $user->email = trim(strtolower($email));
                    $user->id = -1;
                    $user->auth = 1;
                    $user->suspended = 0;
                    $user->deleted = 0;
                    $user->emailstop = 0;
                    $user->mailformat = FORMAT_HTML;
                    $this->recipients[]= $user;
                }
            } else {
                // Get all fields to prevent message_send function fetch full record again.
                $users = get_role_users($option->roleid, $coursecontext, false, 'u.*');
                foreach ($users as $user) {
                    if (!$this->course->visible and !has_capability('moodle/course:viewhiddencourses', $coursecontext, $user)) {
                        // The course is hidden and the user does not have access to it.
                        continue;
                    }
                    if ($user->email) {
                        $this->recipients[] = $user;
                    }
                }
            }
        }

        // Get renderer.
        $page = new \moodle_page();
        $this->renderer = new \core_renderer($page, RENDERER_TARGET_GENERAL);
    }

    /**
     * Send the specified post for the current user.
     *
     * @param stdClass $recipient
     *
     * @return mixed the integer ID of the new message or false if there was a problem with submitted data
     */
    protected function send_notifications($recipient) {
        // Headers to help prevent auto-responders.
        $userfrom = core_user::get_noreply_user();
        $userfrom->customheaders = [
                'Precedence: Bulk',
                'X-Auto-Response-Suppress: All',
                'Auto-Submitted: auto-generated',
        ];

        $data = [
                'firstname' => $recipient->firstname,
                'courseshortname' => $this->course->shortname,
                'coursefullname' => $this->course->fullname,
                'workshopname' => $this->workshop->name,
                'opendate' => ($this->opendate == 0) ? false : userdate($this->opendate),
                'enddate' => ($this->enddate == 0) ? false : userdate($this->enddate),
                'workshoplink' => (new \moodle_url('/mod/workshop/view.php', ['id' => $this->cm->id]))->out(false),
                'singlephase' => $this->singlephase
        ];

        if (!$this->singlephase) {
            $data['oldphase'] = workshop::get_phase_name_by_value($this->oldphase);
            $data['newphase'] = workshop::get_phase_name_by_value($this->newphase);
        } else {
            $data['newphase'] = workshop::get_phase_name_by_value($this->newphase);
        }

        $fullmessagehtml = $this->renderer->render_from_template('mod_workshop/workshop_notification_email', $data);

        if ($recipient->id == 0) {
            return email_to_user($recipient, $userfrom,
                    $this->singlephase == false ? get_string('emailnotification', 'workshop', $data) :
                            get_string('emailnotification_singlephase', 'workshop', $data), $fullmessagehtml, $fullmessagehtml);
        }

        $eventdata = new message();
        $eventdata->courseid = $this->course->id;
        $eventdata->component = 'mod_workshop';
        $eventdata->name = 'phasechanged';
        $eventdata->userfrom = $userfrom;
        $eventdata->userto = $recipient;
        $eventdata->subject = $this->singlephase == false ? get_string('emailnotification', 'workshop', $data) :
                get_string('emailnotification_singlephase', 'workshop', $data);
        $eventdata->fullmessageformat = FORMAT_HTML;
        $eventdata->fullmessagehtml = $fullmessagehtml;
        $eventdata->notification = 1;
        $eventdata->smallmessage = $this->workshop->name;

        return message_send($eventdata);
    }
}
