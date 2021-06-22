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
 * Moodle calendar import
 *
 * @package    core_calendar
 * @copyright  Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../config.php');
require_once($CFG->libdir.'/bennu/bennu.inc.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/calendar/lib.php');

$courseid = optional_param('course', SITEID, PARAM_INT);
$groupcourseid  = optional_param('groupcourseid', 0, PARAM_INT);
$category  = optional_param('category', 0, PARAM_INT);
$data = [];
$url = new moodle_url('/calendar/import.php');

if ($courseid != SITEID && !empty($courseid)) {
    // Course ID must be valid and existing.
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    $issite = false;
    $data['eventtype'] = 'course';
    $data['courseid'] = $course->id;
    $url->param('course', $course->id);
} else {
    $course = get_site();
    $issite = true;
}
require_login($course, false);
if (!calendar_user_can_add_event($course)) {
    throw new \moodle_exception('errorcannotimport', 'calendar');
}

// Populate the 'group' select box based on the given 'groupcourseid', if necessary.
$groups = [];
if (!empty($groupcourseid)) {
    require_once($CFG->libdir . '/grouplib.php');
    $groupcoursedata = groups_get_course_data($groupcourseid);
    if (!empty($groupcoursedata->groups)) {
        foreach ($groupcoursedata->groups as $groupid => $groupdata) {
            $groups[$groupid] = $groupdata->name;
        }
    }
    $data['groupcourseid'] = $groupcourseid;
    $data['eventtype'] = 'group';
    $url->param('groupcourseid', $groupcourseid);
}
if (!empty($category)) {
    $url->param('categoryid', $categoryid);
    $data['category'] = $category;
    $data['eventtype'] = 'category';
}

$PAGE->set_url($url);

$managesubscriptionsurl = new moodle_url('/calendar/managesubscriptions.php');

$customdata = [
    'courseid' => $course->id,
    'groups' => $groups,
];
$form = new \core_calendar\local\event\forms\managesubscriptions(null, $customdata);

$form->set_data($data);

$formdata = $form->get_data();
if (!empty($formdata)) {
    require_sesskey(); // Must have sesskey for all actions.
    $subscriptionid = calendar_add_subscription($formdata);
    if ($formdata->importfrom == CALENDAR_IMPORT_FROM_FILE) {
        // Blank the URL if it's a file import.
        $formdata->url = '';
        $calendar = $form->get_file_content('importfile');
        $ical = new iCalendar();
        $ical->unserialize($calendar);
        $importresults = calendar_import_icalendar_events($ical, null, $subscriptionid);
    } else {
        try {
            $importresults = calendar_update_subscription_events($subscriptionid);
        } catch (\moodle_exception $e) {
            // Delete newly added subscription and show invalid url error.
            calendar_delete_subscription($subscriptionid);
            throw new \moodle_exception($e->errorcode, $e->module, $PAGE->url);
        }
    }
    if (!empty($formdata->courseid)) {
        $managesubscriptionsurl->param('course', $formdata->courseid);
    }
    if (!empty($formdata->categoryid)) {
        $managesubscriptionsurl->param('category', $formdata->categoryid);
    }
    redirect($managesubscriptionsurl, $importresults);
}

$pagetitle = get_string('importcalendar', 'calendar');
$PAGE->set_title($course->shortname.': '.get_string('calendar', 'calendar').': '.$pagetitle);

$calendarsubscriptions = get_string('calendarsubscriptions', 'calendar');

if ($issite) {
    $PAGE->navbar->add($course->shortname, new moodle_url('/course/view.php', ['id' => $course->id]));
}
$PAGE->navbar->add($calendarsubscriptions, $managesubscriptionsurl);
$PAGE->navbar->add($pagetitle);

$heading = get_string('importcalendar', 'calendar');
$PAGE->set_heading($heading);
$PAGE->set_pagelayout('standard');

$renderer = $PAGE->get_renderer('core_calendar');

echo $OUTPUT->header();
echo $renderer->start_layout();
echo $OUTPUT->heading($heading);

$form->display();
echo $OUTPUT->spacer(null, true);
echo $OUTPUT->action_link($managesubscriptionsurl, $OUTPUT->larrow() . ' ' . $calendarsubscriptions);
echo $renderer->complete_layout();
echo $OUTPUT->footer();
